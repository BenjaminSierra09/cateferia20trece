<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SizeResource;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SizeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $sizes = Size::query()
            ->withCount('beveragePrices')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('capacity_ounces')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return SizeResource::collection($sizes);
    }

    public function store(Request $request): SizeResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity_label' => ['nullable', 'string', 'max:255'],
            'capacity_ounces' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $size = Size::query()->create($validated);

        return new SizeResource($size->loadCount('beveragePrices'));
    }

    public function show(Size $size): SizeResource
    {
        return new SizeResource($size->loadCount('beveragePrices'));
    }

    public function update(Request $request, Size $size): SizeResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'capacity_label' => ['nullable', 'string', 'max:255'],
            'capacity_ounces' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $size->update($validated);

        return new SizeResource($size->fresh()->loadCount('beveragePrices'));
    }

    public function destroy(Size $size): Response
    {
        $size->delete();

        return response()->noContent();
    }
}
