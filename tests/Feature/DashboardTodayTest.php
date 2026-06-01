<?php

use App\Enums\SaleStatus;
use App\Livewire\Dashboard;
use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\ReportService;
use Livewire\Livewire;

test('income for date sums only the given day completed sales for the branch', function () {
    $branch = Branch::factory()->create();

    Sale::factory()->create(['branch_id' => $branch->id, 'status' => SaleStatus::Completed, 'sold_at' => now(), 'total' => 100]);
    Sale::factory()->create(['branch_id' => $branch->id, 'status' => SaleStatus::Completed, 'sold_at' => now()->subDay(), 'total' => 50]);
    Sale::factory()->create(['branch_id' => $branch->id, 'status' => SaleStatus::Cancelled, 'sold_at' => now(), 'total' => 999]);

    $result = app(ReportService::class)->incomeForDate($branch->id, today());

    expect($result['income'])->toBe(100.0)
        ->and($result['sales'])->toBe(1);
});

test('sales by shift for date summarizes each shift', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->create();
    $session = WorkSession::factory()->create(['branch_id' => $branch->id, 'user_id' => $user->id, 'work_date' => today()]);

    Sale::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'work_session_id' => $session->id,
        'status' => SaleStatus::Completed,
        'sold_at' => now(),
        'total' => 80,
    ]);

    $shifts = app(ReportService::class)->salesByShiftForDate($branch->id, today());

    expect($shifts)->toHaveCount(1)
        ->and($shifts[0]['sales'])->toBe(1)
        ->and($shifts[0]['total'])->toBe(80.0);
});

test('low stock alerts return rows at or below the threshold and negatives', function () {
    $branch = Branch::factory()->create();
    $low = InventoryItem::factory()->create(['name' => 'Leche']);
    $ok = InventoryItem::factory()->create(['name' => 'Café']);
    $negative = InventoryItem::factory()->create(['name' => 'Vasos']);

    BranchInventoryStock::factory()->create(['branch_id' => $branch->id, 'inventory_item_id' => $low->id, 'quantity' => 5, 'min_quantity' => 10]);
    BranchInventoryStock::factory()->create(['branch_id' => $branch->id, 'inventory_item_id' => $ok->id, 'quantity' => 500, 'min_quantity' => 10]);
    BranchInventoryStock::factory()->create(['branch_id' => $branch->id, 'inventory_item_id' => $negative->id, 'quantity' => -3, 'min_quantity' => 0]);

    $alerts = app(ReportService::class)->lowStockAlerts($branch->id);
    $names = $alerts->map(fn ($stock) => $stock->item->name)->all();

    expect($names)->toContain('Leche')
        ->and($names)->toContain('Vasos')
        ->and($names)->not->toContain('Café');
});

test('the dashboard renders the today summary', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Ingreso de hoy')
        ->assertSee('Inventario en alerta');
});
