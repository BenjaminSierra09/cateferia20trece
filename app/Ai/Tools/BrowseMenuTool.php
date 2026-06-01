<?php

namespace App\Ai\Tools;

use App\Support\MenuQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class BrowseMenuTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Consulta el menú de Café 20Trece: bebidas (con categoría, temperatura y precios por tamaño) y productos de comida. Permite filtrar por categoría, temperatura (hot/cold) o búsqueda por nombre.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();
        $search = $args['search'] ?? null;
        $menu = app(MenuQuery::class);

        $beverages = $menu->beverages([
            'category' => $args['category'] ?? null,
            'temperature' => $args['temperature'] ?? 'any',
            'search' => $search,
        ]);

        $products = ($args['include_products'] ?? true)
            ? $menu->products(['search' => $search])
            : [];

        return (string) json_encode([
            'bebidas' => $beverages,
            'productos' => $products,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()
                ->description('Filtra bebidas por nombre o slug de categoría (coincidencia parcial), p. ej. "café".'),
            'temperature' => $schema->string()
                ->enum(['hot', 'cold', 'any'])
                ->description('Filtra bebidas por temperatura. Por defecto "any".'),
            'search' => $schema->string()
                ->description('Búsqueda parcial por nombre (aplica a bebidas y productos).'),
            'include_products' => $schema->boolean()
                ->description('Incluir productos de comida además de bebidas. Por defecto true.'),
        ];
    }
}
