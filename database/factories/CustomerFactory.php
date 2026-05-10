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
            'reward_balance' => fake()->randomFloat(2, 0, 200),
            'reward_year' => (int) now()->format('Y'),
            'annual_drink_count' => fake()->numberBetween(0, 30),
            'reward_tier' => fake()->randomElement(RewardTier::cases()),
            'is_active' => true,
        ];
    }
}
