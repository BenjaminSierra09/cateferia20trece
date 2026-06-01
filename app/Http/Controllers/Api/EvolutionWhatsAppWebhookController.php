<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleIncomingWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EvolutionWhatsAppWebhookController extends Controller
{
    /**
     * Handle an inbound Evolution API webhook (messages.upsert).
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->isAuthorized($request)) {
            return response('', 401);
        }

        if ($this->normalizedEvent($request) === 'messages.upsert') {
            foreach ($this->extractMessages($request->input('data', [])) as $message) {
                if (is_array($message)) {
                    $this->dispatchMessage($message);
                }
            }
        }

        return response()->noContent();
    }

    /**
     * Verify the optional shared secret when one is configured.
     */
    private function isAuthorized(Request $request): bool
    {
        $token = config('services.evolution.webhook_token');

        if (blank($token)) {
            return true;
        }

        $provided = (string) ($request->header('X-Webhook-Token')
            ?? $request->header('apikey')
            ?? $request->query('token')
            ?? '');

        return $provided !== '' && hash_equals((string) $token, $provided);
    }

    /**
     * Normalize the event name to dot-notation lowercase (e.g. "MESSAGES_UPSERT" => "messages.upsert").
     */
    private function normalizedEvent(Request $request): string
    {
        return (string) Str::of((string) $request->input('event'))
            ->lower()
            ->replace('_', '.');
    }

    /**
     * Evolution may deliver a single message object or a list of them.
     *
     * @return array<int, mixed>
     */
    private function extractMessages(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        return array_is_list($data) ? $data : [$data];
    }

    /**
     * Dispatch a single inbound text message for processing.
     *
     * @param  array<string, mixed>  $message
     */
    private function dispatchMessage(array $message): void
    {
        if (data_get($message, 'key.fromMe') === true) {
            return;
        }

        $remoteJid = (string) data_get($message, 'key.remoteJid', '');

        if ($remoteJid === '' || Str::contains($remoteJid, ['@g.us', '@broadcast'])) {
            return;
        }

        $text = data_get($message, 'message.conversation')
            ?? data_get($message, 'message.extendedTextMessage.text');

        if (! is_string($text) || trim($text) === '') {
            return;
        }

        HandleIncomingWhatsAppMessage::dispatch(
            phone: Str::before($remoteJid, '@'),
            text: trim($text),
            pushName: data_get($message, 'pushName'),
            messageId: data_get($message, 'key.id'),
        );
    }
}
