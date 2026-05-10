<?php

namespace App\Livewire\Categories;

use App\Models\BeverageCategory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Categorías')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $categoryId): void
    {
        $category = BeverageCategory::query()->findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);

        Flux::toast(text: $category->is_active ? 'Categoría reactivada.' : 'Categoría desactivada.');
    }

    public function render(): View
    {
        return view('livewire.categories.manager', [
            'categories' => BeverageCategory::query()
                ->withCount('beverages')
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
