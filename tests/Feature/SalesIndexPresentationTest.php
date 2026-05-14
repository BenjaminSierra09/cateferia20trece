<?php

use App\Models\Sale;
use App\Models\User;

test('sales list page does not duplicate pagination summary', function () {
    $user = User::factory()->admin()->create();

    Sale::factory()->count(2)->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard.sales.index', [
            'search' => '',
            'payment' => '',
            'per_page' => 10,
            'view' => 'list',
        ]));

    $response->assertOk();

    expect(substr_count($response->getContent(), 'Mostrando 1 a 2 de 2 resultados'))->toBe(1);
});
