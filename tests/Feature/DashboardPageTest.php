<?php

use App\Models\User;

test('dashboard page renders for administrators without a daily session', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ventas');
});
