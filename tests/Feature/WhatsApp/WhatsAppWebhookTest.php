<?php

use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageReaction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * @param  array<int, array<string, mixed>>  $messages
 */
function whatsAppCloudWebhookPayload(array $messages): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => 'WHATSAPP-BUSINESS-ACCOUNT-ID',
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '524151234567',
                                'phone_number_id' => 'PHONE-NUMBER-ID',
                            ],
                            'contacts' => [
                                [
                                    'profile' => ['name' => 'Juan'],
                                    'wa_id' => '524181878244',
                                ],
                            ],
                            'messages' => $messages,
                        ],
                    ],
                ],
            ],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $payload
 */
function postSignedWhatsAppWebhook(TestCase $testCase, array $payload, string $secret = 'test-app-secret'): TestResponse
{
    $json = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = 'sha256='.hash_hmac('sha256', $json, $secret);

    return $testCase
        ->call(
            method: 'POST',
            uri: route('api.whatsapp.webhook'),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            content: $json,
        );
}

beforeEach(function () {
    Queue::fake();
    config()->set('services.whatsapp.webhook_verify_token', 'test-verify-token');
    config()->set('services.whatsapp.app_secret', 'test-app-secret');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-NUMBER-ID');
});

it('answers the Meta webhook verification challenge', function () {
    $query = http_build_query([
        'hub.mode' => 'subscribe',
        'hub.verify_token' => 'test-verify-token',
        'hub.challenge' => 'CHALLENGE-123',
    ]);

    $this->get(route('api.whatsapp.webhook').'?'.$query)
        ->assertOk()
        ->assertContent('CHALLENGE-123');
});

it('rejects a webhook verification request with the wrong token', function () {
    $query = http_build_query([
        'hub.mode' => 'subscribe',
        'hub.verify_token' => 'wrong-token',
        'hub.challenge' => 'CHALLENGE-123',
    ]);

    $this->get(route('api.whatsapp.webhook').'?'.$query)
        ->assertForbidden();
});

it('dispatches a job for an inbound text message', function () {
    postSignedWhatsAppWebhook($this, whatsAppCloudWebhookPayload([
        [
            'from' => '524181878244',
            'id' => 'wamid.ABC123',
            'timestamp' => '1758254144',
            'text' => ['body' => 'Hola, ¿cuál es mi saldo?'],
            'type' => 'text',
        ],
    ]))->assertOk()->assertContent('EVENT_RECEIVED');

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, function (HandleIncomingWhatsAppMessage $job): bool {
        return $job->phone === '524181878244'
            && $job->text === 'Hola, ¿cuál es mi saldo?'
            && $job->pushName === 'Juan'
            && $job->messageId === 'wamid.ABC123'
            && $job->messageType === 'text'
            && $job->timestamp === 1758254144;
    });
});

it('dispatches all text messages from a batched webhook', function () {
    postSignedWhatsAppWebhook($this, whatsAppCloudWebhookPayload([
        [
            'from' => '524181878244',
            'id' => 'wamid.X1',
            'text' => ['body' => 'Quiero un latte'],
            'type' => 'text',
        ],
        [
            'from' => '524181878244',
            'id' => 'wamid.X2',
            'text' => ['body' => 'Y un espresso'],
            'type' => 'text',
        ],
    ]))->assertOk();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, fn (HandleIncomingWhatsAppMessage $job): bool => $job->text === 'Quiero un latte');
    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, fn (HandleIncomingWhatsAppMessage $job): bool => $job->text === 'Y un espresso');
    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, 2);
});

it('dispatches non-text messages so they remain visible in the inbox', function () {
    postSignedWhatsAppWebhook($this, whatsAppCloudWebhookPayload([
        [
            'from' => '524181878244',
            'id' => 'wamid.IMG',
            'image' => ['id' => 'MEDIA-ID', 'mime_type' => 'image/jpeg'],
            'type' => 'image',
        ],
    ]))->assertOk();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, function (HandleIncomingWhatsAppMessage $job): bool {
        return $job->messageId === 'wamid.IMG'
            && $job->messageType === 'image'
            && $job->text === '[Imagen]';
    });
});

