<?php

use App\Enums\CustomerDebtMovementType;
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
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Queue::fake();
});

test('v1 api exposes metadata and catalog endpoints', function () {
    Sanctum::actingAs(User::factory()->create());

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
        'image_path' => 'catalog/latte.png',
    ]);
    $product = Product::factory()->create([
        'name' => 'Brownie',
        'image_path' => 'catalog/brownie.png',
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
        ->assertJsonFragment(['name' => $option->name])
        ->assertJsonPath('beverages.0.image_url', url('/storage/catalog/latte.png'));

    $this->getJson('/api/v1/products')
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'Brownie',
            'image_url' => url('/storage/catalog/brownie.png'),
        ]);
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

test('v1 api can create update and deassign customer qr uuids', function () {
    Sanctum::actingAs(User::factory()->create());

    $firstUuid = 'c56a4180-65aa-42ec-a945-5fd21dec0538';
    $secondUuid = 'c56a4180-65aa-42ec-a945-5fd21dec0539';

    $storeResponse = $this->postJson('/api/v1/customers', [
        'name' => 'Citlali Rivera',
        'phone' => '+524151234567',
        'birthday' => '1996-02-09',
        'email' => 'citlali@cafeteria20trece.com',
        'notes' => 'Aplicar promoción familiar cuando venga con su tarjeta.',
        'qr_codes' => [
            ['uuid' => $firstUuid, 'is_active' => true],
            ['uuid' => $secondUuid, 'is_active' => true],
        ],
    ]);

    $customerId = $storeResponse->json('data.id');

    $storeResponse->assertSuccessful()
        ->assertJsonPath('data.name', 'Citlali Rivera')
        ->assertJsonPath('data.birthday', '1996-02-09')
        ->assertJsonPath('data.notes', 'Aplicar promoción familiar cuando venga con su tarjeta.')
        ->assertJsonCount(2, 'data.qr_codes');

    expect(CustomerQrCode::query()->where('customer_id', $customerId)->count())->toBe(2);

    $this->patchJson("/api/v1/customers/{$customerId}", [
        'name' => 'Citlali Rivera López',
        'notes' => '',
        'qr_codes' => [
            ['uuid' => $secondUuid, 'is_active' => true],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Citlali Rivera López')
        ->assertJsonPath('data.notes', null)
        ->assertJsonCount(1, 'data.qr_codes')
        ->assertJsonPath('data.qr_codes.0.uuid', $secondUuid);

    $deassignedQrCode = CustomerQrCode::query()->where('uuid', $firstUuid)->firstOrFail();
    $activeQrCode = CustomerQrCode::query()->where('uuid', $secondUuid)->firstOrFail();

    expect($deassignedQrCode->customer_id)->toBeNull();
    expect($deassignedQrCode->is_active)->toBeFalse();
    expect($activeQrCode->customer_id)->toBe($customerId);
});

test('v1 api auto-assigns a qr uuid when a customer is created without qr payload', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/customers', [
        'name' => 'Cliente QR Automático',
        'phone' => '+524151234500',
        'email' => 'cliente-auto@cafeteria20trece.com',
    ]);

    $customerId = $response->json('data.id');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data.qr_codes')
        ->assertJsonPath('data.qr_codes.0.customer_id', $customerId)
        ->assertJsonPath('data.qr_codes.0.is_active', true);

    expect(CustomerQrCode::query()->where('customer_id', $customerId)->count())->toBe(1)
        ->and(CustomerQrCode::query()->where('customer_id', $customerId)->first()?->uuid)->not->toBeNull();
});

test('v1 api keeps the auto-created qr when an empty qr payload is sent', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/v1/customers', [
        'name' => 'Cliente QR Vacío',
        'phone' => '+524151234501',
        'email' => 'cliente-vacio@cafeteria20trece.com',
        'qr_codes' => [],
    ]);

    $customerId = $response->json('data.id');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data.qr_codes')
        ->assertJsonPath('data.qr_codes.0.customer_id', $customerId)
        ->assertJsonPath('data.qr_codes.0.is_active', true);

    expect(CustomerQrCode::query()->where('customer_id', $customerId)->count())->toBe(1);
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
        ->assertJsonPath('data.customizations.0.type.name', 'Temperatura')
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
        ->assertJsonFragment(['id' => $option->id, 'name' => 'Shot extra'])
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

