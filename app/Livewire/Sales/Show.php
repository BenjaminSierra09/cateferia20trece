<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalles de la venta')]
class Show extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        $this->sale = $sale->load(['branch', 'customer', 'user', 'items.customizations']);
    }

    public function render(): View
    {
        return view('livewire.sales.show', [
            'sale' => $this->sale,
        ])->layout('layouts.app');
    }
}
