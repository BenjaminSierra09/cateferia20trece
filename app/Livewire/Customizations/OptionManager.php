<?php

namespace App\Livewire\Customizations;

use App\Livewire\Concerns\SortsTables;
use App\Models\CustomizationOption;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Opciones de personalización')]
class OptionManager extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleOptionAvailability(int $optionId): void
    {
        $option = CustomizationOption::query()->findOrFail($optionId);
        $option->update(['is_available' => ! $option->is_available]);

        Flux::toast(text: $option->is_available ? 'Opción reactivada.' : 'Opción desactivada.');
    }

    public function render(): View
    {
        $query = CustomizationOption::query()->with('type');

        return view('livewire.customizations.option-manager', [
            'customizationOptions' => ($this->sortBy === '' ? $query->latest() : $this->applySorting($query))
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
            'price' => 'price',
            'is_available' => 'is_available',
            'created_at' => 'created_at',
        ];
    }
}
