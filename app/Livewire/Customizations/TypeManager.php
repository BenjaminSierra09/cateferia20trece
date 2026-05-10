<?php

namespace App\Livewire\Customizations;

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
        return view('livewire.customizations.type-manager', [
            'customizationTypes' => CustomizationType::query()
                ->withCount('options')
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
