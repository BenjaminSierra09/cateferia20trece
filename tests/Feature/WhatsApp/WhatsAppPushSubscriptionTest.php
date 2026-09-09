<?php

use App\Actions\WhatsApp\RecordWhatsAppMarketingConsent;
use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Actions\WhatsApp\StoreIncomingWhatsAppAudio;
use App\Actions\WhatsApp\StoreIncomingWhatsAppReaction;
use App\Ai\Agents\WhatsAppConcierge;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\User;
use App\Notifications\IncomingWhatsAppMessageNotification;
use App\Support\CustomerPhoneMatcher;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-access-token');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
});

function validWhatsAppPushSubscription(string $endpoint = 'https://push.example.com/subscriptions/device-1'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => [
            'p256dh' => 'browser-public-key',
            'auth' => 'browser-auth-token',
        ],
        'content_encoding' => 'aes128gcm',
    ];
}

function handlePushNotificationTestMessage(string $messageId): void
{
    (new HandleIncomingWhatsAppMessage(
        phone: '5219990002233',
        text: 'Necesito ayuda con mi pedido',
        pushName: 'Ana Pérez',
        messageId: $messageId,
    ))->handle(
        app(CustomerPhoneMatcher::class),
        app(SendWhatsAppTextMessage::class),
        app(StoreIncomingWhatsAppReaction::class),
        app(RecordWhatsAppMarketingConsent::class),
        app(StoreIncomingWhatsAppAudio::class),
    );
}

it('stores and removes a push subscription for the authenticated administrator', function () {
    $admin = User::factory()->admin()->create();
    $payload = validWhatsAppPushSubscription();

    $this->actingAs($admin)
        ->postJson(route('whatsapp.pwa.push-subscription.store'), $payload)
        ->assertCreated()
        ->assertJson(['subscribed' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_type' => $admin->getMorphClass(),
        'subscribable_id' => $admin->id,
        'endpoint' => $payload['endpoint'],
        'public_key' => $payload['keys']['p256dh'],
        'auth_token' => $payload['keys']['auth'],
        'content_encoding' => 'aes128gcm',
    ]);

    $this->actingAs($admin)
        ->deleteJson(route('whatsapp.pwa.push-subscription.destroy'), ['endpoint' => $payload['endpoint']])
        ->assertNoContent();

    $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $payload['endpoint']]);
});

it('does not let a user remove another administrators subscription', function () {
    $owner = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $endpoint = 'https://push.example.com/subscriptions/owned-device';
    $owner->updatePushSubscription($endpoint, 'key', 'token', 'aes128gcm');

    $this->actingAs($otherAdmin)
        ->deleteJson(route('whatsapp.pwa.push-subscription.destroy'), ['endpoint' => $endpoint])
        ->assertNoContent();

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $owner->id,
        'endpoint' => $endpoint,
    ]);
});

it('rejects unauthorized or unsafe push subscriptions', function () {
    $accounting = User::factory()->accounting()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($accounting)
        ->postJson(route('whatsapp.pwa.push-subscription.store'), validWhatsAppPushSubscription())
        ->assertForbidden();

    $this->actingAs($admin)
        ->postJson(
            route('whatsapp.pwa.push-subscription.store'),
            validWhatsAppPushSubscription('https://127.0.0.1/push'),
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('queues one notification for each new inbound message and ignores Meta retries', function () {
    $admin = User::factory()->admin()->create();
    $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
    $accounting = User::factory()->accounting()->create();
    $admin->updatePushSubscription('https://push.example.com/admin', 'key', 'token', 'aes128gcm');
    $inactiveAdmin->updatePushSubscription('https://push.example.com/inactive', 'key', 'token', 'aes128gcm');
    $accounting->updatePushSubscription('https://push.example.com/accounting', 'key', 'token', 'aes128gcm');

    Notification::fake();
    WhatsAppConcierge::fake();
    Http::fake();

    handlePushNotificationTestMessage('wamid.PUSH-1');
    handlePushNotificationTestMessage('wamid.PUSH-1');

    Notification::assertSentToTimes($admin, IncomingWhatsAppMessageNotification::class, 1);
    Notification::assertNotSentTo($inactiveAdmin, IncomingWhatsAppMessageNotification::class);
    Notification::assertNotSentTo($accounting, IncomingWhatsAppMessageNotification::class);
    Notification::assertSentTo(
        $admin,
        IncomingWhatsAppMessageNotification::class,
        function (IncomingWhatsAppMessageNotification $notification) use ($admin): bool {
            $payload = $notification->toWebPush($admin, $notification)->toArray();

            return $notification->contactName === 'Ana Pérez'
                && $notification->messagePreview === 'Necesito ayuda con mi pedido'
                && str_contains($payload['data']['url'], 'conversation=');
        },
    );
});
