<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MercadoPagoPointException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Services\MercadoPagoPointService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MercadoPagoPointOrderController extends Controller
{
    public function manual(Request $request, Branch $branch, MercadoPagoPointService $mercadoPagoPointService): array|JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'terminal_id' => ['required', 'string', 'max:255'],
            'terminal_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:150'],
            'print_on_terminal' => ['nullable', 'in:seller_ticket,no_ticket'],
        ]);

        try {
            $pointOrder = $mercadoPagoPointService->createManualPaymentOrder(
                branch: $branch,
                amount: round((float) $validated['amount'], 2),
                terminalId: $validated['terminal_id'],
                terminalName: $validated['terminal_name'] ?? null,
                description: $validated['description'] ?? null,
                printOnTerminal: $validated['print_on_terminal'] ?? 'seller_ticket',
            );
        } catch (MercadoPagoPointException $exception) {
            return $this->mercadoPagoErrorResponse($exception);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'mercado_pago' => [$exception->getMessage()],
            ]);
        }

        return [
            'data' => [
                'id' => $pointOrder->id,
                'sale_id' => $pointOrder->sale_id,
                'terminal_id' => $pointOrder->terminal_id,
                'terminal_name' => $pointOrder->terminal_name,
                'external_reference' => $pointOrder->external_reference,
                'mercado_pago_order_id' => $pointOrder->mercado_pago_order_id,
                'status' => $pointOrder->status,
                'amount' => $pointOrder->amount,
            ],
        ];
    }

    public function store(Request $request, Sale $sale, MercadoPagoPointService $mercadoPagoPointService): array|JsonResponse
    {
        $validated = $request->validate([
            'terminal_id' => ['required', 'string', 'max:255'],
            'terminal_name' => ['nullable', 'string', 'max:255'],
            'print_on_terminal' => ['nullable', 'in:seller_ticket,no_ticket'],
        ]);

        try {
            $pointOrder = $mercadoPagoPointService->createPaymentOrder(
                sale: $sale,
                terminalId: $validated['terminal_id'],
                terminalName: $validated['terminal_name'] ?? null,
                printOnTerminal: $validated['print_on_terminal'] ?? 'seller_ticket',
            );
        } catch (MercadoPagoPointException $exception) {
            return $this->mercadoPagoErrorResponse($exception);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'mercado_pago' => [$exception->getMessage()],
            ]);
        }

        return [
            'data' => [
                'id' => $pointOrder->id,
                'sale_id' => $pointOrder->sale_id,
                'terminal_id' => $pointOrder->terminal_id,
                'terminal_name' => $pointOrder->terminal_name,
                'external_reference' => $pointOrder->external_reference,
                'mercado_pago_order_id' => $pointOrder->mercado_pago_order_id,
                'status' => $pointOrder->status,
                'amount' => $pointOrder->amount,
            ],
        ];
    }

    public function print(Request $request, Sale $sale, MercadoPagoPointService $mercadoPagoPointService): array|JsonResponse
    {
        $validated = $request->validate([
            'terminal_id' => ['required', 'string', 'max:255'],
            'terminal_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $action = $mercadoPagoPointService->createPrintAction(
                sale: $sale,
                terminalId: $validated['terminal_id'],
                terminalName: $validated['terminal_name'] ?? null,
            );
        } catch (RequestException $exception) {
            return $this->mercadoPagoErrorResponse(MercadoPagoPointException::fromRequestException($exception));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'mercado_pago' => [$exception->getMessage()],
            ]);
        }

        return ['data' => $action];
    }

    private function mercadoPagoErrorResponse(MercadoPagoPointException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->mercadoPagoCode,
            'errors' => [
                'mercado_pago' => [$exception->getMessage()],
            ],
        ], $exception->statusCode);
    }
}
