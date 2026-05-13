<?php

use App\Enums\PaymentMethod;
use App\Livewire\Reports\Overview;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Livewire\Livewire;

test('visual reports page prepares friendly date badge and compact chart labels', function () {
    $admin = User::factory()->admin()->create();
    $branch = Branch::factory()->create(['name' => 'Sucursal Centro Histórico']);
    $sale = Sale::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $admin->id,
        'payment_method' => PaymentMethod::Transfer,
    ]);

    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'item_name' => 'Chocolate con almendra y caramelo extra grande',
        'line_total' => 240.00,
    ]);

    $this->actingAs($admin);

    Livewire::test(Overview::class)
        ->assertSet('topBeveragesChart.0.bebida_corta', 'Chocolate con alme…')
        ->assertSet('paymentChart.0.metodo_corto', 'Transferencia');

    $this->get(route('dashboard.reports.index', ['view' => 'visual']))
        ->assertOk()
        ->assertSee('Periodo actual');
});
