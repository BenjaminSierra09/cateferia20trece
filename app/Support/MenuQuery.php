<?php

namespace App\Support;

use App\Models\Beverage;
use App\Models\Product;

class MenuQuery
{
    /**
     * Resolve beverages on the menu with category, temperature, base price, and per-size prices.
     *
     * @param  array{category?:?string,temperature?:?string,search?:?string,only_active?:bool,limit?:int}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function beverages(array $filters = []): array
    {
        $onlyActive = $filters['only_active'] ?? true;
        $temperature = $filters['temperature'] ?? 'any';
        $search = $filters['search'] ?? null;
        $category = $filters['category'] ?? null;

        return Beverage::query()
            ->with([
                'category',
                'sizePrices' => fn ($query) => $query->where('is_active', true)->with('size'),
            ])
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->when(
                filled($temperature) && $temperature !== 'any',
                fn ($query) => $query->where('is_hot', $temperature === 'hot'),
            )
            ->when(filled($search), fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when(
                filled($category),
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($categoryQuery) => $categoryQuery
                        ->where('name', 'like', '%'.$category.'%')
                        ->orWhere('slug', 'like', '%'.$category.'%'),
                ),
            )
            ->orderBy('name')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (Beverage $beverage): array => [
                'id' => $beverage->id,
                'name' => $beverage->name,
                'category' => $beverage->category?->name,
                'description' => $beverage->description,
                'temperature' => $beverage->is_hot ? 'hot' : 'cold',
                'base_price' => (float) $beverage->base_price,
                'is_active' => $beverage->is_active,
                'sizes' => $beverage->sizePrices
                    ->map(fn ($sizePrice): array => [
                        'size' => $sizePrice->size?->name,
                        'capacity' => $sizePrice->size?->capacity_label,
                        'price' => (float) $sizePrice->price,
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Resolve food / non-beverage products on the menu with their prices.
     *
     * @param  array{search?:?string,only_active?:bool,limit?:int}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function products(array $filters = []): array
    {
        $onlyActive = $filters['only_active'] ?? true;
        $search = $filters['search'] ?? null;

        return Product::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->when(filled($search), fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'unit_type' => $product->unit_type,
                'base_price' => (float) $product->base_price,
                'is_active' => $product->is_active,
            ])
            ->all();
    }
}
