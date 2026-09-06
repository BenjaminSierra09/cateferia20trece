<?php

namespace App\Jobs;

use App\Contracts\WhatsAppService;
use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppPhoneNormalizer;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Tries(0)]
#[Timeout(60)]
class SendWhatsAppCampaignRecipient implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 86400;

    public int $expiresAt;

    public function __construct(public int $recipientId)
    {
        $this->expiresAt = now()->addDay()->timestamp;
    }

    public function uniqueId(): string
    {
        return (string) $this->recipientId;
    }

    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::createFromTimestampUTC($this->expiresAt);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new RateLimited('whatsapp-marketing'))->releaseAfter(6),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        WhatsAppService $whatsApp,
        WhatsAppPhoneNormalizer $phoneNormalizer,
    ): void {
        $recipient = WhatsAppCampaignRecipient::query()
            ->with(['campaign', 'customer', 'message'])
            ->find($this->recipientId);

        if ($recipient === null) {
            return;
        }

        if ($recipient->status === WhatsAppCampaignRecipientStatus::Sending) {
            $this->markUncertain($recipient, 'El envío anterior se interrumpió y no es seguro repetirlo.');

            return;
        }

        if ($recipient->status !== WhatsAppCampaignRecipientStatus::Pending
            || $recipient->campaign->status !== WhatsAppCampaignStatus::Sending) {
            return;
        }

        $customer = $recipient->customer;

        if ($customer === null
            || ! $customer->is_active
            || ! $customer->hasWhatsAppMarketingConsent()
            || $phoneNormalizer->normalize($customer->phone) !== $recipient->phone) {
            $recipient->update([
                'status' => WhatsAppCampaignRecipientStatus::Skipped,
                'error_message' => 'El contacto ya no tiene autorización vigente para recibir promociones.',
            ]);
            $this->completeCampaign($recipient->whatsapp_campaign_id);

            return;
        }

        $attemptedAt = now();
        $recipient->update([
            'status' => WhatsAppCampaignRecipientStatus::Sending,
            'attempted_at' => $attemptedAt,
            'error_code' => null,
            'error_message' => null,
        ]);

        $conversation = WhatsAppConversation::query()->firstOrCreate(
            ['phone' => $recipient->phone],
            [
                'profile_name' => $recipient->name,
                'customer_id' => $customer->id,
            ],
        );
        $conversation->update([
            'profile_name' => $conversation->profile_name ?: $recipient->name,
            'customer_id' => $customer->id,
        ]);

        $message = $recipient->message ?? $conversation->messages()->create([
            'sent_by_user_id' => $recipient->campaign->created_by_user_id,
            'direction' => WhatsAppMessageDirection::Outbound,
            'type' => 'template',
            'body' => $recipient->message_preview,
            'status' => WhatsAppMessageStatus::Queued,
            'sent_at' => $attemptedAt,
        ]);

        if ($recipient->whatsapp_message_id === null) {
            $recipient->update(['whatsapp_message_id' => $message->id]);
        } else {
            $message->update([
                'provider_message_id' => null,
                'status' => WhatsAppMessageStatus::Queued,
                'error_code' => null,
                'sent_at' => $attemptedAt,
            ]);
        }

        try {
            $providerMessageId = $whatsApp->sendMarketingTemplate(
                number: $recipient->phone,
                templateName: $recipient->campaign->template_name,
                language: $recipient->campaign->template_language,
                bodyParameters: $recipient->parameters ?? [],
            );

            if ($providerMessageId === null) {
                throw new WhatsAppCloudApiException(
                    message: 'WhatsApp no devolvió un identificador para el mensaje de campaña.',
                    operation: 'send_marketing_template',
                );
            }

            DB::transaction(function () use ($recipient, $message, $conversation, $providerMessageId): void {
                $sentAt = now();
                $message->update([
                    'provider_message_id' => $providerMessageId,
                    'status' => WhatsAppMessageStatus::Sent,
                    'sent_at' => $sentAt,
                ]);
                $recipient->update([
                    'status' => WhatsAppCampaignRecipientStatus::Sent,
                    'provider_message_id' => $providerMessageId,
                    'sent_at' => $sentAt,
                ]);
                $conversation->update([
                    'last_message_id' => $providerMessageId,
                    'last_outbound_at' => $sentAt,
                    'last_message_at' => $sentAt,
                ]);
            });
        } catch (WhatsAppCloudApiException $exception) {
            $this->recordFailure(
                recipient: $recipient,
                message: $message,
                uncertain: $exception->status === null || $exception->status === 408 || $exception->status >= 500,
                errorCode: $exception->graphCode !== null ? (string) $exception->graphCode : null,
                errorMessage: $exception->getMessage(),
            );
        } catch (Throwable $exception) {
            $this->recordFailure(
                recipient: $recipient,
                message: $message,
                uncertain: true,
                errorCode: null,
                errorMessage: 'El envío se interrumpió y no es seguro repetirlo automáticamente.',
            );

            Log::error('Se interrumpió un envío de campaña de WhatsApp.', [
                'recipient_id' => $recipient->id,
                'campaign_id' => $recipient->whatsapp_campaign_id,
                'exception' => $exception,
            ]);
        }

        $this->completeCampaign($recipient->whatsapp_campaign_id);
    }

    public function failed(?Throwable $exception): void
    {
        $recipient = WhatsAppCampaignRecipient::query()->with('message')->find($this->recipientId);

        if ($recipient !== null && in_array($recipient->status, [
            WhatsAppCampaignRecipientStatus::Pending,
            WhatsAppCampaignRecipientStatus::Sending,
        ], true)) {
            $this->recordFailure(
                recipient: $recipient,
                message: $recipient->message,
                uncertain: true,
                errorCode: null,
                errorMessage: 'El trabajo terminó sin confirmar si Meta recibió el mensaje.',
            );
            $this->completeCampaign($recipient->whatsapp_campaign_id);
        }

        Log::error('No fue posible terminar un envío de campaña de WhatsApp.', [
            'recipient_id' => $this->recipientId,
            'exception' => $exception,
        ]);
    }

    private function markUncertain(WhatsAppCampaignRecipient $recipient, string $message): void
    {
        $this->recordFailure(
            recipient: $recipient,
            message: $recipient->message,
            uncertain: true,
            errorCode: null,
            errorMessage: $message,
        );
        $this->completeCampaign($recipient->whatsapp_campaign_id);
    }

    private function recordFailure(
        WhatsAppCampaignRecipient $recipient,
        ?WhatsAppMessage $message,
        bool $uncertain,
        ?string $errorCode,
        string $errorMessage,
    ): void {
        $recipient->update([
            'status' => $uncertain
                ? WhatsAppCampaignRecipientStatus::Uncertain
                : WhatsAppCampaignRecipientStatus::Failed,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        $message?->update([
            'status' => WhatsAppMessageStatus::Failed,
            'error_code' => $errorCode,
        ]);
    }

    private function completeCampaign(int $campaignId): void
    {
        WhatsAppCampaign::query()->find($campaignId)?->markCompletedIfFinished();
    }
}
