<?php

namespace App\Livewire\Sizes;

use App\Livewire\Concerns\SortsTables;
use App\Models\Size;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tamaños')]
class Manager extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $sizeId): void
    {
        $size = Size::query()->findOrFail($sizeId);
        $size->update(['is_active' => ! $size->is_active]);

        Flux::toast(text: $size->is_active ? 'Tamaño reactivado.' : 'Tamaño desactivado.');
    }

    public function render(): View
    {
        $query = Size::query()->withCount('beveragePrices');

        return view('livewire.sizes.manager', [
            'sizes' => ($this->sortBy === '' ? $query->latest() : $this->applySorting($query))
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
            'capacity_label' => 'capacity_label',
            'capacity_ounces' => 'capacity_ounces',
            'beverage_prices_count' => 'beverage_prices_count',
            'is_active' => 'is_active',
        ];
    }
}
