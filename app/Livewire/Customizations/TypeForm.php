<?php

namespace App\Livewire\Customizations;

use App\Models\CustomizationType;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Tipo de personalización')]
class TypeForm extends Component
{
    use WithFileUploads;

    public ?CustomizationType $customizationType = null;

    public string $type_name = '';

    public string $selection_mode = 'single';

    public bool $type_is_active = true;

    public $type_image;

    public function mount(?CustomizationType $customizationType = null): void
    {
        $this->customizationType = $customizationType?->exists ? $customizationType : null;

        if ($this->customizationType !== null) {
            $this->type_name = $this->customizationType->name;
            $this->selection_mode = $this->customizationType->selection_mode;
            $this->type_is_active = $this->customizationType->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type_name' => ['required', 'string', 'max:255'],
            'selection_mode' => ['required', 'in:single,multiple'],
            'type_is_active' => ['boolean'],
            'type_image' => ['nullable', 'image', 'max:3072'],
        ]);

        $typeImagePath = $this->customizationType?->image_path;

        if ($this->type_image !== null) {
            $typeImagePath = app(CatalogImageManager::class)->storeSquareUpload($this->type_image, 'catalog/customization-types');
        }

        $type = CustomizationType::query()->updateOrCreate([
            'id' => $this->customizationType?->id,
        ], [
            'name' => $validated['type_name'],
            'selection_mode' => $validated['selection_mode'],
            'image_path' => $typeImagePath,
            'is_active' => $validated['type_is_active'],
        ]);

        Flux::toast(variant: 'success', text: $this->customizationType ? 'Tipo actualizado.' : 'Tipo creado.');

        $this->redirectRoute('dashboard.customizations.types.edit', ['customizationType' => $type], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.customizations.type-form')->layout('layouts.app');
    }
}
