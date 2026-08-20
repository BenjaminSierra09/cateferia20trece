<?php

namespace App\Jobs;

use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Ai\Agents\WhatsAppConcierge;
use App\Enums\WhatsAppMessageDirection;
use App\Enums\WhatsAppMessageStatus;
use App\Models\WhatsAppConversation;
use App\Support\CustomerPhoneMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;

#[Tries(3)]
#[Backoff([10, 30, 60])]
#[Timeout(60)]
class HandleIncomingWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $text,
        public ?string $pushName = null,
        public ?string $messageId = null,
        public string $messageType = 'text',
        public ?int $timestamp = null,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('whatsapp:'.$this->phone))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(CustomerPhoneMatcher $matcher, SendWhatsAppTextMessage $sendMessage): void
    {
        $normalizedPhone = $matcher->normalize($this->phone);

        if ($normalizedPhone === '') {
            return;
        }

        $customer = $matcher->find($this->phone);
        $receivedAt = $this->timestamp !== null
            ? CarbonImmutable::createFromTimestampUTC($this->timestamp)
            : now();

        /** @var WhatsAppConversation $conversation */
        $conversation = WhatsAppConversation::query()->firstOrNew(['phone' => $normalizedPhone]);
        $conversation->fill([
            'profile_name' => filled($this->pushName) ? $this->pushName : $conversation->profile_name,
            'customer_id' => $customer?->id,
            'last_message_id' => $this->messageId,
            'last_inbound_at' => $receivedAt,
            'last_message_at' => $receivedAt,
        ])->save();

        $messageAttributes = [
            'direction' => WhatsAppMessageDirection::Inbound,
            'type' => $this->messageType,
            'body' => $this->text,
            'status' => WhatsAppMessageStatus::Received,
            'sent_at' => $receivedAt,
        ];

        if ($this->messageId !== null) {
            $message = $conversation->messages()->firstOrCreate(
                ['provider_message_id' => $this->messageId],
                $messageAttributes,
            );

            if (! $message->wasRecentlyCreated) {
                return;
            }
        } else {
            $conversation->messages()->create($messageAttributes);
        }

        if ($this->messageType !== 'text') {
            return;
        }

        if ($customer === null) {
            $sendMessage->execute($conversation, $this->registrationMessage());

            return;
        }

        $agent = new WhatsAppConcierge($customer);
        $model = config('ai.whatsapp.model');

        $response = $conversation->conversation_id
            ? $agent->continue($conversation->conversation_id, as: $customer)->prompt($this->text, model: $model)
            : $agent->forUser($customer)->prompt($this->text, model: $model);

        $conversation->forceFill([
            'conversation_id' => $response->conversationId ?? $conversation->conversation_id,
        ])->save();

        $sendMessage->execute($conversation, $response->text);
    }

    /**
     * Deterministic, AI-free reply inviting an unknown number to register.
     */
    private function registrationMessage(): string
    {
        $greeting = filled($this->pushName) ? '¡Hola, '.$this->pushName.'!' : '¡Hola!';

        return $greeting.' Soy el asistente de Café 20Trece ☕. '
            .'Todavía no encuentro tu número en nuestro programa de clientes. '
            .'Regístrate aquí para consultar tu saldo, ver tus bebidas favoritas y hacer pedidos: '
            .route('public.register');
    }
}
