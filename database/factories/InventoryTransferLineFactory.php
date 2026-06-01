<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransferLine>
 */
class InventoryTransferLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_transfer_id' => InventoryTransfer::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(3, 1, 100),
        ];
    }
}
