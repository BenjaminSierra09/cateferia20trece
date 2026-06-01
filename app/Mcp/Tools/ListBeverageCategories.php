<?php

namespace App\Mcp\Tools;

use App\Models\BeverageCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the beverage menu categories (e.g. coffee, tea, cold drinks), each with the number of active beverages it contains.')]
class ListBeverageCategories extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'only_active' => ['nullable', 'boolean'],
        ]);

        $onlyActive = $validated['only_active'] ?? true;

        $categories = BeverageCategory::query()
            ->withCount(['beverages as active_beverages_count' => fn ($query) => $query->where('is_active', true)])
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        $items = $categories->map(fn (BeverageCategory $category): array => [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'active_beverages_count' => (int) $category->active_beverages_count,
        ])->all();

        return Response::json([
            'count' => count($items),
            'categories' => $items,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'only_active' => $schema->boolean()
                ->description('When true (default), only active categories are returned.'),
        ];
    }
}
