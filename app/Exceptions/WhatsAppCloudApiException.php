<?php

namespace App\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class WhatsAppCloudApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $operation,
        public readonly ?int $status = null,
        public readonly ?int $graphCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function fromHttpFailure(
        string $message,
        string $operation,
        Throwable $exception,
    ): self {
        $response = $exception instanceof RequestException ? $exception->response : null;
        $graphCode = $response?->json('error.code');

        return new self(
            message: $message,
            operation: $operation,
            status: $response?->status(),
            graphCode: is_numeric($graphCode) ? (int) $graphCode : null,
            previous: $exception,
        );
    }

    /**
     * @return array{operation: string, status?: int, graph_code?: int}
     */
    public function context(): array
    {
        return array_filter([
            'operation' => $this->operation,
            'status' => $this->status,
            'graph_code' => $this->graphCode,
        ], fn (mixed $value): bool => $value !== null);
    }
}