test('v1 api clears clock out when reopening an existing same day work session', function () {
    Sanctum::actingAs(User::factory()->create());

    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    $session = WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-10',
        'clock_in_at' => '2026-05-10 09:00:00',
        'clock_out_at' => '2026-05-10 17:00:00',
        'status' => WorkSessionStatus::Closed,
    ]);

    $response = $this->postJson('/api/v1/work-sessions', [
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-10',
        'clock_in_at' => '2026-05-10T18:20:00Z',
        'status' => WorkSessionStatus::Open->value,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $session->id)
        ->assertJsonPath('data.status', WorkSessionStatus::Open->value)
        ->assertJsonPath('data.clock_out_at', null);

    expect($session->fresh())
        ->status->toBe(WorkSessionStatus::Open)
        ->clock_out_at->toBeNull();
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
        ->assertJsonPath('data.0.tonalpohualli.espanol', $reading['espanol'])
        ->assertJsonPath('data.0.tonalpohualli.tonalli_display', $reading['tonalli_display']);

    $this->getJson("/api/v1/qr/{$qrCode->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('customer.id', $customer->id)
        ->assertJsonPath('customer.tonalpohualli.nahua', $reading['nahua'])
        ->assertJsonPath('customer.tonalpohualli.trecena', $reading['trecena'])
        ->assertJsonPath('customer.tonalpohualli.trecena_display', $reading['trecena_display']);
});

