<?php

namespace Database\Factories;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Chico', 'Mediano', 'Grande']),
            'capacity_label' => fake()->randomElement(['8 oz', '12 oz', '16 oz']),
            'capacity_ounces' => fake()->randomElement([8, 12, 16]),
            'is_active' => true,
        ];
    }
}
