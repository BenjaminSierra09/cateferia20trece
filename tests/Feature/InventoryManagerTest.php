<?php

use App\Enums\MeasurementUnit;
use App\Livewire\Inventory\ItemForm;
use App\Livewire\Inventory\Manager;
use App\Livewire\Inventory\Transfer;
use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use App\Models\User;
use App\Services\InventoryService;
use Livewire\Livewire;

test('the manager lists items and registers an entrada into the selected branch', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create(['name' => 'Leche entera', 'unit' => MeasurementUnit::Milliliter]);

    Livewire::actingAs($user)
        ->test(Manager::class)
        ->set('branchId', $branch->id)
        ->assertSee('Leche entera')
        ->call('openAction', $item->id, 'entrada')
        ->set('actionQuantity', '5000')
        ->call('saveAction')
        ->assertHasNoErrors();

    expect((float) BranchInventoryStock::query()
        ->where('branch_id', $branch->id)
        ->where('inventory_item_id', $item->id)
        ->value('quantity'))->toBe(5000.0);
});

test('the item form creates an inventory item', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ItemForm::class)
        ->set('name', 'Vaso 12 oz')
        ->set('unit', 'pieza')
        ->set('category', 'Desechables')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.inventory.index'));

    $this->assertDatabaseHas('inventory_items', [
        'name' => 'Vaso 12 oz',
        'unit' => 'pieza',
    ]);
});

test('the transfer component moves stock between branches', function () {
    $user = User::factory()->create();
    $from = Branch::factory()->create();
    $to = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    app(InventoryService::class)->receive($from, $item, 1000);

    Livewire::actingAs($user)
        ->test(Transfer::class)
        ->set('fromBranchId', $from->id)
        ->set('toBranchId', $to->id)
        ->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '250']])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.inventory.index'));

    expect((float) app(InventoryService::class)->stockFor($to, $item)->quantity)->toBe(250.0)
        ->and((float) app(InventoryService::class)->stockFor($from, $item)->quantity)->toBe(750.0);
});

test('the transfer component rejects same origin and destination', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $item = InventoryItem::factory()->create();

    Livewire::actingAs($user)
        ->test(Transfer::class)
        ->set('fromBranchId', $branch->id)
        ->set('toBranchId', $branch->id)
        ->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '10']])
        ->call('save')
        ->assertHasErrors('fromBranchId');
});
