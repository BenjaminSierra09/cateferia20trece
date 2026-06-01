<?php

use App\Jobs\HandleIncomingWhatsAppMessage;
use Illuminate\Support\Facades\Queue;

/**
 * Build a minimal Evolution `messages.upsert` webhook payload.
 *
 * @param  array<string, mixed>  $message
 */
function evolutionUpsertPayload(array $message): array
{
    return [
        'event' => 'messages.upsert',
        'instance' => 'CAFETERIA20TRECE',
        'data' => $message,
    ];
}

beforeEach(function () {
    Queue::fake();
});

it('dispatches a job for an inbound text message', function () {
    $this->postJson(route('api.whatsapp.webhook'), evolutionUpsertPayload([
        'key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'fromMe' => false, 'id' => 'ABC123'],
        'pushName' => 'Juan',
        'message' => ['conversation' => 'Hola, ¿cuál es mi saldo?'],
    ]))->assertNoContent();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, function (HandleIncomingWhatsAppMessage $job): bool {
        return $job->phone === '5214181878244'
            && $job->text === 'Hola, ¿cuál es mi saldo?'
            && $job->pushName === 'Juan'
            && $job->messageId === 'ABC123';
    });
});

it('reads text from extendedTextMessage payloads', function () {
    $this->postJson(route('api.whatsapp.webhook'), evolutionUpsertPayload([
        'key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'fromMe' => false, 'id' => 'X1'],
        'message' => ['extendedTextMessage' => ['text' => 'Quiero un latte']],
    ]))->assertNoContent();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, fn (HandleIncomingWhatsAppMessage $job): bool => $job->text === 'Quiero un latte');
});

it('ignores messages sent by the bot itself', function () {
    $this->postJson(route('api.whatsapp.webhook'), evolutionUpsertPayload([
        'key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'fromMe' => true, 'id' => 'SELF'],
        'message' => ['conversation' => 'respuesta del bot'],
    ]))->assertNoContent();

    Queue::assertNothingPushed();
});

it('ignores group messages', function () {
    $this->postJson(route('api.whatsapp.webhook'), evolutionUpsertPayload([
        'key' => ['remoteJid' => '123456789-987654@g.us', 'fromMe' => false, 'id' => 'G1'],
        'message' => ['conversation' => 'hola grupo'],
    ]))->assertNoContent();

    Queue::assertNothingPushed();
});

it('ignores non-text messages', function () {
    $this->postJson(route('api.whatsapp.webhook'), evolutionUpsertPayload([
        'key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'fromMe' => false, 'id' => 'IMG'],
        'message' => ['imageMessage' => ['url' => 'https://example.test/x.jpg']],
    ]))->assertNoContent();

    Queue::assertNothingPushed();
});

it('ignores events that are not message upserts', function () {
    $this->postJson(route('api.whatsapp.webhook'), [
        'event' => 'messages.update',
        'data' => ['key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'id' => 'U1']],
    ])->assertNoContent();

    Queue::assertNothingPushed();
});

it('rejects requests without the configured webhook token', function () {
    config()->set('services.evolution.webhook_token', 'super-secret');

    $payload = evolutionUpsertPayload([
        'key' => ['remoteJid' => '5214181878244@s.whatsapp.net', 'fromMe' => false, 'id' => 'T1'],
        'message' => ['conversation' => 'hola'],
    ]);

    $this->postJson(route('api.whatsapp.webhook'), $payload)->assertStatus(401);
    Queue::assertNothingPushed();

    $this->withHeaders(['X-Webhook-Token' => 'super-secret'])
        ->postJson(route('api.whatsapp.webhook'), $payload)
        ->assertNoContent();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class);
});
