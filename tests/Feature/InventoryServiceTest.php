<?php

use App\Enums\InventoryMovementType;
use App\Enums\MeasurementUnit;
use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Services\InventoryService;

beforeEach(function () {
    $this->service = app(InventoryService::class);
});

test('receive adds stock and records an entrada movement', function () {
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create(['unit' => MeasurementUnit::Gram]);

    $stock = $this->service->receive($branch, $item, 1000, notes: 'Compra inicial');

    expect((float) $stock->quantity)->toBe(1000.0);

    $movement = InventoryMovement::query()->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::Entrada)
        ->and((float) $movement->quantity)->toBe(1000.0)
        ->and((float) $movement->quantity_after)->toBe(1000.0)
        ->and($movement->branch_id)->toBe($branch->id);
});

test('adjust sets stock to an exact value and records the difference', function () {
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    $this->service->receive($branch, $item, 1000);
    $stock = $this->service->adjust($branch, $item, 750);

    expect((float) $stock->quantity)->toBe(750.0);

    $movement = InventoryMovement::query()->latest('id')->first();
    expect($movement->type)->toBe(InventoryMovementType::Ajuste)
        ->and((float) $movement->quantity)->toBe(-250.0)
        ->and((float) $movement->quantity_after)->toBe(750.0);
});

test('transfer moves stock between branches with paired movements', function () {
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    $this->service->receive($from, $item, 1000);

    $transfer = $this->service->transfer($from, $to, [
        ['inventory_item_id' => $item->id, 'quantity' => 300],
    ]);

    expect((float) $this->service->stockFor($from, $item)->quantity)->toBe(700.0)
        ->and((float) $this->service->stockFor($to, $item)->quantity)->toBe(300.0);

    expect($transfer)->toBeInstanceOf(InventoryTransfer::class)
        ->and($transfer->lines)->toHaveCount(1);

    expect(InventoryMovement::query()->where('type', InventoryMovementType::TraspasoSalida)->where('branch_id', $from->id)->where('inventory_transfer_id', $transfer->id)->exists())->toBeTrue()
        ->and(InventoryMovement::query()->where('type', InventoryMovementType::TraspasoEntrada)->where('branch_id', $to->id)->where('inventory_transfer_id', $transfer->id)->exists())->toBeTrue();
});

test('stock is allowed to go negative so sales are never blocked', function () {
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    $this->service->receive($branch, $item, 30);
    $this->service->record($branch, $item, -50, InventoryMovementType::Venta);

    expect((float) $this->service->stockFor($branch, $item)->quantity)->toBe(-20.0);
});

test('transfer between the same branch is rejected', function () {
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    expect(fn () => $this->service->transfer($branch, $branch, [
        ['inventory_item_id' => $item->id, 'quantity' => 10],
    ]))->toThrow(InvalidArgumentException::class);
});

test('a low stock row is flagged against its threshold', function () {
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    $stock = BranchInventoryStock::factory()->create([
        'branch_id' => $branch->id,
        'inventory_item_id' => $item->id,
        'quantity' => 5,
        'min_quantity' => 10,
    ]);

    expect($stock->isLow())->toBeTrue();
});
