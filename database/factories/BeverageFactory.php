<?php

namespace Database\Factories;

use App\Models\Beverage;
use App\Models\BeverageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Beverage>
 */
class BeverageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beverage_category_id' => BeverageCategory::factory(),
            'name' => fake()->unique()->randomElement(['Latte', 'Capuchino', 'Americano', 'Matcha']),
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'base_price' => fake()->randomFloat(2, 40, 120),
            'is_hot' => true,
            'is_active' => true,
        ];
    }
}
