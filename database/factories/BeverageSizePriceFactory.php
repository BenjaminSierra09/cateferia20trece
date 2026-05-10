<?php

namespace Database\Factories;

use App\Models\Beverage;
use App\Models\BeverageSizePrice;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeverageSizePrice>
 */
class BeverageSizePriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beverage_id' => Beverage::factory(),
            'size_id' => Size::factory(),
            'price' => fake()->randomFloat(2, 45, 130),
            'is_active' => true,
        ];
    }
}
