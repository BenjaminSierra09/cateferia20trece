<?php

use App\Livewire\Beverages\Create as BeverageCreate;
use App\Livewire\Customizations\OptionForm;
use App\Livewire\Customizations\TypeForm;
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

test('customization type form can select all beverages and sync all of its options', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();

    $type = CustomizationType::factory()->create([
        'name' => 'Leches',
        'is_active' => true,
    ]);
    $otherType = CustomizationType::factory()->create([
        'name' => 'Jarabes',
        'is_active' => true,
    ]);

    $milkOptions = CustomizationOption::factory()->count(2)->create([
        'customization_type_id' => $type->id,
        'is_available' => true,
    ]);
    $otherOption = CustomizationOption::factory()->create([
        'customization_type_id' => $otherType->id,
        'is_available' => true,
    ]);

    $firstBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $secondBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    $firstBeverage->customizationOptions()->attach($otherOption->id);

    Livewire::test(TypeForm::class, ['customizationType' => $type])
        ->call('selectAllBeverages')
        ->assertSet('selected_beverage_ids', [$firstBeverage->id, $secondBeverage->id]);

    expect($firstBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toEqualCanonicalizing([...$milkOptions->pluck('id')->all(), $otherOption->id]);

    expect($secondBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toEqualCanonicalizing($milkOptions->pluck('id')->all());

    Livewire::test(TypeForm::class, ['customizationType' => $type])
        ->call('clearAllBeverages')
        ->assertSet('selected_beverage_ids', []);

    expect($firstBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toEqualCanonicalizing([$otherOption->id]);

    expect($secondBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toBeEmpty();
});

test('customization option form can select and clear all beverages', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();

    $type = CustomizationType::factory()->create([
        'name' => 'Leches',
        'is_active' => true,
    ]);

    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Avena',
        'is_available' => true,
    ]);

    $firstBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $secondBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    $firstBeverage->customizationOptions()->attach($option->id);

    Livewire::test(OptionForm::class, ['customizationOption' => $option])
        ->assertSet('selected_beverage_ids', [$firstBeverage->id])
        ->call('selectAllBeverages')
        ->assertSet('selected_beverage_ids', [$firstBeverage->id, $secondBeverage->id])
        ->call('clearAllBeverages')
        ->assertSet('selected_beverage_ids', []);

    expect($firstBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toBeEmpty();

    expect($secondBeverage->fresh()->customizationOptions()->pluck('customization_options.id')->all())
        ->toBeEmpty();
});
