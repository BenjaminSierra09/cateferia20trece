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

    /**
     * @var array<int>
     */
    public array $synced_beverage_ids = [];

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
            $this->selected_beverage_ids = $this->relatedBeverageIds();
            $this->synced_beverage_ids = $this->selected_beverage_ids;
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

        $this->syncSelectedBeverages(
            array_values(array_diff($this->selected_beverage_ids, [$beverageId])),
        );

        Flux::toast(variant: 'success', text: 'La bebida se desvinculó de esta opción.');
    }

    public function removeSelectedBeverages(): void
    {
        if ($this->customizationOption === null || $this->selected_beverage_ids === []) {
            return;
        }

        $count = count($this->selected_beverage_ids);
        $this->syncSelectedBeverages([]);

        Flux::toast(variant: 'success', text: $count === 1 ? 'Se desvinculó 1 bebida.' : "Se desvincularon {$count} bebidas.");
    }

    public function render(): View
    {
        $beverages = collect();

        if ($this->customizationOption !== null) {
            $beverages = Beverage::query()
                ->with('category')
                ->orderBy('name')
                ->get();
        }

        return view('livewire.customizations.option-form', [
            'customizationTypes' => CustomizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'beverages' => $beverages,
        ])->layout('layouts.app');
    }

    public function selectAllBeverages(): void
    {
        if ($this->customizationOption === null) {
            return;
        }

        $this->syncSelectedBeverages(
            Beverage::query()->orderBy('name')->pluck('id')->all(),
        );

        Flux::toast(variant: 'success', text: 'Se vinculó esta opción con todas las bebidas.');
    }

    public function clearAllBeverages(): void
    {
        if ($this->customizationOption === null) {
            return;
        }

        $this->syncSelectedBeverages([]);

        Flux::toast(variant: 'success', text: 'Se desvinculó esta opción de todas las bebidas.');
    }

    public function updatedSelectedBeverageIds(): void
    {
        if ($this->customizationOption === null) {
            return;
        }

        $this->syncSelectedBeverages($this->selected_beverage_ids);
    }

    /**
     * @param  array<int>  $beverageIds
     */
    protected function syncSelectedBeverages(array $beverageIds): void
    {
        if ($this->customizationOption === null) {
            return;
        }

        $normalizedBeverageIds = collect($beverageIds)
            ->map(fn (mixed $beverageId): int => (int) $beverageId)
            ->filter(fn (int $beverageId): bool => $beverageId > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $beverageIdsToAttach = array_diff($normalizedBeverageIds, $this->synced_beverage_ids);
        $beverageIdsToDetach = array_diff($this->synced_beverage_ids, $normalizedBeverageIds);

        Beverage::query()
            ->whereIn('id', $beverageIdsToAttach)
            ->get()
            ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->syncWithoutDetaching([$this->customizationOption->id]));

        Beverage::query()
            ->whereIn('id', $beverageIdsToDetach)
            ->get()
            ->each(fn (Beverage $beverage) => $beverage->customizationOptions()->detach([$this->customizationOption->id]));

        $this->selected_beverage_ids = $normalizedBeverageIds;
        $this->synced_beverage_ids = $normalizedBeverageIds;
    }

    /**
     * @return array<int>
     */
    protected function relatedBeverageIds(): array
    {
        if ($this->customizationOption === null) {
            return [];
        }

        return $this->customizationOption->beverages()
            ->orderBy('name')
            ->pluck('beverages.id')
            ->all();
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
