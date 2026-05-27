<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\MercadoPagoPointService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MercadoPagoTerminalController extends Controller
{
    public function index(Branch $branch, MercadoPagoPointService $mercadoPagoPointService): array
    {
        try {
            return [
                'data' => $mercadoPagoPointService->terminals($branch),
                'default_terminal' => [
                    'id' => $branch->mercado_pago_default_terminal_id,
                    'name' => $branch->mercado_pago_default_terminal_name,
                ],
            ];
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'mercado_pago' => [$exception->getMessage()],
            ]);
        }
    }

    public function update(Request $request, Branch $branch): array
    {
        $validated = $request->validate([
            'terminal_id' => ['nullable', 'string', 'max:255'],
            'terminal_name' => ['nullable', 'string', 'max:255'],
        ]);

        $branch->update([
            'mercado_pago_default_terminal_id' => $validated['terminal_id'] ?? null,
            'mercado_pago_default_terminal_name' => $validated['terminal_name'] ?? null,
        ]);

        return [
            'data' => [
                'id' => $branch->fresh()->mercado_pago_default_terminal_id,
                'name' => $branch->fresh()->mercado_pago_default_terminal_name,
            ],
        ];
    }
}
