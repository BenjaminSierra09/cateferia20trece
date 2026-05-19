<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoiceSaleDraftRequest;
use App\Http\Resources\SaleResource;
use App\Models\CustomerQrCode;
use App\Models\User;
use App\Services\SaleService;
use App\Services\VoiceSaleDraftService;
use App\Services\WorkSessionService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class VoiceSaleDraftController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreVoiceSaleDraftRequest $request,
        SaleService $saleService,
        VoiceSaleDraftService $voiceSaleDraftService,
        WorkSessionService $workSessionService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $workSession = $workSessionService->currentFor($user);

        if ($workSession === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'El colaborador no tiene sucursal confirmada hoy.',
                'errors' => [
                    'audio' => ['El colaborador no tiene sucursal confirmada hoy.'],
                ],
            ], 422));
        }

        $customerUuid = $request->string('customer_uuid')->toString() ?: null;
        $customerId = $this->resolveCustomerIdFromUuid($customerUuid);

        $draft = $voiceSaleDraftService->fromAudio(
            audio: $request->file('audio'),
            user: $user,
            workSession: $workSession,
            language: $request->string('language')->toString() ?: 'es',
            notes: $request->string('notes')->toString() ?: null,
            customerId: $customerId,
            customerUuid: $customerUuid,
        );

        try {
            $sale = $saleService->register($draft['sale_payload'], $user, $workSession);
        } catch (InvalidArgumentException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'audio' => [$exception->getMessage()],
                ],
                'draft' => $draft,
            ], 422));
        }

        return response()->json([
            'transcript' => $draft['transcript'],
            'submitted_at' => $draft['submitted_at'],
            'collaborator' => $draft['collaborator'],
            'branch' => $draft['branch'],
            'assumptions' => $draft['assumptions'],
            'sale_payload' => $draft['sale_payload'],
            'sale' => (new SaleResource($sale->load([
                'branch',
                'user',
                'customer.qrCodes',
                'items.size',
                'items.beverage',
                'items.product',
                'items.customizations',
            ])))->resolve(),
        ], 201);
    }

    protected function resolveCustomerIdFromUuid(?string $customerUuid): ?int
    {
        if ($customerUuid === null) {
            return null;
        }

        $customerId = CustomerQrCode::query()
            ->where('uuid', $customerUuid)
            ->value('customer_id');

        if ($customerId === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'El QR del cliente no existe o ya no está asignado.',
                'errors' => [
                    'customer_uuid' => ['El QR del cliente no existe o ya no está asignado.'],
                ],
            ], 422));
        }

        return (int) $customerId;
    }
}
