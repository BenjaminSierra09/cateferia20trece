<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsAppService;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Throwable;

class SendWhatsAppTextMessage
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function execute(WhatsAppConversation $conversation, string $body, ?User $sentBy = null): WhatsAppMessage
    {
        if (! $this->whatsApp->isConfigured()) {
            throw new WhatsAppCloudApiException(
                message: 'La API de WhatsApp no está configurada.',
                operation: 'send_text',
            );
        }

        $message = $conversation->messages()->create([
            'sent_by_user_id' => $sentBy?->id,
            'direction' => WhatsAppMessageDirection::Outbound,
            'type' => 'text',
            'body' => $body,
            'status' => WhatsAppMessageStatus::Queued,
            'sent_at' => now(),
        ]);

        try {
            $providerMessageId = $this->whatsApp->sendMessage($conversation->phone, $body);

            $message->update([
                'provider_message_id' => $providerMessageId,
                'status' => WhatsAppMessageStatus::Sent,
                'error_code' => null,
            ]);

            $conversation->update([
                'last_outbound_at' => $message->sent_at,
                'last_message_at' => $message->sent_at,
            ]);
        } catch (Throwable $throwable) {
            $message->update([
                'status' => WhatsAppMessageStatus::Failed,
                'error_code' => $throwable instanceof WhatsAppCloudApiException
                    ? (string) ($throwable->graphCode ?? $throwable->status ?? 'unknown')
                    : 'unknown',
            ]);

            throw $throwable;
        }

        return $message->refresh();
    }
}
