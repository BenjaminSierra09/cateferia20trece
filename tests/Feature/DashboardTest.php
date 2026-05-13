<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('employees can not access the dashboard', function () {
    $user = User::factory()->employee()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', 'Tu usuario solo puede operar desde la app de Android.');

    $this->assertGuest();
});

test('administrators can visit the dashboard without opening a shift', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
