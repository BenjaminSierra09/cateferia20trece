<?php

namespace App\Mcp\Tools;

use App\Support\MenuQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List food products / non-beverage items on the menu, with their unit type and base price. Supports a name search.')]
class ListProducts extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'only_active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = app(MenuQuery::class)->products([
            'search' => $validated['search'] ?? null,
            'only_active' => $validated['only_active'] ?? true,
            'limit' => $validated['limit'] ?? 50,
        ]);

        return Response::json([
            'count' => count($products),
            'products' => $products,
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
            'search' => $schema->string()
                ->description('Partial, case-insensitive match on the product name.'),
            'only_active' => $schema->boolean()
                ->description('When true (default), only products currently available on the menu are returned.'),
            'limit' => $schema->integer()
                ->description('Maximum number of products to return (1-100). Defaults to 50.'),
        ];
    }
}
