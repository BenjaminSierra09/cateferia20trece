<?php

namespace App\Livewire\Beverages;

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\BeverageCustomizationTypeSetting;
use App\Models\Branch;
use App\Models\BranchBeveragePriceOverride;
use App\Models\BranchBeverageSizeAvailability;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use App\Support\BeverageTemperatureCustomization;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Title('Nueva bebida')]
class Create extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    public ?Beverage $beverage = null;

    public string $name = '';

    public string $description = '';

    public ?int $beverage_category_id = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $size_pricing = [];

    /**
     * @var array<int>
     */
    public array $selected_customization_option_ids = [];

    /**
     * @var array<int>
     */
    public array $default_customization_option_ids = [];

    /**
     * @var array<int|string, array{sort_order:int, is_open_by_default:bool}>
     */
    public array $customization_type_settings = [];

    /**
     * @var array<int>
     */
    public array $collapsed_customization_type_ids = [];

    public bool $is_active = true;

    public $image;

    public function generateImage(): void
    {
        $wasCreating = $this->beverage === null;
        $beverage = $this->persistForImageGeneration();

        try {
            app(CatalogImageManager::class)->generateImageOrFail($beverage, force: true);
        } catch (RuntimeException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->image = null;
        $this->beverage = $beverage->fresh(['sizePrices', 'customizationOptions']);

        Flux::toast(variant: 'success', text: 'Imagen generada correctamente.');

        if ($wasCreating) {
            $this->redirectRoute('dashboard.beverages.edit', ['beverage' => $beverage], navigate: true);
        }
    }

    public function selectAllCustomizationOptions(?int $typeId = null): void
    {
        $availableOptionIds = CustomizationType::query()
            ->when($typeId !== null, fn ($query) => $query->whereKey($typeId))
            ->with(['options' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (CustomizationType $type) => $type->options->pluck('id'))
            ->all();

        $this->selected_customization_option_ids = collect($this->selected_customization_option_ids)
            ->merge($availableOptionIds)
            ->map(fn (mixed $optionId): int => (int) $optionId)
            ->sort()
            ->unique()
            ->values()
            ->all();
    }

    public function clearCustomizationOptions(?int $typeId = null): void
    {
        if ($typeId === null) {
            $this->selected_customization_option_ids = [];
            $this->default_customization_option_ids = [];
            $this->sanitizeDefaultCustomizationOptions();

            return;
        }

        $optionIdsToRemove = CustomizationType::query()
            ->whereKey($typeId)
            ->with(['options' => fn ($query) => $query->where('is_available', true)])
            ->get()
            ->flatMap(fn (CustomizationType $type) => $type->options->pluck('id'))
            ->map(fn (mixed $optionId): int => (int) $optionId)
            ->all();

        $this->selected_customization_option_ids = collect($this->selected_customization_option_ids)
            ->reject(fn (mixed $optionId): bool => in_array((int) $optionId, $optionIdsToRemove, true))
            ->map(fn (mixed $optionId): int => (int) $optionId)
            ->sort()
            ->values()
            ->all();

        $this->sanitizeDefaultCustomizationOptions();
    }

    public function updatedSelectedCustomizationOptionIds(mixed $value = null): void
    {
        $this->sanitizeDefaultCustomizationOptions();
    }

    public function updatedDefaultCustomizationOptionIds(mixed $value = null): void
    {
        $this->sanitizeDefaultCustomizationOptions();
    }

    public function sortCustomizationType(int $customizationTypeId, int $position): void
    {
        if ($customizationTypeId === app(BeverageTemperatureCustomization::class)->typeId()) {
            return;
        }

        $orderedTypeIds = collect($this->customization_type_settings)
            ->sortBy('sort_order')
            ->keys()
            ->map(fn (int|string $typeId): int => (int) $typeId)
            ->reject(fn (int $typeId): bool => $typeId === $customizationTypeId)
            ->values();

        $orderedTypeIds->splice($position, 0, [$customizationTypeId]);

        $orderedTypeIds->each(function (int $typeId, int $sortOrder): void {
            $this->customization_type_settings[$typeId] = [
                'sort_order' => $sortOrder,
                'is_open_by_default' => (bool) ($this->customization_type_settings[$typeId]['is_open_by_default'] ?? false),
            ];
        });
        $this->customization_type_settings = app(BeverageTemperatureCustomization::class)
            ->normalizeSettings($this->customization_type_settings);

        if ($this->beverage !== null) {
            $this->persistCustomizationTypeSettings($this->beverage, $this->selected_customization_option_ids);
        }
    }

    public function toggleCustomizationTypeOptions(int $customizationTypeId): void
    {
        $collapsedTypeIds = collect($this->collapsed_customization_type_ids)
            ->map(fn (mixed $typeId): int => (int) $typeId);

        $this->collapsed_customization_type_ids = $collapsedTypeIds->contains($customizationTypeId)
            ? $collapsedTypeIds->reject(fn (int $typeId): bool => $typeId === $customizationTypeId)->values()->all()
            : $collapsedTypeIds->push($customizationTypeId)->unique()->values()->all();
    }

    public function collapseAllCustomizationTypes(): void
    {
        $this->collapsed_customization_type_ids = CustomizationType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn (mixed $typeId): int => (int) $typeId)
            ->all();
    }

    public function expandAllCustomizationTypes(): void
    {
        $this->collapsed_customization_type_ids = [];
    }

    public function mount(?Beverage $beverage = null): void
    {
        $this->beverage = $beverage?->exists
            ? $beverage->load(['sizePrices', 'customizationOptions', 'customizationTypeSettings'])
            : null;

        if ($this->beverage !== null) {
            app(BeverageTemperatureCustomization::class)->applyToBeverage($this->beverage, preserveExistingDefault: true);
            $this->beverage = $this->beverage->fresh(['sizePrices', 'customizationOptions', 'customizationTypeSettings']);
            $this->name = $this->beverage->name;
            $this->description = $this->beverage->description ?? '';
            $this->beverage_category_id = $this->beverage->beverage_category_id;
            $this->is_active = $this->beverage->is_active;
            $this->selected_customization_option_ids = $this->beverage->customizationOptions->pluck('id')->all();
            $this->default_customization_option_ids = $this->beverage->customizationOptions
                ->filter(fn (CustomizationOption $option): bool => (bool) $option->pivot?->is_default)
                ->pluck('id')
                ->values()
                ->all();
        }

        $this->sanitizeDefaultCustomizationOptions();
        $this->size_pricing = $this->buildSizePricingRows();
        $this->customization_type_settings = $this->buildCustomizationTypeSettingsRows();
        $this->collapsed_customization_type_ids = $this->buildCollapsedCustomizationTypeIds();
    }

    public function save(): void
    {
        $this->size_pricing = collect($this->size_pricing)
            ->map(function (array $pricing): array {
                $pricing['price'] = $pricing['price'] === '' ? null : $pricing['price'];
                $pricing['branch_prices'] = collect($pricing['branch_prices'] ?? [])
                    ->map(fn (mixed $price): mixed => $price === '' ? null : $price)
                    ->all();

                return $pricing;
            })
            ->all();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'beverage_category_id' => ['required', 'integer', 'exists:beverage_categories,id'],
            'size_pricing' => ['required', 'array', 'min:1'],
            'size_pricing.*.size_id' => ['required', 'integer', 'exists:sizes,id'],
            'size_pricing.*.enabled' => ['boolean'],
            'size_pricing.*.price' => ['nullable', 'numeric', 'min:0'],
            'size_pricing.*.branch_prices' => ['nullable', 'array'],
            'size_pricing.*.branch_prices.*' => ['nullable', 'numeric', 'min:0'],
            'size_pricing.*.branch_availability' => ['nullable', 'array'],
            'size_pricing.*.branch_availability.*' => ['boolean'],
            'selected_customization_option_ids' => ['nullable', 'array'],
            'selected_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'default_customization_option_ids' => ['nullable', 'array'],
            'default_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'customization_type_settings' => ['nullable', 'array'],
            'customization_type_settings.*.sort_order' => ['integer', 'min:0'],
            'customization_type_settings.*.is_open_by_default' => ['boolean'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
        ]);

        $enabledSizePricing = collect($validated['size_pricing'])
            ->filter(function (array $pricing, int $index): bool {
                if (! ($pricing['enabled'] ?? false)) {
                    return false;
                }

                if ($pricing['price'] === null || $pricing['price'] === '') {
                    $this->addError("size_pricing.{$index}.price", 'Captura el precio general para este tamaño.');
                }

                return true;
            })
            ->values();

        if ($enabledSizePricing->isEmpty()) {
            $this->addError('size_pricing', 'Activa al menos un tamaño para la bebida.');

            return;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $imagePath = $this->beverage?->image_path;

        if ($this->image !== null) {
            $imagePath = app(CatalogImageManager::class)->storeSquareUpload($this->image, 'catalog/beverages');
        }

        $basePrice = (float) $enabledSizePricing->min(fn (array $pricing): float => (float) $pricing['price']);

        $beverage = Beverage::query()->updateOrCreate([
            'id' => $this->beverage?->id,
        ], [
            'beverage_category_id' => $validated['beverage_category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'base_price' => $basePrice,
            'is_active' => $validated['is_active'],
        ]);

        $selectedSizeIds = $enabledSizePricing->pluck('size_id')->all();

        $beverage->sizePrices()->whereNotIn('size_id', $selectedSizeIds)->delete();

        BranchBeveragePriceOverride::query()
            ->where('beverage_id', $beverage->id)
            ->whereNotIn('size_id', $selectedSizeIds)
            ->delete();

        BranchBeverageSizeAvailability::query()
            ->where('beverage_id', $beverage->id)
            ->whereNotIn('size_id', $selectedSizeIds)
            ->delete();

        foreach ($enabledSizePricing as $pricing) {
            $price = round((float) $pricing['price'], 2);

            $beverage->sizePrices()->updateOrCreate(
                ['size_id' => $pricing['size_id']],
                ['price' => $price, 'is_active' => true],
            );

            foreach ($pricing['branch_prices'] ?? [] as $branchId => $branchPrice) {
                $branchPrice = $branchPrice === null || $branchPrice === '' ? null : round((float) $branchPrice, 2);

                if ($branchPrice === null || $branchPrice === $price) {
                    BranchBeveragePriceOverride::query()
                        ->where('branch_id', (int) $branchId)
                        ->where('beverage_id', $beverage->id)
                        ->where('size_id', $pricing['size_id'])
                        ->delete();

                    continue;
                }

                BranchBeveragePriceOverride::query()->updateOrCreate(
                    [
                        'branch_id' => (int) $branchId,
                        'beverage_id' => $beverage->id,
                        'size_id' => $pricing['size_id'],
                    ],
                    [
                        'price' => $branchPrice,
                    ],
                );
            }

            foreach ($pricing['branch_availability'] ?? [] as $branchId => $isAvailable) {
                if ((bool) $isAvailable) {
                    BranchBeverageSizeAvailability::query()
                        ->where('branch_id', (int) $branchId)
                        ->where('beverage_id', $beverage->id)
                        ->where('size_id', $pricing['size_id'])
                        ->delete();

                    continue;
                }

                BranchBeverageSizeAvailability::query()->updateOrCreate(
                    [
                        'branch_id' => (int) $branchId,
                        'beverage_id' => $beverage->id,
                        'size_id' => $pricing['size_id'],
                    ],
                    [
                        'is_available' => false,
                    ],
                );
            }
        }

        $selectedOptionIds = $this->sanitizeOptionIds($validated['selected_customization_option_ids'] ?? []);
        $defaultOptionIds = $this->sanitizeOptionIds($validated['default_customization_option_ids'] ?? []);

        $this->syncCustomizationOptions($beverage, $selectedOptionIds, $defaultOptionIds);
        $this->persistCustomizationTypeSettings($beverage, $selectedOptionIds);

        Flux::toast(variant: 'success', text: $this->beverage ? 'Bebida actualizada.' : 'Bebida creada.');

        $this->redirectRoute('dashboard.beverages.edit', ['beverage' => $beverage], navigate: true);
    }

    /**
     * Build the editable size pricing matrix for the form.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildSizePricingRows(): array
    {
        $sizes = Size::query()
            ->where('is_active', true)
            ->orderBy('capacity_ounces')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $existingPrices = $this->beverage?->sizePrices
            ?->keyBy('size_id')
            ?? collect();

        $existingOverrides = $this->beverage === null
            ? collect()
            : BranchBeveragePriceOverride::query()
                ->where('beverage_id', $this->beverage->id)
                ->get()
                ->groupBy('size_id');

        $existingAvailabilityRules = $this->beverage === null
            ? collect()
            : BranchBeverageSizeAvailability::query()
                ->where('beverage_id', $this->beverage->id)
                ->get()
                ->groupBy('size_id');

        return $sizes->map(function (Size $size) use ($branches, $existingAvailabilityRules, $existingOverrides, $existingPrices): array {
            $priceRecord = $existingPrices->get($size->id);
            $sizeOverrides = $existingOverrides->get($size->id, collect())->keyBy('branch_id');
            $sizeAvailabilityRules = $existingAvailabilityRules->get($size->id, collect())->keyBy('branch_id');

            return [
                'size_id' => $size->id,
                'size_name' => $size->name,
                'capacity_label' => $size->capacity_label,
                'enabled' => $priceRecord !== null,
                'price' => $priceRecord?->price,
                'branch_prices' => $branches
                    ->mapWithKeys(fn (Branch $branch): array => [
                        (string) $branch->id => $sizeOverrides->get($branch->id)?->price,
                    ])
                    ->all(),
                'branch_availability' => $branches
                    ->mapWithKeys(fn (Branch $branch): array => [
                        (string) $branch->id => (bool) ($sizeAvailabilityRules->get($branch->id)?->is_available ?? true),
                    ])
                    ->all(),
            ];
        })->all();
    }

    public function render(): View
    {
        $customizationTypes = CustomizationType::query()
            ->with(['options' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $this->ensureCustomizationTypeSettings($customizationTypes);

        return view('livewire.beverages.create', [
            'categories' => BeverageCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'customizationTypes' => $customizationTypes
                ->sortBy(fn (CustomizationType $type): string => sprintf(
                    '%010d-%s',
                    $this->customization_type_settings[$type->id]['sort_order'] ?? PHP_INT_MAX,
                    $type->name,
                ))
                ->values(),
        ])->layout('layouts.app');
    }

    /**
     * Build editable customization category settings for the form.
     *
     * @return array<int, array{sort_order:int, is_open_by_default:bool}>
     */
    protected function buildCustomizationTypeSettingsRows(): array
    {
        $existingSettings = $this->beverage?->customizationTypeSettings?->keyBy('customization_type_id') ?? collect();

        return CustomizationType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->values()
            ->mapWithKeys(function (CustomizationType $type, int $index) use ($existingSettings): array {
                $setting = $existingSettings->get($type->id);

                return [
                    $type->id => [
                        'sort_order' => (int) ($setting?->sort_order ?? $index),
                        'is_open_by_default' => (bool) ($setting?->is_open_by_default ?? false),
                    ],
                ];
            })
            ->pipe(fn ($settings): array => app(BeverageTemperatureCustomization::class)->normalizeSettings($settings->all()));
    }

    /**
     * Start large option groups collapsed so category ordering stays manageable.
     *
     * @return array<int>
     */
    protected function buildCollapsedCustomizationTypeIds(): array
    {
        return CustomizationType::query()
            ->withCount(['options' => fn ($query) => $query->where('is_available', true)])
            ->where('is_active', true)
            ->get()
            ->filter(fn (CustomizationType $type): bool => $type->options_count > 4)
            ->pluck('id')
            ->map(fn (mixed $typeId): int => (int) $typeId)
            ->values()
            ->all();
    }

    protected function ensureCustomizationTypeSettings(mixed $customizationTypes): void
    {
        collect($customizationTypes)->each(function (CustomizationType $type): void {
            if (array_key_exists($type->id, $this->customization_type_settings)) {
                return;
            }

            $this->customization_type_settings[$type->id] = [
                'sort_order' => count($this->customization_type_settings),
                'is_open_by_default' => false,
            ];
        });

        $this->customization_type_settings = app(BeverageTemperatureCustomization::class)
            ->normalizeSettings($this->customization_type_settings);
    }

    /**
     * @param  array<int, int|string>  $optionIds
     * @return array<int>
     */
    protected function sanitizeOptionIds(array $optionIds): array
    {
        return collect($optionIds)
            ->map(fn (int|string $optionId): int => (int) $optionId)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function sanitizeDefaultCustomizationOptions(): void
    {
        $selectedOptionIds = $this->sanitizeOptionIds($this->selected_customization_option_ids);

        $this->default_customization_option_ids = collect($this->default_customization_option_ids)
            ->map(fn (mixed $optionId): int => (int) $optionId)
            ->filter(fn (int $optionId): bool => in_array($optionId, $selectedOptionIds, true))
            ->unique()
            ->values()
            ->all();

        $normalized = app(BeverageTemperatureCustomization::class)->normalizeSelections(
            $selectedOptionIds,
            $this->default_customization_option_ids,
        );

        $this->selected_customization_option_ids = $normalized['selected'];
        $this->default_customization_option_ids = $normalized['defaults'];
    }

    /**
     * @param  array<int>  $selectedOptionIds
     * @param  array<int>  $defaultOptionIds
     */
    protected function syncCustomizationOptions(Beverage $beverage, array $selectedOptionIds, array $defaultOptionIds): void
    {
        $normalized = app(BeverageTemperatureCustomization::class)->normalizeSelections(
            $selectedOptionIds,
            $defaultOptionIds,
        );
        $selectedOptionIds = $normalized['selected'];
        $defaultOptionIds = $normalized['defaults'];

        $defaultOptionIds = collect($defaultOptionIds)
            ->intersect($selectedOptionIds)
            ->values()
            ->all();

        $beverage->customizationOptions()->sync(
            collect($selectedOptionIds)
                ->mapWithKeys(fn (int $optionId): array => [
                    $optionId => ['is_default' => in_array($optionId, $defaultOptionIds, true)],
                ])
                ->all(),
        );
    }

    /**
     * @param  array<int>  $selectedOptionIds
     */
    protected function persistCustomizationTypeSettings(Beverage $beverage, array $selectedOptionIds): void
    {
        $normalized = app(BeverageTemperatureCustomization::class)->normalizeSelections(
            $selectedOptionIds,
            $this->default_customization_option_ids,
        );
        $selectedOptionIds = $normalized['selected'];
        $this->customization_type_settings = app(BeverageTemperatureCustomization::class)
            ->normalizeSettings($this->customization_type_settings);

        $selectedTypeIds = CustomizationOption::query()
            ->whereIn('id', $selectedOptionIds)
            ->pluck('customization_type_id')
            ->map(fn (mixed $typeId): int => (int) $typeId)
            ->unique()
            ->values();

        $staleSettingsQuery = BeverageCustomizationTypeSetting::query()
            ->where('beverage_id', $beverage->id);

        if ($selectedTypeIds->isNotEmpty()) {
            $staleSettingsQuery->whereNotIn('customization_type_id', $selectedTypeIds);
        }

        $staleSettingsQuery->delete();

        $selectedTypeIds->each(function (int $typeId) use ($beverage): void {
            $settings = $this->customization_type_settings[$typeId] ?? [
                'sort_order' => count($this->customization_type_settings),
                'is_open_by_default' => false,
            ];

            BeverageCustomizationTypeSetting::query()->updateOrCreate(
                [
                    'beverage_id' => $beverage->id,
                    'customization_type_id' => $typeId,
                ],
                [
                    'sort_order' => (int) ($settings['sort_order'] ?? 0),
                    'is_open_by_default' => (bool) ($settings['is_open_by_default'] ?? false),
                ],
            );
        });
    }

    protected function persistForImageGeneration(): Beverage
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'beverage_category_id' => ['required', 'integer', 'exists:beverage_categories,id'],
            'selected_customization_option_ids' => ['nullable', 'array'],
            'selected_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'default_customization_option_ids' => ['nullable', 'array'],
            'default_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'is_active' => ['boolean'],
        ]);

        $beverage = CatalogImageManager::withoutQueueing(function () use ($validated): Beverage {
            return Beverage::query()->updateOrCreate([
                'id' => $this->beverage?->id,
            ], [
                'beverage_category_id' => $validated['beverage_category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image_path' => $this->beverage?->image_path,
                'base_price' => $this->beverage?->base_price ?? 0,
                'is_active' => $validated['is_active'],
            ]);
        });

        $selectedOptionIds = $this->sanitizeOptionIds($validated['selected_customization_option_ids'] ?? []);
        $defaultOptionIds = $this->sanitizeOptionIds($validated['default_customization_option_ids'] ?? []);

        $this->syncCustomizationOptions($beverage, $selectedOptionIds, $defaultOptionIds);
        $this->persistCustomizationTypeSettings($beverage, $selectedOptionIds);

        return $beverage;
    }
}
