<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsAppService;
use App\Exceptions\WhatsAppCloudApiException;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreIncomingWhatsAppAudio
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

    /**
     * @return array{media_path: string, media_mime_type: string}
     */
    public function execute(
        WhatsAppConversation $conversation,
        string $mediaId,
        ?string $webhookMimeType = null,
    ): array {
        $media = $this->whatsApp->downloadMedia($mediaId);
        $mimeType = $this->normalizeMimeType($media['mime_type'] ?: (string) $webhookMimeType);
        $extension = self::EXTENSIONS_BY_MIME[$mimeType] ?? null;

        if ($extension === null) {
            throw new WhatsAppCloudApiException(
                message: 'El formato del audio recibido no es compatible.',
                operation: 'store_incoming_audio',
            );
        }

        if ($media['sha256'] !== null
            && ! hash_equals(strtolower($media['sha256']), hash('sha256', $media['contents']))) {
            throw new WhatsAppCloudApiException(
                message: 'El audio recibido no superó la verificación de integridad.',
                operation: 'store_incoming_audio',
            );
        }

        $mediaPath = sprintf(
            'whatsapp/%d/%s.%s',
            $conversation->id,
            Str::uuid(),
            $extension,
        );

        if (! Storage::disk('local')->put($mediaPath, $media['contents'])) {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible guardar el audio recibido.',
                operation: 'store_incoming_audio',
            );
        }

        return [
            'media_path' => $mediaPath,
            'media_mime_type' => $mimeType,
        ];
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
