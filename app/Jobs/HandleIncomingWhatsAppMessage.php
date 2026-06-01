<?php

namespace App\Jobs;

use App\Ai\Agents\WhatsAppConcierge;
use App\Models\WhatsAppConversation;
use App\Services\EvolutionWhatsAppService;
use App\Support\CustomerPhoneMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CustomerPhoneMatcher $matcher, EvolutionWhatsAppService $whatsApp): void
    {
        $normalizedPhone = $matcher->normalize($this->phone);

        if ($normalizedPhone === '') {
            return;
        }

        /** @var WhatsAppConversation $conversation */
        $conversation = WhatsAppConversation::query()->firstOrNew(['phone' => $normalizedPhone]);

        // Ignore duplicate webhook deliveries of the same inbound message.
        if ($this->messageId !== null
            && $conversation->exists
            && $conversation->last_message_id === $this->messageId) {
            return;
        }

        $conversation->fill([
            'last_message_id' => $this->messageId,
            'last_inbound_at' => now(),
        ]);

        $customer = $matcher->find($this->phone);

        if ($customer === null) {
            $conversation->customer_id = null;
            $conversation->save();

            $whatsApp->sendMessage($this->phone, $this->registrationMessage());

            $conversation->forceFill(['last_outbound_at' => now()])->save();

            return;
        }

        $conversation->customer_id = $customer->id;
        $conversation->save();

        $agent = new WhatsAppConcierge($customer);
        $model = config('ai.whatsapp.model');

        $response = $conversation->conversation_id
            ? $agent->continue($conversation->conversation_id, as: $customer)->prompt($this->text, model: $model)
            : $agent->forUser($customer)->prompt($this->text, model: $model);

        $conversation->forceFill([
            'conversation_id' => $response->conversationId ?? $conversation->conversation_id,
            'last_outbound_at' => now(),
        ])->save();

        $whatsApp->sendMessage($this->phone, $response->text);
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
