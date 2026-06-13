<?php

use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\TableOrder;
use App\Models\User;
use App\Enums\TableOrderStatus;
use Laravel\Sanctum\Sanctum;

test('dining tables can be created and listed for a branch', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/dining-tables', [
        'branch_id' => $branch->id,
        'name' => 'Mesa 1',
        'seats' => 4,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mesa 1')
        ->assertJsonPath('data.seats', 4)
        ->assertJsonPath('data.is_occupied', false);

    $this->getJson("/api/v1/dining-tables?branch_id={$branch->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mesa 1');
});

test('occupied dining tables cannot be deleted', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create();
    $table = DiningTable::query()->create([
        'branch_id' => $branch->id,
        'name' => 'Mesa 2',
        'is_active' => true,
    ]);
    $order = TableOrder::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'status' => TableOrderStatus::Open,
        'guest_count' => 1,
        'opened_at' => now(),
    ]);
    $order->tables()->attach($table);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/dining-tables/{$table->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('table');

    expect($table->fresh())->not->toBeNull();
});
