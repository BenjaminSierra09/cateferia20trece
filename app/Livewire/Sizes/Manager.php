<?php

namespace App\Livewire\Sizes;

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
        return view('livewire.sizes.manager', [
            'sizes' => Size::query()
                ->withCount('beveragePrices')
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
