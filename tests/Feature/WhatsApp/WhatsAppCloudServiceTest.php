<?php

use App\Contracts\WhatsAppService;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Services\CustomerCardRenderer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-access-token');
    config()->set('services.whatsapp.phone_number_id', '123456789');
    config()->set('services.whatsapp.templates.language', 'es_MX');
    config()->set('services.whatsapp.templates.customer_credential', 'customer_credential');
});

it('sends free-form text through the official Graph API', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/123456789/messages' => Http::response([
            'messages' => [['id' => 'wamid.123']],
        ]),
    ]);

    app(WhatsAppService::class)->sendMessage('+52 1 418 187 8244', 'Hola desde Café 20Trece');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://graph.facebook.test/v23.0/123456789/messages'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request['messaging_product'] === 'whatsapp'
            && $request['recipient_type'] === 'individual'
            && $request['to'] === '524181878244'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Hola desde Café 20Trece'
            && $request['text']['preview_url'] === true;
    });
});

it('uploads the credential image and sends an approved template', function () {
    $customer = Customer::factory()->make([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
    ]);
    $qrCode = CustomerQrCode::factory()->make([
        'customer_id' => 1,
        'uuid' => 'a4d645e5-9172-4aca-925d-b52fb82a55ad',
    ]);

    $this->mock(CustomerCardRenderer::class)
        ->shouldReceive('pngBase64')
        ->once()
        ->andReturn(base64_encode('fake-png'));

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/123456789/media' => Http::response(['id' => 'MEDIA-123']),
        'https://graph.facebook.test/v23.0/123456789/messages' => Http::response([
            'messages' => [['id' => 'wamid.credential']],
        ]),
    ]);

    app(WhatsAppService::class)->sendCustomerCredential($customer, $qrCode);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://graph.facebook.test/v23.0/123456789/media'
        && str_contains($request->body(), 'messaging_product')
        && str_contains($request->body(), 'whatsapp')
        && str_contains($request->body(), 'image/png'));
    Http::assertSent(function (Request $request) use ($customer, $qrCode): bool {
        if ($request->url() !== 'https://graph.facebook.test/v23.0/123456789/messages') {
            return false;
        }

        $parameters = $request['template']['components'][1]['parameters'];

        return $request['to'] === '524151234567'
            && $request['type'] === 'template'
            && $request['template']['name'] === 'customer_credential'
            && $request['template']['language']['code'] === 'es_MX'
            && $request['template']['components'][0]['parameters'][0]['image']['id'] === 'MEDIA-123'
            && $parameters[0]['text'] === $customer->name
            && $parameters[1]['text'] === route('public.rewards')
            && $parameters[2]['text'] === route('public.qr.show', ['uuid' => $qrCode->uuid]);
    });
});

it('does not make requests when the Cloud API is not configured', function () {
    config()->set('services.whatsapp.access_token');

    Http::preventStrayRequests();
    Http::fake();

    app(WhatsAppService::class)->sendMessage('+524181878244', 'Hola');

    Http::assertNothingSent();
});

it('exposes Graph API error context without leaking credentials', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/*' => Http::response([
            'error' => [
                'message' => 'Invalid OAuth access token',
                'code' => 190,
            ],
        ], 401),
    ]);

    try {
        app(WhatsAppService::class)->sendMessage('+524181878244', 'Hola');
    } catch (WhatsAppCloudApiException $exception) {
        expect($exception->getMessage())->toBe('No fue posible enviar la respuesta de WhatsApp.')
            ->and($exception->context())->toBe([
                'operation' => 'send_text',
                'status' => 401,
                'graph_code' => 190,
            ]);

        return;
    }

    $this->fail('Expected a WhatsAppCloudApiException to be thrown.');
});
