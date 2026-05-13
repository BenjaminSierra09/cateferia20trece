<?php

use App\Models\Branch;
use App\Models\User;
use App\Services\WorkSessionService;

test('users can confirm their branch for the day', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->employee()->create();

    $session = app(WorkSessionService::class)->start($user, $branch);

    expect($session->branch_id)->toBe($branch->id);
    expect(app(WorkSessionService::class)->currentFor($user)?->id)->toBe($session->id);
});

test('closed sessions are no longer considered the current shift', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->employee()->create();
    $session = app(WorkSessionService::class)->start($user, $branch);

    app(WorkSessionService::class)->close($session);

    expect(app(WorkSessionService::class)->currentFor($user))->toBeNull();
});
