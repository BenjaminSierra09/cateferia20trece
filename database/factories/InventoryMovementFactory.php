<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 100);

        return [
            'branch_id' => Branch::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'type' => InventoryMovementType::Entrada,
            'quantity' => $quantity,
            'quantity_after' => $quantity,
            'user_id' => null,
            'sale_id' => null,
            'inventory_transfer_id' => null,
            'notes' => null,
            'recorded_at' => now(),
        ];
    }
}
