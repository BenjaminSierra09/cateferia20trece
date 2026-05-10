<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomizationOptionResource;
use App\Models\CustomizationOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomizationOptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $options = CustomizationOption::query()
            ->with(['type'])
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

        return new CustomizationOptionResource($option->load('type')->loadCount('beverages'));
    }

    public function show(CustomizationOption $customizationOption): CustomizationOptionResource
    {
        return new CustomizationOptionResource($customizationOption->load('type')->loadCount('beverages'));
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

        return new CustomizationOptionResource($customizationOption->fresh()->load('type')->loadCount('beverages'));
    }

    public function destroy(CustomizationOption $customizationOption): Response
    {
        $customizationOption->delete();

        return response()->noContent();
    }
}
