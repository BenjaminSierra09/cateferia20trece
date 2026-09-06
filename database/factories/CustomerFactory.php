<?php

namespace Database\Factories;

use App\Enums\RewardTier;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'birthday' => fake()->optional()->date(),
            'email' => fake()->optional()->safeEmail(),
            'whatsapp_marketing_opted_in_at' => null,
            'whatsapp_marketing_opted_out_at' => null,
            'whatsapp_marketing_consent_source' => null,
            'whatsapp_marketing_consent_version' => null,
            'whatsapp_marketing_consented_phone' => null,
            'reward_balance' => 0,
            'reward_year' => (int) now()->format('Y'),
            'annual_drink_count' => fake()->numberBetween(0, 30),
            'reward_tier' => fake()->randomElement(RewardTier::cases()),
            'is_active' => true,
        ];
    }

    public function withWhatsAppMarketingConsent(string $phone = '+524151234567'): static
    {
        return $this->state(function () use ($phone): array {
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (mb_strlen($digits) === 10) {
                $digits = '52'.$digits;
            } elseif (mb_strlen($digits) === 13 && str_starts_with($digits, '521')) {
                $digits = '52'.mb_substr($digits, 3);
            }

            return [
                'phone' => $phone,
                'whatsapp_marketing_opted_in_at' => now()->subDay(),
                'whatsapp_marketing_opted_out_at' => null,
                'whatsapp_marketing_consent_source' => 'factory',
                'whatsapp_marketing_consent_version' => '2026-09-06',
                'whatsapp_marketing_consented_phone' => $digits,
            ];
        });
    }
}
