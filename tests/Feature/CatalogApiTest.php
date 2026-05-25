<?php

use App\Enums\SaleStatus;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\BranchBeverageSizeAvailability;
use App\Models\BranchCustomizationSizePriceOverride;
use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;
use App\Models\CustomizationType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use App\Support\BeverageTemperatureCustomization;
use Illuminate\Support\Facades\Queue;

test('catalog api returns beverages branches and customizations', function () {
    Queue::fake();

    $branch = Branch::factory()->create(['name' => 'Centro']);
    $category = BeverageCategory::factory()->create(['name' => 'Café', 'slug' => 'cafe']);
    $size = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz']);
    $type = CustomizationType::factory()->create(['name' => 'Extras', 'slug' => 'extras']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Shot extra',
    ]);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
        'slug' => 'latte',
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 70,
    ]);
    $beverage->customizationOptions()->attach($option->id);
    app(BeverageTemperatureCustomization::class)->applyToBeverage($beverage, true);

    $response = $this->getJson(route('api.catalog'));

    $response->assertSuccessful()
        ->assertJsonFragment(['name' => $branch->name])
        ->assertJsonFragment(['name' => $beverage->name])
        ->assertJsonFragment(['name' => $option->name]);
});

test('catalog api orders beverages by completed sales popularity', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create(['name' => 'Café']);
    $size = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz']);

    $popularBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $lessPopularBeverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    foreach ([$popularBeverage, $lessPopularBeverage] as $beverage) {
        $beverage->sizePrices()->create([
            'size_id' => $size->id,
            'price' => 65,
        ]);
    }

    $completedSale = Sale::factory()->create();
    $cancelledSale = Sale::factory()->create(['status' => SaleStatus::Cancelled]);

    SaleItem::factory()->create([
        'sale_id' => $completedSale->id,
        'beverage_id' => $popularBeverage->id,
        'size_id' => $size->id,
        'item_name' => $popularBeverage->name,
        'quantity' => 8,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $completedSale->id,
        'beverage_id' => $lessPopularBeverage->id,
        'size_id' => $size->id,
        'item_name' => $lessPopularBeverage->name,
        'quantity' => 2,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $cancelledSale->id,
        'beverage_id' => $lessPopularBeverage->id,
        'size_id' => $size->id,
        'item_name' => $lessPopularBeverage->name,
        'quantity' => 20,
    ]);

    $response = $this->getJson(route('api.catalog'));

    $response->assertSuccessful()
        ->assertJsonPath('beverages.0.id', $popularBeverage->id)
        ->assertJsonPath('beverages.0.popularity_quantity', 8)
        ->assertJsonPath('beverages.1.id', $lessPopularBeverage->id)
        ->assertJsonPath('beverages.1.popularity_quantity', 2);
});

test('catalog api filters beverage sizes by selected branch availability', function () {
    Queue::fake();

    $branch = Branch::factory()->create(['name' => 'Centro']);
    $category = BeverageCategory::factory()->create();
    $small = Size::factory()->create(['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8]);
    $large = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16]);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Chocolate',
    ]);

    $beverage->sizePrices()->createMany([
        ['size_id' => $small->id, 'price' => 55],
        ['size_id' => $large->id, 'price' => 70],
    ]);

    BranchBeverageSizeAvailability::query()->create([
        'branch_id' => $branch->id,
        'beverage_id' => $beverage->id,
        'size_id' => $large->id,
        'is_available' => false,
    ]);

    $this->getJson(route('api.catalog', ['branch_id' => $branch->id]))
        ->assertSuccessful()
        ->assertJsonPath('beverages.0.sizes.0.size_id', $small->id)
        ->assertJsonCount(1, 'beverages.0.sizes');

    $this->getJson(route('api.catalog'))
        ->assertSuccessful()
        ->assertJsonCount(2, 'beverages.0.sizes');
});

