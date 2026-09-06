<?php

namespace Database\Factories;

use App\Enums\WhatsAppCampaignStatus;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppCampaign>
 */
class WhatsAppCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'template_name' => 'promocion_cafe',
            'template_language' => 'es_MX',
            'message_preview' => 'Hola, tenemos una promoción para ti.',
            'template_parameters' => ['{{first_name}}'],
            'status' => WhatsAppCampaignStatus::Queued,
            'recipient_count' => 0,
        ];
    }
}
