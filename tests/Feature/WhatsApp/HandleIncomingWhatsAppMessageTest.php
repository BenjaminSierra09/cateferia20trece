<?php

use App\Ai\Agents\WhatsAppConcierge;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\Customer;
use App\Models\WhatsAppConversation;
use App\Services\EvolutionWhatsAppService;
use App\Support\CustomerPhoneMatcher;
use Illuminate\Support\Facades\Http;

function configureEvolutionForTests(): void
{
    config()->set('services.evolution.api_key', 'test-key');
    config()->set('services.evolution.instance_id', 'TESTINSTANCE');
    config()->set('services.evolution.api_url', 'https://evolution.test');
}

function runWhatsAppConciergeJob(string $phone, string $text, ?string $pushName = null, ?string $messageId = null): void
{
    (new HandleIncomingWhatsAppMessage($phone, $text, $pushName, $messageId))
        ->handle(app(CustomerPhoneMatcher::class), app(EvolutionWhatsAppService::class));
}

it('replies to an unregistered number with the registration link and does not use the AI', function () {
    WhatsAppConcierge::fake();
    configureEvolutionForTests();
    Http::fake();

    runWhatsAppConciergeJob('5219990001122', 'Hola', 'Desconocido', 'MID-1');

    WhatsAppConcierge::assertNeverPrompted();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/message/sendText/')
        && str_contains((string) $request['text'], route('public.register')));

    $conversation = WhatsAppConversation::query()->firstWhere('phone', '5219990001122');
    expect($conversation)->not->toBeNull()
        ->and($conversation->customer_id)->toBeNull();
});

it('answers a registered customer through the concierge and remembers the conversation', function () {
    $customer = Customer::factory()->create(['name' => 'Juan', 'phone' => '+524181878244']);

    WhatsAppConcierge::fake(['¡Hola Juan! Con gusto te ayudo.']);
    configureEvolutionForTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Hola, ¿cuál es mi saldo?', 'Juan', 'MID-2');

    WhatsAppConcierge::assertPrompted('Hola, ¿cuál es mi saldo?');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/message/sendText/')
        && str_contains((string) $request['text'], '¡Hola Juan!'));

    $conversation = WhatsAppConversation::query()->firstWhere('phone', '5214181878244');
    expect($conversation->customer_id)->toBe($customer->id)
        ->and($conversation->conversation_id)->not->toBeNull();
});

it('continues the same conversation across multiple messages', function () {
    Customer::factory()->create(['phone' => '+524181878244']);

    WhatsAppConcierge::fake(['Respuesta uno', 'Respuesta dos']);
    configureEvolutionForTests();
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
    configureEvolutionForTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Hola', null, 'DUP-1');
    runWhatsAppConciergeJob('5214181878244', 'Hola', null, 'DUP-1');

    // The second (duplicate) delivery is skipped, so only one reply is sent.
    Http::assertSentCount(1);
});
