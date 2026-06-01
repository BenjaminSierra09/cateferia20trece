<?php

use App\Enums\InventoryMovementType;
use App\Enums\MeasurementUnit;
use App\Enums\PaymentMethod;
use App\Models\Beverage;
use App\Models\BeverageRecipeLine;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationRecipeLine;
use App\Models\CustomizationType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Size;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\InventoryService;
use App\Services\SaleService;

function inventorySaleContext(): array
{
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $session = WorkSession::factory()->create(['branch_id' => $branch->id, 'user_id' => $user->id]);

    return [$user, $branch, $session];
}

test('a completed sale deducts the beverage recipe from branch stock', function () {
    [$user, $branch, $session] = inventorySaleContext();

    $size = Size::factory()->create(['name' => 'Mediano']);
    $beverage = Beverage::factory()->create(['name' => 'Latte', 'base_price' => 50]);
    $cafe = InventoryItem::factory()->create(['name' => 'Café', 'unit' => MeasurementUnit::Gram]);
    $leche = InventoryItem::factory()->create(['name' => 'Leche', 'unit' => MeasurementUnit::Milliliter]);

    BeverageRecipeLine::factory()->create(['beverage_id' => $beverage->id, 'size_id' => $size->id, 'inventory_item_id' => $cafe->id, 'quantity' => 20]);
    BeverageRecipeLine::factory()->create(['beverage_id' => $beverage->id, 'size_id' => $size->id, 'inventory_item_id' => $leche->id, 'quantity' => 200]);

    $inventory = app(InventoryService::class);
    $inventory->receive($branch, $cafe, 1000);
    $inventory->receive($branch, $leche, 5000);

    $sale = app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 1]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $cafe)->quantity)->toBe(980.0)
        ->and((float) $inventory->stockFor($branch, $leche)->quantity)->toBe(4800.0);

    expect(InventoryMovement::query()->where('sale_id', $sale->id)->where('type', InventoryMovementType::Venta)->count())->toBe(2);
});

test('consumption scales with the sold quantity', function () {
    [$user, $branch, $session] = inventorySaleContext();

    $size = Size::factory()->create();
    $beverage = Beverage::factory()->create(['base_price' => 50]);
    $cafe = InventoryItem::factory()->create(['unit' => MeasurementUnit::Gram]);

    BeverageRecipeLine::factory()->create(['beverage_id' => $beverage->id, 'size_id' => $size->id, 'inventory_item_id' => $cafe->id, 'quantity' => 20]);

    $inventory = app(InventoryService::class);
    $inventory->receive($branch, $cafe, 1000);

    app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 3]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $cafe)->quantity)->toBe(940.0);
});

test('a customization consumes the category default or the option override', function () {
    [$user, $branch, $session] = inventorySaleContext();

    $size = Size::factory()->create();
    $beverage = Beverage::factory()->create(['base_price' => 50]);
    $jarabeStock = InventoryItem::factory()->create(['name' => 'Jarabe', 'unit' => MeasurementUnit::Milliliter]);

    $type = CustomizationType::factory()->create(['name' => 'Jarabes']);
    $vainilla = CustomizationOption::factory()->create(['customization_type_id' => $type->id, 'name' => 'Vainilla']);
    $caramelo = CustomizationOption::factory()->create(['customization_type_id' => $type->id, 'name' => 'Caramelo']);

    // Category default: 50 ml each. Vanilla overrides to 70 ml.
    CustomizationRecipeLine::factory()->create(['customization_type_id' => $type->id, 'customization_option_id' => null, 'inventory_item_id' => $jarabeStock->id, 'quantity' => 50]);
    CustomizationRecipeLine::factory()->create(['customization_type_id' => $type->id, 'customization_option_id' => $vainilla->id, 'inventory_item_id' => $jarabeStock->id, 'quantity' => 70]);

    $inventory = app(InventoryService::class);
    $inventory->receive($branch, $jarabeStock, 1000);

    // Override path (Vanilla = 70).
    app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 1, 'customization_option_ids' => [$vainilla->id]]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $jarabeStock)->quantity)->toBe(930.0);

    // Default path (Caramel = 50).
    app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 1, 'customization_option_ids' => [$caramelo->id]]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $jarabeStock)->quantity)->toBe(880.0);
});

test('cancelling a sale restores the consumed inventory', function () {
    [$user, $branch, $session] = inventorySaleContext();

    $size = Size::factory()->create();
    $beverage = Beverage::factory()->create(['base_price' => 50]);
    $cafe = InventoryItem::factory()->create(['unit' => MeasurementUnit::Gram]);

    BeverageRecipeLine::factory()->create(['beverage_id' => $beverage->id, 'size_id' => $size->id, 'inventory_item_id' => $cafe->id, 'quantity' => 20]);

    $inventory = app(InventoryService::class);
    $inventory->receive($branch, $cafe, 1000);

    $sale = app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 1]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $cafe)->quantity)->toBe(980.0);

    app(SaleService::class)->cancel($sale->fresh());

    expect((float) $inventory->stockFor($branch, $cafe)->quantity)->toBe(1000.0)
        ->and(InventoryMovement::query()->where('sale_id', $sale->id)->where('type', InventoryMovementType::Cancelacion)->exists())->toBeTrue();
});

test('a sale for a beverage without a recipe leaves inventory untouched and never fails', function () {
    [$user, $branch, $session] = inventorySaleContext();

    $size = Size::factory()->create();
    $beverage = Beverage::factory()->create(['base_price' => 50]);
    $cafe = InventoryItem::factory()->create(['unit' => MeasurementUnit::Gram]);

    $inventory = app(InventoryService::class);
    $inventory->receive($branch, $cafe, 1000);

    app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [['beverage_id' => $beverage->id, 'size_id' => $size->id, 'quantity' => 2]],
    ], $user, $session);

    expect((float) $inventory->stockFor($branch, $cafe)->quantity)->toBe(1000.0)
        ->and(InventoryMovement::query()->where('type', InventoryMovementType::Venta)->count())->toBe(0);
});
