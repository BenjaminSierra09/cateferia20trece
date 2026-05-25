<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreBeverageRequest;
use App\Http\Requests\UpdateBeverageRequest;
use App\Http\Resources\BeverageResource;
use App\Models\Beverage;
use App\Support\BeverageTemperatureCustomization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BeverageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $beverages = Beverage::query()
            ->with($this->beverageRelations($request->integer('branch_id') ?: null))
            ->when($request->filled('beverage_category_id'), fn ($query) => $query->where('beverage_category_id', $request->integer('beverage_category_id')))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return BeverageResource::collection($beverages);
    }

    public function store(StoreBeverageRequest $request): BeverageResource
    {
        $validated = $request->validated();

        $beverage = Beverage::query()->create([
            'beverage_category_id' => $validated['beverage_category_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->slugFromName($validated['name']),
            'description' => $validated['description'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'base_price' => collect($validated['size_prices'])->min('price'),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->syncSizePrices($beverage, $validated['size_prices']);
        $this->syncCustomizationOptions($beverage, $validated['customization_option_ids'] ?? []);

        return new BeverageResource($beverage->fresh()->load($this->beverageRelations()));
    }

    public function show(Request $request, Beverage $beverage): BeverageResource
    {
        return new BeverageResource($beverage->load($this->beverageRelations($request->integer('branch_id') ?: null)));
    }

    public function update(UpdateBeverageRequest $request, Beverage $beverage): BeverageResource
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->slugFromName($validated['name']);
        }

        if (array_key_exists('size_prices', $validated)) {
            $validated['base_price'] = collect($validated['size_prices'])->min('price');
        }

        $beverage->update($validated);

        if (array_key_exists('size_prices', $validated)) {
            $this->syncSizePrices($beverage, $validated['size_prices']);
        }

        if (array_key_exists('customization_option_ids', $validated)) {
            $this->syncCustomizationOptions($beverage, $validated['customization_option_ids']);
        }

        return new BeverageResource($beverage->fresh()->load($this->beverageRelations()));
    }

    public function destroy(Beverage $beverage): Response
    {
        $beverage->delete();

        return response()->noContent();
    }

    /**
     * @param  array<int, array<string, mixed>>  $sizePrices
     */
    private function syncSizePrices(Beverage $beverage, array $sizePrices): void
    {
        $beverage->sizePrices()->delete();

        $beverage->sizePrices()->createMany(
            collect($sizePrices)->map(fn (array $sizePrice) => [
                'size_id' => $sizePrice['size_id'],
                'price' => $sizePrice['price'],
                'is_active' => $sizePrice['is_active'] ?? true,
            ])->all(),
        );
    }

    /**
     * @param  array<int, int>  $customizationOptionIds
     */
    private function syncCustomizationOptions(Beverage $beverage, array $customizationOptionIds): void
    {
        $normalized = app(BeverageTemperatureCustomization::class)->normalizeSelections(
            $customizationOptionIds,
            [],
        );

        $beverage->customizationOptions()->sync(
            collect($normalized['selected'])
                ->mapWithKeys(fn (int $optionId): array => [
                    $optionId => ['is_default' => in_array($optionId, $normalized['defaults'], true)],
                ])
                ->all(),
        );

        app(BeverageTemperatureCustomization::class)->applyToBeverage($beverage, preserveExistingDefault: true);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function beverageRelations(?int $branchId = null): array
    {
        return [
            'category',
            'sizePrices.size',
            'customizationOptions.type',
            'customizationOptions.sizePrices.size',
            'customizationOptions.branchSizePriceOverrides' => fn ($query) => $query->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)),
            'customizationTypeSettings',
            'branchSizeAvailabilities' => fn ($query) => $query->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)),
        ];
    }
}
