<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\UnusableAudioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoiceSaleDraftRequest;
use App\Models\CustomerQrCode;
use App\Models\User;
use App\Services\VoiceSaleDraftService;
use App\Services\WorkSessionService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class VoiceSaleDraftController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        StoreVoiceSaleDraftRequest $request,
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

        try {
            $draft = $voiceSaleDraftService->fromAudio(
                audio: $request->file('audio'),
                user: $user,
                workSession: $workSession,
                language: $request->string('language')->toString() ?: 'es',
                notes: $request->string('notes')->toString() ?: null,
                customerId: $customerId,
                customerUuid: $customerUuid,
            );
        } catch (UnusableAudioException $exception) {
            // Expected user error (empty/noisy/corrupted audio): respond cleanly and don't log it.
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'audio' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'transcript' => $draft['transcript'],
            'submitted_at' => $draft['submitted_at'],
            'collaborator' => $draft['collaborator'],
            'branch' => $draft['branch'],
            'assumptions' => $draft['assumptions'],
            'sale_payload' => $draft['sale_payload'],
        ], 201);
    }

    protected function resolveCustomerIdFromUuid(?string $customerUuid): ?int
    {
        if ($customerUuid === null) {
            return null;
        }

        $customerId = CustomerQrCode::query()
            ->where('is_active', true)
            ->where('uuid', $customerUuid)
            ->whereHas('customer', fn ($query) => $query->active())
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
