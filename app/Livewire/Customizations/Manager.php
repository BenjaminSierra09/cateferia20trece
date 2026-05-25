<?php

namespace App\Livewire\Customizations;

use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Personalizaciones')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public string $typesSortBy = '';

    public string $typesSortDirection = 'asc';

    public string $optionsSortBy = '';

    public string $optionsSortDirection = 'asc';

    public function updatedPerPage(): void
    {
        $this->resetPage(pageName: 'types-page');
        $this->resetPage(pageName: 'options-page');
    }

    public function sortTypes(string $column): void
    {
        if (! array_key_exists($column, $this->typeSortableColumns())) {
            return;
        }

        if ($this->typesSortBy === $column) {
            $this->typesSortDirection = $this->typesSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->typesSortBy = $column;
            $this->typesSortDirection = 'asc';
        }

        $this->resetPage(pageName: 'types-page');
    }

    public function sortOptions(string $column): void
    {
        if (! array_key_exists($column, $this->optionSortableColumns())) {
            return;
        }

        if ($this->optionsSortBy === $column) {
            $this->optionsSortDirection = $this->optionsSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->optionsSortBy = $column;
            $this->optionsSortDirection = 'asc';
        }

        $this->resetPage(pageName: 'options-page');
    }

    public function toggleTypeActive(int $typeId): void
    {
        $type = CustomizationType::query()->findOrFail($typeId);
        $type->update(['is_active' => ! $type->is_active]);

        Flux::toast(text: $type->is_active ? 'Tipo reactivado.' : 'Tipo desactivado.');
    }

    public function toggleOptionAvailability(int $optionId): void
    {
        $option = CustomizationOption::query()->findOrFail($optionId);
        $option->update(['is_available' => ! $option->is_available]);

        Flux::toast(text: $option->is_available ? 'Opción reactivada.' : 'Opción desactivada.');
    }

    public function render(): View
    {
        $typesQuery = CustomizationType::query()->withCount('options');
        $optionsQuery = CustomizationOption::query()->with('type');

        return view('livewire.customizations.manager', [
            'customizationTypes' => ($this->typesSortBy === '' ? $typesQuery->latest() : $typesQuery->orderBy($this->typeSortableColumns()[$this->typesSortBy], $this->typesSortDirection))
                ->paginate($this->perPage, pageName: 'types-page'),
            'customizationOptions' => ($this->optionsSortBy === '' ? $optionsQuery->latest() : $optionsQuery->orderBy($this->optionSortableColumns()[$this->optionsSortBy], $this->optionsSortDirection))
                ->paginate($this->perPage, pageName: 'options-page'),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    protected function typeSortableColumns(): array
    {
        return [
            'name' => 'name',
            'selection_mode' => 'selection_mode',
            'options_count' => 'options_count',
            'is_active' => 'is_active',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function optionSortableColumns(): array
    {
        return [
            'name' => 'name',
            'price' => 'price',
            'is_available' => 'is_available',
        ];
    }
}
