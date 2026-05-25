<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchCustomizationSizePriceOverride;
use App\Models\CustomizationOption;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchCustomizationSizePriceOverride>
 */
class BranchCustomizationSizePriceOverrideFactory extends Factory
{
    protected $model = BranchCustomizationSizePriceOverride::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'customization_option_id' => CustomizationOption::factory(),
            'size_id' => Size::factory(),
            'price' => fake()->randomFloat(2, 0, 35),
        ];
    }
}
