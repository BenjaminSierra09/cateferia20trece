<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->withCount('saleItems')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    public function store(Request $request): ProductResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', Rule::in(['piece', 'gram', 'kilo'])],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product = Product::query()->create([
            ...$validated,
            'slug' => $this->slugFromName($validated['name']),
        ]);

        return new ProductResource($product->loadCount('saleItems'));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->loadCount('saleItems'));
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['sometimes', Rule::in(['piece', 'gram', 'kilo'])],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->slugFromName($validated['name']);
        }

        $product->update($validated);

        return new ProductResource($product->fresh()->loadCount('saleItems'));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
