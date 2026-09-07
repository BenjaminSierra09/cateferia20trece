<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IncomingWhatsAppMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $conversationId,
        public readonly string $contactName,
        public readonly string $messagePreview,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nuevo mensaje de '.$this->contactName)
            ->body($this->messagePreview)
            ->icon('/web-app-manifest-192x192.png')
            ->badge('/favicon-96x96.png')
            ->lang('es-MX')
            ->tag('whatsapp-conversation-'.$this->conversationId)
            ->renotify()
            ->vibrate([180, 80, 180])
            ->action('Abrir conversación', 'open_whatsapp')
            ->data([
                'url' => route('whatsapp.pwa', ['conversation' => $this->conversationId], absolute: false),
                'conversation_id' => $this->conversationId,
            ])
            ->options(['TTL' => 3600, 'urgency' => 'high']);
    }
}
