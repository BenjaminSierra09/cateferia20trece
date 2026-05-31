<?php

use App\Mail\ArcoRequestMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config()->set('services.privacy.email', 'privacidad@example.com');
});

test('the privacy page renders the LFPDPPP notice, cookies section and ARCO form', function () {
    $this->get(route('public.privacy'))
        ->assertOk()
        ->assertSee('Aviso de privacidad')
        ->assertSee('Aviso de Privacidad Integral')
        ->assertSee('José Benjamín Sierra Rangel')
        ->assertSee('Solicitud de derechos ARCO')
        ->assertSee('Aviso de cookies')
        ->assertSee('id="arco"', false)
        ->assertSee('name="derechos[]"', false)
        ->assertSee(route('public.arco.store'), false);
});

test('a valid ARCO request is emailed to the data controller and confirmed', function () {
    Mail::fake();

    $response = $this->post(route('public.arco.store'), [
        'nombre' => 'María López',
        'email' => 'maria@example.com',
        'telefono' => '+524151234567',
        'derechos' => ['cancelacion', 'acceso'],
        'cuenta_identificador' => 'maria@example.com',
        'detalle' => 'Solicito que eliminen por completo mi cuenta y mis datos personales.',
        'identidad_consent' => '1',
        'website' => '',
    ]);

    $response->assertRedirect(route('public.privacy').'#arco');
    $response->assertSessionHas('arco_status');

    Mail::assertSent(ArcoRequestMail::class, function (ArcoRequestMail $mail) {
        return $mail->hasTo('privacidad@example.com')
            && $mail->nombre === 'María López'
            && in_array('cancelacion', $mail->derechos, true);
    });
});

test('an ARCO request requires at least one right and the identity declaration', function () {
    Mail::fake();

    $this->from(route('public.privacy'))
        ->post(route('public.arco.store'), [
            'nombre' => 'María López',
            'email' => 'maria@example.com',
            'detalle' => 'Quiero ejercer mis derechos.',
            // no derechos, no identidad_consent
        ])
        ->assertRedirect(route('public.privacy'))
        ->assertSessionHasErrors(['derechos', 'identidad_consent']);

    Mail::assertNothingSent();
});

test('an ARCO request with the honeypot filled is rejected', function () {
    Mail::fake();

    $this->from(route('public.privacy'))
        ->post(route('public.arco.store'), [
            'nombre' => 'Spam Bot',
            'email' => 'spam@example.com',
            'derechos' => ['acceso'],
            'detalle' => 'Mensaje automatizado de prueba.',
            'identidad_consent' => '1',
            'website' => 'http://spam.example',
        ])
        ->assertSessionHasErrors('website');

    Mail::assertNothingSent();
});
