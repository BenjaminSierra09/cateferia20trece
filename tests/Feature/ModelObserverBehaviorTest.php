<?php

use App\Jobs\GenerateCatalogImage;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Models\User;
use App\Support\CatalogImageManager;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Image;

function configureWhatsAppCloudForObserverTests(): void
{
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', 'test-access-token');
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');
    config()->set('services.whatsapp.templates.language', 'es_MX');
    config()->set('services.whatsapp.templates.customer_credential', 'customer_credential');
}

function fakeWhatsAppCloudForObserverTests(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.test/v23.0/PHONE-ID/media' => Http::response(['id' => 'MEDIA-ID']),
        'https://graph.facebook.test/v23.0/PHONE-ID/messages' => Http::response([
            'messages' => [['id' => 'wamid.credential']],
        ]),
    ]);
}

test('catalog observers generate unique slugs on create', function () {
    $firstCategory = BeverageCategory::factory()->create([
        'name' => 'Café helado',
        'slug' => '',
    ]);

    $secondCategory = BeverageCategory::factory()->create([
        'name' => 'Café helado',
        'slug' => '',
    ]);

    expect($firstCategory->slug)->toBe('cafe-helado');
    expect($secondCategory->slug)->toBe('cafe-helado-2');
});

test('catalog observers refresh slug when the source name changes', function () {
    $beverage = Beverage::factory()->create([
        'name' => 'Latte clásico',
        'slug' => '',
    ]);

    expect($beverage->slug)->toBe('latte-clasico');

    $beverage->update(['name' => 'Latte moka']);

    expect($beverage->fresh()->slug)->toBe('latte-moka');
});

test('customization and product observers generate slugs automatically', function () {
    $type = CustomizationType::factory()->create([
        'name' => 'Tipo de leche',
        'slug' => '',
    ]);

    $product = Product::factory()->create([
        'name' => 'Bolsa premium',
        'slug' => '',
    ]);

    expect($type->slug)->toBe('tipo-de-leche');
    expect($product->slug)->toBe('bolsa-premium');
});

test('customization option observer queues AI image generation when missing image', function () {
    Queue::fake();

    $option = CustomizationOption::factory()->create([
        'image_path' => null,
    ]);

    Queue::assertPushed(GenerateCatalogImage::class, function (GenerateCatalogImage $job) use ($option) {
        return $job->modelClass === CustomizationOption::class
            && $job->modelId === $option->id;
    });
});

