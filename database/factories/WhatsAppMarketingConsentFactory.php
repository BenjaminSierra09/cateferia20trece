<?php

namespace Database\Factories;

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Models\Customer;
use App\Models\WhatsAppMarketingConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppMarketingConsent>
 */
class WhatsAppMarketingConsentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'recorded_by_user_id' => null,
            'phone' => '524151234567',
            'status' => 'granted',
            'source' => 'factory',
            'consent_text_version' => RecordWhatsAppMarketingConsent::CONSENT_TEXT_VERSION,
            'ip_address' => null,
            'occurred_at' => now(),
        ];
    }
}
