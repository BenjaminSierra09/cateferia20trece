<?php

use App\Models\Branch;
use App\Models\User;
use App\Services\WorkSessionService;

test('dashboard page renders for users with a daily session', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ventas');
});