test('v1 api records customer debts and payments and exposes debt balances', function () {
    $branch = Branch::factory()->create(['name' => 'Centro']);
    $user = User::factory()->assignedToBranch($branch)->create();

    Sanctum::actingAs($user);

    $customer = Customer::factory()->create([
        'name' => 'Benjamín Flores',
        'birthday' => '1994-10-21',
    ]);

    $qrCode = CustomerQrCode::factory()->create([
        'customer_id' => $customer->id,
        'uuid' => '0d4b1cb2-98b5-41b5-9976-a98bb2452332',
    ]);

    $debtResponse = $this->postJson("/api/v1/customers/{$customer->id}/debt-movements", [
        'type' => CustomerDebtMovementType::Debt->value,
        'amount' => 120.50,
        'notes' => 'Pago pendiente de ayer',
        'branch_id' => $branch->id,
        'recorded_at' => '2026-05-13T09:00:00-06:00',
    ]);

    $debtResponse->assertSuccessful()
        ->assertJsonPath('data.type', CustomerDebtMovementType::Debt->value)
        ->assertJsonPath('data.balance_after', '120.50');

    $paymentResponse = $this->postJson("/api/v1/customers/{$customer->id}/debt-movements", [
        'type' => CustomerDebtMovementType::Payment->value,
        'amount' => 20.50,
        'notes' => 'Abono parcial',
        'branch_id' => $branch->id,
        'recorded_at' => '2026-05-13T09:30:00-06:00',
    ]);

    $paymentResponse->assertSuccessful()
        ->assertJsonPath('data.type', CustomerDebtMovementType::Payment->value)
        ->assertJsonPath('data.balance_after', '100.00');

    $this->getJson('/api/v1/customers?search=Benjam')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Benjamín Flores')
        ->assertJsonPath('data.0.debt_balance', 100)
        ->assertJsonPath('data.0.has_debt', true)
        ->assertJsonCount(2, 'data.0.debt_movements');

    $this->getJson("/api/v1/qr/{$qrCode->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('customer.debt_balance', 100)
        ->assertJsonPath('customer.has_debt', true)
        ->assertJsonPath('customer.debt_movements.0.type', CustomerDebtMovementType::Payment->value);

    $this->getJson("/api/v1/customers/{$customer->id}/debt-movements")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

test('v1 api exposes a customers top favorite beverages with frequent customizations', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'name' => 'Valeria Campos',
    ]);

    Sanctum::actingAs($user);

    $category = BeverageCategory::factory()->create(['name' => 'Café caliente']);
    $small = Size::factory()->create(['name' => 'Chico', 'capacity_label' => '8 oz']);
    $medium = Size::factory()->create(['name' => 'Mediano', 'capacity_label' => '12 oz']);
    $large = Size::factory()->create(['name' => 'Grande', 'capacity_label' => '16 oz']);

    $milkType = CustomizationType::factory()->create(['name' => 'Tipo de leche']);
    $extrasType = CustomizationType::factory()->create(['name' => 'Extras']);

    $almondMilk = CustomizationOption::factory()->create([
        'customization_type_id' => $milkType->id,
        'name' => 'Almendra',
    ]);
    $vanilla = CustomizationOption::factory()->create([
        'customization_type_id' => $extrasType->id,
        'name' => 'Vainilla',
    ]);
    $shot = CustomizationOption::factory()->create([
        'customization_type_id' => $extrasType->id,
        'name' => 'Shot extra',
    ]);
    $caramel = CustomizationOption::factory()->create([
        'customization_type_id' => $extrasType->id,
        'name' => 'Caramelo',
    ]);

    $latte = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Latte',
        'image_path' => 'catalog/latte.png',
    ]);
    $americano = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Americano',
    ]);
    $mocha = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Moka',
    ]);
    $chai = Beverage::factory()->create([
        'beverage_category_id' => $category->id,
        'name' => 'Chai',
    ]);

    foreach ([$latte, $americano, $mocha, $chai] as $beverage) {
        $beverage->sizePrices()->createMany([
            ['size_id' => $small->id, 'price' => 48],
            ['size_id' => $medium->id, 'price' => 58],
            ['size_id' => $large->id, 'price' => 68],
        ]);
    }

    $workSession = app(WorkSessionService::class)->start($user, $branch);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Cash->value,
        'items' => [
            [
                'beverage_id' => $latte->id,
                'size_id' => $medium->id,
                'quantity' => 2,
                'customization_option_ids' => [$almondMilk->id, $vanilla->id],
            ],
        ],
    ], $user, $workSession);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Card->value,
        'items' => [
            [
                'beverage_id' => $latte->id,
                'size_id' => $medium->id,
                'quantity' => 2,
                'customization_option_ids' => [$almondMilk->id],
            ],
            [
                'beverage_id' => $americano->id,
                'size_id' => $large->id,
                'quantity' => 3,
                'customization_option_ids' => [$shot->id],
            ],
        ],
    ], $user, $workSession);

    app(SaleService::class)->register([
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Transfer->value,
        'items' => [
            [
                'beverage_id' => $mocha->id,
                'size_id' => $small->id,
                'quantity' => 2,
                'customization_option_ids' => [$caramel->id],
            ],
            [
                'beverage_id' => $chai->id,
                'size_id' => $small->id,
                'quantity' => 1,
            ],
        ],
    ], $user, $workSession);

    $this->getJson("/api/v1/customers/{$customer->id}/favorite-beverages")
        ->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.beverage_name', 'Latte')
        ->assertJsonPath('data.0.beverage_image_url', url('/storage/catalog/latte.png'))
        ->assertJsonPath('data.0.total_quantity', 4)
        ->assertJsonPath('data.0.top_size.size_name', 'Mediano')
        ->assertJsonPath('data.0.frequent_customizations.0.name', 'Almendra')
        ->assertJsonPath('data.0.frequent_customizations.0.selection_count', 4)
        ->assertJsonPath('data.0.frequent_customizations.1.name', 'Vainilla')
        ->assertJsonPath('data.0.frequent_customizations.1.selection_count', 2)
        ->assertJsonPath('data.1.beverage_name', 'Americano')
        ->assertJsonPath('data.1.top_size.size_name', 'Grande')
        ->assertJsonPath('data.1.frequent_customizations.0.name', 'Shot extra')
        ->assertJsonPath('data.2.beverage_name', 'Moka')
        ->assertJsonMissingPath('data.3');
});

test('v1 api prevents a customer payment from exceeding the current debt', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/customers/{$customer->id}/debt-movements", [
        'type' => CustomerDebtMovementType::Debt->value,
        'amount' => 40,
        'branch_id' => $branch->id,
    ])->assertSuccessful();

    $this->postJson("/api/v1/customers/{$customer->id}/debt-movements", [
        'type' => CustomerDebtMovementType::Payment->value,
        'amount' => 60,
        'branch_id' => $branch->id,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount'])
        ->assertJsonPath('errors.amount.0', 'El abono no puede ser mayor a la deuda actual después de aplicar el saldo a favor.');
});
