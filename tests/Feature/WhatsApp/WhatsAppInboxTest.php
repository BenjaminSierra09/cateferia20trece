<?php

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Livewire\WhatsApp\Inbox;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-access-token');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
});

it('shows recent conversations to administrators', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create([
        'profile_name' => 'María López',
        'last_inbound_at' => now(),
        'last_message_at' => now(),
    ]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'body' => 'Quisiera pedir dos lattes',
    ]);

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->assertOk()
        ->assertSee('María López')
        ->assertSee('Quisiera pedir dos lattes');
});

it('forbids accounting users from the WhatsApp inbox', function () {
    $accounting = User::factory()->accounting()->create();

    $this->actingAs($accounting)
        ->get(route('dashboard.whatsapp.index'))
        ->assertForbidden();
});

it('sends a reply during the customer service window', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create([
        'phone' => '524181878244',
        'last_inbound_at' => now()->subHour(),
        'last_message_at' => now()->subHour(),
    ]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'sent_at' => now()->subHour(),
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.OUTBOUND']],
        ]),
    ]);

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $conversation->id)
        ->set('reply', 'Claro, ¿qué tamaño deseas?')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('reply', '');

    $this->assertDatabaseHas('whatsapp_messages', [
        'whatsapp_conversation_id' => $conversation->id,
        'sent_by_user_id' => $admin->id,
        'provider_message_id' => 'wamid.OUTBOUND',
        'direction' => WhatsAppMessageDirection::Outbound->value,
        'body' => 'Claro, ¿qué tamaño deseas?',
        'status' => WhatsAppMessageStatus::Sent->value,
    ]);

    Http::assertSent(fn (Request $request): bool => $request['to'] === '524181878244'
        && $request['text']['body'] === 'Claro, ¿qué tamaño deseas?');
});

it('blocks free-form replies after the 24 hour window closes', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create([
        'last_inbound_at' => now()->subDay()->subMinute(),
        'last_message_at' => now()->subDay()->subMinute(),
    ]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'sent_at' => now()->subDay()->subMinute(),
    ]);

    Http::preventStrayRequests();
    Http::fake();

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $conversation->id)
        ->set('reply', 'Hola nuevamente')
        ->call('send')
        ->assertHasErrors(['reply']);

    Http::assertNothingSent();
    expect($conversation->messages()->where('direction', WhatsAppMessageDirection::Outbound)->count())->toBe(0);
});

it('escapes message contents in the inbox', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create([
        'last_inbound_at' => now(),
        'last_message_at' => now(),
    ]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'body' => '<script>alert("xss")</script>',
    ]);

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>alert("xss")</script>', escape: false);
});
