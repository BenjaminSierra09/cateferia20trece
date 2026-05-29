<?php

use App\Enums\PaymentMethod;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Size;
use App\Models\User;
use App\Services\WorkSessionService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

test('voice sale draft endpoint transcribes audio and returns the interpreted cart draft', function () {
    config()->set('ai.providers.openai.key', 'test-key');
    config()->set('ai.providers.openai.url', 'https://api.openai.test/v1');

    $branch = Branch::factory()->create(['name' => 'Juárez']);
    $user = User::factory()->assignedToBranch($branch)->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Capuchino',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);
    $type = CustomizationType::factory()->create(['name' => 'Leche']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Almendra',
        'price' => 12,
    ]);
    $product = Product::factory()->create([
        'name' => 'Galleta',
        'base_price' => 20,
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 65,
    ]);
    $beverage->customizationOptions()->attach($option->id);

    app(WorkSessionService::class)->start($user, $branch);

    Http::fake([
        'https://api.openai.test/v1/audio/transcriptions' => Http::response([
            'text' => 'Vendí un capuchio grande con leche de almendra y una galleta. Pagaron con tarjeta.',
        ]),
        'https://api.openai.test/v1/responses' => Http::response([
            'output_text' => json_encode([
                'payment_method' => PaymentMethod::Card->value,
                'payment_breakdown' => [
                    'cash' => null,
                    'card' => null,
                    'transfer' => null,
                    'reward_balance' => null,
                    'debt' => null,
                ],
                'reward_redeemed_total' => 0,
                'discount_total' => 0,
                'discount_concept' => null,
                'notes' => 'Interpretado desde audio',
                'assumptions' => ['Se corrigió "capuchio" a "Capuchino".'],
                'items' => [
                    [
                        'item_type' => 'beverage',
                        'beverage_id' => $beverage->id,
                        'product_id' => null,
                        'size_id' => $size->id,
                        'item_name' => null,
                        'unit_price' => null,
                        'quantity' => 1,
                        'customization_option_ids' => [$option->id],
                        'special_instructions' => null,
                    ],
                    [
                        'item_type' => 'product',
                        'beverage_id' => null,
                        'product_id' => $product->id,
                        'size_id' => null,
                        'item_name' => null,
                        'unit_price' => null,
                        'quantity' => 1,
                        'customization_option_ids' => [],
                        'special_instructions' => null,
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]),
    ]);

    Sanctum::actingAs($user);

    $response = $this->post(route('api.v1.sales.voice-drafts.store'), [
        'audio' => UploadedFile::fake()->create('venta.m4a', 200, 'audio/mp4'),
        'language' => 'es',
    ]);

    $response->assertCreated()
        ->assertJsonPath('transcript', 'Vendí un capuchio grande con leche de almendra y una galleta. Pagaron con tarjeta.')
        ->assertJsonPath('sale_payload.user_id', $user->id)
        ->assertJsonPath('sale_payload.payment_method', PaymentMethod::Card->value)
        ->assertJsonPath('sale_payload.notes', 'Interpretado desde audio')
        ->assertJsonPath('sale_payload.items.0.beverage_id', $beverage->id)
        ->assertJsonPath('sale_payload.items.0.item_type', 'beverage')
        ->assertJsonPath('sale_payload.items.0.size_id', $size->id)
        ->assertJsonPath('sale_payload.items.0.customization_option_ids.0', $option->id)
        ->assertJsonPath('sale_payload.items.1.product_id', $product->id)
        ->assertJsonPath('sale_payload.items.1.item_type', 'product')
        ->assertJsonPath('assumptions.0', 'Se corrigió "capuchio" a "Capuchino".');

    expect(Sale::query()->count())->toBe(0);

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request): bool {
        return str_ends_with($request->url(), '/audio/transcriptions');
    });
    Http::assertSent(function (Request $request) use ($branch, $user): bool {
        if (! str_ends_with($request->url(), '/responses')) {
            return false;
        }

        $payload = json_decode($request->body(), true);

        return ($payload['model'] ?? null) === config('ai.voice_sale.response_model')
            && str_contains($payload['input'][1]['content'][0]['text'] ?? '', $branch->name)
            && str_contains($payload['input'][1]['content'][0]['text'] ?? '', $user->name);
    });
});

test('voice sale draft endpoint defaults payment method to cash when omitted by the model', function () {
    config()->set('ai.providers.openai.key', 'test-key');
    config()->set('ai.providers.openai.url', 'https://api.openai.test/v1');

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    app(WorkSessionService::class)->start($user, $branch);

    Http::fake([
        'https://api.openai.test/v1/audio/transcriptions' => Http::response([
            'text' => 'Acabo de vender 2 americanos medianos.',
        ]),
        'https://api.openai.test/v1/responses' => Http::response([
            'output_text' => json_encode([
                'payment_method' => '',
                'payment_breakdown' => [
                    'cash' => null,
                    'card' => null,
                    'transfer' => null,
                    'reward_balance' => null,
                    'debt' => null,
                ],
                'reward_redeemed_total' => 0,
                'discount_total' => 0,
                'discount_concept' => null,
                'notes' => null,
                'assumptions' => ['Se asumió pago en efectivo por omisión.'],
                'items' => [
                    [
                        'item_type' => 'temporary',
                        'beverage_id' => null,
                        'product_id' => null,
                        'size_id' => null,
                        'item_name' => 'Pan de 20 pesos',
                        'unit_price' => 20,
                        'quantity' => 2,
                        'customization_option_ids' => [],
                        'special_instructions' => null,
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]),
    ]);

    Sanctum::actingAs($user);

    $response = $this->withHeader('Accept', 'application/json')
        ->post(route('api.v1.sales.voice-drafts.store'), [
            'audio' => UploadedFile::fake()->create('venta.wav', 200, 'audio/wav'),
        ]);

    $response->assertCreated()
        ->assertJsonPath('sale_payload.payment_method', PaymentMethod::Cash->value)
        ->assertJsonPath('sale_payload.items.0.item_type', 'temporary')
        ->assertJsonPath('sale_payload.items.0.item_name', 'Pan de 20 pesos');

    expect(Sale::query()->count())->toBe(0);
});

test('voice sale draft endpoint assigns the sale to a customer when customer uuid is provided', function () {
    config()->set('ai.providers.openai.key', 'test-key');
    config()->set('ai.providers.openai.url', 'https://api.openai.test/v1');

    $branch = Branch::factory()->create(['name' => 'Canal']);
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create(['name' => 'Benjamin Sierra']);
    $qrCode = CustomerQrCode::factory()->for($customer)->create([
        'uuid' => '166ed10f-df8c-49b4-859c-271d01d58e93',
        'is_active' => true,
    ]);
    $product = Product::factory()->create([
        'name' => 'Croissant de chocolate',
        'base_price' => 30,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    Http::fake([
        'https://api.openai.test/v1/audio/transcriptions' => Http::response([
            'text' => 'Vendí un croissant de chocolate y pagaron en efectivo.',
        ]),
        'https://api.openai.test/v1/responses' => Http::response([
            'output_text' => json_encode([
                'payment_method' => PaymentMethod::Cash->value,
                'payment_breakdown' => [
                    'cash' => null,
                    'card' => null,
                    'transfer' => null,
                    'reward_balance' => null,
                    'debt' => null,
                ],
                'reward_redeemed_total' => 0,
                'discount_total' => 0,
                'discount_concept' => null,
                'notes' => null,
                'assumptions' => [],
                'items' => [
                    [
                        'item_type' => 'product',
                        'beverage_id' => null,
                        'product_id' => $product->id,
                        'size_id' => null,
                        'item_name' => null,
                        'unit_price' => null,
                        'quantity' => 1,
                        'customization_option_ids' => [],
                        'special_instructions' => null,
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]),
    ]);

    Sanctum::actingAs($user);

    $response = $this->post(route('api.v1.sales.voice-drafts.store'), [
        'audio' => UploadedFile::fake()->create('venta.m4a', 200, 'audio/mp4'),
        'customer_uuid' => $qrCode->uuid,
    ]);

    $response->assertCreated()
        ->assertJsonPath('sale_payload.customer_id', $customer->id);

    expect(Sale::query()->count())->toBe(0);

    Http::assertSent(function (Request $request) use ($qrCode): bool {
        if (! str_ends_with($request->url(), '/responses')) {
            return false;
        }

        return str_contains($request->body(), $qrCode->uuid);
    });
});

test('voice sale draft endpoint reads structured output text from the raw output array when output_text is absent', function () {
    config()->set('ai.providers.openai.key', 'test-key');
    config()->set('ai.providers.openai.url', 'https://api.openai.test/v1');

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $product = Product::factory()->create([
        'name' => 'Pan del día',
        'base_price' => 20,
    ]);

    app(WorkSessionService::class)->start($user, $branch);

    Http::fake([
        'https://api.openai.test/v1/audio/transcriptions' => Http::response([
            'text' => 'Vendí un pan del día.',
        ]),
        'https://api.openai.test/v1/responses' => Http::response([
            'output' => [
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => json_encode([
                                'payment_method' => PaymentMethod::Cash->value,
                                'payment_breakdown' => [
                                    'cash' => null,
                                    'card' => null,
                                    'transfer' => null,
                                    'reward_balance' => null,
                                    'debt' => null,
                                ],
                                'reward_redeemed_total' => 0,
                                'discount_total' => 0,
                                'discount_concept' => null,
                                'notes' => null,
                                'assumptions' => [],
                                'items' => [
                                    [
                                        'item_type' => 'product',
                                        'beverage_id' => null,
                                        'product_id' => $product->id,
                                        'size_id' => null,
                                        'item_name' => null,
                                        'unit_price' => null,
                                        'quantity' => 1,
                                        'customization_option_ids' => [],
                                        'special_instructions' => null,
                                    ],
                                ],
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    Sanctum::actingAs($user);

    $response = $this->post(route('api.v1.sales.voice-drafts.store'), [
        'audio' => UploadedFile::fake()->create('venta.m4a', 200, 'audio/mp4'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('sale_payload.payment_method', PaymentMethod::Cash->value)
        ->assertJsonPath('sale_payload.items.0.product_id', $product->id);
});

test('voice sale draft endpoint rejects an unknown customer uuid', function () {
    config()->set('ai.providers.openai.key', 'test-key');

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    app(WorkSessionService::class)->start($user, $branch);

    Sanctum::actingAs($user);

    $response = $this->post(route('api.v1.sales.voice-drafts.store'), [
        'audio' => UploadedFile::fake()->create('venta.wav', 200, 'audio/wav'),
        'customer_uuid' => '166ed10f-df8c-49b4-859c-271d01d58e93',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_uuid']);
});

test('voice sale draft endpoint requires an open work session', function () {
    config()->set('ai.providers.openai.key', 'test-key');

    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->post(route('api.v1.sales.voice-drafts.store'), [
        'audio' => UploadedFile::fake()->create('venta.wav', 200, 'audio/wav'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['audio']);
});
