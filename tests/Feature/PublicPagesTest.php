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
use Illuminate\Support\Facades\Http;

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
        ->assertSee('Escanea tu tarjeta QR')
        ->assertSee('Escanear con cámara');

    $this->get(route('public.register'))
        ->assertOk()
        ->assertSee('Obtén tu QR de cliente')
        ->assertSee('Obtener mi QR')
        ->assertSee('for="privacy_consent"', false)
        ->assertSee('type="checkbox"', false)
        ->assertSee('id="phone_hidden"', false);
});

test('public customer registration creates a customer and redirects to the qr portal', function () {
    $response = $this->post(route('public.register.store'), [
        'name' => 'Cliente Público',
        'phone' => '+524151234567',
        'email' => 'cliente@example.com',
        'birthday' => '1994-03-21',
        'privacy_consent' => '1',
    ]);

    $customer = Customer::query()
        ->where('email', 'cliente@example.com')
        ->first();

    expect($customer)->not->toBeNull();
    expect($customer->phone)->toBe('+524151234567');

    $customer->load('qrCodes');

    expect($customer->qrCodes)->toHaveCount(1);

    $qrCode = $customer->qrCodes->first();

    $response->assertRedirect(route('public.qr.show', ['uuid' => $qrCode->uuid]));

    $this->followingRedirects()
        ->post(route('public.register.store'), [
            'name' => 'Cliente Público 2',
            'phone' => '+524151234568',
            'email' => 'cliente2@example.com',
            'privacy_consent' => '1',
        ])
        ->assertSee('Registro completado. Este es tu QR de cliente.')
        ->assertSee('Tu QR de cliente')
        ->assertSee('UUID:');
});

test('public customer registration requires a recaptcha token when recaptcha is enabled', function () {
    config()->set('services.recaptcha.site_key', 'site-key');
    config()->set('services.recaptcha.secret_key', 'secret-key');
    Http::fake();

    $response = $this->from(route('public.register'))
        ->post(route('public.register.store'), [
            'name' => 'Cliente Bloqueado',
            'email' => 'blocked@example.com',
            'privacy_consent' => '1',
        ]);

    $response->assertRedirect(route('public.register'));
    $response->assertSessionHasErrors('recaptcha');

    expect(Customer::query()->where('email', 'blocked@example.com')->exists())->toBeFalse();

    Http::assertNothingSent();
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
        ->assertSee('Matriz Centro')
        ->assertSee('Descargar tarjeta')
        ->assertSee('Tarjeta de cliente')
        ->assertSee('id="customer-card-image"', false)
        ->assertSee('data-download-name="tarjeta-cliente-', false);

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
