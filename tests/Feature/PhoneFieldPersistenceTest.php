<?php

use App\Livewire\Branches\Create as BranchCreate;
use App\Livewire\Customers\Form as CustomerForm;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Livewire\Livewire;

test('customer form stores phone numbers in international format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CustomerForm::class)
        ->set('name', 'Cliente Demo')
        ->set('phone', '+524151234567')
        ->set('notes', 'Descuento especial autorizado.')
        ->call('save');

    $customer = Customer::query()->where('name', 'Cliente Demo')->first();

    expect($customer)->not->toBeNull();
    expect($customer->phone)->toBe('+524151234567');
    expect($customer->notes)->toBe('Descuento especial autorizado.');
});

test('branch form preserves international phone numbers on update', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create([
        'phone' => '+524151111111',
    ]);

    Livewire::actingAs($user)
        ->test(BranchCreate::class, ['branch' => $branch])
        ->set('name', $branch->name)
        ->set('city', $branch->city)
        ->set('address', $branch->address)
        ->set('phone', '+524152222222')
        ->set('operating_hours', $branch->operating_hours)
        ->set('is_active', true)
        ->call('save');

    expect($branch->fresh()->phone)->toBe('+524152222222');
});

test('customer form rejects phone numbers without country code', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CustomerForm::class)
        ->set('name', 'Cliente Sin Prefijo')
        ->set('phone', '4151234567')
        ->call('save')
        ->assertHasErrors(['phone' => ['regex']]);
});
