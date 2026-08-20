<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsAppService;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageReaction;
use Throwable;

class SendWhatsAppReaction
{
    /** @var array<int, string> */
    public const EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function execute(
        WhatsAppConversation $conversation,
        WhatsAppMessage $target,
        string $emoji,
        User $sentBy,
    ): WhatsAppMessageReaction {
        if ($target->whatsapp_conversation_id !== $conversation->id
            || ! $this->whatsApp->isConfigured()
            || blank($target->provider_message_id)
            || ! in_array($emoji, self::EMOJIS, true)
            || $target->sent_at === null
            || $target->sent_at->isBefore(now()->subDays(30))) {
            throw new WhatsAppCloudApiException(
                message: 'El mensaje no está disponible para recibir una reacción.',
                operation: 'send_reaction',
            );
        }

        $reaction = $target->reactions()->updateOrCreate([
            'direction' => WhatsAppMessageDirection::Outbound,
        ], [
            'sent_by_user_id' => $sentBy->id,
            'emoji' => $emoji,
            'status' => WhatsAppMessageStatus::Queued,
            'error_code' => null,
            'reacted_at' => now(),
        ]);

        try {
            $providerMessageId = $this->whatsApp->sendReaction(
                $conversation->phone,
                $target->provider_message_id,
                $emoji,
            );

            $reaction->update([
                'provider_message_id' => $providerMessageId,
                'status' => WhatsAppMessageStatus::Sent,
            ]);
        } catch (Throwable $throwable) {
            $reaction->update([
                'status' => WhatsAppMessageStatus::Failed,
                'error_code' => $throwable instanceof WhatsAppCloudApiException
                    ? (string) ($throwable->graphCode ?? $throwable->status ?? 'unknown')
                    : 'unknown',
            ]);

            throw $throwable;
        }

        return $reaction->refresh();
    }
}
