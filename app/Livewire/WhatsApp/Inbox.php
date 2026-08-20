<?php

namespace App\Livewire\WhatsApp;

use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Models\User;
use App\Models\WhatsAppConversation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('WhatsApp')]
class Inbox extends Component
{
    public string $search = '';

    public ?int $selectedConversationId = null;

    public string $reply = '';

    public int $messageLimit = 60;

    public function mount(): void
    {
        $this->authorizeWhatsApp();

        $this->selectedConversationId = WhatsAppConversation::query()
            ->whereHas('messages')
            ->latest('last_message_at')
            ->value('id');
    }

    /**
     * @return Collection<int, WhatsAppConversation>
     */
    #[Computed]
    public function conversations(): Collection
    {
        return WhatsAppConversation::query()
            ->select([
                'id',
                'phone',
                'profile_name',
                'customer_id',
                'last_inbound_at',
                'last_message_at',
            ])
            ->with([
                'customer:id,name,phone',
                'latestMessage',
            ])
            ->whereHas('messages')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('phone', 'like', $search)
                        ->orWhere('profile_name', 'like', $search)
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->latest('last_message_at')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function selectedConversation(): ?WhatsAppConversation
    {
        if ($this->selectedConversationId === null) {
            return null;
        }

        $conversation = WhatsAppConversation::query()
            ->with('customer:id,name,phone')
            ->find($this->selectedConversationId);

        if ($conversation === null) {
            return null;
        }

        $messages = $conversation->messages()
            ->with('sentBy:id,name')
            ->latest('sent_at')
            ->latest('id')
            ->limit($this->messageLimit)
            ->get()
            ->reverse()
            ->values();

        return $conversation->setRelation('messages', $messages);
    }

    public function updatedSearch(): void
    {
        unset($this->conversations);
    }

    public function selectConversation(int $conversationId): void
    {
        $this->authorizeWhatsApp();

        $this->selectedConversationId = WhatsAppConversation::query()
            ->whereHas('messages')
            ->findOrFail($conversationId)
            ->id;
        $this->messageLimit = 60;
        $this->reset(['reply']);
        $this->resetErrorBag();
        unset($this->selectedConversation);
    }

    public function closeConversation(): void
    {
        $this->selectedConversationId = null;
        $this->reset(['reply']);
        $this->resetErrorBag();
        unset($this->selectedConversation);
    }

    public function loadEarlier(): void
    {
        $this->messageLimit = min($this->messageLimit + 50, 250);
        unset($this->selectedConversation);
    }

    public function send(SendWhatsAppTextMessage $sendMessage): void
    {
        $user = $this->authorizeWhatsApp();
        $validated = $this->validate([
            'reply' => ['required', 'string', 'max:4096'],
        ], [
            'reply.required' => 'Escribe un mensaje antes de enviarlo.',
            'reply.max' => 'El mensaje no puede exceder 4096 caracteres.',
        ]);

        $conversation = $this->selectedConversation;

        if ($conversation === null) {
            $this->addError('reply', 'Selecciona una conversación.');

            return;
        }

        if (! $conversation->hasOpenCustomerServiceWindow()) {
            $this->addError('reply', 'La ventana de atención de 24 horas ya terminó.');

            return;
        }

        try {
            $sendMessage->execute($conversation, trim($validated['reply']), $user);
        } catch (Throwable $throwable) {
            report($throwable);

            $this->addError('reply', 'No fue posible enviar el mensaje. Inténtalo nuevamente.');
            Flux::toast(variant: 'danger', text: 'No fue posible enviar el mensaje por WhatsApp.');

            return;
        }

        $this->reset(['reply']);
        unset($this->conversations, $this->selectedConversation);
        $this->dispatch('whatsapp-message-sent');

        Flux::toast(variant: 'success', text: 'Mensaje enviado por WhatsApp.');
    }

    public function render(): View
    {
        return view('livewire.whats-app.inbox')->layout('layouts.app');
    }

    protected function authorizeWhatsApp(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canManageWhatsApp(), 403);

        return $user;
    }
}
