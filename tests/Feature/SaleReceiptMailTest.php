<?php

use App\Enums\PaymentMethod;
use App\Mail\SaleReceipt;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Illuminate\Support\Facades\Mail;

test('sale service queues a receipt email with sale details for customers with email', function () {
    Mail::fake();

    $branch = Branch::factory()->create(['name' => 'Sucursal Centro']);
    $user = User::factory()->assignedToBranch($branch)->create(['name' => 'Ana López']);
    $customer = Customer::factory()->create([
        'name' => 'María Gómez',
        'email' => 'maria@example.com',
        'reward_balance' => 120,
        'annual_drink_count' => 44,
    ]);
    $product = Product::factory()->create([
        'name' => 'Pan de canela',
        'unit_type' => 'piece',
        'base_price' => 35,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    $sale = app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 2,
        ]],
    ], $user, $workSession);

    Mail::assertQueued(SaleReceipt::class, function (SaleReceipt $mail) use ($sale, $customer) {
        return $mail->hasTo($customer->email)
            && $mail->sale->is($sale)
            && $mail->sale->customer?->email === $customer->email;
    });

    $html = (new SaleReceipt($sale->fresh(['branch', 'user', 'customer', 'items'])))->render();

    expect($html)
        ->toContain('logotipo.png')
        ->toContain('Sucursal')
        ->toContain('Nivel')
        ->toContain('Saldo disponible')
        ->toContain('Pan de canela')
        ->toContain('Sucursal Centro');
});

test('sale service does not queue a receipt email when the customer has no email', function () {
    Mail::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'email' => null,
    ]);
    $product = Product::factory()->create([
        'name' => 'Galleta',
        'unit_type' => 'piece',
        'base_price' => 20,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [[
            'product_id' => $product->id,
            'quantity' => 1,
        ]],
    ], $user, $workSession);

    Mail::assertNothingQueued();
});
