<?php

use App\Mcp\Servers\MenuServer;
use App\Mcp\Tools\ListBeverageCategories;
use App\Mcp\Tools\ListBeverages;
use App\Mcp\Tools\ListProducts;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

test('list_beverages returns active beverages with their category, temperature, and size prices', function () {
    $category = BeverageCategory::factory()->create(['name' => 'Café', 'slug' => 'cafe']);
    $size = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz']);

    $latte = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
        'slug' => 'latte',
        'is_hot' => true,
        'is_active' => true,
    ]);
    $latte->sizePrices()->create(['size_id' => $size->id, 'price' => 70]);

    Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Discontinued Brew',
        'slug' => 'discontinued-brew',
        'is_active' => false,
    ]);

    MenuServer::tool(ListBeverages::class)
        ->assertOk()
        ->assertHasNoErrors()
        ->assertSee(['Latte', 'Café', 'Grande', '"temperature":"hot"', '"count":1'])
        ->assertDontSee('Discontinued Brew');
});

test('list_beverages filters by temperature', function () {
    $category = BeverageCategory::factory()->create();

    Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
        'slug' => 'latte',
        'is_hot' => true,
    ]);
    Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Limonada',
        'slug' => 'limonada',
        'is_hot' => false,
    ]);

    MenuServer::tool(ListBeverages::class, ['temperature' => 'cold'])
        ->assertOk()
        ->assertSee('Limonada')
        ->assertDontSee('Latte');
});

test('list_beverages filters by category and name search', function () {
    $coffee = BeverageCategory::factory()->create(['name' => 'Café', 'slug' => 'cafe']);
    $tea = BeverageCategory::factory()->create(['name' => 'Té', 'slug' => 'te']);

    Beverage::factory()->create(['beverage_category_id' => $coffee->id, 'name' => 'Latte', 'slug' => 'latte']);
    Beverage::factory()->create(['beverage_category_id' => $coffee->id, 'name' => 'Americano', 'slug' => 'americano']);
    Beverage::factory()->create(['beverage_category_id' => $tea->id, 'name' => 'Matcha', 'slug' => 'matcha']);

    MenuServer::tool(ListBeverages::class, ['category' => 'cafe'])
        ->assertOk()
        ->assertSee(['Latte', 'Americano'])
        ->assertDontSee('Matcha');

    MenuServer::tool(ListBeverages::class, ['search' => 'Latt'])
        ->assertOk()
        ->assertSee('Latte')
        ->assertDontSee(['Americano', 'Matcha']);
});

test('list_beverages can include inactive beverages when only_active is false', function () {
    $category = BeverageCategory::factory()->create();

    Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
        'slug' => 'latte',
        'is_active' => true,
    ]);
    Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Retired Mocha',
        'slug' => 'retired-mocha',
        'is_active' => false,
    ]);

    MenuServer::tool(ListBeverages::class, ['only_active' => false])
        ->assertOk()
        ->assertSee(['Latte', 'Retired Mocha', '"count":2']);
});

test('list_beverage_categories returns categories with their active beverage counts', function () {
    $category = BeverageCategory::factory()->create(['name' => 'Café', 'slug' => 'cafe']);

    Beverage::factory()->create(['beverage_category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte', 'is_active' => true]);
    Beverage::factory()->create(['beverage_category_id' => $category->id, 'name' => 'Americano', 'slug' => 'americano', 'is_active' => true]);
    Beverage::factory()->create(['beverage_category_id' => $category->id, 'name' => 'Retired', 'slug' => 'retired', 'is_active' => false]);

    MenuServer::tool(ListBeverageCategories::class)
        ->assertOk()
        ->assertSee(['Café', '"active_beverages_count":2', '"count":1']);
});

test('list_products returns active products and supports a name search', function () {
    Product::factory()->create(['name' => 'Medialuna', 'slug' => 'medialuna', 'is_active' => true]);
    Product::factory()->create(['name' => 'Alfajor', 'slug' => 'alfajor', 'is_active' => true]);
    Product::factory()->create(['name' => 'Stale Toast', 'slug' => 'stale-toast', 'is_active' => false]);

    MenuServer::tool(ListProducts::class)
        ->assertOk()
        ->assertSee(['Medialuna', 'Alfajor'])
        ->assertDontSee('Stale Toast');

    MenuServer::tool(ListProducts::class, ['search' => 'Media'])
        ->assertOk()
        ->assertSee('Medialuna')
        ->assertDontSee('Alfajor');
});
