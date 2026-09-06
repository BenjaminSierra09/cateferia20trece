<?php

use App\Actions\WhatsApp\CancelWhatsAppCampaign;
use App\Actions\WhatsApp\RetryWhatsAppCampaignFailures;
use App\Contracts\WhatsAppService;
use App\Enums\WhatsAppCampaignRecipientStatus;
use App\Enums\WhatsAppCampaignStatus;
use App\Jobs\QueueWhatsAppCampaign;
use App\Jobs\SendWhatsAppCampaignRecipient;
use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\WhatsApp\Campaigns;
use App\Models\Customer;
use App\Models\User;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use App\Support\WhatsAppPhoneNormalizer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-access-token');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
});

it('allows only administrators to open WhatsApp campaigns', function () {
    $admin = User::factory()->admin()->create();
    $accounting = User::factory()->accounting()->create();

    $this->actingAs($admin)
        ->get(route('dashboard.whatsapp.campaigns'))
        ->assertOk()
        ->assertSee('Campañas de WhatsApp');

    $this->actingAs($accounting)
        ->get(route('dashboard.whatsapp.campaigns'))
        ->assertForbidden();
});

it('creates a campaign only for contacts with current marketing consent', function () {
    Queue::fake();
    $admin = User::factory()->admin()->create();
    $authorized = Customer::factory()->withWhatsAppMarketingConsent('+524151234567')->create([
        'name' => 'María Autorizada',
    ]);
    Customer::factory()->create([
        'name' => 'Cliente sin permiso',
        'phone' => '+524151234568',
    ]);
    Customer::factory()->withWhatsAppMarketingConsent('+524151234569')->create([
        'name' => 'Cliente dado de baja',
        'whatsapp_marketing_opted_out_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Campaigns::class)
        ->set('name', 'Promo de septiembre')
        ->set('template_name', 'promo_septiembre')
        ->set('template_language', 'es_MX')
        ->set('message_preview', 'Hola, tenemos una promoción para ti.')
        ->set('template_parameter_lines', '{{first_name}}'.PHP_EOL.'2x1 en bebidas')
        ->set('audience', 'all')
        ->set('consent_confirmed', true)
        ->call('createCampaign')
        ->assertHasNoErrors();

    $campaign = WhatsAppCampaign::query()->firstOrFail();
    $recipient = $campaign->recipients()->firstOrFail();

    expect($campaign->recipient_count)->toBe(1)
        ->and($campaign->status)->toBe(WhatsAppCampaignStatus::Queued)
        ->and($recipient->customer_id)->toBe($authorized->id)
        ->and($recipient->phone)->toBe('524151234567')
        ->and($recipient->parameters)->toBe(['María', '2x1 en bebidas']);

    Queue::assertPushed(QueueWhatsAppCampaign::class, fn (QueueWhatsAppCampaign $job): bool => $job->campaignId === $campaign->id);
});

it('records auditable marketing consent from the customer form', function () {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create([
        'name' => 'Cliente en mostrador',
        'phone' => '+524151234578',
    ]);

    Livewire::actingAs($admin)
        ->test(CustomerForm::class, ['customer' => $customer])
        ->set('whatsapp_marketing_consent', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->refresh()->hasWhatsAppMarketingConsent())->toBeTrue()
        ->and($customer->whatsapp_marketing_consent_source)->toBe('dashboard_customer')
        ->and($customer->whatsappMarketingConsents()->firstOrFail()->recorded_by_user_id)->toBe($admin->id);
});

it('fans a campaign out into one queued job per recipient', function () {
    Queue::fake();
    $campaign = WhatsAppCampaign::factory()->create(['recipient_count' => 2]);
    $first = WhatsAppCampaignRecipient::factory()->create(['whatsapp_campaign_id' => $campaign->id]);
    $second = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'phone' => '524151234568',
    ]);

    (new QueueWhatsAppCampaign($campaign->id))->handle();

    expect($campaign->refresh()->status)->toBe(WhatsAppCampaignStatus::Sending);
    Queue::assertPushed(SendWhatsAppCampaignRecipient::class, 2);
    Queue::assertPushed(SendWhatsAppCampaignRecipient::class, fn (SendWhatsAppCampaignRecipient $job): bool => $job->recipientId === $first->id);
    Queue::assertPushed(SendWhatsAppCampaignRecipient::class, fn (SendWhatsAppCampaignRecipient $job): bool => $job->recipientId === $second->id);
});

