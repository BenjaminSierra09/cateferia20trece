<?php

use App\Enums\RewardTier;
use App\Enums\SaleStatus;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use Carbon\Carbon;

test('public policy and rewards pages are accessible', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Momentos y rincones de 20Trece')
        ->assertSee('Mi cuenta QR')
        ->assertSee('/gallery/coffe-shop-1.jpg', false);

    $this->get(route('public.terms'))
        ->assertOk()
        ->assertSee('Términos y condiciones');

    $this->get(route('public.privacy'))
        ->assertOk()
        ->assertSee('Aviso de privacidad');

    $this->get(route('public.rewards'))
        ->assertOk()
        ->assertSee('Programa de recompensas')
        ->assertSee('Plata')
        ->assertSee('Oro');

    $this->get(route('public.lookup'))
        ->assertOk()
        ->assertSee('Escanea tu QR o pega tu UUID')
        ->assertSee('Escanear con cámara');
});

test('public customer portal shows rewards, favorites and recent purchases from an active qr', function () {
    Carbon::setTestNow('2026-05-14 10:30:00');

    $customer = Customer::factory()->create([
        'name' => 'Benjamín Sierra',
        'reward_balance' => 124.96,
        'annual_drink_count' => 31,
        'reward_tier' => RewardTier::Silver,
    ]);

    $qrCode = CustomerQrCode::factory()->for($customer)->create([
        'uuid' => '4e9b7ac3-0cc0-4dd6-9ec2-3400dcd8af11',
        'last_scanned_at' => null,
        'is_active' => true,
    ]);

    $branch = Branch::factory()->create([
        'name' => 'Matriz Centro',
    ]);

    $size = Size::factory()->create([
        'name' => 'Grande',
        'capacity_label' => '16 oz',
        'capacity_ounces' => 16,
    ]);

    $favoriteBeverage = Beverage::factory()->create([
        'name' => 'Dirty Chai',
    ]);

    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'status' => SaleStatus::Completed,
        'sold_at' => now()->subHour(),
        'subtotal' => 95,
        'total' => 95,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'beverage_id' => $favoriteBeverage->id,
        'size_id' => $size->id,
        'item_name' => 'Dirty Chai',
        'quantity' => 2,
        'unit_price' => 47.5,
        'line_total' => 95,
    ]);

    $response = $this->get(route('public.qr.show', ['uuid' => $qrCode->uuid]));

    $response->assertOk()
        ->assertSee('Benjamín Sierra')
        ->assertSee('Saldo a favor')
        ->assertSee('Dirty Chai')
        ->assertSee('Compras recientes')
        ->assertSee('Matriz Centro');

    expect($qrCode->fresh()->last_scanned_at)->not->toBeNull();

    Carbon::setTestNow();
});

test('public customer portal returns not found for inactive qr codes', function () {
    $customer = Customer::factory()->create();
    $qrCode = CustomerQrCode::factory()->for($customer)->create([
        'is_active' => false,
    ]);

    $this->get(route('public.qr.show', ['uuid' => $qrCode->uuid]))
        ->assertNotFound();
});
