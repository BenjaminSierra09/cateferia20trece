<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'work_session_id' => WorkSession::factory(),
            'sold_at' => now(),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => SaleStatus::Completed,
            'subtotal' => fake()->randomFloat(2, 40, 300),
            'discount_total' => 0,
            'reward_redeemed_total' => 0,
            'total' => fake()->randomFloat(2, 40, 300),
            'discount_concept' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
