<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\SortsTables;
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
    use SortsTables;
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
        $query = Product::query()->withCount('saleItems');

        return view('livewire.products.manager', [
            'products' => ($this->sortBy === '' ? $query->latest() : $this->applySorting($query))
                ->paginate($this->perPage),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'unit_type' => 'unit_type',
            'base_price' => 'base_price',
            'sale_items_count' => 'sale_items_count',
            'is_active' => 'is_active',
        ];
    }
}
