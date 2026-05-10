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

    public function updatedPerPage(): void
    {
        $this->resetPage(pageName: 'types-page');
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
        return view('livewire.customizations.manager', [
            'customizationTypes' => CustomizationType::query()
                ->withCount('options')
                ->latest()
                ->paginate($this->perPage, pageName: 'types-page'),
            'customizationOptions' => CustomizationOption::query()
                ->with('type')
                ->latest()
                ->paginate($this->perPage, pageName: 'options-page'),
        ])->layout('layouts.app');
    }
}
