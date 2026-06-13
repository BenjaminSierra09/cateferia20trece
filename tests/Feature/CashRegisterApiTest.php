<?php

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\CashRegisterCut;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;
use Laravel\Sanctum\Sanctum;

test('cash movements can be registered and listed for the active shift', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $session = WorkSession::factory()->for($user)->for($branch)->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/cash-movements', [
        'user_id' => $user->id,
        'type' => CashMovementType::Expense->value,
        'amount' => 85.50,
        'concept' => 'Compra de servilletas',
        'notes' => 'Urgente para barra',
    ])
        ->assertCreated()
        ->assertJsonPath('data.branch_id', $branch->id)
        ->assertJsonPath('data.work_session_id', $session->id)
        ->assertJsonPath('data.type', CashMovementType::Expense->value)
        ->assertJsonPath('data.signed_amount', -85.5);

    $this->getJson("/api/v1/cash-movements?work_session_id={$session->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.concept', 'Compra de servilletas');
});

test('cash register cut compares counted cash against expected cash', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $session = WorkSession::factory()->for($user)->for($branch)->create([
        'clock_in_at' => today()->setTime(9, 0),
    ]);
    Sanctum::actingAs($user);

    Sale::factory()->for($branch)->for($user)->create([
        'work_session_id' => $session->id,
        'payment_method' => PaymentMethod::Cash,
        'status' => SaleStatus::Completed,
        'sold_at' => today()->setTime(10, 0),
        'subtotal' => 100,
        'total' => 100,
    ]);
    Sale::factory()->for($branch)->for($user)->create([
        'work_session_id' => $session->id,
        'payment_method' => PaymentMethod::Mixed,
        'payment_breakdown' => ['cash' => 40, 'card' => 60],
        'status' => SaleStatus::Completed,
        'sold_at' => today()->setTime(10, 30),
        'subtotal' => 100,
        'total' => 100,
    ]);

    $this->postJson('/api/v1/cash-movements', [
        'user_id' => $user->id,
        'type' => CashMovementType::CashIn->value,
        'amount' => 200,
        'concept' => 'Depósito de monedas para cambio',
        'occurred_at' => today()->setTime(10, 45)->toIso8601String(),
    ])->assertCreated();

    $this->postJson('/api/v1/cash-movements', [
        'user_id' => $user->id,
        'type' => CashMovementType::Expense->value,
        'amount' => 50,
        'concept' => 'Compra de leche',
        'occurred_at' => today()->setTime(11, 0)->toIso8601String(),
    ])->assertCreated();

    $this->postJson('/api/v1/cash-register-cuts', [
        'user_id' => $user->id,
        'opening_cash_amount' => 500,
        'counted_cash_amount' => 780,
        'cut_at' => today()->setTime(12, 0)->toIso8601String(),
        'notes' => 'Corte pedido por jefe',
    ])
        ->assertCreated()
        ->assertJsonPath('data.opening_cash_amount', '500.00')
        ->assertJsonPath('data.cash_sales_total', '140.00')
        ->assertJsonPath('data.manual_income_total', '200.00')
        ->assertJsonPath('data.manual_expense_total', '50.00')
        ->assertJsonPath('data.expected_cash_amount', '790.00')
        ->assertJsonPath('data.counted_cash_amount', '780.00')
        ->assertJsonPath('data.difference_amount', '-10.00');
});

test('next cash register cut starts from previous counted cash when opening cash is omitted', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $session = WorkSession::factory()->for($user)->for($branch)->create([
        'clock_in_at' => today()->setTime(9, 0),
    ]);
    Sanctum::actingAs($user);

    CashRegisterCut::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'work_session_id' => $session->id,
        'period_start_at' => today()->setTime(9, 0),
        'cut_at' => today()->setTime(12, 0),
        'opening_cash_amount' => 500,
        'counted_cash_amount' => 780,
        'expected_cash_amount' => 790,
        'difference_amount' => -10,
        'cash_sales_total' => 140,
        'manual_income_total' => 200,
        'manual_expense_total' => 50,
    ]);

    Sale::factory()->for($branch)->for($user)->create([
        'work_session_id' => $session->id,
        'payment_method' => PaymentMethod::Cash,
        'status' => SaleStatus::Completed,
        'sold_at' => today()->setTime(12, 30),
        'subtotal' => 30,
        'total' => 30,
    ]);

    $this->postJson('/api/v1/cash-movements', [
        'user_id' => $user->id,
        'type' => CashMovementType::CashIn->value,
        'amount' => 20,
        'concept' => 'Más monedas',
        'occurred_at' => today()->setTime(12, 40)->toIso8601String(),
    ])->assertCreated();

    $this->postJson('/api/v1/cash-register-cuts', [
        'user_id' => $user->id,
        'counted_cash_amount' => 830,
        'cut_at' => today()->setTime(13, 0)->toIso8601String(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.opening_cash_amount', '780.00')
        ->assertJsonPath('data.cash_sales_total', '30.00')
        ->assertJsonPath('data.manual_income_total', '20.00')
        ->assertJsonPath('data.expected_cash_amount', '830.00')
        ->assertJsonPath('data.difference_amount', '0.00');
});
