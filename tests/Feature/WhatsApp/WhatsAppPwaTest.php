<?php

use App\Livewire\WhatsApp\PwaInbox;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

it('redirects guests to the dedicated WhatsApp login', function () {
    $response = $this->get(route('whatsapp.pwa'));

    $response->assertRedirect(route('whatsapp.pwa.login'));
    $response->assertSessionHas('url.intended', route('whatsapp.pwa'));
});

it('renders a dedicated login that always remembers the device', function () {
    $this->get(route('whatsapp.pwa.login'))
        ->assertOk()
        ->assertSee('WhatsApp Café 20Trece')
        ->assertSee('mantendremos tu sesión en este dispositivo')
        ->assertSee('name="remember" value="1"', escape: false)
        ->assertSee('/whatsapp-pwa.webmanifest', escape: false);
});

it('returns an administrator to the PWA with a persistent login cookie', function () {
    $admin = User::factory()->admin()->create();
    $this->get(route('whatsapp.pwa.login'));

    $response = $this->post(route('login.store'), [
        'username' => $admin->username,
        'password' => 'password',
        'remember' => '1',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('whatsapp.pwa', absolute: false))
        ->assertCookie(Auth::guard()->getRecallerName());
    $this->assertAuthenticatedAs($admin);
});

it('allows only active administrators to open the WhatsApp PWA', function () {
    $admin = User::factory()->admin()->create();
    $accounting = User::factory()->accounting()->create();
    $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);

    $this->actingAs($admin)
        ->get(route('whatsapp.pwa'))
        ->assertOk()
        ->assertSee('WhatsApp 20Trece')
        ->assertSee('/whatsapp-pwa.webmanifest', escape: false);

    $this->actingAs($accounting)
        ->get(route('whatsapp.pwa'))
        ->assertForbidden();

    $this->actingAs($inactiveAdmin)
        ->get(route('whatsapp.pwa'))
        ->assertForbidden();
});

it('opens the conversation requested by a notification link', function () {
    $admin = User::factory()->admin()->create();
    $olderConversation = WhatsAppConversation::factory()->create(['last_message_at' => now()->subMinute()]);
    $requestedConversation = WhatsAppConversation::factory()->create(['last_message_at' => now()]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $olderConversation->id,
        'body' => 'Mensaje anterior',
    ]);
    WhatsAppMessage::factory()->create([
        'whatsapp_conversation_id' => $requestedConversation->id,
        'body' => 'Mensaje abierto desde la notificación',
    ]);

    Livewire::actingAs($admin)
        ->test(PwaInbox::class, ['conversation' => $olderConversation->id])
        ->assertSet('selectedConversationId', $olderConversation->id);
});

it('ships a valid standalone manifest without caching private conversations', function () {
    $manifest = json_decode(
        file_get_contents(public_path('whatsapp-pwa.webmanifest')),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );
    $serviceWorker = file_get_contents(public_path('whatsapp-pwa-sw.js'));

    expect($manifest)
        ->toMatchArray([
            'id' => '/whatsapp-app',
            'start_url' => '/whatsapp-app',
            'scope' => '/whatsapp-app',
            'display' => 'standalone',
        ])
        ->and($serviceWorker)->toContain("self.addEventListener('push'")
        ->and($serviceWorker)->not->toContain("addEventListener('fetch'");
});
