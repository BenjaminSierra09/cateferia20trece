<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsAppService;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

class SendWhatsAppAudio
{
    /** @var array<string, string> */
    private const EXTENSIONS_BY_MIME = [
        'audio/aac' => 'aac',
        'audio/amr' => 'amr',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/ogg' => 'ogg',
    ];

    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function execute(
        WhatsAppConversation $conversation,
        UploadedFile $audio,
        User $sentBy,
    ): WhatsAppMessage {
        if (! $this->whatsApp->isConfigured()) {
            throw new WhatsAppCloudApiException(
                message: 'La API de WhatsApp no está configurada.',
                operation: 'send_audio',
            );
        }

        $mimeType = $this->normalizeMimeType((string) $audio->getMimeType());
        $extension = self::EXTENSIONS_BY_MIME[$mimeType] ?? null;

        if ($extension === null) {
            throw new WhatsAppCloudApiException(
                message: 'El formato del audio no es compatible con WhatsApp.',
                operation: 'send_audio',
            );
        }

        $fileName = Str::uuid().'.'.$extension;
        $contents = file_get_contents($audio->getRealPath());

        if ($contents === false || $contents === '') {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible preparar el audio.',
                operation: 'send_audio',
            );
        }

        $mediaPath = $audio->storeAs(
            'whatsapp/'.$conversation->id,
            $fileName,
            'local',
        );

        if (! is_string($mediaPath)) {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible guardar el audio.',
                operation: 'send_audio',
            );
        }

        $message = $conversation->messages()->create([
            'sent_by_user_id' => $sentBy->id,
            'direction' => WhatsAppMessageDirection::Outbound,
            'type' => 'audio',
            'body' => null,
            'media_path' => $mediaPath,
            'media_mime_type' => $mimeType,
            'status' => WhatsAppMessageStatus::Queued,
            'sent_at' => now(),
        ]);

        try {
            $providerMessageId = $this->whatsApp->sendAudio(
                number: $conversation->phone,
                contents: $contents,
                fileName: $fileName,
                mimeType: $mimeType,
            );

            $message->update([
                'provider_message_id' => $providerMessageId,
                'status' => WhatsAppMessageStatus::Sent,
                'error_code' => null,
            ]);

            $conversation->update([
                'last_outbound_at' => $message->sent_at,
                'last_message_at' => $message->sent_at,
            ]);
        } catch (Throwable $throwable) {
            $message->update([
                'status' => WhatsAppMessageStatus::Failed,
                'error_code' => $throwable instanceof WhatsAppCloudApiException
                    ? (string) ($throwable->graphCode ?? $throwable->status ?? 'unknown')
                    : 'unknown',
            ]);

            throw $throwable;
        }

        return $message->refresh();
    }

    private function normalizeMimeType(string $mimeType): string
    {
        $normalized = strtolower(trim(Str::before($mimeType, ';')));

        return match ($normalized) {
            'audio/mp3' => 'audio/mpeg',
            'audio/m4a', 'audio/x-m4a' => 'audio/mp4',
            'application/ogg' => 'audio/ogg',
            default => $normalized,
        };
    }
}
