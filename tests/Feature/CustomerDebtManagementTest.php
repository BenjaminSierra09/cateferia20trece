<?php

use App\Enums\CustomerDebtMovementType;
use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Customers\Manager as CustomerManager;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerDebtService;
use Livewire\Livewire;

test('customer form can register debt and payment movements', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'name' => 'Benjamín Flores',
        'reward_balance' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(CustomerForm::class, ['customer' => $customer])
        ->set('debt_amount', '150')
        ->set('debt_notes', 'Faltó cambio en caja')
        ->call('registerDebt')
        ->assertSet('debt_amount', '')
        ->assertSet('debt_notes', '');

    $customer->refresh();

    expect($customer->debtBalance())->toBe(150.0);
    expect($customer->debtMovements()->count())->toBe(1);
    expect($customer->debtMovements()->first()->notes)->toBe('Faltó cambio en caja');

    Livewire::actingAs($user)
        ->test(CustomerForm::class, ['customer' => $customer->fresh()])
        ->set('debt_amount', '50')
        ->set('debt_notes', 'Abono parcial')
        ->call('registerPayment');

    $customer->refresh();

    expect($customer->debtBalance())->toBe(100.0);
    expect($customer->debtMovements()->count())->toBe(2);
});

test('customer form prevents payments larger than the current debt', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create();

    Livewire::actingAs($user)
        ->test(CustomerForm::class, ['customer' => $customer])
        ->set('debt_amount', '40')
        ->call('registerDebt');

    Livewire::actingAs($user)
        ->test(CustomerForm::class, ['customer' => $customer->fresh()])
        ->set('debt_amount', '80')
        ->call('registerPayment')
        ->assertHasErrors(['debt_amount']);
});

test('customer manager shows debt information for customers with open balances', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();
    $customer = Customer::factory()->create([
        'name' => 'Cliente con adeudo',
    ]);

    app(CustomerDebtService::class)->register(
        customer: $customer,
        type: CustomerDebtMovementType::Debt,
        amount: 75,
        user: $user,
        branchId: $branch->id,
    );

    Livewire::actingAs($user)
        ->test(CustomerManager::class)
        ->assertSee('Cliente con adeudo')
        ->assertSee('Debe')
        ->assertSee('$75.00');
});
