<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'description' => fake()->optional()->sentence(),
            'image_path' => null,
            'unit_type' => fake()->randomElement(['piece', 'gram']),
            'base_price' => fake()->randomFloat(2, 1, 150),
            'is_active' => true,
        ];
    }
}