it('dispatches a reaction with its target message id', function () {
    postSignedWhatsAppWebhook($this, whatsAppCloudWebhookPayload([
        [
            'from' => '524181878244',
            'id' => 'wamid.REACTION',
            'reaction' => [
                'message_id' => 'wamid.TARGET',
                'emoji' => '👍',
            ],
            'type' => 'reaction',
        ],
    ]))->assertOk();

    Queue::assertPushed(HandleIncomingWhatsAppMessage::class, function (HandleIncomingWhatsAppMessage $job): bool {
        return $job->messageId === 'wamid.REACTION'
            && $job->messageType === 'reaction'
            && $job->text === '👍'
            && $job->reactionToMessageId === 'wamid.TARGET';
    });
});

it('updates outbound delivery statuses', function () {
    $conversation = WhatsAppConversation::factory()->create();
    $message = WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'provider_message_id' => 'wamid.STATUS',
        'direction' => WhatsAppMessageDirection::Outbound,
        'status' => WhatsAppMessageStatus::Sent,
    ]);

    $payload = whatsAppCloudWebhookPayload([]);
    $payload['entry'][0]['changes'][0]['value']['statuses'] = [
        [
            'id' => 'wamid.STATUS',
            'status' => 'delivered',
            'timestamp' => '1758254144',
            'recipient_id' => '524181878244',
        ],
    ];

    postSignedWhatsAppWebhook($this, $payload)->assertOk();

    Queue::assertNothingPushed();
    expect($message->refresh()->status)->toBe(WhatsAppMessageStatus::Delivered);
});

it('updates campaign recipient delivery tracking', function () {
    $campaign = WhatsAppCampaign::factory()->create();
    $message = WhatsAppMessage::factory()->create([
        'provider_message_id' => 'wamid.CAMPAIGN-STATUS',
        'direction' => WhatsAppMessageDirection::Outbound,
        'status' => WhatsAppMessageStatus::Sent,
    ]);
    $recipient = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'whatsapp_message_id' => $message->id,
        'provider_message_id' => 'wamid.CAMPAIGN-STATUS',
        'status' => WhatsAppCampaignRecipientStatus::Sent,
    ]);
    $payload = whatsAppCloudWebhookPayload([]);
    $payload['entry'][0]['changes'][0]['value']['statuses'] = [[
        'id' => 'wamid.CAMPAIGN-STATUS',
        'status' => 'read',
        'timestamp' => '1758254144',
    ]];

    postSignedWhatsAppWebhook($this, $payload)->assertOk();

    expect($recipient->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Read)
        ->and($recipient->read_at)->not->toBeNull()
        ->and($message->refresh()->status)->toBe(WhatsAppMessageStatus::Read);
});

it('updates outbound reaction delivery statuses', function () {
    $message = WhatsAppMessage::factory()->create();
    $reaction = WhatsAppMessageReaction::factory()->create([
        'whatsapp_message_id' => $message->id,
        'provider_message_id' => 'wamid.REACTION-STATUS',
        'status' => WhatsAppMessageStatus::Sent,
    ]);
    $payload = whatsAppCloudWebhookPayload([]);
    $payload['entry'][0]['changes'][0]['value']['statuses'] = [[
        'id' => 'wamid.REACTION-STATUS',
        'status' => 'delivered',
    ]];

    postSignedWhatsAppWebhook($this, $payload)->assertOk();

    expect($reaction->refresh()->status)->toBe(WhatsAppMessageStatus::Delivered);
});

it('rejects webhook payloads with an invalid signature', function () {
    $payload = whatsAppCloudWebhookPayload([
        [
            'from' => '524181878244',
            'id' => 'wamid.T1',
            'text' => ['body' => 'hola'],
            'type' => 'text',
        ],
    ]);

    postSignedWhatsAppWebhook($this, $payload, secret: 'wrong-app-secret')
        ->assertForbidden();

    Queue::assertNothingPushed();
});
