<?php

use App\Enums\PaymentMethod;
use App\Enums\RewardTier;
use App\Enums\RewardTransactionType;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Size;
use App\Models\User;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

function registerRewardSale(
    Customer $customer,
    User $user,
    mixed $workSession,
    Beverage $beverage,
    Size $size,
    array $overrides = [],
): void {
    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Card->value,
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 1,
            'customization_option_ids' => [],
        ]],
        ...$overrides,
    ], $user, $workSession);
}

test('customer rewards advance to silver after thirty qualifying visit days', function () {
    $startDate = CarbonImmutable::parse('2026-01-01 09:00:00');
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => $startDate->subDays(30),
        'updated_at' => $startDate->subDays(30),
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

    foreach (range(0, 29) as $offset) {
        Carbon::setTestNow($startDate->addDays($offset));
        registerRewardSale($customer, $user, $workSession, $beverage, $size);
    }

    Carbon::setTestNow();
    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(30)
        ->and($customer->reward_tier)->toBe(RewardTier::Silver)
        ->and((float) $customer->reward_balance)->toBe(31.0);
});

test('customer rewards advance to gold after forty five qualifying visit days', function () {
    $startDate = CarbonImmutable::parse('2026-01-01 09:00:00');
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => $startDate->subDays(45),
        'updated_at' => $startDate->subDays(45),
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

    foreach (range(0, 44) as $offset) {
        Carbon::setTestNow($startDate->addDays($offset));
        registerRewardSale($customer, $user, $workSession, $beverage, $size);
    }

    Carbon::setTestNow();
    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(45)
        ->and($customer->reward_tier)->toBe(RewardTier::Gold)
        ->and((float) $customer->reward_balance)->toBe(62.0);
});

test('customer receives welcome bonus during the first three days after registration', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $registeredAt = CarbonImmutable::parse('2026-02-01 08:00:00');
    $customer = Customer::factory()->create([
        'created_at' => $registeredAt,
        'updated_at' => $registeredAt,
        'reward_balance' => 0,
        'annual_drink_count' => 0,
        'reward_tier' => RewardTier::Bronze,
    ]);
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create(['beverage_category_id' => $category->id]);
    $size = Size::factory()->create();
    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 100,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    Carbon::setTestNow($registeredAt->addDay());
    registerRewardSale($customer, $user, $workSession, $beverage, $size);

    Carbon::setTestNow($registeredAt->addDays(4));
    registerRewardSale($customer, $user, $workSession, $beverage, $size);

    Carbon::setTestNow();
    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(2)
        ->and($customer->reward_tier)->toBe(RewardTier::Bronze)
        ->and((float) $customer->reward_balance)->toBe(15.0);

    $descriptions = $customer->rewardTransactions()->where('type', RewardTransactionType::Earned)->pluck('description')->all();

    expect($descriptions[0])->toContain('10%')
        ->and($descriptions[1])->toContain('5%');
});

test('multiple sales on the same day only count as one visit', function () {
    $saleMoment = CarbonImmutable::parse('2026-03-10 10:00:00');
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => $saleMoment->subDays(10),
        'updated_at' => $saleMoment->subDays(10),
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

    Carbon::setTestNow($saleMoment);
    registerRewardSale($customer, $user, $workSession, $beverage, $size);

    Carbon::setTestNow($saleMoment->addHours(3));
    registerRewardSale($customer, $user, $workSession, $beverage, $size);

    Carbon::setTestNow($saleMoment->addHours(6));
    registerRewardSale($customer, $user, $workSession, $beverage, $size, [
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 2,
            'customization_option_ids' => [],
        ]],
    ]);

    Carbon::setTestNow();
    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(1)
        ->and((float) $customer->reward_balance)->toBe(4.0)
        ->and($customer->rewardTransactions()->where('type', RewardTransactionType::Earned)->count())->toBe(3);
});

test('sales paid with reward balance do not earn bonus or visit progress', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => now()->subDays(20),
        'reward_balance' => 50,
        'annual_drink_count' => 9,
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
        'payment_method' => PaymentMethod::Mixed->value,
        'reward_redeemed_total' => 10,
        'payment_breakdown' => [
            'cash' => 10,
            'reward_balance' => 10,
        ],
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 1,
            'customization_option_ids' => [],
        ]],
    ], $user, $workSession);

    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(9)
        ->and($customer->reward_tier)->toBe(RewardTier::Bronze)
        ->and((float) $customer->reward_balance)->toBe(40.0);

    expect(
        $customer->rewardTransactions()->where('type', RewardTransactionType::Earned)->count()
    )->toBe(0);
});

test('sales paid only as debt do not earn bonus or visit progress', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => now()->subDays(20),
        'reward_balance' => 50,
        'annual_drink_count' => 9,
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
        'payment_method' => PaymentMethod::Debt->value,
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 1,
            'customization_option_ids' => [],
        ]],
    ], $user, $workSession);

    $customer->refresh();

    expect($customer->annual_drink_count)->toBe(9)
        ->and($customer->reward_tier)->toBe(RewardTier::Bronze)
        ->and((float) $customer->reward_balance)->toBe(50.0);

    expect(
        $customer->rewardTransactions()->where('type', RewardTransactionType::Earned)->count()
    )->toBe(0);
});
