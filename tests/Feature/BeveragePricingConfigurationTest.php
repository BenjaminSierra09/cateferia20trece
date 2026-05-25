<?php

use App\Livewire\Beverages\Create as BeverageCreate;
use App\Livewire\Sales\Pos;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\BeverageCustomizationTypeSetting;
use App\Models\Branch;
use App\Models\BranchBeveragePriceOverride;
use App\Models\BranchBeverageSizeAvailability;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use App\Models\User;
use App\Services\WorkSessionService;
use App\Support\BeverageTemperatureCustomization;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function (): void {
    Queue::fake();
});

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
        ->set('size_pricing.0.enabled', true)
        ->set('size_pricing.0.price', 58)
        ->set("size_pricing.0.branch_prices.{$branchNorth->id}", 61)
        ->set("size_pricing.0.branch_availability.{$branchDowntown->id}", false)
        ->set('size_pricing.1.enabled', true)
        ->set('size_pricing.1.price', 72)
        ->call('save');

    $beverage = Beverage::query()->where('name', 'Moka')->first();

    expect($beverage)->not->toBeNull();
    expect((float) $beverage->base_price)->toBe(58.0);
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
    expect(
        BranchBeverageSizeAvailability::query()
            ->where('branch_id', $branchDowntown->id)
            ->where('beverage_id', $beverage->id)
            ->where('size_id', $small->id)
            ->value('is_available')
    )->toBeFalse();
    expect(
        BranchBeverageSizeAvailability::query()
            ->where('branch_id', $branchNorth->id)
            ->where('beverage_id', $beverage->id)
            ->where('size_id', $small->id)
            ->exists()
    )->toBeFalse();
});

test('beverage form stores temperature as first customization with selected default', function () {
    $user = User::factory()->create();
    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8]);
    ['type' => $temperatureType, 'hot' => $hot, 'cold' => $cold] = app(BeverageTemperatureCustomization::class)->ensureExists();

    Livewire::actingAs($user)
        ->test(BeverageCreate::class)
        ->set('name', 'Limonada')
        ->set('beverage_category_id', $category->id)
        ->set('size_pricing.0.enabled', true)
        ->set('size_pricing.0.price', 55)
        ->set('default_customization_option_ids', [$cold->id])
        ->call('save');

    $beverage = Beverage::query()->where('name', 'Limonada')->first();

    expect($beverage)->not->toBeNull();
    expect($beverage->customizationOptions()->whereKey($hot->id)->exists())->toBeTrue();
    expect($beverage->customizationOptions()->whereKey($cold->id)->exists())->toBeTrue();
    expect((bool) $beverage->customizationOptions()->whereKey($cold->id)->first()->pivot->is_default)->toBeTrue();
    expect((int) BeverageCustomizationTypeSetting::query()
        ->where('beverage_id', $beverage->id)
        ->where('customization_type_id', $temperatureType->id)
        ->value('sort_order'))->toBe(0);
});

