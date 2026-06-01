<?php

namespace Database\Factories;

use App\Models\CustomizationRecipeLine;
use App\Models\CustomizationType;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomizationRecipeLine>
 */
class CustomizationRecipeLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customization_type_id' => CustomizationType::factory(),
            'customization_option_id' => null,
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(3, 1, 100),
        ];
    }
}
