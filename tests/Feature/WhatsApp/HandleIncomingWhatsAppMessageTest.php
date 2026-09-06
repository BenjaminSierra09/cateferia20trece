<?php

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Actions\WhatsApp\StoreIncomingWhatsAppReaction;
use App\Ai\Agents\WhatsAppConcierge;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\Customer;
use App\Models\WhatsAppConversation;
use App\Support\CustomerPhoneMatcher;
use Carbon\CarbonImmutable;
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
        ->handle(
            app(CustomerPhoneMatcher::class),
            app(SendWhatsAppTextMessage::class),
            app(StoreIncomingWhatsAppReaction::class),
            app(RecordWhatsAppMarketingConsent::class),
        );
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
        ->and($conversation->customer_id)->toBeNull()
        ->and($conversation->messages)->toHaveCount(2);
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
        ->and($conversation->conversation_id)->not->toBeNull()
        ->and($conversation->messages)->toHaveCount(2);
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
    expect(WhatsAppConversation::query()->firstWhere('phone', '5214181878244')->messages)->toHaveCount(2);
});

it('stores Meta timestamps in the application timezone', function () {
    WhatsAppConcierge::fake();
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    $timestamp = CarbonImmutable::parse('2026-08-20 21:49:00', 'UTC')->timestamp;
    $job = new HandleIncomingWhatsAppMessage(
        phone: '5219990001122',
        text: '¿Hoy hasta qué hora está abierto?',
        messageId: 'MID-TIMEZONE',
        timestamp: $timestamp,
    );

    $job->handle(
        app(CustomerPhoneMatcher::class),
        app(SendWhatsAppTextMessage::class),
        app(StoreIncomingWhatsAppReaction::class),
        app(RecordWhatsAppMarketingConsent::class),
    );

    $message = WhatsAppConversation::query()
        ->firstWhere('phone', '5219990001122')
        ->messages()
        ->where('provider_message_id', 'MID-TIMEZONE')
        ->firstOrFail();

    expect($message->sent_at->format('Y-m-d H:i:s'))->toBe('2026-08-20 15:49:00');
});

it('records inbound messages without replying while the bot is paused', function () {
    Customer::factory()->create(['phone' => '+524181878244']);
    WhatsAppConversation::factory()->create([
        'phone' => '5214181878244',
        'bot_paused_at' => now(),
    ]);

    WhatsAppConcierge::fake();
    configureWhatsAppCloudForConciergeTests();
    Http::fake();

    runWhatsAppConciergeJob('5214181878244', 'Necesito ayuda', null, 'MID-PAUSED');

    WhatsAppConcierge::assertNeverPrompted();
    Http::assertNothingSent();

    $this->assertDatabaseHas('whatsapp_messages', [
        'provider_message_id' => 'MID-PAUSED',
        'body' => 'Necesito ayuda',
    ]);
});

it('attaches inbound reactions to their target message', function () {
    $conversation = WhatsAppConversation::factory()->create(['phone' => '5214181878244']);
    $target = $conversation->messages()->create([
        'provider_message_id' => 'wamid.TARGET',
        'direction' => 'outbound',
        'type' => 'text',
        'body' => 'Hola',
        'status' => 'sent',
        'sent_at' => now(),
    ]);
    $job = new HandleIncomingWhatsAppMessage(
        phone: '5214181878244',
        text: '❤️',
        messageId: 'wamid.REACTION',
        messageType: 'reaction',
        reactionToMessageId: 'wamid.TARGET',
    );

    $job->handle(
        app(CustomerPhoneMatcher::class),
        app(SendWhatsAppTextMessage::class),
        app(StoreIncomingWhatsAppReaction::class),
        app(RecordWhatsAppMarketingConsent::class),
    );

    expect($conversation->messages()->count())->toBe(1)
        ->and($target->reactions()->firstOrFail()->emoji)->toBe('❤️');
});

it('revokes marketing consent when a customer replies BAJA', function () {
    $customer = Customer::factory()->withWhatsAppMarketingConsent('+524181878244')->create();

    WhatsAppConcierge::fake();
    configureWhatsAppCloudForConciergeTests();
    Http::fake([
        'https://graph.facebook.test/*' => Http::response([
            'messages' => [['id' => 'wamid.OPTOUT-CONFIRMATION']],
        ]),
    ]);

    runWhatsAppConciergeJob('5214181878244', 'BAJA', null, 'MID-OPTOUT');

    WhatsAppConcierge::assertNeverPrompted();
    expect($customer->refresh()->hasWhatsAppMarketingConsent())->toBeFalse()
        ->and($customer->whatsappMarketingConsents()->latest('id')->firstOrFail()->status)->toBe('revoked');
    Http::assertSent(fn ($request): bool => str_contains(
        (string) $request['text']['body'],
        'Ya no recibirás promociones',
    ));
});
