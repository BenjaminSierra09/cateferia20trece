<?php

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use Laravel\Sanctum\Sanctum;

test('accounting users can access the dashboard', function () {
    $user = User::factory()->accounting()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Vista limitada por permisos');
});

test('accounting users cannot access cash register api resources', function () {
    $user = User::factory()->accounting()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/cash-movements')->assertForbidden();
    $this->getJson('/api/v1/cash-register-cuts')->assertForbidden();
});

test('accounting reports exclude cash-sensitive sales and declare the limitation', function () {
    $accounting = User::factory()->accounting()->create();
    $cashier = User::factory()->create();
    $branch = Branch::factory()->create();

    Sale::factory()->for($branch)->for($cashier)->create([
        'payment_method' => PaymentMethod::Cash,
        'status' => SaleStatus::Completed,
        'subtotal' => 100,
        'total' => 100,
    ]);

    Sale::factory()->for($branch)->for($cashier)->create([
        'payment_method' => PaymentMethod::Mixed,
        'status' => SaleStatus::Completed,
        'subtotal' => 120,
        'total' => 120,
    ]);

    Sale::factory()->for($branch)->for($cashier)->create([
        'payment_method' => PaymentMethod::Card,
        'status' => SaleStatus::Completed,
        'subtotal' => 80,
        'total' => 80,
    ]);

    $overview = app(ReportService::class)->overview([], $accounting);

    expect($overview['gross_revenue'])->toBe(80.0)
        ->and($overview['sales_count'])->toBe(1)
        ->and($overview['limited_by_permissions'])->toBeTrue()
        ->and(collect($overview['sales_by_payment_method'])->pluck('payment_method')->all())
        ->toBe(['Tarjeta']);
});

test('accounting users cannot view cash sale details', function () {
    $accounting = User::factory()->accounting()->create();
    $cashier = User::factory()->create();
    $branch = Branch::factory()->create();

    $sale = Sale::factory()->for($branch)->for($cashier)->create([
        'payment_method' => PaymentMethod::Cash,
        'status' => SaleStatus::Completed,
    ]);

    $this->actingAs($accounting)
        ->get(route('dashboard.sales.show', $sale))
        ->assertForbidden();
});
