<?php

namespace App\Http\Controllers\Api;

use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Http\Controllers\Controller;
use App\Jobs\HandleIncomingWhatsAppMessage;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageReaction;
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

            $this->updateMessageStatuses($value);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $value
     */
    protected function dispatchMessage(array $message, array $value): void
    {
        $phone = data_get($message, 'from');
        $messageId = data_get($message, 'id');
        $messageType = data_get($message, 'type');
        $timestamp = data_get($message, 'timestamp');

        if (! is_string($phone)
            || $phone === ''
            || ! is_string($messageType)
            || $messageType === '') {
            return;
        }

        HandleIncomingWhatsAppMessage::dispatch(
            phone: $phone,
            text: $this->messageBody($message, $messageType),
            pushName: $this->contactName($value, $phone),
            messageId: is_string($messageId) ? $messageId : null,
            messageType: $messageType,
            timestamp: is_numeric($timestamp) ? (int) $timestamp : null,
            reactionToMessageId: is_string(data_get($message, 'reaction.message_id'))
                ? data_get($message, 'reaction.message_id')
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function messageBody(array $message, string $messageType): string
    {
        if ($messageType === 'reaction') {
            $emoji = data_get($message, 'reaction.emoji');

            return is_string($emoji) ? trim($emoji) : '';
        }

        $body = match ($messageType) {
            'text' => data_get($message, 'text.body'),
            'button' => data_get($message, 'button.text'),
            'interactive' => data_get($message, 'interactive.button_reply.title')
                ?? data_get($message, 'interactive.list_reply.title'),
            'document' => data_get($message, 'document.filename'),
            default => null,
        };

        if (is_string($body) && trim($body) !== '') {
            return match ($messageType) {
                'document' => '[Documento] '.trim($body),
                default => trim($body),
            };
        }

        return match ($messageType) {
            'image' => '[Imagen]',
            'audio' => '[Audio]',
            'video' => '[Video]',
            'document' => '[Documento]',
            'sticker' => '[Sticker]',
            'location' => '[Ubicación]',
            'contacts' => '[Contacto]',
            default => '[Mensaje '.strtolower($messageType).']',
        };
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function updateMessageStatuses(array $value): void
    {
        $statuses = data_get($value, 'statuses', []);

        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $statusPayload) {
            if (! is_array($statusPayload)) {
                continue;
            }

            $providerMessageId = data_get($statusPayload, 'id');
            $statusValue = data_get($statusPayload, 'status');

            if (! is_string($providerMessageId) || ! is_string($statusValue)) {
                continue;
            }

            $status = WhatsAppMessageStatus::tryFrom($statusValue);
            $message = WhatsAppMessage::query()
                ->where('provider_message_id', $providerMessageId)
                ->where('direction', WhatsAppMessageDirection::Outbound)
                ->first();

            $message ??= WhatsAppMessageReaction::query()
                ->where('provider_message_id', $providerMessageId)
                ->where('direction', WhatsAppMessageDirection::Outbound)
                ->first();

            if ($status === null || $message === null || ! $this->canAdvanceStatus($message->status, $status)) {
                continue;
            }

            $errorCode = data_get($statusPayload, 'errors.0.code');

            $message->update([
                'status' => $status,
                'error_code' => $status === WhatsAppMessageStatus::Failed && (is_string($errorCode) || is_int($errorCode))
                    ? (string) $errorCode
                    : $message->error_code,
            ]);
        }
    }

    protected function canAdvanceStatus(WhatsAppMessageStatus $current, WhatsAppMessageStatus $next): bool
    {
        $rank = [
            WhatsAppMessageStatus::Queued->value => 0,
            WhatsAppMessageStatus::Sent->value => 1,
            WhatsAppMessageStatus::Delivered->value => 2,
            WhatsAppMessageStatus::Read->value => 3,
            WhatsAppMessageStatus::Failed->value => 4,
            WhatsAppMessageStatus::Received->value => 0,
        ];

        return $rank[$next->value] >= $rank[$current->value];
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
