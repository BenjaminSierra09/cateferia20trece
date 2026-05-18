<?php

namespace App\Livewire\Beverages;

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\BranchBeveragePriceOverride;
use App\Models\CustomizationType;
use App\Models\Size;
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

    public bool $is_active = true;

    public string $temperature = 'hot';

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
    }

    public function mount(?Beverage $beverage = null): void
    {
        $this->beverage = $beverage?->exists ? $beverage->load(['sizePrices', 'customizationOptions']) : null;

        if ($this->beverage !== null) {
            $this->name = $this->beverage->name;
            $this->description = $this->beverage->description ?? '';
            $this->beverage_category_id = $this->beverage->beverage_category_id;
            $this->temperature = $this->beverage->is_hot ? 'hot' : 'cold';
            $this->is_active = $this->beverage->is_active;
            $this->selected_customization_option_ids = $this->beverage->customizationOptions->pluck('id')->all();
        }

        $this->size_pricing = $this->buildSizePricingRows();
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
            'selected_customization_option_ids' => ['nullable', 'array'],
            'selected_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'temperature' => ['required', 'in:hot,cold'],
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
            'is_hot' => $validated['temperature'] === 'hot',
            'is_active' => $validated['is_active'],
        ]);

        $selectedSizeIds = $enabledSizePricing->pluck('size_id')->all();

        $beverage->sizePrices()->whereNotIn('size_id', $selectedSizeIds)->delete();

        BranchBeveragePriceOverride::query()
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
        }

        $beverage->customizationOptions()->sync($validated['selected_customization_option_ids'] ?? []);

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

        return $sizes->map(function (Size $size) use ($branches, $existingOverrides, $existingPrices): array {
            $priceRecord = $existingPrices->get($size->id);
            $sizeOverrides = $existingOverrides->get($size->id, collect())->keyBy('branch_id');

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
            ];
        })->all();
    }

    public function render(): View
    {
        return view('livewire.beverages.create', [
            'categories' => BeverageCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'customizationTypes' => CustomizationType::query()
                ->with(['options' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app');
    }

    protected function persistForImageGeneration(): Beverage
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'beverage_category_id' => ['required', 'integer', 'exists:beverage_categories,id'],
            'selected_customization_option_ids' => ['nullable', 'array'],
            'selected_customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
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

        $beverage->customizationOptions()->sync($validated['selected_customization_option_ids'] ?? []);

        return $beverage;
    }
}
