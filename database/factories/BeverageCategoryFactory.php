<?php

namespace Database\Factories;

use App\Models\BeverageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BeverageCategory>
 */
class BeverageCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Espresso', 'Frappé', 'Té', 'Chocolate']),
            'slug' => Str::slug(fake()->unique()->word()),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
