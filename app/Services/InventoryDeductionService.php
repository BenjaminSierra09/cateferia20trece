<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\BeverageRecipeLine;
use App\Models\CustomizationOption;
use App\Models\CustomizationRecipeLine;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductRecipeLine;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Collection;

class InventoryDeductionService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Consume a branch's inventory for a sale, based on the configured recipes.
     * Aggregates per inventory item, then records one negative "venta" movement each.
     */
    public function consume(Sale $sale): void
    {
        $sale->loadMissing(['items.customizations', 'branch', 'user']);

        $branch = $sale->branch;

        if ($branch === null) {
            return;
        }

        /** @var array<int, float> $totals item id => quantity consumed */
        $totals = [];

        foreach ($sale->items as $item) {
            $this->accumulateForItem($item, $totals);
        }

        foreach ($totals as $inventoryItemId => $quantity) {
            $quantity = round($quantity, 3);

            if ($quantity <= 0) {
                continue;
            }

            $inventoryItem = InventoryItem::query()->find($inventoryItemId);

            if ($inventoryItem === null) {
                continue;
            }

            $this->inventory->record($branch, $inventoryItem, -$quantity, InventoryMovementType::Venta, [
                'sale' => $sale,
                'user' => $sale->user,
                'notes' => 'Venta #'.$sale->id,
            ]);
        }
    }

    /**
     * Reverse a sale's consumption (e.g. on cancellation) by undoing its recorded
     * "venta" movements. Idempotent: skips if already reversed.
     */
    public function reverse(Sale $sale): void
    {
        $alreadyReversed = InventoryMovement::query()
            ->where('sale_id', $sale->id)
            ->where('type', InventoryMovementType::Cancelacion)
            ->exists();

        if ($alreadyReversed) {
            return;
        }

        $movements = InventoryMovement::query()
            ->with(['item', 'branch'])
            ->where('sale_id', $sale->id)
            ->where('type', InventoryMovementType::Venta)
            ->get();

        foreach ($movements as $movement) {
            if ($movement->item === null || $movement->branch === null) {
                continue;
            }

            $this->inventory->record(
                $movement->branch,
                $movement->item,
                -(float) $movement->quantity,
                InventoryMovementType::Cancelacion,
                ['sale' => $sale, 'notes' => 'Cancelación venta #'.$sale->id],
            );
        }
    }

    /**
     * Add a sale item's recipe consumption into the running totals.
     *
     * @param  array<int, float>  $totals
     */
    protected function accumulateForItem(SaleItem $item, array &$totals): void
    {
        $itemQuantity = max(1, (int) $item->quantity);

        if ($item->beverage_id !== null && $item->size_id !== null) {
            $lines = BeverageRecipeLine::query()
                ->where('beverage_id', $item->beverage_id)
                ->where('size_id', $item->size_id)
                ->get();

            foreach ($lines as $line) {
                $totals[$line->inventory_item_id] = ($totals[$line->inventory_item_id] ?? 0) + (float) $line->quantity * $itemQuantity;
            }
        }

        if ($item->product_id !== null) {
            $lines = ProductRecipeLine::query()->where('product_id', $item->product_id)->get();

            foreach ($lines as $line) {
                $multiplier = $line->scales_with_quantity ? $itemQuantity : 1;
                $totals[$line->inventory_item_id] = ($totals[$line->inventory_item_id] ?? 0) + (float) $line->quantity * $multiplier;
            }
        }

        foreach ($item->customizations as $customization) {
            if ($customization->customization_option_id === null) {
                continue;
            }

            $customizationQuantity = max(1, (int) $customization->quantity);

            foreach ($this->customizationLinesFor((int) $customization->customization_option_id) as $line) {
                $totals[$line->inventory_item_id] = ($totals[$line->inventory_item_id] ?? 0) + (float) $line->quantity * $customizationQuantity * $itemQuantity;
            }
        }
    }

    /**
     * Recipe lines for a customization option: its own override if defined,
     * otherwise the category (type) default lines.
     *
     * @return Collection<int, CustomizationRecipeLine>
     */
    protected function customizationLinesFor(int $optionId): Collection
    {
        $option = CustomizationOption::query()->find($optionId);

        if ($option === null) {
            return collect();
        }

        $override = CustomizationRecipeLine::query()
            ->where('customization_option_id', $optionId)
            ->get();

        if ($override->isNotEmpty()) {
            return $override;
        }

        return CustomizationRecipeLine::query()
            ->where('customization_type_id', $option->customization_type_id)
            ->whereNull('customization_option_id')
            ->get();
    }
}
