<?php

namespace App\Mcp\Tools;

use App\Support\MenuQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List café beverages on the menu with their category, serving temperature, base price, and per-size prices. Supports filtering by category, temperature (hot/cold), and a name search.')]
class ListBeverages extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'temperature' => ['nullable', 'in:hot,cold,any'],
            'search' => ['nullable', 'string', 'max:255'],
            'only_active' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $beverages = app(MenuQuery::class)->beverages([
            'category' => $validated['category'] ?? null,
            'temperature' => $validated['temperature'] ?? 'any',
            'search' => $validated['search'] ?? null,
            'only_active' => $validated['only_active'] ?? true,
            'limit' => $validated['limit'] ?? 50,
        ]);

        return Response::json([
            'count' => count($beverages),
            'beverages' => $beverages,
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
            'category' => $schema->string()
                ->description('Filter beverages by category name or slug (partial, case-insensitive match), e.g. "café".'),
            'temperature' => $schema->string()
                ->enum(['hot', 'cold', 'any'])
                ->description('Filter by serving temperature. Defaults to "any".'),
            'search' => $schema->string()
                ->description('Partial, case-insensitive match on the beverage name.'),
            'only_active' => $schema->boolean()
                ->description('When true (default), only beverages currently available on the menu are returned.'),
            'limit' => $schema->integer()
                ->description('Maximum number of beverages to return (1-100). Defaults to 50.'),
        ];
    }
}
