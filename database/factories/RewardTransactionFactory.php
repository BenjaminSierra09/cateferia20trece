<?php

namespace Database\Factories;

use App\Enums\RewardTransactionType;
use App\Models\Customer;
use App\Models\RewardTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RewardTransaction>
 */
class RewardTransactionFactory extends Factory
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
            'sale_id' => null,
            'type' => fake()->randomElement(RewardTransactionType::cases()),
            'amount' => fake()->randomFloat(2, -50, 100),
            'balance_after' => fake()->randomFloat(2, 0, 300),
            'description' => fake()->sentence(),
            'transacted_at' => now(),
        ];
    }
}
