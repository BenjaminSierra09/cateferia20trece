<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sucursal '.fake()->city(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'phone' => fake()->phoneNumber(),
            'operating_hours' => '07:00 - 21:00',
            'is_active' => true,
        ];
    }
}
