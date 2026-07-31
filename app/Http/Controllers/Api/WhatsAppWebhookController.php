<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleIncomingWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        if (! $this->hasValidSignature($request)) {
            return response('', Response::HTTP_FORBIDDEN);
        }

        if ($request->input('object') !== 'whatsapp_business_account') {
            return response('EVENT_RECEIVED');
        }

        $entries = $request->input('entry', []);

        if (! is_array($entries)) {
            return response('EVENT_RECEIVED');
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->dispatchEntryMessages($entry);
        }

        return response('EVENT_RECEIVED');
    }

    protected function verify(Request $request): Response
    {
        $verifyToken = (string) config('services.whatsapp.webhook_verify_token');
        $providedToken = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode !== 'subscribe'
            || $challenge === null
            || $verifyToken === ''
            || ! hash_equals($verifyToken, $providedToken)) {
            return response('', Response::HTTP_FORBIDDEN);
        }

        return response((string) $challenge)
            ->header('Content-Type', 'text/plain');
    }

    protected function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $providedSignature = (string) $request->header('X-Hub-Signature-256', '');

        if ($appSecret === '' || $providedSignature === '') {
            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function dispatchEntryMessages(array $entry): void
    {
        $changes = data_get($entry, 'changes', []);

        if (! is_array($changes)) {
            return;
        }

        foreach ($changes as $change) {
            if (! is_array($change) || data_get($change, 'field') !== 'messages') {
                continue;
            }

            $value = data_get($change, 'value', []);

            if (! is_array($value)) {
                continue;
            }

            if (! $this->isConfiguredPhoneNumber($value)) {
                continue;
            }

            $messages = data_get($value, 'messages', []);

            if (! is_array($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                if (is_array($message)) {
                    $this->dispatchMessage($message, $value);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $value
     */
    protected function dispatchMessage(array $message, array $value): void
    {
        $phone = data_get($message, 'from');
        $text = data_get($message, 'text.body');
        $messageId = data_get($message, 'id');

        if (! is_string($phone)
            || $phone === ''
            || data_get($message, 'type') !== 'text'
            || ! is_string($text)
            || trim($text) === '') {
            return;
        }

        HandleIncomingWhatsAppMessage::dispatch(
            phone: $phone,
            text: trim($text),
            pushName: $this->contactName($value, $phone),
            messageId: is_string($messageId) ? $messageId : null,
        );
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function contactName(array $value, string $phone): ?string
    {
        $contacts = data_get($value, 'contacts', []);

        if (! is_array($contacts)) {
            return null;
        }

        foreach ($contacts as $contact) {
            if (! is_array($contact) || data_get($contact, 'wa_id') !== $phone) {
                continue;
            }

            $name = data_get($contact, 'profile.name');

            return is_string($name) && $name !== '' ? $name : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function isConfiguredPhoneNumber(array $value): bool
    {
        $configuredPhoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $payloadPhoneNumberId = data_get($value, 'metadata.phone_number_id');

        return $configuredPhoneNumberId === ''
            || (is_string($payloadPhoneNumberId)
                && hash_equals($configuredPhoneNumberId, $payloadPhoneNumberId));
    }
}