it('sends a personalized approved template and stores it in the inbox', function () {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->withWhatsAppMarketingConsent('+524151234567')->create([
        'name' => 'María López',
    ]);
    $campaign = WhatsAppCampaign::factory()->create([
        'created_by_user_id' => $admin->id,
        'template_name' => 'promo_cafe',
        'template_language' => 'es_MX',
        'message_preview' => 'Hola María, disfruta nuestra promoción.',
        'status' => WhatsAppCampaignStatus::Sending,
        'recipient_count' => 1,
    ]);
    $recipient = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'customer_id' => $customer->id,
        'phone' => '524151234567',
        'name' => $customer->name,
        'message_preview' => 'Hola María, disfruta nuestra promoción.',
        'parameters' => ['María', '2x1'],
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.CAMPAIGN']],
        ]),
    ]);

    (new SendWhatsAppCampaignRecipient($recipient->id))->handle(
        app(WhatsAppService::class),
        app(WhatsAppPhoneNormalizer::class),
    );

    $recipient->refresh();

    expect($recipient->status)->toBe(WhatsAppCampaignRecipientStatus::Sent)
        ->and($recipient->provider_message_id)->toBe('wamid.CAMPAIGN')
        ->and($recipient->message?->type)->toBe('template')
        ->and($recipient->message?->body)->toBe('Hola María, disfruta nuestra promoción.')
        ->and($campaign->refresh()->status)->toBe(WhatsAppCampaignStatus::Completed);

    Http::assertSent(function (Request $request): bool {
        return $request['to'] === '524151234567'
            && $request['type'] === 'template'
            && $request['template']['name'] === 'promo_cafe'
            && $request['template']['language']['code'] === 'es_MX'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'María'
            && $request['template']['components'][0]['parameters'][1]['text'] === '2x1';
    });
});

it('skips a contact whose consent was revoked after campaign creation', function () {
    $customer = Customer::factory()->withWhatsAppMarketingConsent('+524151234567')->create();
    $campaign = WhatsAppCampaign::factory()->create([
        'status' => WhatsAppCampaignStatus::Sending,
        'recipient_count' => 1,
    ]);
    $recipient = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'customer_id' => $customer->id,
        'phone' => '524151234567',
    ]);
    $customer->update(['whatsapp_marketing_opted_out_at' => now()]);

    Http::fake();

    (new SendWhatsAppCampaignRecipient($recipient->id))->handle(
        app(WhatsAppService::class),
        app(WhatsAppPhoneNormalizer::class),
    );

    expect($recipient->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Skipped)
        ->and($campaign->refresh()->status)->toBe(WhatsAppCampaignStatus::Completed);
    Http::assertNothingSent();
});

it('does not automatically retry an uncertain Meta response', function () {
    $customer = Customer::factory()->withWhatsAppMarketingConsent('+524151234567')->create();
    $campaign = WhatsAppCampaign::factory()->create([
        'status' => WhatsAppCampaignStatus::Sending,
        'recipient_count' => 1,
    ]);
    $recipient = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'customer_id' => $customer->id,
        'phone' => '524151234567',
    ]);

    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/*' => Http::response([
            'error' => ['message' => 'Server error', 'code' => 2],
        ], 500),
    ]);

    (new SendWhatsAppCampaignRecipient($recipient->id))->handle(
        app(WhatsAppService::class),
        app(WhatsAppPhoneNormalizer::class),
    );

    expect($recipient->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Uncertain);
    Http::assertSentCount(1);
});

it('cancels pending recipients and retries only explicit failures', function () {
    Queue::fake();
    $campaign = WhatsAppCampaign::factory()->create([
        'status' => WhatsAppCampaignStatus::Sending,
        'recipient_count' => 2,
    ]);
    $pending = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'status' => WhatsAppCampaignRecipientStatus::Pending,
    ]);
    $sending = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $campaign->id,
        'phone' => '524151234568',
        'status' => WhatsAppCampaignRecipientStatus::Sending,
    ]);

    app(CancelWhatsAppCampaign::class)->execute($campaign);

    expect($campaign->refresh()->status)->toBe(WhatsAppCampaignStatus::Cancelled)
        ->and($pending->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Skipped)
        ->and($sending->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Sending);

    $retryCampaign = WhatsAppCampaign::factory()->create([
        'status' => WhatsAppCampaignStatus::Completed,
        'recipient_count' => 2,
    ]);
    $failed = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $retryCampaign->id,
        'phone' => '524151234569',
        'status' => WhatsAppCampaignRecipientStatus::Failed,
    ]);
    $uncertain = WhatsAppCampaignRecipient::factory()->create([
        'whatsapp_campaign_id' => $retryCampaign->id,
        'phone' => '524151234570',
        'status' => WhatsAppCampaignRecipientStatus::Uncertain,
    ]);

    $count = app(RetryWhatsAppCampaignFailures::class)->execute($retryCampaign);

    expect($count)->toBe(1)
        ->and($failed->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Pending)
        ->and($uncertain->refresh()->status)->toBe(WhatsAppCampaignRecipientStatus::Uncertain)
        ->and($retryCampaign->refresh()->status)->toBe(WhatsAppCampaignStatus::Queued);
    Queue::assertPushed(QueueWhatsAppCampaign::class);
});
