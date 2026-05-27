<?php

namespace App\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;

class MercadoPagoPointException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
        public readonly ?string $mercadoPagoCode = null,
        public readonly array $payload = [],
    ) {
        parent::__construct($message);
    }

    public static function fromRequestException(RequestException $exception): self
    {
        $payload = $exception->response->json();
        $payload = is_array($payload) ? $payload : ['message' => $exception->getMessage()];

        $mercadoPagoCode = data_get($payload, 'errors.0.code');
        $message = match ($mercadoPagoCode) {
            'already_queued_order_on_terminal' => 'La terminal Point ya tiene un cobro pendiente. Termina o cancela ese cobro en la terminal antes de enviar otro.',
            default => data_get($payload, 'errors.0.message')
                ?: data_get($payload, 'message')
                ?: 'Mercado Pago no pudo procesar la solicitud.',
        };

        return new self(
            message: $message,
            statusCode: $exception->response->status(),
            mercadoPagoCode: $mercadoPagoCode,
            payload: $payload,
        );
    }
}
