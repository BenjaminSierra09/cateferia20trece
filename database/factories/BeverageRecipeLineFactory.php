<?php

namespace Database\Factories;

use App\Models\Beverage;
use App\Models\BeverageRecipeLine;
use App\Models\InventoryItem;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeverageRecipeLine>
 */
class BeverageRecipeLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beverage_id' => Beverage::factory(),
            'size_id' => Size::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(3, 1, 300),
        ];
    }
}
