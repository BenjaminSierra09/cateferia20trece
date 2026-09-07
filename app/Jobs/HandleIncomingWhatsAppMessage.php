<?php

namespace App\Jobs;

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Actions\WhatsApp\StoreIncomingWhatsAppReaction;
use App\Ai\Agents\WhatsAppConcierge;
use App\Enums\UserRole;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Notifications\IncomingWhatsAppMessageNotification;
use App\Support\CustomerPhoneMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

#[Tries(3)]
#[Backoff([10, 30, 60])]
#[Timeout(60)]
class HandleIncomingWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $text,
        public ?string $pushName = null,
        public ?string $messageId = null,
        public string $messageType = 'text',
        public ?int $timestamp = null,
        public ?string $reactionToMessageId = null,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('whatsapp:'.$this->phone))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        CustomerPhoneMatcher $matcher,
        SendWhatsAppTextMessage $sendMessage,
        StoreIncomingWhatsAppReaction $storeReaction,
        RecordWhatsAppMarketingConsent $recordMarketingConsent,
    ): void {
        $normalizedPhone = $matcher->normalize($this->phone);

        if ($normalizedPhone === '') {
            return;
        }

        $customer = $matcher->find($this->phone);
        $receivedAt = $this->timestamp !== null
            ? CarbonImmutable::createFromTimestampUTC($this->timestamp)
                ->setTimezone((string) config('app.timezone'))
            : now();

        /** @var WhatsAppConversation $conversation */
        $conversation = WhatsAppConversation::query()->firstOrNew(['phone' => $normalizedPhone]);
        $conversation->fill([
            'profile_name' => filled($this->pushName) ? $this->pushName : $conversation->profile_name,
            'customer_id' => $customer?->id,
        ]);

        if ($conversation->last_inbound_at === null || $receivedAt->greaterThan($conversation->last_inbound_at)) {
            $conversation->last_inbound_at = $receivedAt;
        }

        if ($this->messageType !== 'reaction'
            && ($conversation->last_message_at === null || $receivedAt->greaterThanOrEqualTo($conversation->last_message_at))) {
            $conversation->last_message_id = $this->messageId;
            $conversation->last_message_at = $receivedAt;
        }

        $conversation->save();

        if ($this->messageType === 'reaction' && filled($this->reactionToMessageId)) {
            $storeReaction->execute(
                conversation: $conversation,
                targetProviderMessageId: $this->reactionToMessageId,
                emoji: $this->text,
                providerMessageId: $this->messageId,
                reactedAt: $receivedAt,
            );

            return;
        }

        $messageAttributes = [
            'direction' => WhatsAppMessageDirection::Inbound,
            'type' => $this->messageType,
            'body' => $this->text,
            'status' => WhatsAppMessageStatus::Received,
            'sent_at' => $receivedAt,
        ];

        if ($this->messageId !== null) {
            $message = $conversation->messages()->firstOrCreate(
                ['provider_message_id' => $this->messageId],
                $messageAttributes,
            );

            if (! $message->wasRecentlyCreated) {
                return;
            }
        } else {
            $message = $conversation->messages()->create($messageAttributes);
        }

        $this->notifyWhatsAppAdministrators($conversation, $message->previewText());

        if ($this->messageType !== 'text') {
            return;
        }

        if ($customer !== null
            && $customer->hasWhatsAppMarketingConsent()
            && $this->isMarketingOptOut($this->text)) {
            $recordMarketingConsent->revoke($customer, 'whatsapp_keyword');
            $sendMessage->execute(
                $conversation,
                'Listo. Ya no recibirás promociones de Café 20Trece por WhatsApp.',
            );

            return;
        }

        if ($conversation->refresh()->isBotPaused()) {
            return;
        }

        if ($customer === null) {
            if (! $conversation->refresh()->isBotPaused()) {
                $sendMessage->execute($conversation, $this->registrationMessage());
            }

            return;
        }

        $agent = new WhatsAppConcierge($customer);
        $model = config('ai.whatsapp.model');

        $response = $conversation->conversation_id
            ? $agent->continue($conversation->conversation_id, as: $customer)->prompt($this->text, model: $model)
            : $agent->forUser($customer)->prompt($this->text, model: $model);

        $conversation->forceFill([
            'conversation_id' => $response->conversationId ?? $conversation->conversation_id,
        ])->save();

        if (! $conversation->refresh()->isBotPaused()) {
            $sendMessage->execute($conversation, $response->text);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('No fue posible procesar un mensaje entrante de WhatsApp.', [
            'phone' => $this->phone,
            'provider_message_id' => $this->messageId,
            'exception' => $exception,
        ]);
    }

    /**
     * Deterministic, AI-free reply inviting an unknown number to register.
     */
    private function registrationMessage(): string
    {
        $greeting = filled($this->pushName) ? '¡Hola, '.$this->pushName.'!' : '¡Hola!';

        return $greeting.' Soy el asistente de Café 20Trece ☕. '
            .'Todavía no encuentro tu número en nuestro programa de clientes. '
            .'Regístrate aquí para consultar tu saldo, ver tus bebidas favoritas y hacer pedidos: '
            .route('public.register');
    }

    private function isMarketingOptOut(string $text): bool
    {
        $normalized = Str::of($text)
            ->ascii()
            ->lower()
            ->squish()
            ->toString();

        return in_array($normalized, [
            'baja',
            'baja promociones',
            'stop',
            'alto',
            'salir',
            'cancelar promociones',
            'no quiero promociones',
        ], true);
    }

    private function notifyWhatsAppAdministrators(WhatsAppConversation $conversation, string $messagePreview): void
    {
        $administrators = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereHas('pushSubscriptions')
            ->get();

        if ($administrators->isEmpty()) {
            return;
        }

        Notification::send($administrators, new IncomingWhatsAppMessageNotification(
            conversationId: $conversation->id,
            contactName: $conversation->displayName(),
            messagePreview: Str::limit($messagePreview, 140),
        ));
    }
}
