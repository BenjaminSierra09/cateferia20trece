<?php

use App\Enums\CustomerDebtMovementType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Livewire\Sales\Show;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Size;
use App\Models\User;
use App\Services\CustomerDebtService;
use App\Services\RewardProgramService;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Livewire\Livewire;

function createSaleCancellationContext(): array
{
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'created_at' => now()->subMonth(),
        'reward_balance' => 0,
    ]);
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte cancelable',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 150,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    return compact('branch', 'user', 'customer', 'beverage', 'size', 'workSession');
}

test('sale cancellation rebuilds customer rewards and marks the sale as cancelled', function () {
    $context = createSaleCancellationContext();

    app(RewardProgramService::class)->creditManualBalance(
        customer: $context['customer'],
        amount: 30,
        description: 'Saldo inicial',
        transactedAt: now()->subDay(),
    );

    $saleToCancel = app(SaleService::class)->register([
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
    ], $context['user'], $context['workSession']);

    $secondSale = app(SaleService::class)->register([
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ], $context['user'], $context['workSession']);

    expect((float) $context['customer']->fresh()->reward_balance)->toBe(17.5);

    $cancelledSale = app(SaleService::class)->cancel($saleToCancel);

    expect($cancelledSale->status)->toBe(SaleStatus::Cancelled)
        ->and((float) $context['customer']->fresh()->reward_balance)->toBe(37.5)
        ->and($context['customer']->fresh()->annual_drink_count)->toBe(1)
        ->and($context['customer']->rewardTransactions()->where('sale_id', $saleToCancel->id)->count())->toBe(0)
        ->and($context['customer']->rewardTransactions()->where('sale_id', $secondSale->id)->count())->toBe(1);
});

test('sale cancellation removes the automatic debt movement', function () {
    $context = createSaleCancellationContext();

    $sale = app(SaleService::class)->register([
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Debt->value,
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ], $context['user'], $context['workSession']);

    expect($context['customer']->fresh()->grossDebtBalance())->toBe(150.0);

    app(SaleService::class)->cancel($sale);

    expect($context['customer']->fresh()->grossDebtBalance())->toBe(0.0)
        ->and($context['customer']->debtMovements()->where('sale_id', $sale->id)->exists())->toBeFalse();
});

test('sale cancellation is blocked when the customer already has later debt movements', function () {
    $context = createSaleCancellationContext();

    $sale = app(SaleService::class)->register([
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Debt->value,
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ], $context['user'], $context['workSession']);

    app(CustomerDebtService::class)->register(
        customer: $context['customer'],
        type: CustomerDebtMovementType::Payment,
        amount: 50,
        notes: 'Abono posterior',
        user: $context['user'],
        branchId: $context['branch']->id,
        recordedAt: now()->addMinute()->toIso8601String(),
    );

    expect(fn () => app(SaleService::class)->cancel($sale))
        ->toThrow('No se puede cancelar esta venta porque la cuenta del cliente ya tiene movimientos de deuda posteriores.');
});

test('sale details screen can cancel a completed sale', function () {
    $context = createSaleCancellationContext();

    $sale = app(SaleService::class)->register([
        'customer_id' => $context['customer']->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'beverage_id' => $context['beverage']->id,
            'size_id' => $context['size']->id,
            'quantity' => 1,
        ]],
    ], $context['user'], $context['workSession']);

    Livewire::actingAs($context['user'])
        ->test(Show::class, ['sale' => $sale])
        ->call('cancelSale')
        ->assertSee('Cancelada');

    expect($sale->fresh()->status)->toBe(SaleStatus::Cancelled);
});
