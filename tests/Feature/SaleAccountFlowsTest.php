<?php

use App\Enums\CustomerDebtMovementType;
use App\Enums\PaymentMethod;
use App\Enums\RewardTransactionType;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Size;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\CustomerDebtService;
use Laravel\Sanctum\Sanctum;

function createOperationalSaleContext(): array
{
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'reward_balance' => 100,
    ]);
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte de prueba',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 150,
    ]);

    $workSession = WorkSession::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'work_date' => now()->toDateString(),
        'clock_in_at' => now(),
        'status' => 'open',
    ]);

    return compact('branch', 'user', 'customer', 'beverage', 'size', 'workSession');
}

test('v1 api can credit reward balance manually for a customer', function () {
    $context = createOperationalSaleContext();

    Sanctum::actingAs($context['user']);

    $response = $this->postJson("/api/v1/customers/{$context['customer']->id}/reward-transactions", [
        'amount' => 80,
        'notes' => 'Cambio abonado a cuenta',
        'recorded_at' => '2026-05-14T10:00:00-06:00',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.type', RewardTransactionType::ManualAdjustment->value)
        ->assertJsonPath('data.amount', '80.00')
        ->assertJsonPath('data.balance_after', '180.00')
        ->assertJsonPath('data.description', 'Cambio abonado a cuenta');

    expect((float) $context['customer']->fresh()->reward_balance)->toBe(180.0);
});

test('v1 api stores mixed payment breakdown on sales', function () {
    $context = createOperationalSaleContext();

    Sanctum::actingAs($context['user']);

    $response = $this->postJson('/api/v1/sales', [
        'user_id' => $context['user']->id,
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Mixed->value,
        'reward_redeemed_total' => 30,
        'payment_breakdown' => [
            'cash' => 40,
            'transfer' => 80,
            'reward_balance' => 30,
        ],
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.payment_method', PaymentMethod::Mixed->value)
        ->assertJsonPath('data.payment_breakdown.cash', 40)
        ->assertJsonPath('data.payment_breakdown.transfer', 80)
        ->assertJsonPath('data.payment_breakdown.reward_balance', 30)
        ->assertJsonPath('data.reward_redeemed_total', '30.00')
        ->assertJsonPath('data.total', '120.00');
});

test('v1 api can register a sale as debt and create the debt movement automatically', function () {
    $context = createOperationalSaleContext();

    Sanctum::actingAs($context['user']);

    $response = $this->postJson('/api/v1/sales', [
        'user_id' => $context['user']->id,
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Debt->value,
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.payment_method', PaymentMethod::Debt->value)
        ->assertJsonPath('data.total', '150.00');

    $context['customer']->refresh();

    expect($context['customer']->hasDebt())->toBeTrue()
        ->and($context['customer']->grossDebtBalance())->toBe(150.0)
        ->and($context['customer']->debtBalance())->toBe(50.0)
        ->and(
            $context['customer']->debtMovements()->latest('id')->value('type')
        )->toBe(CustomerDebtMovementType::Debt);
});

test('v1 api caps redeemed reward balance using the amount available after offsetting debt', function () {
    $context = createOperationalSaleContext();

    app(CustomerDebtService::class)->register(
        customer: $context['customer'],
        type: CustomerDebtMovementType::Debt,
        amount: 80,
        user: $context['user'],
        branchId: $context['branch']->id,
    );

    Sanctum::actingAs($context['user']);

    $response = $this->postJson('/api/v1/sales', [
        'user_id' => $context['user']->id,
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Mixed->value,
        'reward_redeemed_total' => 20,
        'payment_breakdown' => [
            'cash' => 130,
            'reward_balance' => 20,
        ],
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.reward_redeemed_total', '20.00')
        ->assertJsonPath('data.total', '130.00');
});

test('v1 api can register temporary sale items', function () {
    $context = createOperationalSaleContext();

    Sanctum::actingAs($context['user']);

    $response = $this->postJson('/api/v1/sales', [
        'user_id' => $context['user']->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'item_name' => 'Croissant de chocolate',
            'unit_price' => 30,
            'quantity' => 2,
        ]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.subtotal', '60.00')
        ->assertJsonPath('data.total', '60.00')
        ->assertJsonPath('data.items.0.item_name', 'Croissant de chocolate')
        ->assertJsonPath('data.items.0.product_id', null)
        ->assertJsonPath('data.items.0.beverage_id', null);
});
