<?php

namespace App\Ai\Tools;

use App\Models\Customer;
use App\Services\CustomerFavoriteBeverageService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListFavoriteBeveragesTool implements Tool
{
    public function __construct(private Customer $customer) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Lista las bebidas favoritas del cliente según su historial de compras, con su tamaño preferido y personalizaciones frecuentes.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $limit = max(1, min((int) ($request->all()['limit'] ?? 3), 5));

        $favorites = app(CustomerFavoriteBeverageService::class)
            ->topForCustomer($this->customer, $limit);

        if ($favorites->isEmpty()) {
            return 'El cliente todavía no tiene bebidas favoritas registradas.';
        }

        $payload = $favorites->map(fn (array $favorite): array => [
            'bebida' => $favorite['beverage_name'],
            'veces_pedida' => $favorite['total_quantity'],
            'tamano_preferido' => $favorite['top_size']['size_name'] ?? null,
            'personalizaciones_frecuentes' => collect($favorite['frequent_customizations'])
                ->pluck('name')
                ->all(),
        ])->all();

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Cuántas bebidas favoritas devolver (1-5). Usa null para el valor por defecto (3).')
                ->required()
                ->nullable(),
        ];
    }
}
