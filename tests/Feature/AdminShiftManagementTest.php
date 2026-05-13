<?php

use App\Enums\WorkSessionStatus;
use App\Livewire\Reports\Shifts;
use App\Models\Branch;
use App\Models\User;
use App\Models\WorkSession;
use Livewire\Livewire;

test('administrators can close active shifts from the shifts report', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->employee()->create();
    $session = WorkSession::factory()->create([
        'user_id' => $employee->id,
        'branch_id' => $branch->id,
        'work_date' => today(),
        'status' => WorkSessionStatus::Open,
        'clock_out_at' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(Shifts::class)
        ->call('closeShift', $session->id)
        ->assertSee('Turnos activos');

    expect($session->fresh())
        ->status->toBe(WorkSessionStatus::Closed)
        ->clock_out_at->not->toBeNull();
});

test('administrators can view the dedicated shifts report', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard.reports.shifts'))
        ->assertOk()
        ->assertSee('Turnos de empleados');
});
