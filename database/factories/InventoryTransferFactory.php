<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransfer>
 */
class InventoryTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_branch_id' => Branch::factory(),
            'to_branch_id' => Branch::factory(),
            'user_id' => null,
            'notes' => null,
            'transferred_at' => now(),
        ];
    }
}
