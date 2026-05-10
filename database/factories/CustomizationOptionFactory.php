<?php

namespace Database\Factories;

use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomizationOption>
 */
class CustomizationOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customization_type_id' => CustomizationType::factory(),
            'name' => fake()->randomElement(['Almendra', 'Avena', 'Vainilla', 'Shot extra']),
            'price' => fake()->randomFloat(2, 0, 25),
            'is_available' => true,
        ];
    }
}
