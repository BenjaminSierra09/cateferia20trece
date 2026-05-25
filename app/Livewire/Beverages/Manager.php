<?php

namespace App\Livewire\Beverages;

use App\Livewire\Concerns\SortsTables;
use App\Models\Beverage;
use App\Support\InitialIndexViewModeResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bebidas')]
class Manager extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    /**
     * @var array<int>
     */
    public array $selectedBeverageIds = [];

    public bool $selectPage = false;

    public function mount(InitialIndexViewModeResolver $initialIndexViewModeResolver): void
    {
        $this->viewMode = $initialIndexViewModeResolver->resolve(request());
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectedBeverageIds(): void
    {
        $this->syncSelectionState();
    }

    public function togglePageSelection(): void
    {
        $visibleIds = $this->visibleBeverageIds();

        if ($this->selectPage) {
            $this->selectedBeverageIds = array_values(array_diff($this->selectedBeverageIds, $visibleIds));
            $this->selectPage = false;

            return;
        }

        $this->selectedBeverageIds = array_values(array_unique([...$this->selectedBeverageIds, ...$visibleIds]));
        $this->selectPage = true;
    }

    public function clearSelection(): void
    {
        $this->selectedBeverageIds = [];
        $this->selectPage = false;
    }

    public function deactivateSelected(): void
    {
        if ($this->selectedBeverageIds === []) {
            return;
        }

        Beverage::query()->whereKey($this->selectedBeverageIds)->update(['is_active' => false]);

        $this->clearSelection();

        Flux::toast(text: 'Bebidas desactivadas.');
    }

    public function reactivateSelected(): void
    {
        if ($this->selectedBeverageIds === []) {
            return;
        }

        Beverage::query()->whereKey($this->selectedBeverageIds)->update(['is_active' => true]);

        $this->clearSelection();

        Flux::toast(text: 'Bebidas reactivadas.');
    }

    public function toggleActive(int $beverageId): void
    {
        $beverage = Beverage::query()->findOrFail($beverageId);
        $beverage->update(['is_active' => ! $beverage->is_active]);

        Flux::toast(text: $beverage->is_active ? 'Bebida reactivada.' : 'Bebida desactivada.');
    }

    protected function beverageQuery(): Builder
    {
        $query = Beverage::query()->with(['category', 'customizationOptions.type', 'sizePrices.size']);

        return $this->sortBy === '' ? $query->latest() : $this->applySorting($query);
    }

    /**
     * @return array<int>
     */
    protected function visibleBeverageIds(): array
    {
        return $this->beverageQuery()
            ->paginate($this->perPage)
            ->pluck('id')
            ->all();
    }

    protected function syncSelectionState(): void
    {
        $visibleIds = $this->visibleBeverageIds();

        $this->selectPage = $visibleIds !== []
            && count(array_intersect($visibleIds, $this->selectedBeverageIds)) === count($visibleIds);
    }

    public function render(): View
    {
        return view('livewire.beverages.manager', [
            'beverages' => $this->beverageQuery()->paginate($this->perPage),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'base_price' => 'base_price',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
        ];
    }
}
