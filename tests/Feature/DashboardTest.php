<?php

use App\Models\Branch;
use App\Models\User;
use App\Models\WorkSession;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users without a confirmed branch are redirected to work session check in', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('dashboard.work-session.check-in'));
});

test('authenticated users with a confirmed branch can visit the dashboard', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => today(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