test('catalog image job stores a generated square image on the public disk', function () {
    Storage::fake('public');
    Queue::fake([GenerateCatalogImage::class]);
    config()->set('ai.providers.openai.key', 'fake-openai-key');
    config()->set('ai.providers.gemini.key', null);
    config()->set('ai.providers.xai.key', null);

    $fixture = UploadedFile::fake()->image('generated.png', 1200, 900);

    Image::fake([
        base64_encode((string) file_get_contents($fixture->getRealPath())),
    ])->preventStrayImages();

    $product = Product::factory()->create([
        'name' => 'Pan de la casa',
        'image_path' => null,
    ]);

    $job = new GenerateCatalogImage(Product::class, $product->id);
    $job->handle(app(CatalogImageManager::class));

    $product->refresh();

    expect($product->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($product->image_path);

    [$width, $height] = getimagesize(Storage::disk('public')->path($product->image_path));

    expect($width)->toBe($height);
});

test('catalog image job exits cleanly when no AI image provider is configured', function () {
    Storage::fake('public');
    Queue::fake([GenerateCatalogImage::class]);
    Log::spy();

    config()->set('ai.providers.openai.key', null);
    config()->set('ai.providers.gemini.key', null);
    config()->set('ai.providers.xai.key', null);

    $product = Product::factory()->create([
        'name' => 'Galleta de avena',
        'image_path' => null,
    ]);

    $job = new GenerateCatalogImage(Product::class, $product->id);
    $job->handle(app(CatalogImageManager::class));

    expect($product->fresh()->image_path)->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});

test('catalog image manager can suppress queued generation temporarily', function () {
    Queue::fake();

    $product = Product::factory()->create([
        'image_path' => 'products/existing-image.png',
    ]);

    CatalogImageManager::withoutQueueing(function () use ($product): void {
        app(CatalogImageManager::class)->queueImageGeneration($product);
    });

    Queue::assertNothingPushed();
});

test('catalog image manager can regenerate an image even when one already exists', function () {
    Storage::fake('public');
    config()->set('ai.providers.openai.key', 'fake-openai-key');
    config()->set('ai.providers.gemini.key', null);
    config()->set('ai.providers.xai.key', null);

    $fixture = UploadedFile::fake()->image('regenerated.png', 900, 900);

    Image::fake([
        base64_encode((string) file_get_contents($fixture->getRealPath())),
    ])->preventStrayImages();

    $product = Product::factory()->create([
        'name' => 'Pan regenerado',
        'image_path' => 'catalog/products/existing.png',
    ]);

    $path = app(CatalogImageManager::class)->generateImageOrFail($product, force: true);

    $product->refresh();

    expect($product->image_path)->toBe($path)
        ->and($path)->not->toBe('catalog/products/existing.png');

    Storage::disk('public')->assertExists($path);
});

test('user observer normalizes name username and email', function () {
    $user = User::factory()->create([
        'name' => '  Ana López  ',
        'username' => 'Ana-Lopez',
        'email' => 'ANA@Example.COM',
    ]);

    expect($user->fresh()->name)->toBe('Ana López');
    expect($user->fresh()->username)->toBe('ana-lopez');
    expect($user->fresh()->email)->toBe('ana@example.com');
});

test('customer qr code observer normalizes uuid casing and whitespace', function () {
    $customer = Customer::factory()->create();

    $qrCode = CustomerQrCode::query()->create([
        'customer_id' => $customer->id,
        'uuid' => '  ABCD-1234-EFGH  ',
        'is_active' => true,
    ]);

    expect($qrCode->fresh()->uuid)->toBe('abcd-1234-efgh');
});

test('customer observer creates a qr code automatically on customer creation', function () {
    $customer = Customer::factory()->create();

    $customer->load('qrCodes');

    expect($customer->qrCodes)->toHaveCount(1);
    expect(Str::isUuid($customer->qrCodes->first()->uuid))->toBeTrue();
    expect($customer->qrCodes->first()->customer_id)->toBe($customer->id);
});

test('customer observer sends the welcome qr credential through WhatsApp Cloud', function () {
    configureWhatsAppCloudForObserverTests();
    fakeWhatsAppCloudForObserverTests();

    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
    ]);

    $customer->load('qrCodes');
    $qrCode = $customer->qrCodes->sole();

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request): bool {
        return str_ends_with($request->url(), '/media')
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && str_contains($request->body(), 'messaging_product')
            && str_contains($request->body(), 'image/png');
    });
    Http::assertSent(function (Request $request) use ($customer, $qrCode): bool {
        if (! str_ends_with($request->url(), '/messages')) {
            return false;
        }

        $parameters = $request['template']['components'][1]['parameters'];

        return $request['to'] === '524151234567'
            && $request['type'] === 'template'
            && $request['template']['name'] === 'customer_credential'
            && $request['template']['components'][0]['parameters'][0]['image']['id'] === 'MEDIA-ID'
            && $parameters[0]['text'] === $customer->name
            && $parameters[1]['text'] === route('public.rewards')
            && $parameters[2]['text'] === route('public.qr.show', ['uuid' => $qrCode->uuid]);
    });
});

test('customer observer resends welcome qr credential when phone changes to a new number', function () {
    configureWhatsAppCloudForObserverTests();
    fakeWhatsAppCloudForObserverTests();

    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
    ]);

    Http::assertSentCount(2);

    $customer->update([
        'phone' => '+52 415 765 4321',
    ]);

    Http::assertSentCount(4);
    Http::assertSent(function (Request $request): bool {
        return str_ends_with($request->url(), '/messages')
            && $request['to'] === '524157654321';
    });
});

test('customer observer does not resend welcome qr credential when phone formatting changes only', function () {
    configureWhatsAppCloudForObserverTests();
    fakeWhatsAppCloudForObserverTests();

    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
    ]);

    Http::assertSentCount(2);

    $customer->update([
        'phone' => '524151234567',
    ]);

    Http::assertSentCount(2);
});

test('customer observer anonymizes personal data and disables qr codes when a customer is deactivated', function () {
    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
        'birthday' => '1991-08-15',
        'email' => 'benjamin@example.com',
        'notes' => 'Cliente frecuente',
        'is_active' => true,
    ]);

    $customer->qrCodes()->create([
        'uuid' => (string) Str::uuid(),
        'is_active' => true,
    ]);

    $customer->update(['is_active' => false]);

    $customer->refresh();

    expect($customer->is_active)->toBeFalse()
        ->and($customer->name)->toContain('Cliente desactivado')
        ->and($customer->phone)->not->toBe('+52 415 123 4567')
        ->and($customer->birthday)->toBeNull()
        ->and($customer->email)->toEndWith('@redacted.local')
        ->and($customer->notes)->toBeNull()
        ->and($customer->qrCodes()->where('is_active', true)->exists())->toBeFalse();
});

test('customer observer skips whatsapp delivery when the Cloud API access token is missing', function () {
    config()->set('services.whatsapp.api_url', 'https://graph.facebook.test');
    config()->set('services.whatsapp.graph_version', 'v23.0');
    config()->set('services.whatsapp.access_token', null);
    config()->set('services.whatsapp.phone_number_id', 'PHONE-ID');

    Http::fake();

    Customer::factory()->create([
        'phone' => '+524151234567',
    ]);

    Http::assertNothingSent();
});
