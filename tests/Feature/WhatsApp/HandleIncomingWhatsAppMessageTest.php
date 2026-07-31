<?php

use App\Ai\Agents\WhatsAppConcierge;
use App\Contracts\WhatsAppService;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\Customer;
use App\Models\WhatsAppConversation;
use App\Support\CustomerPhoneMatcher;
use Illuminate\Support\Facades\Http;

function configureWhatsAppCloudForConciergeTests(): void
{
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-key');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
}

function runWhatsAppConciergeJob(string $phone, string $text, ?string $pushName = null, ?string $messageId = null): void
{
    (new HandleIncomingWhatsAppMessage($phone, $text, $pushName, $messageId))
        ->handle(app(CustomerPhoneMatcher::class), app(WhatsAppService::class));
}

it('replies to an unregistered number with the registration link and does not use the AI', function () {
    WhatsAppConcierge::fake();
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    runWhatsAppConciergeJob('5219990001122', 'Hola', 'Desconocido', 'MID-1');

    WhatsAppConcierge::assertNeverPrompted();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages')
        && str_contains((string) $request['text']['body'], route('public.register')));

    $conversation = WhatsAppConversation::query()->firstWhere('phone', '5219990001122');
    expect($conversation)->not->toBeNull()
        ->and($conversation->customer_id)->toBeNull();
});

it('answers a registered customer through the concierge and remembers the conversation', function () {
    $customer = Customer::factory()->create(['name' => 'Juan', 'phone' => '+524181878244']);

    WhatsAppConcierge::fake(['¡Hola Juan! Con gusto te ayudo.']);
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Hola, ¿cuál es mi saldo?', 'Juan', 'MID-2');

    WhatsAppConcierge::assertPrompted('Hola, ¿cuál es mi saldo?');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages')
        && str_contains((string) $request['text']['body'], '¡Hola Juan!'));

    $conversation = WhatsAppConversation::query()->firstWhere('phone', '5214181878244');
    expect($conversation->customer_id)->toBe($customer->id)
        ->and($conversation->conversation_id)->not->toBeNull();
});

it('continues the same conversation across multiple messages', function () {
    Customer::factory()->create(['phone' => '+524181878244']);

    WhatsAppConcierge::fake(['Respuesta uno', 'Respuesta dos']);
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Primer mensaje', null, 'MID-A');
    $firstId = WhatsAppConversation::query()->firstWhere('phone', '5214181878244')->conversation_id;

    runWhatsAppConciergeJob('5214181878244', 'Segundo mensaje', null, 'MID-B');
    $secondId = WhatsAppConversation::query()->firstWhere('phone', '5214181878244')->conversation_id;

    expect($firstId)->not->toBeNull()
        ->and($secondId)->toBe($firstId);

    WhatsAppConcierge::assertPrompted('Primer mensaje');
    WhatsAppConcierge::assertPrompted('Segundo mensaje');
});

it('ignores duplicate webhook deliveries of the same message', function () {
    Customer::factory()->create(['phone' => '+524181878244']);

    WhatsAppConcierge::fake(['Respuesta']);
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Hola', null, 'DUP-1');
    runWhatsAppConciergeJob('5214181878244', 'Hola', null, 'DUP-1');

    // The second (duplicate) delivery is skipped, so only one reply is sent.
    Http::assertSentCount(1);
});
