<?php

use App\Livewire\Beverages\Manager as BeverageManager;
use App\Livewire\Branches\Manager as BranchManager;
use App\Livewire\Customers\Manager as CustomerManager;
use App\Livewire\Products\Manager as ProductManager;
use App\Livewire\Reports\Overview as ReportsOverview;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use Livewire\Livewire;

test('branch manager can select visible rows and bulk update their status', function () {
    Branch::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(BranchManager::class)
        ->call('togglePageSelection');

    expect($component->get('selectedBranchIds'))->toHaveCount(2);
    expect($component->get('selectPage'))->toBeTrue();

    $component->call('deactivateSelected');

    expect(Branch::query()->where('is_active', false)->count())->toBe(2);
    expect($component->get('selectedBranchIds'))->toBe([]);
});

test('beverage manager supports grid mode and bulk deactivate', function () {
    Beverage::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(BeverageManager::class)
        ->set('viewMode', 'grid')
        ->call('togglePageSelection');

    $component->assertSet('viewMode', 'grid');
    expect($component->get('selectedBeverageIds'))->toHaveCount(2);

    $component->call('deactivateSelected');

    expect(Beverage::query()->where('is_active', false)->count())->toBe(2);
});

test('customer manager can select visible rows and bulk deactivate', function () {
    Customer::factory()->count(2)->create(['is_active' => true]);

    $component = Livewire::test(CustomerManager::class)
        ->call('togglePageSelection');

    expect($component->get('selectedCustomerIds'))->toHaveCount(2);

    $component->call('deactivateSelected');

    expect(Customer::query()->where('is_active', false)->count())->toBe(2);
});

test('product manager supports grid mode', function () {
    Product::factory()->count(2)->create();

    Livewire::test(ProductManager::class)
        ->set('viewMode', 'grid')
        ->assertSet('viewMode', 'grid')
        ->assertSee('Productos');
});

test('reports overview can switch between visual and detail modes', function () {
    Livewire::test(ReportsOverview::class)
        ->set('presentationMode', 'detail')
        ->assertSet('presentationMode', 'detail')
        ->assertSee('Bebidas destacadas');
});
