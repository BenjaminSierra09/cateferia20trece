<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use RuntimeException;

class EvolutionWhatsAppService
{
    public function __construct(
        protected HttpFactory $http,
        protected CustomerCardRenderer $customerCardRenderer,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.evolution.api_key'))
            && filled(config('services.evolution.instance_id'))
            && filled($this->baseUrl());
    }

    public function sendCustomerCredential(Customer $customer, CustomerQrCode $qrCode): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $number = $this->normalizePhoneNumber($customer->phone);

        if ($number === null) {
            return;
        }

        $portalUrl = route('public.qr.show', ['uuid' => $qrCode->uuid]);
        $rewardsUrl = route('public.rewards');
        $fileName = sprintf('credencial-%s.png', Str::slug($customer->name ?: 'cliente'));
        $caption = sprintf(
            'Bienvenido, %s. Aqui tienes tu credencial digital con QR para identificar tu cuenta en caja.',
            $customer->name
        );
        $text = implode("\n\n", [
            sprintf('Hola %s, te damos la bienvenida a Cafe 20Trece.', $customer->name),
            'Tu credencial QR ya esta lista para presentar en caja.',
            'Programa de recompensas: '.$rewardsUrl,
            'Consulta tu cuenta y tus puntos: '.$portalUrl,
        ]);

        $this->sendMedia(
            number: $number,
            caption: $caption,
            mediaBase64: $this->customerCardRenderer->pngBase64($customer, $qrCode, asset('logotipo.png')),
            fileName: $fileName,
        );

        $this->sendText(
            number: $number,
            text: $text,
        );
    }

    protected function sendMedia(string $number, string $caption, string $mediaBase64, string $fileName): void
    {
        try {
            $this->client()->post($this->endpointUrl('sendMedia'), [
                'number' => $number,
                'mediatype' => 'image',
                'mimetype' => 'image/png',
                'caption' => $caption,
                'media' => $mediaBase64,
                'fileName' => $fileName,
                'delay' => 300,
                'linkPreview' => true,
                'mentionsEveryOne' => false,
                'mentioned' => [],
            ])->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible enviar la credencial QR por WhatsApp.', previous: $exception);
        }
    }

    protected function sendText(string $number, string $text): void
    {
        try {
            $this->client()->post($this->endpointUrl('sendText'), [
                'number' => $number,
                'text' => $text,
                'delay' => 300,
                'linkPreview' => true,
                'mentionsEveryOne' => false,
                'mentioned' => [],
            ])->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible enviar el mensaje de bienvenida por WhatsApp.', previous: $exception);
        }
    }

    protected function client()
    {
        return $this->http
            ->withHeaders([
                'apikey' => (string) config('services.evolution.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->retry([500, 1000], throw: false);
    }

    protected function endpointUrl(string $action): string
    {
        return sprintf(
            '%s/message/%s/%s',
            $this->baseUrl(),
            $action,
            rawurlencode((string) config('services.evolution.instance_id')),
        );
    }

    protected function baseUrl(): string
    {
        $configuredUrl = rtrim((string) config('services.evolution.api_url'), '/');

        if ($configuredUrl === '') {
            return '';
        }

        if (str_contains($configuredUrl, '/message/')) {
            return Str::before($configuredUrl, '/message/');
        }

        return $configuredUrl;
    }

    protected function normalizePhoneNumber(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return $normalized !== '' ? $normalized : null;
    }
}
