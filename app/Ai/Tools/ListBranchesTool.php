<?php

namespace App\Ai\Tools;

use App\Models\Branch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListBranchesTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Lista las sucursales disponibles para enviar un pedido por WhatsApp, con su id y nombre. El cliente debe elegir una sucursal antes de ordenar. No recibe parámetros.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->where('mercado_pago_is_active', true)
            ->whereNotNull('mercado_pago_default_terminal_id')
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'city']);

        if ($branches->isEmpty()) {
            return 'No hay sucursales disponibles para pedidos por WhatsApp en este momento.';
        }

        return (string) json_encode(
            $branches->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'nombre' => $branch->name,
                'direccion' => trim(implode(', ', array_filter([$branch->address, $branch->city]))) ?: null,
            ])->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
