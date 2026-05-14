<?php

use App\Livewire\Beverages\Create as BeverageCreate;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('beverage form syncs selected customization options', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();
    Size::factory()->create(['is_active' => true]);
    Branch::factory()->create(['is_active' => true]);

    $intensityType = CustomizationType::factory()->create([
        'name' => 'Intensidad',
        'is_active' => true,
    ]);
    $milkType = CustomizationType::factory()->create([
        'name' => 'Leches',
        'is_active' => true,
    ]);

    $intenseOption = CustomizationOption::factory()->create([
        'customization_type_id' => $intensityType->id,
        'name' => 'Carga alta',
        'is_available' => true,
    ]);
    $milkOption = CustomizationOption::factory()->create([
        'customization_type_id' => $milkType->id,
        'name' => 'Avena',
        'is_available' => true,
    ]);

    Livewire::test(BeverageCreate::class)
        ->set('name', 'Latte de prueba')
        ->set('description', 'Con intensidad')
        ->set('beverage_category_id', $category->id)
        ->set('selected_customization_option_ids', [$intenseOption->id, $milkOption->id])
        ->set('size_pricing.0.enabled', true)
        ->set('size_pricing.0.price', '68')
        ->call('save')
        ->assertHasNoErrors();

    $beverage = Beverage::query()->where('name', 'Latte de prueba')->firstOrFail();

    expect($beverage->customizationOptions()->pluck('customization_options.id')->all())
        ->toEqualCanonicalizing([$intenseOption->id, $milkOption->id]);
});

test('editing a beverage preloads linked customization options', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['is_active' => true]);
    $type = CustomizationType::factory()->create([
        'name' => 'Intensidad',
        'is_active' => true,
    ]);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Extra fuerte',
        'is_available' => true,
    ]);

    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
    ]);
    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 72,
        'is_active' => true,
    ]);
    $beverage->customizationOptions()->attach($option->id);

    Livewire::test(BeverageCreate::class, ['beverage' => $beverage])
        ->assertSet('selected_customization_option_ids', [$option->id]);
});

test('beverage form can select and clear all options for a customization type', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();
    Size::factory()->create(['is_active' => true]);
    Branch::factory()->create(['is_active' => true]);

    $intensityType = CustomizationType::factory()->create([
        'name' => 'Intensidad',
        'is_active' => true,
    ]);
    $milkType = CustomizationType::factory()->create([
        'name' => 'Leches',
        'is_active' => true,
    ]);

    $intensityOptions = CustomizationOption::factory()->count(2)->create([
        'customization_type_id' => $intensityType->id,
        'is_available' => true,
    ]);
    $milkOption = CustomizationOption::factory()->create([
        'customization_type_id' => $milkType->id,
        'is_available' => true,
    ]);

    Livewire::test(BeverageCreate::class)
        ->set('beverage_category_id', $category->id)
        ->call('selectAllCustomizationOptions', $intensityType->id)
        ->assertSet('selected_customization_option_ids', $intensityOptions->pluck('id')->all())
        ->set('selected_customization_option_ids', [...$intensityOptions->pluck('id')->all(), $milkOption->id])
        ->call('clearCustomizationOptions', $intensityType->id)
        ->assertSet('selected_customization_option_ids', [$milkOption->id]);
});
