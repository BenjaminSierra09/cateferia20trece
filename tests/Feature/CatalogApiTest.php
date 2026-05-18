<?php

use App\Enums\SaleStatus;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
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

    $response = $this->getJson(route('api.catalog'));

    $response->assertSuccessful()
        ->assertJsonFragment(['name' => $branch->name])
        ->assertJsonFragment(['name' => $beverage->name])
        ->assertJsonFragment(['is_hot' => true])
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
