<?php

namespace App\Livewire\Customizations;

use App\Models\Beverage;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Title('Opción de personalización')]
class OptionForm extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    public ?CustomizationOption $customizationOption = null;

    public ?int $customization_type_id = null;

    public string $option_name = '';

    public float $option_price = 0;

    public bool $is_available = true;

    public $option_image;

    /**
     * @var array<int>
     */
    public array $selected_beverage_ids = [];

    public function generateImage(): void
    {
        $wasCreating = $this->customizationOption === null;
        $option = $this->persistForImageGeneration();

        try {
            app(CatalogImageManager::class)->generateImageOrFail($option, force: true);
        } catch (RuntimeException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->option_image = null;
        $this->customizationOption = $option->fresh(['type']);

        Flux::toast(variant: 'success', text: 'Imagen generada correctamente.');

        if ($wasCreating) {
            $this->redirectRoute('dashboard.customizations.options.edit', ['customizationOption' => $option], navigate: true);
        }
    }

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

    public function removeBeverage(int $beverageId): void
    {
        if ($this->customizationOption === null) {
            return;
        }

        Beverage::query()->findOrFail($beverageId)
            ->customizationOptions()
            ->detach($this->customizationOption->id);

        Flux::toast(variant: 'success', text: 'La bebida se desvinculó de esta opción.');
    }

    public function removeSelectedBeverages(): void
    {
        if ($this->customizationOption === null || $this->selected_beverage_ids === []) {
            return;
        }

        Beverage::query()
            ->whereIn('id', $this->selected_beverage_ids)
            ->get()
            ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->detach($this->customizationOption?->id));

        $count = count($this->selected_beverage_ids);
        $this->selected_beverage_ids = [];

        Flux::toast(variant: 'success', text: $count === 1 ? 'Se desvinculó 1 bebida.' : "Se desvincularon {$count} bebidas.");
    }

    public function render(): View
    {
        return view('livewire.customizations.option-form', [
            'customizationTypes' => CustomizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'relatedBeverages' => $this->customizationOption?->beverages()->with('category')->orderBy('name')->get() ?? collect(),
        ])->layout('layouts.app');
    }

    protected function persistForImageGeneration(): CustomizationOption
    {
        $validated = $this->validate([
            'customization_type_id' => ['required', 'integer', 'exists:customization_types,id'],
            'option_name' => ['required', 'string', 'max:255'],
            'option_price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['boolean'],
        ]);

        return CatalogImageManager::withoutQueueing(function () use ($validated): CustomizationOption {
            return CustomizationOption::query()->updateOrCreate([
                'id' => $this->customizationOption?->id,
            ], [
                'customization_type_id' => $validated['customization_type_id'],
                'name' => $validated['option_name'],
                'image_path' => $this->customizationOption?->image_path,
                'price' => $validated['option_price'],
                'is_available' => $validated['is_available'],
            ]);
        });
    }
}
