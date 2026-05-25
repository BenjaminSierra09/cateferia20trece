<?php

use App\Enums\PaymentMethod;
use App\Enums\WorkSessionStatus;
use App\Livewire\Reports\Overview;
use App\Livewire\Reports\Shifts;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\ReportExcelExportService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('dashboard sales report can be exported as excel', function () {
    Carbon::setTestNow('2026-05-24 10:00:00');

    $branch = Branch::factory()->create(['name' => 'Centro']);
    $user = User::factory()->create();
    $sale = Sale::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'payment_method' => PaymentMethod::Cash,
        'sold_at' => '2026-05-24 09:00:00',
        'subtotal' => 120,
        'total' => 120,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'item_name' => 'Latte Grande',
        'quantity' => 2,
        'line_total' => 120,
    ]);

    Livewire::actingAs($user)
        ->test(Overview::class)
        ->set('branch_id', $branch->id)
        ->set('date_from', '2026-05-24')
        ->set('date_to', '2026-05-24')
        ->call('exportExcel')
        ->assertFileDownloaded('reporte-ventas-2026-05-24-100000.xlsx');
});

test('dashboard shift report can be exported as excel', function () {
    Carbon::setTestNow('2026-05-24 10:00:00');

    $branch = Branch::factory()->create(['name' => 'Centro']);
    $user = User::factory()->create(['name' => 'Ana']);
    $session = WorkSession::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'status' => WorkSessionStatus::Open,
        'work_date' => '2026-05-24',
    ]);

    Sale::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'work_session_id' => $session->id,
    ]);

    Livewire::actingAs($user)
        ->test(Shifts::class)
        ->set('branch_id', $branch->id)
        ->set('date_from', '2026-05-24')
        ->set('date_to', '2026-05-24')
        ->call('exportExcel')
        ->assertFileDownloaded('reporte-turnos-2026-05-24-100000.xlsx');
});

test('sales report excel contains workbook worksheets', function () {
    $branch = Branch::factory()->create(['name' => 'Centro']);
    $sale = Sale::factory()->create([
        'branch_id' => $branch->id,
        'payment_method' => PaymentMethod::Cash,
        'total' => 70,
    ]);
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'item_name' => 'Americano',
        'quantity' => 1,
        'line_total' => 70,
    ]);

    $contents = app(ReportExcelExportService::class)->overview([
        'branch_id' => $branch->id,
    ]);
    $path = tempnam(sys_get_temp_dir(), 'report_test_');

    file_put_contents($path, $contents);

    $zip = new ZipArchive;

    expect($zip->open($path))->toBeTrue()
        ->and($zip->locateName('xl/workbook.xml'))->not->toBeFalse()
        ->and($zip->locateName('xl/worksheets/sheet1.xml'))->not->toBeFalse()
        ->and($zip->getFromName('xl/workbook.xml'))->toContain('Resumen');

    $zip->close();
    @unlink($path);
});