test('beverage form stores customization category settings and default options', function () {
    $user = User::factory()->create();
    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz', 'capacity_ounces' => 12]);
    $milkType = CustomizationType::factory()->create(['name' => 'Leches', 'selection_mode' => 'single']);
    $intensityType = CustomizationType::factory()->create(['name' => 'Intensidad', 'selection_mode' => 'single']);
    $wholeMilk = CustomizationOption::factory()->create([
        'customization_type_id' => $milkType->id,
        'name' => 'Entera',
    ]);
    $strong = CustomizationOption::factory()->create([
        'customization_type_id' => $intensityType->id,
        'name' => 'Fuerte',
    ]);

    Livewire::actingAs($user)
        ->test(BeverageCreate::class)
        ->set('name', 'Latte')
        ->set('description', 'Espresso con leche')
        ->set('beverage_category_id', $category->id)
        ->set('size_pricing.0.enabled', true)
        ->set('size_pricing.0.price', 65)
        ->set('selected_customization_option_ids', [$wholeMilk->id, $strong->id])
        ->set('default_customization_option_ids', [$wholeMilk->id])
        ->set("customization_type_settings.{$milkType->id}.sort_order", 0)
        ->set("customization_type_settings.{$milkType->id}.is_open_by_default", true)
        ->set("customization_type_settings.{$intensityType->id}.sort_order", 1)
        ->call('save');

    $beverage = Beverage::query()->where('name', 'Latte')->firstOrFail();

    expect((bool) $beverage->customizationOptions()->whereKey($wholeMilk->id)->first()->pivot->is_default)->toBeTrue();
    expect((bool) $beverage->customizationOptions()->whereKey($strong->id)->first()->pivot->is_default)->toBeFalse();
    expect(
        BeverageCustomizationTypeSetting::query()
            ->where('beverage_id', $beverage->id)
            ->where('customization_type_id', $milkType->id)
            ->first()
            ?->is_open_by_default
    )->toBeTrue();
});

test('beverage form persists customization category sort order immediately', function () {
    $user = User::factory()->create();
    $beverage = Beverage::factory()->create();
    $milkType = CustomizationType::factory()->create(['name' => 'Leches']);
    $intensityType = CustomizationType::factory()->create(['name' => 'Intensidad']);
    $milkOption = CustomizationOption::factory()->create(['customization_type_id' => $milkType->id]);
    $intensityOption = CustomizationOption::factory()->create(['customization_type_id' => $intensityType->id]);

    $beverage->customizationOptions()->attach([
        $milkOption->id => ['is_default' => false],
        $intensityOption->id => ['is_default' => false],
    ]);
    $beverage->customizationTypeSettings()->createMany([
        ['customization_type_id' => $milkType->id, 'sort_order' => 0, 'is_open_by_default' => true],
        ['customization_type_id' => $intensityType->id, 'sort_order' => 1, 'is_open_by_default' => false],
    ]);

    Livewire::actingAs($user)
        ->test(BeverageCreate::class, ['beverage' => $beverage])
        ->call('sortCustomizationType', $intensityType->id, 1);

    expect(
        BeverageCustomizationTypeSetting::query()
            ->where('beverage_id', $beverage->id)
            ->where('customization_type_id', $milkType->id)
            ->value('sort_order')
    )->toBe(2);
    expect(
        BeverageCustomizationTypeSetting::query()
            ->where('beverage_id', $beverage->id)
            ->where('customization_type_id', $intensityType->id)
            ->value('sort_order')
    )->toBe(1);
    expect(
        BeverageCustomizationTypeSetting::query()
            ->where('beverage_id', $beverage->id)
            ->where('customization_type_id', app(BeverageTemperatureCustomization::class)->typeId())
            ->value('sort_order')
    )->toBe(0);
});

test('beverage form starts large customization groups collapsed and can expand them', function () {
    $user = User::factory()->create();
    ['type' => $temperatureType] = app(BeverageTemperatureCustomization::class)->ensureExists();
    $syrups = CustomizationType::factory()->create(['name' => 'Jarabes']);
    $intensity = CustomizationType::factory()->create(['name' => 'Intensidad']);

    CustomizationOption::factory()
        ->count(5)
        ->create(['customization_type_id' => $syrups->id]);
    CustomizationOption::factory()
        ->count(2)
        ->create(['customization_type_id' => $intensity->id]);

    Livewire::actingAs($user)
        ->test(BeverageCreate::class)
        ->assertSet('collapsed_customization_type_ids', [$syrups->id])
        ->call('toggleCustomizationTypeOptions', $syrups->id)
        ->assertSet('collapsed_customization_type_ids', [])
        ->call('collapseAllCustomizationTypes')
        ->assertSet('collapsed_customization_type_ids', [$intensity->id, $syrups->id, $temperatureType->id]);
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
