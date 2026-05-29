<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomerResource;
use App\Models\CustomerQrCode;
use Illuminate\Http\JsonResponse;

class QrLookupController extends Controller
{
    /**
     * Look up a customer by QR UUID.
     */
    public function __invoke(string $uuid): JsonResponse
    {
        $qrCode = CustomerQrCode::query()
            ->with(['customer.qrCodes', 'customer.debtMovements'])
            ->where('is_active', true)
            ->where('uuid', $uuid)
            ->whereHas('customer', fn ($query) => $query->active())
            ->first();

        if ($qrCode === null) {
            return response()->json([
                'message' => 'UUID no encontrado.',
            ], 404);
        }

        $qrCode->update([
            'last_scanned_at' => now(),
        ]);

        return response()->json([
            'uuid' => $qrCode->uuid,
            'is_active' => $qrCode->is_active,
            'customer' => $qrCode->customer ? new CustomerResource($qrCode->customer->load('rewardTransactions', 'debtMovements')) : null,
        ]);
    }
}
