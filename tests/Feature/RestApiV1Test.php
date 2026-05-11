<?php

use App\Enums\PaymentMethod;
use App\Enums\RewardTransactionType;
use App\Enums\WorkSessionStatus;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Models\RewardTransaction;
use App\Models\Size;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

test('v1 api exposes metadata and catalog endpoints', function () {
    $branch = Branch::factory()->create(['name' => 'Centro']);
    $category = BeverageCategory::factory()->create(['name' => 'Café caliente']);
    $size = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz']);
    $type = CustomizationType::factory()->create(['name' => 'Leches']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Avena',
    ]);
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
    ]);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 72,
    ]);
    $beverage->customizationOptions()->attach($option->id);

    $this->getJson('/api/v1/meta')
        ->assertSuccessful()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('payment_methods')
            ->has('reward_tiers')
            ->has('user_roles')
            ->has('work_session_statuses')
            ->etc());

    $this->getJson('/api/v1/catalog')
        ->assertSuccessful()
        ->assertJsonFragment(['name' => $branch->name])
        ->assertJsonFragment(['name' => $beverage->name])
        ->assertJsonFragment(['name' => $option->name]);
});

test('v1 api protects private endpoints and authenticates users with sanctum tokens', function () {
    $user = User::factory()->create([
        'username' => 'cafe_admin',
        'email' => 'admin@cafeteria.test',
    ]);

    $this->getJson('/api/v1/branches')
        ->assertUnauthorized();

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => 'CAFE_ADMIN',
        'password' => 'password',
        'device_name' => 'iphone-15',
    ]);

    $plainTextToken = $loginResponse->json('token');

    $loginResponse->assertSuccessful()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.username', $user->username);

    $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
        ->getJson('/api/v1/auth/me')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $user->id);

    $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
        ->deleteJson('/api/v1/auth/logout')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Sesión de API cerrada correctamente.');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

