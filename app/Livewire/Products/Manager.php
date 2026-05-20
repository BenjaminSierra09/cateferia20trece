<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Support\InitialIndexViewModeResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Productos')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    public function mount(InitialIndexViewModeResolver $initialIndexViewModeResolver): void
    {
        $this->viewMode = $initialIndexViewModeResolver->resolve(request());
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        Flux::toast(text: $product->is_active ? 'Producto reactivado.' : 'Producto desactivado.');
    }

    public function render(): View
    {
        return view('livewire.products.manager', [
            'products' => Product::query()
                ->withCount('saleItems')
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
