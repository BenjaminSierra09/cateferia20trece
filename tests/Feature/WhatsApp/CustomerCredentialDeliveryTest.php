<?php

use App\Models\Customer;
use App\Services\CustomerCardRenderer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function configureWhatsAppCloudForCredentialTests(): void
{
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-key');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
    config()->set('services.whatsapp.templates.language', 'es_MX');
    config()->set('services.whatsapp.templates.customer_credential', 'customer_credential');
}

function fakeWhatsAppCloudForCredentialTests(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/media' => Http::response(['id' => 'MEDIA-ID']),
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.credential']],
        ]),
    ]);
}

beforeEach(function () {
    // Avoid depending on Imagick/system fonts when rendering the credential PNG.
    $this->mock(CustomerCardRenderer::class)
        ->shouldReceive('pngBase64')
        ->andReturn(base64_encode('fake-png'));
});

it('sends the credential to a Mexican number in E.164 format without the legacy mobile prefix', function () {
    configureWhatsAppCloudForCredentialTests();
    fakeWhatsAppCloudForCredentialTests();

    Customer::factory()->create(['phone' => '527298190594']);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/messages')
        && $request['to'] === '527298190594');
});

it('prefixes a bare national number with the Mexico country code', function () {
    configureWhatsAppCloudForCredentialTests();
    fakeWhatsAppCloudForCredentialTests();

    Customer::factory()->create(['phone' => '729 819 0594']);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/messages')
        && $request['to'] === '527298190594');
});

it('removes the legacy Mexican mobile prefix from stored numbers', function () {
    configureWhatsAppCloudForCredentialTests();
    fakeWhatsAppCloudForCredentialTests();

    Customer::factory()->create(['phone' => '+52 1 999 000 1122']);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/messages')
        && $request['to'] === '529990001122');
});
