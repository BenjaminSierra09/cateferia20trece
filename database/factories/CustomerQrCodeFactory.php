<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerQrCode>
 */
class CustomerQrCodeFactory extends Factory
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
            'uuid' => (string) Str::uuid(),
            'is_active' => true,
        ];
    }
}
