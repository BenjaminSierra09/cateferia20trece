<?php

namespace App\Actions\WhatsApp;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessageReaction;
use Carbon\CarbonInterface;

class StoreIncomingWhatsAppReaction
{
    public function execute(
        WhatsAppConversation $conversation,
        string $targetProviderMessageId,
        string $emoji,
        ?string $providerMessageId,
        CarbonInterface $reactedAt,
    ): ?WhatsAppMessageReaction {
        $target = $conversation->messages()
            ->where('provider_message_id', $targetProviderMessageId)
            ->first();

        if ($target === null) {
            return null;
        }

        if (blank($emoji)) {
            $target->reactions()
                ->where('direction', WhatsAppMessageDirection::Inbound)
                ->delete();

            return null;
        }

        return $target->reactions()->updateOrCreate([
            'direction' => WhatsAppMessageDirection::Inbound,
        ], [
            'sent_by_user_id' => null,
            'provider_message_id' => $providerMessageId,
            'emoji' => $emoji,
            'status' => WhatsAppMessageStatus::Received,
            'error_code' => null,
            'reacted_at' => $reactedAt,
        ]);
    }
}
