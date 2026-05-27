<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MercadoPagoPointOrder;
use App\Models\MercadoPagoWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->all();
        $eventId = data_get($payload, 'id') ?? data_get($payload, 'event_id');
        $topic = $request->query('topic') ?? data_get($payload, 'topic');
        $type = data_get($payload, 'type') ?? $topic;
        $action = data_get($payload, 'action');
        $resourceId = data_get($payload, 'data.id') ?? $request->query('id') ?? data_get($payload, 'resource');
        $externalReference = data_get($payload, 'external_reference') ?? data_get($payload, 'data.external_reference');
        $mercadoPagoOrderId = data_get($payload, 'order.id') ?? data_get($payload, 'data.id');

        $pointOrder = MercadoPagoPointOrder::query()
            ->when($externalReference, fn ($query) => $query->where('external_reference', $externalReference))
            ->when(! $externalReference && $mercadoPagoOrderId, fn ($query) => $query->where('mercado_pago_order_id', $mercadoPagoOrderId))
            ->first();

        $event = MercadoPagoWebhookEvent::query()->create([
            'mercado_pago_point_order_id' => $pointOrder?->id,
            'event_id' => $eventId,
            'topic' => $topic,
            'type' => $type,
            'action' => $action,
            'resource_id' => $resourceId,
            'external_reference' => $externalReference,
            'mercado_pago_order_id' => $mercadoPagoOrderId,
            'headers' => collect($request->headers->all())->map(fn (array $values): ?string => $values[0] ?? null)->all(),
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        if ($pointOrder !== null) {
            $status = data_get($payload, 'status') ?? data_get($payload, 'data.status') ?? data_get($payload, 'order.status');

            $pointOrder->update([
                'status' => $status ?? $pointOrder->status,
                'last_webhook_payload' => $payload,
                'completed_at' => in_array($status, ['processed', 'paid', 'closed'], true) ? now() : $pointOrder->completed_at,
            ]);
        }

        return response()->noContent();
    }
}
