<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Services\SaleService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalles de la venta')]
class Show extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        abort_if(
            auth()->user()?->hasLimitedAccountingView()
            && in_array($sale->payment_method, [PaymentMethod::Cash, PaymentMethod::Mixed], true),
            403,
        );

        $this->sale = $sale->load(['branch', 'customer', 'user', 'items.customizations', 'debtMovements']);
    }

    public function cancelSale(SaleService $saleService): void
    {
        if (! $this->sale->canBeCancelled()) {
            Flux::toast(text: 'Esa venta ya estaba cancelada.');

            return;
        }

        try {
            $this->sale = $saleService->cancel($this->sale);
        } catch (InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: 'Venta cancelada. Ya puedes capturarla nuevamente con los datos correctos.');
    }

    public function render(): View
    {
        return view('livewire.sales.show', [
            'sale' => $this->sale,
        ])->layout('layouts.app');
    }
}
