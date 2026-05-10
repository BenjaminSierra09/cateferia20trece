<?php

namespace App\Livewire\Customizations;

use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Opción de personalización')]
class OptionForm extends Component
{
    use WithFileUploads;

    public ?CustomizationOption $customizationOption = null;

    public ?int $customization_type_id = null;

    public string $option_name = '';

    public float $option_price = 0;

    public bool $is_available = true;

    public $option_image;

    public function mount(?CustomizationOption $customizationOption = null): void
    {
        $this->customizationOption = $customizationOption?->exists ? $customizationOption->load('type') : null;

        if ($this->customizationOption !== null) {
            $this->customization_type_id = $this->customizationOption->customization_type_id;
            $this->option_name = $this->customizationOption->name;
            $this->option_price = (float) $this->customizationOption->price;
            $this->is_available = $this->customizationOption->is_available;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'customization_type_id' => ['required', 'integer', 'exists:customization_types,id'],
            'option_name' => ['required', 'string', 'max:255'],
            'option_price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['boolean'],
            'option_image' => ['nullable', 'image', 'max:3072'],
        ]);

        $optionImagePath = $this->customizationOption?->image_path;

        if ($this->option_image !== null) {
            $optionImagePath = app(CatalogImageManager::class)->storeSquareUpload($this->option_image, 'catalog/customization-options');
        }

        $option = CustomizationOption::query()->updateOrCreate([
            'id' => $this->customizationOption?->id,
        ], [
            'customization_type_id' => $validated['customization_type_id'],
            'name' => $validated['option_name'],
            'image_path' => $optionImagePath,
            'price' => $validated['option_price'],
            'is_available' => $validated['is_available'],
        ]);

        Flux::toast(variant: 'success', text: $this->customizationOption ? 'Opción actualizada.' : 'Opción creada.');

        $this->redirectRoute('dashboard.customizations.options.edit', ['customizationOption' => $option], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.customizations.option-form', [
            'customizationTypes' => CustomizationType::query()->where('is_active', true)->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
