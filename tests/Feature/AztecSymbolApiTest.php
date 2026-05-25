<?php

use App\Models\AztecSymbol;
use App\Models\User;
use Database\Seeders\AztecSymbolSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

it('exposes active aztec symbols for android', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->seed(AztecSymbolSeeder::class);

    AztecSymbol::query()
        ->where('slug', 'cipactli')
        ->update([
            'customer_greeting' => 'Saludo personalizado desde dashboard.',
            'recommended_items' => ['Espresso personalizado'],
        ]);

    $this->getJson('/api/v1/aztec-symbols')
        ->assertSuccessful()
        ->assertJsonCount(20, 'data')
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data.0', fn (AssertableJson $json) => $json
                ->where('slug', 'cipactli')
                ->where('customer_greeting', 'Saludo personalizado desde dashboard.')
                ->where('recommended_items.0', 'Espresso personalizado')
                ->etc()
            )
            ->etc()
        );
});

it('hides inactive aztec symbols from the android endpoint', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->seed(AztecSymbolSeeder::class);

    AztecSymbol::query()->where('slug', 'cipactli')->update(['is_active' => false]);

    $this->getJson('/api/v1/aztec-symbols')
        ->assertSuccessful()
        ->assertJsonCount(19, 'data')
        ->assertJsonMissing(['slug' => 'cipactli']);
});
