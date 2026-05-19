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

test('customer observer sends welcome qr credential by whatsapp when evolution is configured', function () {
    config()->set('services.evolution.api_url', 'https://evolution.benjaminsierra.com/message/sendText/San Miguel Live');
    config()->set('services.evolution.api_key', 'test-api-key');
    config()->set('services.evolution.instance_id', 'San Miguel Live');

    Http::preventStrayRequests();
    Http::fake([
        'https://evolution.benjaminsierra.com/message/sendMedia/*' => Http::response(['status' => 'PENDING'], 201),
        'https://evolution.benjaminsierra.com/message/sendText/*' => Http::response(['status' => 'PENDING'], 201),
    ]);

    $customer = Customer::factory()->create([
        'name' => 'Benjamin Sierra',
        'phone' => '+52 415 123 4567',
    ]);

    $customer->load('qrCodes');
    $qrCode = $customer->qrCodes->sole();

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request) use ($customer): bool {
        return str_contains($request->url(), '/message/sendMedia/')
            && $request->hasHeader('apikey', 'test-api-key')
            && $request['number'] === '524151234567'
            && $request['mediatype'] === 'image'
            && $request['mimetype'] === 'image/png'
            && str_contains($request['caption'], $customer->name)
            && filled($request['media'])
            && ! array_key_exists('mentioned', $request->data())
            && ! array_key_exists('mentionsEveryOne', $request->data())
            && str_ends_with($request['fileName'], '.png');
    });
    Http::assertSent(function (Request $request) use ($customer, $qrCode): bool {
        return str_contains($request->url(), '/message/sendText/')
            && $request['number'] === '524151234567'
            && str_contains($request['text'], $customer->name)
            && ! array_key_exists('mentioned', $request->data())
            && ! array_key_exists('mentionsEveryOne', $request->data())
            && str_contains($request['text'], route('public.rewards'))
            && str_contains($request['text'], route('public.qr.show', ['uuid' => $qrCode->uuid]));
    });
});

test('customer observer skips whatsapp delivery when evolution api key is missing', function () {
    config()->set('services.evolution.api_url', 'https://evolution.benjaminsierra.com');
    config()->set('services.evolution.api_key', null);
    config()->set('services.evolution.instance_id', 'San Miguel Live');

    Http::fake();

    Customer::factory()->create([
        'phone' => '+524151234567',
    ]);

    Http::assertNothingSent();
});
