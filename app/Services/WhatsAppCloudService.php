<?php

namespace App\Services;

use App\Contracts\WhatsAppService;
use App\Exceptions\WhatsAppCloudApiException;
use App\Http\Requests\PublicInvoiceRequest;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\Sale;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

class WhatsAppCloudService implements WhatsAppService
{
    public function __construct(
        protected HttpFactory $http,
        protected CustomerCardRenderer $customerCardRenderer,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.access_token'))
            && filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.graph_version'))
            && filled(config('services.whatsapp.api_url'));
    }

    public function sendMessage(string $number, string $text): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $normalizedNumber = $this->normalizePhoneNumber($number);

        if ($normalizedNumber === null || trim($text) === '') {
            return;
        }

        $this->sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $normalizedNumber,
                'type' => 'text',
                'text' => [
                    'preview_url' => true,
                    'body' => $text,
                ],
            ],
            operation: 'send_text',
            failureMessage: 'No fue posible enviar la respuesta de WhatsApp.',
        );
    }

    public function sendCustomerCredential(Customer $customer, CustomerQrCode $qrCode): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $normalizedNumber = $this->normalizePhoneNumber($customer->phone);

        if ($normalizedNumber === null) {
            return;
        }

        $fileName = sprintf('credencial-%s.png', Str::slug($customer->name ?: 'cliente'));
        $mediaId = $this->uploadImage(
            mediaBase64: $this->customerCardRenderer->pngBase64($customer, $qrCode, asset('logotipo.png')),
            fileName: $fileName,
        );

        $this->sendTemplate(
            number: $normalizedNumber,
            name: (string) config('services.whatsapp.templates.customer_credential'),
            components: [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'image',
                            'image' => ['id' => $mediaId],
                        ],
                    ],
                ],
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $customer->name],
                        ['type' => 'text', 'text' => route('public.rewards')],
                        ['type' => 'text', 'text' => route('public.qr.show', ['uuid' => $qrCode->uuid])],
                    ],
                ],
            ],
            operation: 'send_customer_credential',
            failureMessage: 'No fue posible enviar la credencial QR por WhatsApp.',
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendInvoiceRequestToAccounting(Sale $sale, array $data): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $normalizedNumber = $this->normalizePhoneNumber(config('services.invoicing.whatsapp'));

        if ($normalizedNumber === null) {
            return;
        }

        $sale->loadMissing(['branch', 'items.customizations']);

        $regimenLabel = PublicInvoiceRequest::REGIMENES[$data['regimen_fiscal']] ?? $data['regimen_fiscal'];
        $invoicePaymentMethod = $data['invoice_payment_method'].' - '.PublicInvoiceRequest::PAYMENT_FORMS[$data['invoice_payment_method']];
        $soldAt = $sale->sold_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin fecha';
        $items = $sale->items
            ->map(function ($item): string {
                $customizations = $item->customizations
                    ->pluck('customization_name')
                    ->filter()
                    ->implode(', ');

                $line = sprintf('%s x %s', $item->quantity, $item->item_name);

                return $customizations !== ''
                    ? $line.' ('.$customizations.')'
                    : $line;
            })
            ->implode("\n");

        $text = implode("\n\n", array_filter([
            sprintf('Codigo de facturacion: %s', $data['billing_token']),
            sprintf('Venta: %s', $soldAt),
            sprintf('Sucursal: %s', $sale->branch?->name ?? 'Sin sucursal'),
            sprintf('Total: $%s', number_format((float) $sale->total, 2)),
            sprintf('Metodo registrado en venta: %s', $sale->paymentMethodSummary()),
            sprintf('Metodo de pago para CFDI: %s', $invoicePaymentMethod),
            $items !== '' ? "Productos:\n".$items : null,
            implode("\n", [
                'Datos fiscales:',
                sprintf('RFC: %s', $data['rfc']),
                sprintf('Razon social: %s', $data['razon_social']),
                sprintf('Regimen fiscal: %s - %s', $data['regimen_fiscal'], $regimenLabel),
                sprintf('Codigo postal: %s', $data['codigo_postal']),
                sprintf('Correo: %s', $data['email']),
                sprintf('Telefono: %s', $data['telefono']),
            ]),
        ]));

        $this->sendTemplate(
            number: $normalizedNumber,
            name: (string) config('services.whatsapp.templates.invoice_request'),
            components: [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
            operation: 'send_invoice_request',
            failureMessage: 'No fue posible enviar la solicitud de factura por WhatsApp.',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    protected function sendTemplate(
        string $number,
        string $name,
        array $components,
        string $operation,
        string $failureMessage,
    ): void {
        if (blank($name)) {
            throw new WhatsAppCloudApiException(
                message: 'No está configurada la plantilla de WhatsApp requerida.',
                operation: $operation,
            );
        }

        $this->sendPayload(
            payload: [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $number,
                'type' => 'template',
                'template' => [
                    'name' => $name,
                    'language' => [
                        'code' => (string) config('services.whatsapp.templates.language'),
                    ],
                    'components' => $components,
                ],
            ],
            operation: $operation,
            failureMessage: $failureMessage,
        );
    }

    protected function uploadImage(string $mediaBase64, string $fileName): string
    {
        $contents = base64_decode($mediaBase64, true);

        if ($contents === false) {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible preparar la credencial QR para WhatsApp.',
                operation: 'upload_media',
            );
        }

        $response = $this->executeRequest(
            request: fn (): Response => $this->client()
                ->attach('file', $contents, $fileName, ['Content-Type' => 'image/png'])
                ->post($this->endpoint('media'), [
                    'messaging_product' => 'whatsapp',
                    'type' => 'image/png',
                ]),
            operation: 'upload_media',
            failureMessage: 'No fue posible subir la credencial QR a WhatsApp.',
        );

        $mediaId = $response->json('id');

        if (! is_string($mediaId) || $mediaId === '') {
            throw new WhatsAppCloudApiException(
                message: 'WhatsApp no devolvió el identificador de la credencial QR.',
                operation: 'upload_media',
                status: $response->status(),
            );
        }

        return $mediaId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendPayload(array $payload, string $operation, string $failureMessage): void
    {
        $this->executeRequest(
            request: fn (): Response => $this->client()->post($this->endpoint('messages'), $payload),
            operation: $operation,
            failureMessage: $failureMessage,
        );
    }

    /**
     * @param  Closure(): Response  $request
     */
    protected function executeRequest(
        Closure $request,
        string $operation,
        string $failureMessage,
    ): Response {
        try {
            return $request()->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw WhatsAppCloudApiException::fromHttpFailure(
                message: $failureMessage,
                operation: $operation,
                exception: $exception,
            );
        }
    }

    protected function client(): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim((string) config('services.whatsapp.api_url'), '/'))
            ->withToken((string) config('services.whatsapp.access_token'))
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->retry(
                [500, 1000],
                when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && ($exception->response->serverError() || $exception->response->tooManyRequests())),
                throw: false,
            );
    }

    protected function endpoint(string $resource): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->graphVersion(),
            rawurlencode((string) config('services.whatsapp.phone_number_id')),
            $resource,
        );
    }

    protected function graphVersion(): string
    {
        $version = trim((string) config('services.whatsapp.graph_version'), '/');

        return str_starts_with($version, 'v') ? $version : 'v'.$version;
    }

    protected function normalizePhoneNumber(mixed $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (mb_strlen($digits) === 10) {
            return '52'.$digits;
        }

        if (mb_strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return '52'.mb_substr($digits, 3);
        }

        return $digits;
    }
}
