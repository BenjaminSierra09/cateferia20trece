<?php

namespace Database\Factories;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppMessage>
 */
class WhatsAppMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_conversation_id' => WhatsAppConversation::factory(),
            'sent_by_user_id' => null,
            'provider_message_id' => 'wamid.'.fake()->unique()->bothify('????????????????'),
            'direction' => WhatsAppMessageDirection::Inbound,
            'type' => 'text',
            'body' => fake()->sentence(),
            'status' => WhatsAppMessageStatus::Received,
            'error_code' => null,
            'sent_at' => now(),
        ];
    }
}
