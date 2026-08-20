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

class SendWhatsAppImage
{
    public function __construct(
        protected WhatsAppService $whatsApp,
    ) {}

    public function execute(
        WhatsAppConversation $conversation,
        UploadedFile $image,
        ?string $caption,
        User $sentBy,
    ): WhatsAppMessage {
        if (! $this->whatsApp->isConfigured()) {
            throw new WhatsAppCloudApiException(
                message: 'La API de WhatsApp no está configurada.',
                operation: 'send_image',
            );
        }

        $mimeType = (string) $image->getMimeType();
        $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
        $fileName = Str::uuid().'.'.$extension;
        $contents = file_get_contents($image->getRealPath());

        if ($contents === false) {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible preparar la foto.',
                operation: 'send_image',
            );
        }

        $mediaPath = $image->storeAs(
            'whatsapp/'.$conversation->id,
            $fileName,
            'local',
        );

        if (! is_string($mediaPath)) {
            throw new WhatsAppCloudApiException(
                message: 'No fue posible guardar la foto.',
                operation: 'send_image',
            );
        }

        $message = $conversation->messages()->create([
            'sent_by_user_id' => $sentBy->id,
            'direction' => WhatsAppMessageDirection::Outbound,
            'type' => 'image',
            'body' => filled($caption) ? trim((string) $caption) : null,
            'media_path' => $mediaPath,
            'media_mime_type' => $mimeType,
            'status' => WhatsAppMessageStatus::Queued,
            'sent_at' => now(),
        ]);

        try {
            $providerMessageId = $this->whatsApp->sendImage(
                number: $conversation->phone,
                contents: $contents,
                fileName: $fileName,
                mimeType: $mimeType,
                caption: $caption,
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
}
