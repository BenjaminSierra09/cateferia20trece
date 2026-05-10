<?php

namespace Database\Factories;

use App\Enums\WorkSessionStatus;
use App\Models\Branch;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSession>
 */
class WorkSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'work_date' => today(),
            'clock_in_at' => now()->subHours(2),
            'clock_out_at' => null,
            'status' => WorkSessionStatus::Open,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
