<?php

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;

test('sale details page displays correctly', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => today(),
    ]);
    $sale = Sale::factory()->for($branch)->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard.sales.show', $sale->id))
        ->assertStatus(200);
});

test('sale details page shows all sale information', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => today(),
    ]);
    $sale = Sale::factory()
        ->for($branch)
        ->for($user)
        ->create(['notes' => 'Test note']);

    $this->actingAs($user)
        ->get(route('dashboard.sales.show', $sale->id))
        ->assertSee('Venta #'.$sale->id)
        ->assertSee('Detalles completos de la venta');
});

test('authenticated user with work session can access sale details', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => today(),
    ]);
    $sale = Sale::factory()->for($branch)->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard.sales.show', $sale->id))
        ->assertStatus(200);
});

test('unauthenticated user cannot access sale details', function () {
    $branch = Branch::factory()->create();
    $sale = Sale::factory()->for($branch)->create();

    $this->get(route('dashboard.sales.show', $sale->id))
        ->assertRedirect(route('login'));
});