test('v1 api can manage branches', function () {
    Sanctum::actingAs(User::factory()->create());

    $storeResponse = $this->postJson('/api/v1/branches', [
        'name' => 'Norte',
        'address' => 'Av. Siempre Viva 123',
        'city' => 'San Miguel',
        'phone' => '+524151112233',
        'operating_hours' => '7:00 - 19:00',
        'is_active' => true,
    ]);

    $branchId = $storeResponse->json('data.id');

    $storeResponse->assertSuccessful()
        ->assertJsonPath('data.name', 'Norte');

    $this->getJson("/api/v1/branches/{$branchId}")
        ->assertSuccessful()
        ->assertJsonPath('data.city', 'San Miguel');

    $this->patchJson("/api/v1/branches/{$branchId}", [
        'city' => 'Dolores Hidalgo',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.city', 'Dolores Hidalgo');

    $this->deleteJson("/api/v1/branches/{$branchId}")
        ->assertNoContent();
});

test('v1 api can create and update beverages with sizes and customizations', function () {
    Sanctum::actingAs(User::factory()->create());

    $category = BeverageCategory::factory()->create(['name' => 'Frappés']);
    $small = Size::factory()->create(['name' => 'Chico']);
    $large = Size::factory()->create(['name' => 'Grande']);
    $type = CustomizationType::factory()->create(['name' => 'Extras']);
    $option = CustomizationOption::factory()->create([
        'customization_type_id' => $type->id,
        'name' => 'Shot extra',
    ]);

    $storeResponse = $this->postJson('/api/v1/beverages', [
        'beverage_category_id' => $category->id,
        'name' => 'Mocha',
        'description' => 'Bebida de prueba',
        'is_active' => true,
        'size_prices' => [
            ['size_id' => $small->id, 'price' => 58],
            ['size_id' => $large->id, 'price' => 74],
        ],
        'customization_option_ids' => [$option->id],
    ]);

    $beverageId = $storeResponse->json('data.id');

    $storeResponse->assertSuccessful()
        ->assertJsonPath('data.name', 'Mocha')
        ->assertJsonPath('data.slug', 'mocha')
        ->assertJsonPath('data.sizes.0.size_id', $small->id);

    $this->patchJson("/api/v1/beverages/{$beverageId}", [
        'name' => 'Mocha Blanco',
        'size_prices' => [
            ['size_id' => $small->id, 'price' => 60],
            ['size_id' => $large->id, 'price' => 78],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Mocha Blanco')
        ->assertJsonPath('data.slug', 'mocha-blanco');

    $this->getJson("/api/v1/beverages/{$beverageId}")
        ->assertSuccessful()
        ->assertJsonPath('data.customizations.0.id', $option->id)
        ->assertJsonPath('data.sizes.1.price', 78);
});

test('v1 api reuses an existing work session when syncing the same user and date', function () {
    Sanctum::actingAs(User::factory()->create());

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    $firstResponse = $this->postJson('/api/v1/work-sessions', [
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-10',
        'clock_in_at' => '2026-05-10T09:00:00Z',
        'status' => WorkSessionStatus::Open->value,
    ]);

    $firstResponse->assertSuccessful();

    $workSessionId = $firstResponse->json('data.id');

    $secondResponse = $this->postJson('/api/v1/work-sessions', [
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-10',
        'clock_in_at' => '2026-05-10T09:15:00Z',
        'status' => WorkSessionStatus::Open->value,
    ]);

    $secondResponse->assertSuccessful()
        ->assertJsonPath('data.id', $workSessionId);

    expect($secondResponse->json('data.clock_in_at'))->toStartWith('2026-05-10T09:15:00');

    expect(WorkSession::query()
        ->where('user_id', $user->id)
        ->whereDate('work_date', '2026-05-10')
        ->count())->toBe(1);
});

test('v1 api exposes operational resources for users sessions sales and reward transactions', function () {
    Sanctum::actingAs(User::factory()->create());

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create();
    $category = BeverageCategory::factory()->create();
    $beverage = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $size = Size::factory()->create(['name' => 'Grande']);
    $product = Product::factory()->create(['name' => 'Galleta']);

    $beverage->sizePrices()->create([
        'size_id' => $size->id,
        'price' => 64,
    ]);

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    $sale = app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [
            [
                'beverage_id' => $beverage->id,
                'size_id' => $size->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ], $user, $workSession);

    $rewardTransaction = RewardTransaction::query()->create([
        'customer_id' => $customer->id,
        'sale_id' => $sale->id,
        'type' => RewardTransactionType::Earned,
        'amount' => 5,
        'balance_after' => 5,
        'description' => 'Compra inicial',
        'transacted_at' => now(),
    ]);

    $this->getJson('/api/v1/users')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $user->id]);

    $this->getJson('/api/v1/work-sessions')
        ->assertSuccessful()
        ->assertJsonPath('data.0.status', WorkSessionStatus::Open->value);

    $this->getJson('/api/v1/sales')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $sale->id]);

    $this->getJson("/api/v1/sales/{$sale->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.item_name', 'Americano Grande');

    $this->getJson('/api/v1/reward-transactions')
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $rewardTransaction->id]);
});

test('v1 api exposes tonalpohualli data for customer search and qr lookup', function () {
    Sanctum::actingAs(User::factory()->create());

    $customer = Customer::factory()->create([
        'name' => 'Citlali Rivera',
        'birthday' => '1996-02-09',
    ]);

    $qrCode = CustomerQrCode::factory()->create([
        'customer_id' => $customer->id,
        'uuid' => 'customer-qr-tonalpohualli',
    ]);
    $reading = $customer->tonalpohualli();

    $this->getJson('/api/v1/customers?search=Citlali')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Citlali Rivera')
        ->assertJsonPath('data.0.tonalpohualli.nahua', $reading['nahua'])
        ->assertJsonPath('data.0.tonalpohualli.espanol', $reading['espanol']);

    $this->getJson("/api/v1/qr/{$qrCode->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('customer.id', $customer->id)
        ->assertJsonPath('customer.tonalpohualli.nahua', $reading['nahua'])
        ->assertJsonPath('customer.tonalpohualli.trecena', $reading['trecena']);
});
