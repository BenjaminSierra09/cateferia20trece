<?php

namespace Database\Factories;

use App\Models\CustomizationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomizationType>
 */
class CustomizationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Tipo de leche', 'Nivel de azúcar', 'Extras']),
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'selection_mode' => fake()->randomElement(['single', 'multiple']),
            'is_active' => true,
        ];
    }
}
