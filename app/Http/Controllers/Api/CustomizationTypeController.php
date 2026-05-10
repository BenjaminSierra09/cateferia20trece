<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomizationTypeResource;
use App\Models\CustomizationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CustomizationTypeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $types = CustomizationType::query()
            ->withCount('options')
            ->with('options')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return CustomizationTypeResource::collection($types);
    }

    public function store(Request $request): CustomizationTypeResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'selection_mode' => ['required', Rule::in(['single', 'multiple'])],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $type = CustomizationType::query()->create([
            ...$validated,
            'slug' => $this->slugFromName($validated['name']),
        ]);

        return new CustomizationTypeResource($type->load('options')->loadCount('options'));
    }

    public function show(CustomizationType $customizationType): CustomizationTypeResource
    {
        return new CustomizationTypeResource($customizationType->load('options')->loadCount('options'));
    }

    public function update(Request $request, CustomizationType $customizationType): CustomizationTypeResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'selection_mode' => ['sometimes', Rule::in(['single', 'multiple'])],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->slugFromName($validated['name']);
        }

        $customizationType->update($validated);

        return new CustomizationTypeResource($customizationType->fresh()->load('options')->loadCount('options'));
    }

    public function destroy(CustomizationType $customizationType): Response
    {
        $customizationType->delete();

        return response()->noContent();
    }
}
