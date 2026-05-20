<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use App\Support\InitialIndexViewModeResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sucursales')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    /**
     * @var array<int>
     */
    public array $selectedBranchIds = [];

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

    public function updatedSelectedBranchIds(): void
    {
        $this->syncSelectionState();
    }

    public function togglePageSelection(): void
    {
        $visibleIds = $this->visibleBranchIds();

        if ($this->selectPage) {
            $this->selectedBranchIds = array_values(array_diff($this->selectedBranchIds, $visibleIds));
            $this->selectPage = false;

            return;
        }

        $this->selectedBranchIds = array_values(array_unique([...$this->selectedBranchIds, ...$visibleIds]));
        $this->selectPage = true;
    }

    public function clearSelection(): void
    {
        $this->selectedBranchIds = [];
        $this->selectPage = false;
    }

    public function deactivateSelected(): void
    {
        if ($this->selectedBranchIds === []) {
            return;
        }

        Branch::query()->whereKey($this->selectedBranchIds)->update(['is_active' => false]);

        $this->clearSelection();

        Flux::toast(text: 'Sucursales desactivadas.');
    }

    public function reactivateSelected(): void
    {
        if ($this->selectedBranchIds === []) {
            return;
        }

        Branch::query()->whereKey($this->selectedBranchIds)->update(['is_active' => true]);

        $this->clearSelection();

        Flux::toast(text: 'Sucursales reactivadas.');
    }

    public function toggleActive(int $branchId): void
    {
        $branch = Branch::query()->findOrFail($branchId);
        $branch->update(['is_active' => ! $branch->is_active]);

        Flux::toast(text: $branch->is_active ? 'Sucursal reactivada.' : 'Sucursal desactivada.');
    }

    protected function branchQuery(): Builder
    {
        return Branch::query()
            ->withCount(['sales', 'workSessions'])
            ->latest();
    }

    /**
     * @return array<int>
     */
    protected function visibleBranchIds(): array
    {
        return $this->branchQuery()
            ->paginate($this->perPage)
            ->pluck('id')
            ->all();
    }

    protected function syncSelectionState(): void
    {
        $visibleIds = $this->visibleBranchIds();

        $this->selectPage = $visibleIds !== []
            && count(array_intersect($visibleIds, $this->selectedBranchIds)) === count($visibleIds);
    }

    public function render(): View
    {
        return view('livewire.branches.manager', [
            'branches' => $this->branchQuery()->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
