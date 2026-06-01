<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchInventoryStock>
 */
class BranchInventoryStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(3, 0, 1000),
            'min_quantity' => 0,
        ];
    }
}
