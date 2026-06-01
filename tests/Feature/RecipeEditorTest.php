<?php

use App\Livewire\Recipes\BeverageRecipe;
use App\Livewire\Recipes\CustomizationRecipe;
use App\Livewire\Recipes\Manager;
use App\Livewire\Recipes\ProductRecipe;
use App\Models\Beverage;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Livewire\Livewire;

test('the recipes manager renders', function () {
    $user = User::factory()->create();
    Beverage::factory()->create(['name' => 'Latte']);

    Livewire::actingAs($user)
        ->test(Manager::class)
        ->assertOk()
        ->assertSee('Latte');
});

test('the beverage recipe editor saves lines per size', function () {
    $user = User::factory()->create();
    $beverage = Beverage::factory()->create();
    $size = Size::factory()->create();
    $item = InventoryItem::factory()->create();

    Livewire::actingAs($user)
        ->test(BeverageRecipe::class, ['beverage' => $beverage])
        ->set('sizeId', $size->id)
        ->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '20']])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('beverage_recipe_lines', [
        'beverage_id' => $beverage->id,
        'size_id' => $size->id,
        'inventory_item_id' => $item->id,
        'quantity' => 20,
    ]);
});

test('the product recipe editor saves lines', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $item = InventoryItem::factory()->create();

    Livewire::actingAs($user)
        ->test(ProductRecipe::class, ['product' => $product])
        ->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '15', 'scales_with_quantity' => true]])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('product_recipe_lines', [
        'product_id' => $product->id,
        'inventory_item_id' => $item->id,
        'quantity' => 15,
    ]);
});

test('the customization recipe editor saves the category default and an option override', function () {
    $user = User::factory()->create();
    $type = CustomizationType::factory()->create();
    $option = CustomizationOption::factory()->create(['customization_type_id' => $type->id]);
    $item = InventoryItem::factory()->create();

    $component = Livewire::actingAs($user)->test(CustomizationRecipe::class, ['customizationType' => $type]);

    // Category default (option null).
    $component->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '50']])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customization_recipe_lines', [
        'customization_type_id' => $type->id,
        'customization_option_id' => null,
        'inventory_item_id' => $item->id,
        'quantity' => 50,
    ]);

    // Option-specific override.
    $component->set('scope', (string) $option->id)
        ->set('lines', [['inventory_item_id' => $item->id, 'quantity' => '70']])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customization_recipe_lines', [
        'customization_type_id' => $type->id,
        'customization_option_id' => $option->id,
        'inventory_item_id' => $item->id,
        'quantity' => 70,
    ]);
});
