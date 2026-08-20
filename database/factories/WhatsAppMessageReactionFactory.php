<?php

namespace Database\Factories;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageReaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppMessageReaction>
 */
class WhatsAppMessageReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_message_id' => WhatsAppMessage::factory(),
            'sent_by_user_id' => null,
            'provider_message_id' => fake()->uuid(),
            'direction' => WhatsAppMessageDirection::Outbound,
            'emoji' => '👍',
            'status' => WhatsAppMessageStatus::Sent,
            'error_code' => null,
            'reacted_at' => now(),
        ];
    }
}
