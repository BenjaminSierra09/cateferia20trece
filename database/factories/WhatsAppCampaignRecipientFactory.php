<?php

namespace Database\Factories;

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Models\Customer;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppCampaignRecipient>
 */
class WhatsAppCampaignRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'whatsapp_campaign_id' => WhatsAppCampaign::factory(),
            'customer_id' => Customer::factory()->withWhatsAppMarketingConsent(),
            'phone' => '524151234567',
            'name' => fake()->name(),
            'message_preview' => 'Hola, tenemos una promoción para ti.',
            'parameters' => [fake()->firstName()],
            'consented_at' => now()->subDay(),
            'consent_source' => 'factory',
            'status' => WhatsAppCampaignRecipientStatus::Pending,
        ];
    }
}
