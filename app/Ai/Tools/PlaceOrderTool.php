<?php

namespace App\Ai\Tools;

use App\Models\Branch;
use App\Models\Customer;
use App\Services\MercadoPagoPointService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class PlaceOrderTool implements Tool
{
    public function __construct(private Customer $customer) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Envía el pedido del cliente a la impresora de la sucursal elegida para que el personal lo prepare. NO genera venta ni cobro. Úsala solo después de confirmar con el cliente los productos, las modificaciones y la sucursal.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();

        $branch = Branch::query()
            ->where('is_active', true)
            ->where('mercado_pago_is_active', true)
            ->whereNotNull('mercado_pago_default_terminal_id')
            ->find($args['branch_id'] ?? null);

        if ($branch === null) {
            return 'No encontré esa sucursal o no está disponible para pedidos. Pide al cliente que elija una sucursal de la lista.';
        }

        $items = $this->normalizeItems($args['items'] ?? []);

        if ($items === []) {
            return 'El pedido no tiene productos. Confirma con el cliente qué desea ordenar.';
        }

        try {
            app(MercadoPagoPointService::class)->printOrderTicket(
                branch: $branch,
                terminalId: (string) $branch->mercado_pago_default_terminal_id,
                terminalName: $branch->mercado_pago_default_terminal_name,
                customerName: $this->customer->name ?: 'Cliente',
                items: $items,
                note: filled($args['note'] ?? null) ? (string) $args['note'] : null,
            );
        } catch (Throwable $exception) {
            report($exception);

            return 'No fue posible imprimir el pedido en la sucursal. Discúlpate con el cliente e inténtalo de nuevo en un momento.';
        }

        return sprintf(
            'Pedido enviado a la impresora de %s. No es un cobro. Resumen: %s. Avisa al cliente que el personal lo está preparando.',
            $branch->name,
            $this->summarize($items),
        );
    }

    /**
     * Normalize raw tool items into a clean, printable structure.
     *
     * @return array<int, array{name:string, size:?string, quantity:int, modifications:array<int, string>}>
     */
    private function normalizeItems(mixed $rawItems): array
    {
        return collect(is_array($rawItems) ? $rawItems : [])
            ->map(function ($item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $name = trim((string) ($item['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'size' => filled($item['size'] ?? null) ? trim((string) $item['size']) : null,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'modifications' => collect($item['modifications'] ?? [])
                        ->map(fn ($modification): string => trim((string) $modification))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{name:string, size:?string, quantity:int, modifications:array<int, string>}>  $items
     */
    private function summarize(array $items): string
    {
        return collect($items)
            ->map(function (array $item): string {
                $line = $item['quantity'].' x '.$item['name'];

                if (filled($item['size'])) {
                    $line .= ' ('.$item['size'].')';
                }

                return $item['modifications'] === []
                    ? $line
                    : $line.' ['.implode(', ', $item['modifications']).']';
            })
            ->implode('; ');
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'branch_id' => $schema->integer()
                ->description('Id de la sucursal elegida por el cliente (de la herramienta de sucursales).')
                ->required(),
            'items' => $schema->array()
                ->items($schema->object([
                    'name' => $schema->string()
                        ->description('Nombre de la bebida o producto tal como aparece en el menú.')
                        ->required(),
                    'size' => $schema->string()
                        ->description('Tamaño elegido si aplica, p. ej. "Grande". Usa null si no aplica.')
                        ->required()
                        ->nullable(),
                    'quantity' => $schema->integer()
                        ->description('Cantidad. Usa null para 1 por defecto.')
                        ->required()
                        ->nullable(),
                    'modifications' => $schema->array()
                        ->items($schema->string())
                        ->description('Modificaciones o personalizaciones, p. ej. "sin azúcar", "leche de almendra". Usa null si no hay.')
                        ->required()
                        ->nullable(),
                ]))
                ->description('Lista de productos del pedido.')
                ->required(),
            'note' => $schema->string()
                ->description('Nota opcional para el personal (alergias, instrucciones especiales). Usa null si no hay nota.')
                ->required()
                ->nullable(),
        ];
    }
}
