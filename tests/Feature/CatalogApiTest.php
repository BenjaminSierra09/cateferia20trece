<?php

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;

test('catalog api returns beverages branches and customizations', function () {
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
        ->assertJsonFragment(['name' => $option->name]);
});
