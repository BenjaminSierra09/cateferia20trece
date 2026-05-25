<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomizationOptionResource;
use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomizationOptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $options = CustomizationOption::query()
            ->with([
                'type',
                'sizePrices.size',
                'branchSizePriceOverrides' => fn ($query) => $query->when($request->integer('branch_id') > 0, fn ($branchQuery) => $branchQuery->where('branch_id', $request->integer('branch_id'))),
            ])
            ->withCount('beverages')
            ->when($request->filled('customization_type_id'), fn ($query) => $query->where('customization_type_id', $request->integer('customization_type_id')))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return CustomizationOptionResource::collection($options);
    }

    public function store(Request $request): CustomizationOptionResource
    {
        $validated = $request->validate([
            'customization_type_id' => ['required', 'integer', 'exists:customization_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $option = CustomizationOption::query()->create($validated);
        $this->ensureSizePrices($option);

        return new CustomizationOptionResource($option->load(['type', 'sizePrices.size'])->loadCount('beverages'));
    }

    public function show(CustomizationOption $customizationOption): CustomizationOptionResource
    {
        return new CustomizationOptionResource($customizationOption->load(['type', 'sizePrices.size', 'branchSizePriceOverrides'])->loadCount('beverages'));
    }

    public function update(Request $request, CustomizationOption $customizationOption): CustomizationOptionResource
    {
        $validated = $request->validate([
            'customization_type_id' => ['sometimes', 'integer', 'exists:customization_types,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $customizationOption->update($validated);
        $this->ensureSizePrices($customizationOption);

        return new CustomizationOptionResource($customizationOption->fresh()->load(['type', 'sizePrices.size'])->loadCount('beverages'));
    }

    public function destroy(CustomizationOption $customizationOption): Response
    {
        $customizationOption->delete();

        return response()->noContent();
    }

    private function ensureSizePrices(CustomizationOption $customizationOption): void
    {
        Size::query()
            ->where('is_active', true)
            ->get()
            ->each(fn (Size $size) => CustomizationOptionSizePrice::query()->firstOrCreate([
                'customization_option_id' => $customizationOption->id,
                'size_id' => $size->id,
            ], [
                'price' => $customizationOption->price,
            ]));
    }
}