test('catalog api exposes customization order open state and default options per beverage', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz']);
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
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 65,
    ]);
    $beverage->customizationOptions()->attach([
        $strong->id => ['is_default' => false],
        $wholeMilk->id => ['is_default' => true],
    ]);
    $beverage->customizationTypeSettings()->createMany([
        ['customization_type_id' => $milkType->id, 'sort_order' => 0, 'is_open_by_default' => true],
        ['customization_type_id' => $intensityType->id, 'sort_order' => 1, 'is_open_by_default' => false],
    ]);

    $this->getJson(route('api.catalog'))
        ->assertSuccessful()
        ->assertJsonPath('beverages.0.customizations.0.id', $wholeMilk->id)
        ->assertJsonPath('beverages.0.customizations.0.is_default', true)
        ->assertJsonPath('beverages.0.customizations.0.type.sort_order', 0)
        ->assertJsonPath('beverages.0.customizations.0.type.is_open_by_default', true)
        ->assertJsonPath('beverages.0.customizations.1.id', $strong->id)
        ->assertJsonPath('beverages.0.customizations.1.is_default', false)
        ->assertJsonPath('beverages.0.customizations.1.type.sort_order', 1)
        ->assertJsonPath('beverages.0.customizations.1.type.is_open_by_default', false);
});

test('catalog api exposes temperature first with migrated default option', function () {
    Queue::fake();

    $category = BeverageCategory::factory()->create();
    $size = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz']);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Limonada',
        'is_hot' => false,
    ]);
    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 55,
    ]);

    app(BeverageTemperatureCustomization::class)->applyToBeverage($beverage, false);

    $this->getJson(route('api.catalog'))
        ->assertSuccessful()
        ->assertJsonPath('beverages.0.customizations.0.type.name', 'Temperatura')
        ->assertJsonPath('beverages.0.customizations.0.type.sort_order', 0)
        ->assertJsonPath('beverages.0.customizations.0.name', 'Caliente')
        ->assertJsonPath('beverages.0.customizations.0.is_default', false)
        ->assertJsonPath('beverages.0.customizations.1.name', 'Fría')
        ->assertJsonPath('beverages.0.customizations.1.is_default', true);
});

test('catalog api exposes customization prices by selected branch and size', function () {
    Queue::fake();

    $branch = Branch::factory()->create();
    $category = BeverageCategory::factory()->create();
    $small = Size::factory()->create(['name' => 'Chico', 'capacity_label' => '8 oz', 'capacity_ounces' => 8]);
    $large = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz', 'capacity_ounces' => 16]);
    $type = CustomizationType::factory()->create(['name' => 'Leches']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Almendra',
        'price' => 15,
    ]);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    $beverage->sizePrices()->createMany([
        ['size_id' => $small->id, 'price' => 55],
        ['size_id' => $large->id, 'price' => 70],
    ]);
    $beverage->customizationOptions()->attach($option->id);
    CustomizationOptionSizePrice::query()->insert([
        ['customization_option_id' => $option->id, 'size_id' => $small->id, 'price' => 10],
        ['customization_option_id' => $option->id, 'size_id' => $large->id, 'price' => 20],
    ]);
    BranchCustomizationSizePriceOverride::query()->create([
        'branch_id' => $branch->id,
        'customization_option_id' => $option->id,
        'size_id' => $large->id,
        'price' => 18,
    ]);

    $this->getJson(route('api.catalog', ['branch_id' => $branch->id]))
        ->assertSuccessful()
        ->assertJsonPath('beverages.0.customizations.0.price', 15)
        ->assertJsonPath('beverages.0.customizations.0.base_price', 15)
        ->assertJsonPath('beverages.0.customizations.0.size_prices.0.size_id', $small->id)
        ->assertJsonPath('beverages.0.customizations.0.size_prices.0.price', 10)
        ->assertJsonPath('beverages.0.customizations.0.size_prices.1.size_id', $large->id)
        ->assertJsonPath('beverages.0.customizations.0.size_prices.1.base_price', 20)
        ->assertJsonPath('beverages.0.customizations.0.size_prices.1.price', 18);
});
