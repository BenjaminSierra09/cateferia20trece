<?php

use App\Enums\PaymentMethod;
use App\Mail\InvoiceRequestMail;
use App\Models\Sale;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config()->set('services.invoicing.email', 'facturacion@example.com');
    config()->set('services.evolution.api_key', null);
});

test('the invoice request page renders with the form and SAT regime catalog', function () {
    Sale::factory()->create([
        'billing_token' => 'MfiYIvI',
        'payment_method' => PaymentMethod::Card,
    ]);

    $this->get(route('public.invoice', ['token' => 'MfiYIvI']))
        ->assertOk()
        ->assertSee('Solicita tu factura')
        ->assertSee('Régimen fiscal')
        ->assertSee('626 - Régimen Simplificado de Confianza')
        ->assertSee('name="billing_token"', false)
        ->assertSee('value="MfiYIvI"', false)
        ->assertSee('Método registrado en venta: Tarjeta')
        ->assertSee('name="invoice_payment_method"', false)
        ->assertSee('04 - Tarjeta de crédito')
        ->assertSee('28 - Tarjeta de débito')
        ->assertSee('03 - SPEI / Transferencia electrónica de fondos')
        ->assertSee('02 - Cheque nominativo')
        ->assertSee('05 - Monedero electrónico')
        ->assertSee('29 - Tarjeta de servicios')
        ->assertSee('name="rfc"', false)
        ->assertSee(route('public.invoice.store'), false);
});

test('sales get a seven-letter billing token when created', function () {
    $sale = Sale::factory()->create();

    expect($sale->billing_token)
        ->toMatch('/^[A-Za-z]{7}$/')
        ->and(Sale::query()->where('billing_token', $sale->billing_token)->exists())->toBeTrue();
});

test('mixed payments are summarized for invoicing', function () {
    $sale = Sale::factory()->create([
        'payment_method' => PaymentMethod::Mixed,
        'payment_breakdown' => [
            'cash' => 50,
            'card' => 54.82,
            'transfer' => 0,
        ],
    ]);

    expect($sale->paymentMethodSummary())->toBe('Mixto (Efectivo $50.00, Tarjeta $54.82)');
});

test('sales can suggest invoice payment forms when the sale method is unambiguous', function () {
    expect(Sale::factory()->make(['payment_method' => PaymentMethod::Cash])->suggestedInvoicePaymentForm())->toBe('01')
        ->and(Sale::factory()->make(['payment_method' => PaymentMethod::Transfer])->suggestedInvoicePaymentForm())->toBe('03')
        ->and(Sale::factory()->make(['payment_method' => PaymentMethod::Card])->suggestedInvoicePaymentForm())->toBeNull();
});

test('a valid invoice request is sent to accounting over WhatsApp and email', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake([
        'evolution.benjaminsierra.com/message/sendText/CAFETERIA20TRECE' => Http::response(['ok' => true]),
    ]);
    config()->set('services.evolution.api_key', 'fake-api-key');
    config()->set('services.invoicing.whatsapp', '+524181878244');

    $sale = Sale::factory()->create([
        'billing_token' => 'MfiYIvI',
        'payment_method' => PaymentMethod::Card,
        'sold_at' => now()->setDate(2026, 6, 1)->setTime(9, 30),
        'total' => 104.82,
    ]);

    $response = $this->post(route('public.invoice.store'), [
        'billing_token' => $sale->billing_token,
        'rfc' => 'sirb960209272',
        'razon_social' => 'Jose Benjamin Sierra Rangel',
        'regimen_fiscal' => '626',
        'codigo_postal' => '37700',
        'email' => 'cliente@example.com',
        'telefono' => '+524151234567',
        'invoice_payment_method' => '04',
        'website' => '',
    ]);

    $response->assertRedirect(route('public.invoice'));
    $response->assertSessionHas('invoice_status');

    Mail::assertSent(InvoiceRequestMail::class, function (InvoiceRequestMail $mail) {
        return $mail->hasTo('facturacion@example.com')
            && $mail->rfc === 'SIRB960209272' // normalized to uppercase
            && $mail->regimenFiscal === '626'
            && $mail->billingToken === 'MfiYIvI'
            && $mail->paymentMethod === 'Tarjeta'
            && $mail->invoicePaymentMethod === '04 - Tarjeta de crédito';
    });

    Http::assertSent(fn (Request $request): bool => $request['number'] === '524181878244'
        && str_contains($request['text'], 'Codigo de facturacion: MfiYIvI')
        && str_contains($request['text'], 'Total: $104.82')
        && str_contains($request['text'], 'Metodo registrado en venta: Tarjeta')
        && str_contains($request['text'], 'Metodo de pago para CFDI: 04 - Tarjeta de crédito')
        && str_contains($request['text'], 'RFC: SIRB960209272'));
});

test('an invoice request rejects an invalid RFC and postal code', function () {
    Mail::fake();
    $sale = Sale::factory()->create(['billing_token' => 'MfiYIvI']);

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'billing_token' => $sale->billing_token,
            'rfc' => 'NOPE',
            'razon_social' => 'Cliente',
            'regimen_fiscal' => '626',
            'codigo_postal' => '377',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
            'invoice_payment_method' => '01',
        ])
        ->assertRedirect(route('public.invoice'))
        ->assertSessionHasErrors(['rfc', 'codigo_postal']);

    Mail::assertNothingSent();
});

test('an invoice request rejects an unknown fiscal regime', function () {
    Mail::fake();
    $sale = Sale::factory()->create(['billing_token' => 'MfiYIvI']);

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'billing_token' => $sale->billing_token,
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '999',
            'codigo_postal' => '37700',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
            'invoice_payment_method' => '01',
        ])
        ->assertSessionHasErrors('regimen_fiscal');

    Mail::assertNothingSent();
});

test('an invoice request with the honeypot filled is rejected', function () {
    Mail::fake();
    $sale = Sale::factory()->create(['billing_token' => 'MfiYIvI']);

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'billing_token' => $sale->billing_token,
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '616',
            'codigo_postal' => '37700',
            'email' => 'spam@example.com',
            'telefono' => '+524151234567',
            'invoice_payment_method' => '01',
            'website' => 'http://spam.example',
        ])
        ->assertSessionHasErrors('website');

    Mail::assertNothingSent();
});

test('an invoice request rejects an unknown billing token', function () {
    Mail::fake();

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'billing_token' => 'MfiYIvI',
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '616',
            'codigo_postal' => '37700',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
            'invoice_payment_method' => '01',
        ])
        ->assertSessionHasErrors('billing_token');

    Mail::assertNothingSent();
});

test('an invoice request rejects an unknown invoice payment method', function () {
    Mail::fake();
    $sale = Sale::factory()->create(['billing_token' => 'MfiYIvI']);

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'billing_token' => $sale->billing_token,
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '616',
            'codigo_postal' => '37700',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
            'invoice_payment_method' => '88',
        ])
        ->assertSessionHasErrors('invoice_payment_method');

    Mail::assertNothingSent();
});
