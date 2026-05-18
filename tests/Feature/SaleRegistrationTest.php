<?php

use App\Enums\PaymentMethod;
use App\Livewire\Sales\Pos;
use App\Livewire\Sales\RegisterSale;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Size;
use App\Models\User;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Livewire\Livewire;

test('sale service registers totals and customizations correctly', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create(['beverage_category_id' => $category->id, 'name' => 'Latte']);
    $size = Size::factory()->create(['name' => 'Grande']);
    $type = CustomizationType::factory()->create();
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'price' => 10,
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 50,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    $sale = app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'discount_total' => 10,
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 2,
            'customization_option_ids' => [$option->id],
            'special_customization_name' => 'Canela extra',
            'special_customization_price' => 5,
        ]],
    ], $user, $workSession);

    expect((float) $sale->subtotal)->toBe(130.0);
    expect((float) $sale->total)->toBe(120.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items->first()->customizations)->toHaveCount(2);
});

test('pos registers a sale from quick beverage selection', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Capuchino',
    ]);
    $size = Size::factory()->create(['name' => 'Mediano']);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 62,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->set('payment_method', PaymentMethod::Cash->value)
        ->call('addBeverage', $beverage->id, $size->id)
        ->call('save');

    $sale = Sale::query()->latest('id')->first();

    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(62.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items->first()->item_name)->toContain('Capuchino');
});

test('pos adds beverage using the selected size from the beverage card', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Chocolate',
    ]);
    $smallSize = Size::factory()->create(['name' => 'Chico']);
    $largeSize = Size::factory()->create(['name' => 'Grande']);

    $beverage->sizePrices()->createMany([
        [
            'size_id' => $smallSize->id,
            'price' => 50,
        ],
        [
            'size_id' => $largeSize->id,
            'price' => 80,
        ],
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->assertSee('Agregar al carrito')
        ->set("selectedBeverageSizes.{$beverage->id}", (string) $largeSize->id)
        ->call('addSelectedBeverage', $beverage->id)
        ->call('save');

    $sale = Sale::query()->latest('id')->first();

    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(80.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items->first()->item_name)->toContain('Grande');
});

test('pos registers selected customizations from the cart', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Mocha',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);
    $type = CustomizationType::factory()->create(['name' => 'Leche']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Avena',
        'price' => 8,
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 70,
    ]);
    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->set('payment_method', PaymentMethod::Cash->value)
        ->call('addBeverage', $beverage->id, $size->id)
        ->set('cart.0.customization_option_ids', [$option->id])
        ->call('save');

    $sale = Sale::query()->latest('id')->first();

    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(78.0);
    expect($sale->items->first()->customizations)->toHaveCount(1);
    expect($sale->items->first()->customizations->first()->customization_name)->toBe('Avena');
});

test('sale service registers products sold by piece or gram', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $pieceProduct = Product::factory()->create([
        'name' => 'Panqué de plátano',
        'unit_type' => 'piece',
        'base_price' => 35,
    ]);
    $gramProduct = Product::factory()->create([
        'name' => 'Café de la casa',
        'unit_type' => 'gram',
        'base_price' => 0.42,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    $sale = app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [
            [
                'product_id' => $pieceProduct->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $gramProduct->id,
                'quantity' => 500,
            ],
        ],
    ], $user, $workSession);

    expect((float) $sale->subtotal)->toBe(280.0);
    expect($sale->items)->toHaveCount(2);
    expect($sale->items->pluck('product_id')->filter()->count())->toBe(2);
    expect($sale->items->last()->item_name)->toContain('(500g)');
});

test('sale service registers temporary products without catalog entries', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $workSession = app(WorkSessionService::class)->start($user, $branch);

    $sale = app(SaleService::class)->register([
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'item_name' => 'Croissant de chocolate',
            'unit_price' => 30,
            'quantity' => 2,
        ]],
    ], $user, $workSession);

    expect((float) $sale->subtotal)->toBe(60.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items->first()->beverage_id)->toBeNull();
    expect($sale->items->first()->product_id)->toBeNull();
    expect($sale->items->first()->item_name)->toBe('Croissant de chocolate');
    expect((float) $sale->items->first()->unit_price)->toBe(30.0);
});

test('manual sale can assign a customer by qr', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create(['name' => 'Cliente QR']);
    $qrCode = CustomerQrCode::factory()->create([
        'customer_id' => $customer->id,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(RegisterSale::class)
        ->set('qr_uuid', $qrCode->uuid)
        ->call('assignCustomerByQr')
        ->assertSet('customer_id', $customer->id)
        ->assertSet('customer_search', $customer->name);
});

test('pos registers a non beverage product', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $product = Product::factory()->create([
        'name' => 'Pan artesanal',
        'unit_type' => 'piece',
        'base_price' => 28,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->set('payment_method', PaymentMethod::Cash->value)
        ->call('addProduct', $product->id)
        ->call('save');

    $sale = Sale::query()->latest('id')->first();

    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(28.0);
    expect($sale->items)->toHaveCount(1);
    expect($sale->items->first()->product_id)->toBe($product->id);
});

test('pos only shows customer suggestions while actively searching', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'name' => 'Cliente Visible',
        'phone' => '+524151234567',
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->assertDontSee($customer->name)
        ->set('customer_search', 'Cliente')
        ->assertSee($customer->name)
        ->call('selectCustomer', $customer->id)
        ->assertDontSee($customer->phone);
});

test('pos updates totals when a discount is applied', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 60,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user);

    Livewire::test(Pos::class)
        ->call('addBeverage', $beverage->id, $size->id)
        ->assertSee('$60.00')
        ->assertSee('Total')
        ->set('discount_total', 10)
        ->assertSeeInOrder([
            'Descuento',
            '$10.00',
            'Total',
            '$50.00',
        ]);
});
