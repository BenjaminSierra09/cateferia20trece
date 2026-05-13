<?php

namespace Database\Factories;

use App\Enums\CustomerDebtMovementType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerDebtMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerDebtMovement>
 */
class CustomerDebtMovementFactory extends Factory
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
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'type' => fake()->randomElement(CustomerDebtMovementType::cases()),
            'amount' => fake()->randomFloat(2, 10, 300),
            'balance_after' => fake()->randomFloat(2, 0, 500),
            'notes' => fake()->optional()->sentence(),
            'recorded_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
