<?php

namespace App\Livewire\Customizations;

use App\Livewire\Concerns\SortsTables;
use App\Models\CustomizationType;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tipos de personalización')]
class TypeManager extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleTypeActive(int $typeId): void
    {
        $type = CustomizationType::query()->findOrFail($typeId);
        $type->update(['is_active' => ! $type->is_active]);

        Flux::toast(text: $type->is_active ? 'Tipo reactivado.' : 'Tipo desactivado.');
    }

    public function render(): View
    {
        $query = CustomizationType::query()->withCount('options');

        return view('livewire.customizations.type-manager', [
            'customizationTypes' => ($this->sortBy === '' ? $query->latest() : $this->applySorting($query))
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
            'selection_mode' => 'selection_mode',
            'options_count' => 'options_count',
            'is_active' => 'is_active',
        ];
    }
}
