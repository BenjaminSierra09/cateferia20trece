<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BeverageResource;
use App\Models\Beverage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BeverageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $beverages = Beverage::query()
            ->with(['category', 'sizePrices.size', 'customizationOptions.type'])
            ->when($request->filled('beverage_category_id'), fn ($query) => $query->where('beverage_category_id', $request->integer('beverage_category_id')))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return BeverageResource::collection($beverages);
    }

    public function store(Request $request): BeverageResource
    {
        $validated = $request->validate([
            'beverage_category_id' => ['nullable', 'integer', 'exists:beverage_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'size_prices' => ['required', 'array', 'min:1'],
            'size_prices.*.size_id' => ['required', 'integer', 'exists:sizes,id', 'distinct'],
            'size_prices.*.price' => ['required', 'numeric', 'min:0'],
            'size_prices.*.is_active' => ['sometimes', 'boolean'],
            'customization_option_ids' => ['nullable', 'array'],
            'customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
        ]);

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
        $beverage->customizationOptions()->sync($validated['customization_option_ids'] ?? []);

        return new BeverageResource($beverage->fresh()->load(['category', 'sizePrices.size', 'customizationOptions.type']));
    }

    public function show(Beverage $beverage): BeverageResource
    {
        return new BeverageResource($beverage->load(['category', 'sizePrices.size', 'customizationOptions.type']));
    }

    public function update(Request $request, Beverage $beverage): BeverageResource
    {
        $validated = $request->validate([
            'beverage_category_id' => ['sometimes', 'nullable', 'integer', 'exists:beverage_categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'size_prices' => ['sometimes', 'array', 'min:1'],
            'size_prices.*.size_id' => ['required_with:size_prices', 'integer', 'exists:sizes,id', 'distinct'],
            'size_prices.*.price' => ['required_with:size_prices', 'numeric', 'min:0'],
            'size_prices.*.is_active' => ['sometimes', 'boolean'],
            'customization_option_ids' => ['sometimes', 'array'],
            'customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
        ]);

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
            $beverage->customizationOptions()->sync($validated['customization_option_ids']);
        }

        return new BeverageResource($beverage->fresh()->load(['category', 'sizePrices.size', 'customizationOptions.type']));
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
}
