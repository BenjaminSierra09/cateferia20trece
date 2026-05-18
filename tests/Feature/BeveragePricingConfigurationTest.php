<?php

use App\Livewire\Beverages\Create as BeverageCreate;
use App\Livewire\Sales\Pos;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\BranchBeveragePriceOverride;
use App\Models\Size;
use App\Models\User;
use App\Services\WorkSessionService;
use Livewire\Livewire;

test('beverage form stores multiple size prices and branch overrides', function () {
    $user = User::factory()->create();
    $category = BeverageCategory::factory()->create();
    $branchNorth = Branch::factory()->create(['name' => 'Norte']);
    $branchDowntown = Branch::factory()->create(['name' => 'Centro']);
    $small = Size::factory()->create(['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8]);
    $large = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16]);

    Livewire::actingAs($user)
        ->test(BeverageCreate::class)
        ->set('name', 'Moka')
        ->set('description', 'Chocolate y espresso')
        ->set('beverage_category_id', $category->id)
        ->set('temperature', 'cold')
        ->set('size_pricing.0.enabled', true)
        ->set('size_pricing.0.price', 58)
        ->set("size_pricing.0.branch_prices.{$branchNorth->id}", 61)
        ->set('size_pricing.1.enabled', true)
        ->set('size_pricing.1.price', 72)
        ->call('save');

    $beverage = Beverage::query()->where('name', 'Moka')->first();

    expect($beverage)->not->toBeNull();
    expect((float) $beverage->base_price)->toBe(58.0);
    expect($beverage->is_hot)->toBeFalse();
    expect($beverage->sizePrices)->toHaveCount(2);
    expect((float) $beverage->sizePrices()->where('size_id', $small->id)->value('price'))->toBe(58.0);
    expect((float) $beverage->sizePrices()->where('size_id', $large->id)->value('price'))->toBe(72.0);
    expect(
        (float) BranchBeveragePriceOverride::query()
            ->where('branch_id', $branchNorth->id)
            ->where('beverage_id', $beverage->id)
            ->where('size_id', $small->id)
            ->value('price')
    )->toBe(61.0);
    expect(
        BranchBeveragePriceOverride::query()
            ->where('branch_id', $branchDowntown->id)
            ->where('beverage_id', $beverage->id)
            ->where('size_id', $small->id)
            ->exists()
    )->toBeFalse();
});

test('pos uses branch override price when adding a beverage to the cart', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz', 'capacity_ounces' => 12]);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Chai',
        'base_price' => 54,
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 54,
    ]);

    BranchBeveragePriceOverride::query()->create([
        'branch_id' => $branch->id,
        'beverage_id' => $beverage->id,
        'size_id' => $size->id,
        'price' => 59,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->call('addBeverage', $beverage->id, $size->id)
        ->assertSet('cart.0.base_price', 59.0)
        ->assertSet('cart.0.unit_price', 59.0);
});
