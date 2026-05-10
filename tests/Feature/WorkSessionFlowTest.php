<?php

use App\Models\Branch;
use App\Models\User;
use App\Services\WorkSessionService;

test('users can confirm their branch for the day', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    $session = app(WorkSessionService::class)->start($user, $branch);

    expect($session->branch_id)->toBe($branch->id);
    expect(app(WorkSessionService::class)->currentFor($user)?->id)->toBe($session->id);
});
