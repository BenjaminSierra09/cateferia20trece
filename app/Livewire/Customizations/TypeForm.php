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
use RuntimeException;

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

    /**
     * @var array<int>
     */
    public array $synced_beverage_ids = [];

    public function generateImage(): void
    {
        $wasCreating = $this->customizationType === null;
        $type = $this->persistForImageGeneration();

        try {
            app(CatalogImageManager::class)->generateImageOrFail($type, force: true);
        } catch (RuntimeException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

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
            $this->selected_beverage_ids = $this->relatedBeverageIds();
            $this->synced_beverage_ids = $this->selected_beverage_ids;
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

        $this->syncSelectedBeverages(
            array_values(array_diff($this->selected_beverage_ids, [$beverageId])),
        );

        Flux::toast(variant: 'success', text: 'La bebida se desvinculó de este tipo.');
    }

    public function removeSelectedBeverages(): void
    {
        if ($this->customizationType === null || $this->selected_beverage_ids === []) {
            return;
        }

        $count = count($this->selected_beverage_ids);
        $this->syncSelectedBeverages([]);

        Flux::toast(variant: 'success', text: $count === 1 ? 'Se desvinculó 1 bebida.' : "Se desvincularon {$count} bebidas.");
    }

    public function selectAllBeverages(): void
    {
        if ($this->customizationType === null) {
            return;
        }

        $this->syncSelectedBeverages(
            Beverage::query()->orderBy('name')->pluck('id')->all(),
        );

        Flux::toast(variant: 'success', text: 'Se vincularon todas las bebidas con este tipo.');
    }

    public function clearAllBeverages(): void
    {
        if ($this->customizationType === null) {
            return;
        }

        $this->syncSelectedBeverages([]);

        Flux::toast(variant: 'success', text: 'Se desvincularon todas las bebidas de este tipo.');
    }

    public function updatedSelectedBeverageIds(): void
    {
        if ($this->customizationType === null) {
            return;
        }

        $this->syncSelectedBeverages($this->selected_beverage_ids);
    }

    public function render(): View
    {
        $beverages = collect();

        if ($this->customizationType !== null) {
            $beverages = Beverage::query()
                ->with(['category', 'customizationOptions' => function ($query): void {
                    $query->where('customization_type_id', $this->customizationType?->id)
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
        }

        return view('livewire.customizations.type-form', [
            'beverages' => $beverages,
        ])->layout('layouts.app');
    }

    /**
     * @param  array<int>  $beverageIds
     */
    protected function syncSelectedBeverages(array $beverageIds): void
    {
        if ($this->customizationType === null) {
            return;
        }

        $normalizedBeverageIds = collect($beverageIds)
            ->map(fn (mixed $beverageId): int => (int) $beverageId)
            ->filter(fn (int $beverageId): bool => $beverageId > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $optionIds = $this->customizationType->options()->pluck('customization_options.id')->all();

        $beverageIdsToAttach = array_diff($normalizedBeverageIds, $this->synced_beverage_ids);
        $beverageIdsToDetach = array_diff($this->synced_beverage_ids, $normalizedBeverageIds);

        if ($optionIds !== []) {
            Beverage::query()
                ->whereIn('id', $beverageIdsToAttach)
                ->get()
                ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->syncWithoutDetaching($optionIds));

            Beverage::query()
                ->whereIn('id', $beverageIdsToDetach)
                ->get()
                ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->detach($optionIds));
        }

        $this->selected_beverage_ids = $normalizedBeverageIds;
        $this->synced_beverage_ids = $normalizedBeverageIds;
    }

    /**
     * @return array<int>
     */
    protected function relatedBeverageIds(): array
    {
        if ($this->customizationType === null) {
            return [];
        }

        return Beverage::query()
            ->whereHas('customizationOptions', function ($query): void {
                $query->whereIn(
                    'customization_options.id',
                    $this->customizationType?->options()->pluck('customization_options.id') ?? []
                );
            })
            ->orderBy('name')
            ->pluck('id')
            ->all();
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
