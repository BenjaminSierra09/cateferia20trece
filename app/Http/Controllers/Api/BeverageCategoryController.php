<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BeverageCategoryResource;
use App\Models\BeverageCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BeverageCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $categories = BeverageCategory::query()
            ->withCount('beverages')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return BeverageCategoryResource::collection($categories);
    }

    public function store(Request $request): BeverageCategoryResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = BeverageCategory::query()->create([
            ...$validated,
            'slug' => $this->slugFromName($validated['name']),
        ]);

        return new BeverageCategoryResource($category->loadCount('beverages'));
    }

    public function show(BeverageCategory $beverageCategory): BeverageCategoryResource
    {
        return new BeverageCategoryResource($beverageCategory->loadCount('beverages'));
    }

    public function update(Request $request, BeverageCategory $beverageCategory): BeverageCategoryResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->slugFromName($validated['name']);
        }

        $beverageCategory->update($validated);

        return new BeverageCategoryResource($beverageCategory->fresh()->loadCount('beverages'));
    }

    public function destroy(BeverageCategory $beverageCategory): Response
    {
        $beverageCategory->delete();

        return response()->noContent();
    }
}
