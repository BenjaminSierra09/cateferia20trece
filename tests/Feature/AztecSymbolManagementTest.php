<?php

use App\Livewire\AztecSymbols\Form;
use App\Models\AztecSymbol;
use App\Models\User;
use Database\Seeders\AztecSymbolSeeder;
use Livewire\Livewire;

it('renders the aztec symbols dashboard section', function () {
    $this->seed(AztecSymbolSeeder::class);
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard.aztec-symbols.index'))
        ->assertOk()
        ->assertSee('Símbolos aztecas')
        ->assertSee('Cipactli')
        ->assertSee(route('dashboard.aztec-symbols.edit', AztecSymbol::query()->where('slug', 'cipactli')->firstOrFail()), false)
        ->assertSee('Editar');
});

it('renders the aztec symbol edit route', function () {
    $this->seed(AztecSymbolSeeder::class);
    $user = User::factory()->admin()->create();
    $symbol = AztecSymbol::query()->where('slug', 'cipactli')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard.aztec-symbols.edit', $symbol))
        ->assertOk()
        ->assertSee('Editar Cipactli')
        ->assertSee('Guardar cambios');
});

it('can personalize an aztec symbol from the edit component', function () {
    $this->seed(AztecSymbolSeeder::class);
    $symbol = AztecSymbol::query()->where('slug', 'cipactli')->firstOrFail();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Form::class, ['aztecSymbol' => $symbol])
        ->set('serviceDescription', 'Atención muy personalizada.')
        ->set('customerGreeting', 'Te preparo algo especial.')
        ->set('tasteProfile', 'Cacao, espresso y pan dulce.')
        ->set('recommendedItemsText', "Espresso especial\nPan dulce")
        ->call('save')
        ->assertHasNoErrors();

    $symbol->refresh();

    expect($symbol->service_description)->toBe('Atención muy personalizada.')
        ->and($symbol->customer_greeting)->toBe('Te preparo algo especial.')
        ->and($symbol->taste_profile)->toBe('Cacao, espresso y pan dulce.')
        ->and($symbol->recommended_items)->toBe(['Espresso especial', 'Pan dulce']);
});
