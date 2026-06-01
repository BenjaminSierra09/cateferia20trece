<?php

namespace Database\Factories;

use App\Models\WhatsAppConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppConversation>
 */
class WhatsAppConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => (string) fake()->unique()->numerify('521##########'),
            'customer_id' => null,
            'conversation_id' => null,
            'last_message_id' => null,
            'last_inbound_at' => null,
            'last_outbound_at' => null,
        ];
    }
}
