<?php

namespace Database\Factories;

use App\Models\CustomizationOption;
use App\Models\SaleItem;
use App\Models\SaleItemCustomization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItemCustomization>
 */
class SaleItemCustomizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_item_id' => SaleItem::factory(),
            'customization_option_id' => CustomizationOption::factory(),
            'customization_type_name' => fake()->randomElement(['Extras', 'Leche']),
            'customization_name' => fake()->word(),
            'quantity' => 1,
            'price' => fake()->randomFloat(2, 0, 20),
        ];
    }
}
