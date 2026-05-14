<?php

namespace App\Livewire\Customizations;

use App\Models\Beverage;
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

    public string $activeTab = 'general';

    public ?CustomizationType $customizationType = null;

    public string $type_name = '';

    public string $selection_mode = 'single';

    public bool $type_is_active = true;

    public $type_image;

    /**
     * @var array<int>
     */
    public array $selected_beverage_ids = [];

    public function generateImage(): void
    {
        $wasCreating = $this->customizationType === null;
        $type = $this->persistForImageGeneration();

        $generated = app(CatalogImageManager::class)->generateImage($type);

        if (! $generated) {
            Flux::toast(variant: 'danger', text: 'No se pudo generar la imagen en este momento.');

            return;
        }

        $this->type_image = null;
        $this->customizationType = $type->fresh();

        Flux::toast(variant: 'success', text: 'Imagen generada correctamente.');

        if ($wasCreating) {
            $this->redirectRoute('dashboard.customizations.types.edit', ['customizationType' => $type], navigate: true);
        }
    }

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

    public function removeBeverage(int $beverageId): void
    {
        if ($this->customizationType === null) {
            return;
        }

        $optionIds = $this->customizationType->options()->pluck('customization_options.id');

        Beverage::query()->findOrFail($beverageId)
            ->customizationOptions()
            ->detach($optionIds);

        Flux::toast(variant: 'success', text: 'La bebida se desvinculó de este tipo.');
    }

    public function removeSelectedBeverages(): void
    {
        if ($this->customizationType === null || $this->selected_beverage_ids === []) {
            return;
        }

        $optionIds = $this->customizationType->options()->pluck('customization_options.id');

        Beverage::query()
            ->whereIn('id', $this->selected_beverage_ids)
            ->get()
            ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->detach($optionIds));

        $count = count($this->selected_beverage_ids);
        $this->selected_beverage_ids = [];

        Flux::toast(variant: 'success', text: $count === 1 ? 'Se desvinculó 1 bebida.' : "Se desvincularon {$count} bebidas.");
    }

    public function render(): View
    {
        $relatedBeverages = collect();

        if ($this->customizationType !== null) {
            $relatedBeverages = Beverage::query()
                ->whereHas('customizationOptions', function ($query): void {
                    $query->whereIn(
                        'customization_options.id',
                        $this->customizationType?->options()->pluck('customization_options.id') ?? []
                    );
                })
                ->with(['category', 'customizationOptions' => function ($query): void {
                    $query->where('customization_type_id', $this->customizationType?->id)
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        }

        return view('livewire.customizations.type-form', [
            'relatedBeverages' => $relatedBeverages,
        ])->layout('layouts.app');
    }

    protected function persistForImageGeneration(): CustomizationType
    {
        $validated = $this->validate([
            'type_name' => ['required', 'string', 'max:255'],
            'selection_mode' => ['required', 'in:single,multiple'],
            'type_is_active' => ['boolean'],
        ]);

        return CatalogImageManager::withoutQueueing(function () use ($validated): CustomizationType {
            return CustomizationType::query()->updateOrCreate([
                'id' => $this->customizationType?->id,
            ], [
                'name' => $validated['type_name'],
                'selection_mode' => $validated['selection_mode'],
                'image_path' => $this->customizationType?->image_path,
                'is_active' => $validated['type_is_active'],
            ]);
        });
    }
}
