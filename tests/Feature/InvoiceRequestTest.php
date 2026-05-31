<?php

use App\Mail\InvoiceRequestMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config()->set('services.invoicing.email', 'facturacion@example.com');
});

test('the invoice request page renders with the form and SAT regime catalog', function () {
    $this->get(route('public.invoice'))
        ->assertOk()
        ->assertSee('Solicita tu factura')
        ->assertSee('Régimen fiscal')
        ->assertSee('626 - Régimen Simplificado de Confianza')
        ->assertSee('name="numero_venta"', false)
        ->assertSee('name="rfc"', false)
        ->assertSee(route('public.invoice.store'), false);
});

test('a valid invoice request is emailed with all fiscal data', function () {
    Mail::fake();

    $response = $this->post(route('public.invoice.store'), [
        'numero_venta' => '10482',
        'rfc' => 'sirb960209272',
        'razon_social' => 'Jose Benjamin Sierra Rangel',
        'regimen_fiscal' => '626',
        'codigo_postal' => '37700',
        'email' => 'cliente@example.com',
        'telefono' => '+524151234567',
        'website' => '',
    ]);

    $response->assertRedirect(route('public.invoice'));
    $response->assertSessionHas('invoice_status');

    Mail::assertSent(InvoiceRequestMail::class, function (InvoiceRequestMail $mail) {
        return $mail->hasTo('facturacion@example.com')
            && $mail->rfc === 'SIRB960209272' // normalized to uppercase
            && $mail->regimenFiscal === '626'
            && $mail->numeroVenta === '10482';
    });
});

test('an invoice request rejects an invalid RFC and postal code', function () {
    Mail::fake();

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'numero_venta' => '10482',
            'rfc' => 'NOPE',
            'razon_social' => 'Cliente',
            'regimen_fiscal' => '626',
            'codigo_postal' => '377',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
        ])
        ->assertRedirect(route('public.invoice'))
        ->assertSessionHasErrors(['rfc', 'codigo_postal']);

    Mail::assertNothingSent();
});

test('an invoice request rejects an unknown fiscal regime', function () {
    Mail::fake();

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'numero_venta' => '10482',
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '999',
            'codigo_postal' => '37700',
            'email' => 'cliente@example.com',
            'telefono' => '+524151234567',
        ])
        ->assertSessionHasErrors('regimen_fiscal');

    Mail::assertNothingSent();
});

test('an invoice request with the honeypot filled is rejected', function () {
    Mail::fake();

    $this->from(route('public.invoice'))
        ->post(route('public.invoice.store'), [
            'numero_venta' => '10482',
            'rfc' => 'XAXX010101000',
            'razon_social' => 'Publico General',
            'regimen_fiscal' => '616',
            'codigo_postal' => '37700',
            'email' => 'spam@example.com',
            'telefono' => '+524151234567',
            'website' => 'http://spam.example',
        ])
        ->assertSessionHasErrors('website');

    Mail::assertNothingSent();
});
