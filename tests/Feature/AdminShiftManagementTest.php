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

test('administrators can sort the shifts table with flux sortable columns', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->admin()->create();
    $firstEmployee = User::factory()->employee()->create(['name' => 'Primera']);
    $secondEmployee = User::factory()->employee()->create(['name' => 'Segunda']);

    WorkSession::factory()->create([
        'user_id' => $firstEmployee->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-20',
        'clock_in_at' => '2026-05-20 08:00:00',
        'clock_out_at' => '2026-05-20 16:00:00',
        'status' => WorkSessionStatus::Closed,
    ]);
    WorkSession::factory()->create([
        'user_id' => $secondEmployee->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-21',
        'clock_in_at' => '2026-05-21 08:00:00',
        'clock_out_at' => '2026-05-21 16:00:00',
        'status' => WorkSessionStatus::Closed,
    ]);

    Livewire::actingAs($admin)
        ->test(Shifts::class)
        ->call('sort', 'work_date')
        ->assertSet('sortBy', 'work_date')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Primera', 'Segunda'])
        ->call('sort', 'work_date')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Segunda', 'Primera']);
});
