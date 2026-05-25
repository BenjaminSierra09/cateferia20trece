<?php

namespace Database\Factories;

use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomizationOptionSizePrice>
 */
class CustomizationOptionSizePriceFactory extends Factory
{
    protected $model = CustomizationOptionSizePrice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customization_option_id' => CustomizationOption::factory(),
            'size_id' => Size::factory(),
            'price' => fake()->randomFloat(2, 0, 30),
        ];
    }
}
