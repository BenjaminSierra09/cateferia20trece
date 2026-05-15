<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GoogleRecaptchaVerifier
{
    /**
     * Determine if reCAPTCHA verification is enabled.
     */
    public function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    /**
     * Verify a reCAPTCHA submission token.
     *
     * @return array{successful: bool, message: string}
     */
    public function verify(string $token, ?string $ipAddress, string $expectedAction): array
    {
        if (! $this->enabled()) {
            return [
                'successful' => true,
                'message' => '',
            ];
        }

        if (blank($token)) {
            return [
                'successful' => false,
                'message' => 'No pudimos validar que eres una persona real. Intenta enviar el formulario de nuevo.',
            ];
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->retry([200, 500], throw: false)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);
        } catch (ConnectionException) {
            return [
                'successful' => false,
                'message' => 'No fue posible validar Recaptcha en este momento. Intenta de nuevo.',
            ];
        }

        if (! $response->ok()) {
            return [
                'successful' => false,
                'message' => 'No fue posible validar Recaptcha en este momento. Intenta de nuevo.',
            ];
        }

        $payload = $response->json();
        $score = (float) data_get($payload, 'score', 0);
        $minimumScore = (float) config('services.recaptcha.min_score', 0.5);

        if (! data_get($payload, 'success', false)) {
            return [
                'successful' => false,
                'message' => 'No pudimos confirmar la validación de seguridad. Intenta nuevamente.',
            ];
        }

        if (data_get($payload, 'action') !== $expectedAction) {
            return [
                'successful' => false,
                'message' => 'La validación de seguridad no coincide con este formulario.',
            ];
        }

        if ($score < $minimumScore) {
            return [
                'successful' => false,
                'message' => 'La validación de seguridad no alcanzó el puntaje mínimo. Intenta de nuevo.',
            ];
        }

        return [
            'successful' => true,
            'message' => '',
        ];
    }
}
