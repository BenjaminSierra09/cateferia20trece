<?php

namespace App\Services;

use App\Exceptions\MercadoPagoPointException;
use App\Models\Branch;
use App\Models\MercadoPagoPointOrder;
use App\Models\Sale;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MercadoPagoPointService
{
    private const BASE_URL = 'https://api.mercadopago.com';

    /**
     * Get available Point terminals for a branch.
     *
     * @return array<int, array<string, mixed>>
     */
    public function terminals(Branch $branch): array
    {
        $response = $this->client($branch)
            ->get('/terminals/v1/list')
            ->throw()
            ->json();

        return collect(data_get($response, 'data.terminals', []))
            ->map(fn (array $terminal): array => [
                'id' => $terminal['id'] ?? null,
                'name' => $terminal['name'] ?? $terminal['id'] ?? 'Terminal Point',
                'store_id' => $terminal['store_id'] ?? null,
                'pos_id' => $terminal['pos_id'] ?? null,
                'external_pos_id' => $terminal['external_pos_id'] ?? null,
                'operating_mode' => $terminal['operating_mode'] ?? null,
            ])
            ->filter(fn (array $terminal): bool => filled($terminal['id']))
            ->values()
            ->all();
    }

    public function createPaymentOrder(
        Sale $sale,
        string $terminalId,
        ?string $terminalName = null,
        string $printOnTerminal = 'seller_ticket',
    ): MercadoPagoPointOrder {
        $sale->loadMissing(['branch', 'items.customizations']);
        $branch = $sale->branch;

        if ($branch === null) {
            throw new RuntimeException('La venta no tiene sucursal vinculada.');
        }

        $externalReference = sprintf('sale_%d_%s', $sale->id, Str::lower(Str::random(8)));
        $idempotencyKey = (string) Str::uuid();
        $payload = [
            'type' => 'point',
            'external_reference' => $externalReference,
            'description' => Str::limit(sprintf('Cafe 20Trece venta %d', $sale->id), 150, ''),
            'config' => [
                'point' => [
                    'terminal_id' => $terminalId,
                    'print_on_terminal' => $printOnTerminal,
                ],
            ],
            'transactions' => [
                'payments' => [
                    [
                        'amount' => number_format((float) $sale->total, 2, '.', ''),
                    ],
                ],
            ],
        ];

        $pointOrder = MercadoPagoPointOrder::query()->create([
            'sale_id' => $sale->id,
            'branch_id' => $branch->id,
            'terminal_id' => $terminalId,
            'terminal_name' => $terminalName,
            'external_reference' => $externalReference,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'amount' => $sale->total,
            'request_payload' => $payload,
        ]);

        return $this->sendOrderRequest($branch, $pointOrder, $idempotencyKey, $payload);
    }

    public function createManualPaymentOrder(
        Branch $branch,
        float $amount,
        string $terminalId,
        ?string $terminalName = null,
        ?string $description = null,
        string $printOnTerminal = 'seller_ticket',
    ): MercadoPagoPointOrder {
        $externalReference = sprintf('manual_%d_%s', $branch->id, Str::lower(Str::random(10)));
        $idempotencyKey = (string) Str::uuid();
        $payload = [
            'type' => 'point',
            'external_reference' => $externalReference,
            'description' => Str::limit($description ?: sprintf('Cafe 20Trece cobro manual %s', now()->format('YmdHi')), 150, ''),
            'config' => [
                'point' => [
                    'terminal_id' => $terminalId,
                    'print_on_terminal' => $printOnTerminal,
                ],
            ],
            'transactions' => [
                'payments' => [
                    [
                        'amount' => number_format($amount, 2, '.', ''),
                    ],
                ],
            ],
        ];

        $pointOrder = MercadoPagoPointOrder::query()->create([
            'sale_id' => null,
            'branch_id' => $branch->id,
            'terminal_id' => $terminalId,
            'terminal_name' => $terminalName,
            'external_reference' => $externalReference,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'amount' => $amount,
            'request_payload' => $payload,
        ]);

        return $this->sendOrderRequest($branch, $pointOrder, $idempotencyKey, $payload);
    }

    public function createPrintAction(Sale $sale, string $terminalId, ?string $terminalName = null): array
    {
        $sale->loadMissing(['branch', 'items.customizations']);
        $branch = $sale->branch;

        if ($branch === null) {
            throw new RuntimeException('La venta no tiene sucursal vinculada.');
        }

        $payload = [
            'type' => 'print',
            'external_reference' => sprintf('sale_%d_print_%s', $sale->id, Str::lower(Str::random(6))),
            'config' => [
                'point' => [
                    'terminal_id' => $terminalId,
                    'subtype' => 'custom',
                ],
            ],
            'content' => $this->printContent($sale, $terminalName),
        ];

        return $this->client($branch)
            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
            ->post('/terminals/v1/actions', $payload)
            ->throw()
            ->json();
    }

    private function client(Branch $branch): PendingRequest
    {
        if (! $branch->mercado_pago_is_active || blank($branch->mercado_pago_access_token)) {
            throw new RuntimeException('Mercado Pago no está configurado para esta sucursal.');
        }

        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->withToken($branch->mercado_pago_access_token)
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 250);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendOrderRequest(
        Branch $branch,
        MercadoPagoPointOrder $pointOrder,
        string $idempotencyKey,
        array $payload,
    ): MercadoPagoPointOrder {
        try {
            $response = $this->client($branch)
                ->withHeader('X-Idempotency-Key', $idempotencyKey)
                ->post('/v1/orders', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $responsePayload = $exception->response->json();

            $pointOrder->update([
                'status' => 'failed',
                'response_payload' => is_array($responsePayload)
                    ? $responsePayload
                    : ['message' => $exception->getMessage()],
                'sent_at' => now(),
            ]);

            throw MercadoPagoPointException::fromRequestException($exception);
        }

        $pointOrder->update([
            'mercado_pago_order_id' => data_get($response, 'id'),
            'status' => data_get($response, 'status', $pointOrder->status),
            'response_payload' => $response,
            'sent_at' => now(),
        ]);

        return $pointOrder->fresh();
    }

    private function printContent(Sale $sale, ?string $terminalName): string
    {
        $lines = [
            '{center}{w}Cafe 20Trece{/w}{/center}',
            '{center}Ticket de venta{/center}',
            sprintf('{center}Venta %d{/center}', $sale->id),
            '--------------------------------',
            sprintf('{left}Sucursal: %s{/left}', $sale->branch?->name ?? 'Sin sucursal'),
            sprintf('{left}Terminal: %s{/left}', $terminalName ?? 'Point'),
            '--------------------------------',
        ];

        foreach ($sale->items as $item) {
            $lines[] = sprintf('{left}%d x %s{/left}', $item->quantity, $item->item_name);

            foreach ($item->customizations as $customization) {
                $lines[] = sprintf('{left}  + %s{/left}', $customization->customization_name);
            }
        }

        $lines[] = '--------------------------------';
        $lines[] = sprintf('{left}TOTAL $%s{/left}', number_format((float) $sale->total, 2));
        $lines[] = '{br}{center}Gracias por tu compra{/center}{br}';

        return implode('{br}', $lines);
    }
}
