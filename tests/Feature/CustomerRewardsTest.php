<?php

use App\Enums\PaymentMethod;
use App\Enums\RewardTier;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use App\Services\SaleService;
use App\Services\WorkSessionService;

test('customer rewards advance to silver after twelve beverages in a year', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'reward_balance' => 0,
        'annual_drink_count' => 0,
        'reward_tier' => RewardTier::Bronze,
    ]);
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create(['beverage_category_id' => $category->id]);
    $size = Size::factory()->create();
    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 20,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Card->value,
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 12,
            'customization_option_ids' => [],
        ]],
    ], $user, $workSession);

    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(12);
    expect($customer->reward_tier)->toBe(RewardTier::Silver);
    expect((float) $customer->reward_balance)->toBe(24.0);
});

test('customer reward drink count ignores non beverage products', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'reward_balance' => 0,
        'annual_drink_count' => 0,
        'reward_tier' => RewardTier::Bronze,
    ]);
    $product = Product::factory()->create([
        'unit_type' => 'piece',
        'base_price' => 50,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Card->value,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 3,
        ]],
    ], $user, $workSession);

    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(0);
    expect($customer->reward_tier)->toBe(RewardTier::Bronze);
    expect((float) $customer->reward_balance)->toBe(7.5);
});
