<?php

use App\Models\Customer;
use App\Services\CustomerCardRenderer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function configureEvolutionForCredentialTests(): void
{
    config()->set('services.evolution.api_key', 'test-key');
    config()->set('services.evolution.instance_id', 'TESTINSTANCE');
    config()->set('services.evolution.api_url', 'https://evolution.test');
}

beforeEach(function () {
    // Avoid depending on Imagick/system fonts when rendering the credential PNG.
    $this->mock(CustomerCardRenderer::class)
        ->shouldReceive('pngBase64')
        ->andReturn(base64_encode('fake-png'));
});

it('sends the credential to the Mexican WhatsApp number (52 + 10 digits) as 521', function () {
    configureEvolutionForCredentialTests();
    Http::fake();

    Customer::factory()->create(['phone' => '527298190594']);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/message/sendMedia/')
        && $request['number'] === '5217298190594');
});

it('prefixes a bare national number (10 digits) with 521', function () {
    configureEvolutionForCredentialTests();
    Http::fake();

    Customer::factory()->create(['phone' => '729 819 0594']);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/message/sendMedia/')
        && $request['number'] === '5217298190594');
});

it('leaves an already WhatsApp-formatted number (521 + 10 digits) untouched', function () {
    configureEvolutionForCredentialTests();
    Http::fake();

    Customer::factory()->create(['phone' => '+52 1 999 000 1122']);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/message/sendMedia/')
        && $request['number'] === '5219990001122');
});
