<?php

use App\Models\Branch;
use App\Models\MercadoPagoPointOrder;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

test('branch resources expose mercado pago status without leaking credentials', function () {
    Sanctum::actingAs(User::factory()->create());

    Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
        'mercado_pago_public_key' => 'APP_USR-public-key',
        'mercado_pago_default_terminal_id' => 'POINT__123',
        'mercado_pago_default_terminal_name' => 'Caja principal',
    ]);

    $this->getJson('/api/v1/branches')
        ->assertOk()
        ->assertJsonPath('data.0.mercado_pago_enabled', true)
        ->assertJsonPath('data.0.mercado_pago_default_terminal_id', 'POINT__123')
        ->assertJsonMissing(['mercado_pago_access_token' => 'APP_USR-secret-token'])
        ->assertJsonMissing(['mercado_pago_public_key' => 'APP_USR-public-key']);
});

test('android can list and save mercado pago point terminal for a branch', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::preventStrayRequests();
    Http::fake([
        'api.mercadopago.com/terminals/v1/list' => Http::response([
            'data' => [
                'terminals' => [
                    [
                        'id' => 'NEWLAND_N950__ABC123',
                        'store_id' => 'store-1',
                        'pos_id' => 'pos-1',
                        'operating_mode' => 'PDV',
                    ],
                ],
            ],
        ]),
    ]);

    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
    ]);

    $this->getJson("/api/v1/branches/{$branch->id}/mercado-pago/terminals")
        ->assertOk()
        ->assertJsonPath('data.0.id', 'NEWLAND_N950__ABC123')
        ->assertJsonPath('data.0.operating_mode', 'PDV');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer APP_USR-secret-token'));

    $this->patchJson("/api/v1/branches/{$branch->id}/mercado-pago/default-terminal", [
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'terminal_name' => 'Caja principal',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', 'NEWLAND_N950__ABC123');

    expect($branch->fresh()->mercado_pago_default_terminal_id)->toBe('NEWLAND_N950__ABC123');
});

test('sales can create mercado pago point payment orders', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::preventStrayRequests();
    Http::fake([
        'api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD123',
            'status' => 'created',
        ]),
    ]);

    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
    ]);
    $sale = Sale::factory()->for($branch)->create(['total' => 125.50]);

    $this->postJson("/api/v1/sales/{$sale->id}/mercado-pago/payment-order", [
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'terminal_name' => 'Caja principal',
    ])
        ->assertOk()
        ->assertJsonPath('data.mercado_pago_order_id', 'ORD123')
        ->assertJsonPath('data.status', 'created');

    expect(MercadoPagoPointOrder::query()->where('sale_id', $sale->id)->exists())->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request['type'] === 'point'
        && $request['external_reference'] !== null
        && $request['transactions']['payments'][0]['amount'] === '125.50'
        && $request['config']['point']['terminal_id'] === 'NEWLAND_N950__ABC123');
});

test('branches can create manual mercado pago point payment orders', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::preventStrayRequests();
    Http::fake([
        'api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD-MANUAL-123',
            'status' => 'created',
        ]),
    ]);

    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
    ]);

    $this->postJson("/api/v1/branches/{$branch->id}/mercado-pago/payment-order", [
        'amount' => 77.25,
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'terminal_name' => 'Caja principal',
    ])
        ->assertOk()
        ->assertJsonPath('data.sale_id', null)
        ->assertJsonPath('data.mercado_pago_order_id', 'ORD-MANUAL-123')
        ->assertJsonPath('data.amount', '77.25');

    Http::assertSent(fn (Request $request): bool => str_starts_with($request['external_reference'], 'manual_')
        && $request['transactions']['payments'][0]['amount'] === '77.25'
        && $request['config']['point']['terminal_id'] === 'NEWLAND_N950__ABC123');
});

test('mercado pago terminal busy errors are returned with friendly messages', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::preventStrayRequests();
    Http::fake([
        'api.mercadopago.com/v1/orders' => Http::response([
            'errors' => [
                [
                    'code' => 'already_queued_order_on_terminal',
                    'message' => 'There is already a queued order on the terminal.',
                ],
            ],
        ], 409),
    ]);

    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
    ]);

    $this->postJson("/api/v1/branches/{$branch->id}/mercado-pago/payment-order", [
        'amount' => 77.25,
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'terminal_name' => 'Caja principal',
    ])
        ->assertConflict()
        ->assertJsonPath('code', 'already_queued_order_on_terminal')
        ->assertJsonPath('message', 'La terminal Point ya tiene un cobro pendiente. Termina o cancela ese cobro en la terminal antes de enviar otro.')
        ->assertJsonPath('errors.mercado_pago.0', 'La terminal Point ya tiene un cobro pendiente. Termina o cancela ese cobro en la terminal antes de enviar otro.');

    $pointOrder = MercadoPagoPointOrder::query()->where('branch_id', $branch->id)->first();

    expect($pointOrder)->not->toBeNull()
        ->and($pointOrder->status)->toBe('failed')
        ->and($pointOrder->response_payload['errors'][0]['code'])->toBe('already_queued_order_on_terminal');
});

test('sales can send mercado pago point print actions', function () {
    Sanctum::actingAs(User::factory()->create());
    Http::preventStrayRequests();
    Http::fake([
        'api.mercadopago.com/terminals/v1/actions' => Http::response([
            'id' => 'ACT123',
            'status' => 'created',
        ]),
    ]);

    $branch = Branch::factory()->create([
        'mercado_pago_is_active' => true,
        'mercado_pago_access_token' => 'APP_USR-secret-token',
    ]);
    $sale = Sale::factory()->for($branch)->create(['total' => 90]);

    $this->postJson("/api/v1/sales/{$sale->id}/mercado-pago/print", [
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'terminal_name' => 'Caja principal',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', 'ACT123')
        ->assertJsonPath('data.status', 'created');

    Http::assertSent(fn (Request $request): bool => $request['type'] === 'print'
        && $request['config']['point']['terminal_id'] === 'NEWLAND_N950__ABC123'
        && $request['config']['point']['subtype'] === 'custom');
});

test('mercado pago webhook events are stored and linked to point orders', function () {
    $pointOrder = MercadoPagoPointOrder::query()->create([
        'sale_id' => Sale::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'terminal_id' => 'NEWLAND_N950__ABC123',
        'external_reference' => 'sale_1_abc',
        'idempotency_key' => 'idem-123',
        'mercado_pago_order_id' => 'ORD123',
        'status' => 'created',
        'amount' => 90,
    ]);

    $this->postJson('/api/mercado-pago/webhook', [
        'id' => 'event-123',
        'type' => 'point_integration_wh',
        'action' => 'order.updated',
        'external_reference' => 'sale_1_abc',
        'status' => 'processed',
        'data' => [
            'id' => 'ORD123',
        ],
    ])->assertNoContent();

    expect($pointOrder->fresh()->status)->toBe('processed');
    $this->assertDatabaseHas('mercado_pago_webhook_events', [
        'event_id' => 'event-123',
        'mercado_pago_point_order_id' => $pointOrder->id,
        'external_reference' => 'sale_1_abc',
    ]);
});
