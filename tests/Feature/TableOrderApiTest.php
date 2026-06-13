<?php

use App\Enums\PaymentMethod;
use App\Enums\TableOrderStatus;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Size;
use App\Models\TableOrder;
use App\Models\User;
use App\Models\WorkSession;
use Laravel\Sanctum\Sanctum;

test('pos can open a table, add items and close it as a sale', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    WorkSession::factory()->for($user)->for($branch)->create();
    Sanctum::actingAs($user);

    $size = Size::factory()->create(['name' => 'Grande']);
    $beverage = Beverage::factory()->create(['name' => 'Latte', 'base_price' => 55]);
    $beverage->sizePrices()->create(['size_id' => $size->id, 'price' => 60]);

    $orderId = $this->postJson('/api/v1/table-orders', [
        'user_id' => $user->id,
        'table_name' => 'Mesa 4',
        'guest_count' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', TableOrderStatus::Open->value)
        ->assertJsonPath('data.tables.0.name', 'Mesa 4')
        ->json('data.id');

    $this->postJson("/api/v1/table-orders/{$orderId}/items", [
        'items' => [[
            'beverage_id' => $beverage->id,
            'size_id' => $size->id,
            'quantity' => 2,
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('data.subtotal', 120)
        ->assertJsonPath('data.items.0.item_name', 'Latte Grande');

    $this->postJson("/api/v1/table-orders/{$orderId}/close", [
        'user_id' => $user->id,
        'payment_method' => PaymentMethod::Cash->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.table_order.status', TableOrderStatus::Closed->value)
        ->assertJsonPath('data.sales.0.total', '120.00')
        ->assertJsonPath('data.sales.0.table_order_id', $orderId);

    expect(TableOrder::query()->find($orderId)->status)->toBe(TableOrderStatus::Closed);
});

test('table orders can be split and merged before closing', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    WorkSession::factory()->for($user)->for($branch)->create();
    Sanctum::actingAs($user);

    $product = Product::factory()->create(['name' => 'Brownie', 'base_price' => 45]);

    $firstOrderId = $this->postJson('/api/v1/table-orders', [
        'user_id' => $user->id,
        'table_name' => 'Mesa 1',
    ])->json('data.id');

    $secondOrderId = $this->postJson('/api/v1/table-orders', [
        'user_id' => $user->id,
        'table_name' => 'Mesa 2',
    ])->json('data.id');

    $this->postJson("/api/v1/table-orders/{$firstOrderId}/items", [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertOk();

    $this->postJson("/api/v1/table-orders/{$secondOrderId}/items", [
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertOk();

    $merged = $this->postJson("/api/v1/table-orders/{$firstOrderId}/merge", [
        'source_order_ids' => [$secondOrderId],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data.tables')
        ->assertJsonCount(2, 'data.items')
        ->json('data');

    $itemIds = collect($merged['items'])->pluck('id')->values();

    $this->postJson("/api/v1/table-orders/{$firstOrderId}/close", [
        'user_id' => $user->id,
        'splits' => [
            [
                'payment_method' => PaymentMethod::Cash->value,
                'items' => [['item_id' => $itemIds[0], 'quantity' => 1]],
            ],
            [
                'payment_method' => PaymentMethod::Transfer->value,
                'items' => [['item_id' => $itemIds[1], 'quantity' => 1]],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonCount(2, 'data.sales')
        ->assertJsonPath('data.sales.0.total', '45.00')
        ->assertJsonPath('data.sales.1.total', '45.00');

    expect(TableOrder::query()->find($secondOrderId)->status)->toBe(TableOrderStatus::Merged);
});
