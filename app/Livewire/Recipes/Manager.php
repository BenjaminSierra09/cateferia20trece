<?php

namespace App\Livewire\Recipes;

use App\Models\Beverage;
use App\Models\CustomizationType;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Recetas')]
class Manager extends Component
{
    #[Url(as: 'tab', keep: true)]
    public string $tab = 'bebidas';

    public string $search = '';

    /**
     * @return Collection<int, Beverage>
     */
    #[Computed]
    public function beverages(): Collection
    {
        return Beverage::query()
            ->where('is_active', true)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('recipeLines')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('recipeLines')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, CustomizationType>
     */
    #[Computed]
    public function types(): Collection
    {
        return CustomizationType::query()
            ->where('is_active', true)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('recipeLines')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.recipes.manager')->layout('layouts.app');
    }
}
