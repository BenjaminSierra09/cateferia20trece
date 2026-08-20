<?php

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Livewire\WhatsApp\Inbox;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

it('pauses and resumes the bot for only the selected conversation', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create();
    WhatsAppMessage::factory()->create(['whatsapp_conversation_id' => $conversation->id]);

    $component = Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $conversation->id)
        ->call('toggleBot');

    expect($conversation->refresh()->isBotPaused())->toBeTrue()
        ->and($conversation->bot_paused_by_user_id)->toBe($admin->id);

    $component->call('toggleBot');

    expect($conversation->refresh()->isBotPaused())->toBeFalse()
        ->and($conversation->bot_paused_by_user_id)->toBeNull();
});

it('sends a reaction attached to a message in the selected conversation', function () {
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create();
    $message = WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $conversation->id,
        'provider_message_id' => 'wamid.TARGET',
        'sent_at' => now(),
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.REACTION']],
        ]),
    ]);

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $conversation->id)
        ->call('react', $message->id, '❤️')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('whatsapp_message_reactions', [
        'whatsapp_message_id' => $message->id,
        'sent_by_user_id' => $admin->id,
        'provider_message_id' => 'wamid.REACTION',
        'emoji' => '❤️',
        'status' => WhatsAppMessageStatus::Sent->value,
    ]);
});

it('does not allow reacting to a message from another conversation', function () {
    $admin = User::factory()->admin()->create();
    $selected = WhatsAppConversation::factory()->create();
    $other = WhatsAppConversation::factory()->create();
    WhatsAppMessage::factory()->create(['whatsapp_conversation_id' => $selected->id]);
    $otherMessage = WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $other->id,
        'provider_message_id' => 'wamid.OTHER',
        'sent_at' => now(),
    ]);

    Http::fake();

    $component = Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $selected->id);

    expect(fn () => $component->call('react', $otherMessage->id, '👍'))
        ->toThrow(ModelNotFoundException::class);

    Http::assertNothingSent();
});

it('uploads and sends a private photo during the customer service window', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $conversation = WhatsAppConversation::factory()->create([
        'phone' => '524181878244',
        'last_inbound_at' => now()->subHour(),
        'last_message_at' => now()->subHour(),
    ]);
    WhatsAppMessage::factory()->create(['whatsapp_conversation_id' => $conversation->id]);

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/media' => Http::response(['id' => 'MEDIA-123']),
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.IMAGE']],
        ]),
    ]);

    Livewire::actingAs($admin)
        ->test(Inbox::class)
        ->set('selectedConversationId', $conversation->id)
        ->set('reply', 'Foto del menú')
        ->set('photo', UploadedFile::fake()->image('menu.jpg', 800, 600))
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('photo', null);

    $message = $conversation->messages()->where('provider_message_id', 'wamid.IMAGE')->firstOrFail();

    expect($message->type)->toBe('image')
        ->and($message->body)->toBe('Foto del menú')
        ->and($message->media_mime_type)->toBe('image/jpeg');
    Storage::disk('local')->assertExists($message->media_path);

    $this->actingAs($admin)
        ->get(route('dashboard.whatsapp.media', $message))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});
