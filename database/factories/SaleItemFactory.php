<?php

namespace Database\Factories;

use App\Models\Beverage;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'beverage_id' => Beverage::factory(),
            'product_id' => null,
            'size_id' => Size::factory(),
            'item_name' => fake()->words(2, true),
            'quantity' => fake()->numberBetween(1, 3),
            'base_price' => fake()->randomFloat(2, 40, 120),
            'unit_price' => fake()->randomFloat(2, 40, 150),
            'line_total' => fake()->randomFloat(2, 40, 300),
            'special_instructions' => fake()->optional()->sentence(),
        ];
    }

    public function forProduct(?Product $product = null): static
    {
        return $this->state(fn () => [
            'beverage_id' => null,
            'product_id' => $product?->id ?? Product::factory(),
            'size_id' => null,
        ]);
    }
}
