<?php

namespace App\Livewire\WhatsApp;

use App\Actions\WhatsApp\SendWhatsAppAudio;
use App\Actions\WhatsApp\SendWhatsAppImage;
use App\Actions\WhatsApp\SendWhatsAppReaction;
use App\Actions\WhatsApp\SendWhatsAppTextMessage;
use App\Models\User;
use App\Models\WhatsAppConversation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Title('WhatsApp')]
class Inbox extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $selectedConversationId = null;

    public string $reply = '';

    public $photo;

    public $audio;

    public int $messageLimit = 60;

    public function mount(?int $conversation = null): void
    {
        $this->authorizeWhatsApp();

        $requestedConversationId = $conversation ?? request()->integer('conversation');

        $this->selectedConversationId = WhatsAppConversation::query()
            ->whereHas('messages')
            ->when(
                $requestedConversationId > 0,
                fn ($query) => $query->whereKey($requestedConversationId),
            )
            ->latest('last_message_at')
            ->latest('id')
            ->value('id');

        if ($this->selectedConversationId === null && $requestedConversationId > 0) {
            $this->selectedConversationId = WhatsAppConversation::query()
                ->whereHas('messages')
                ->latest('last_message_at')
                ->latest('id')
                ->value('id');
        }
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
                'bot_paused_at',
                'bot_paused_by_user_id',
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
            ->latest('id')
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
            ->with(['sentBy:id,name', 'reactions.sentBy:id,name'])
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
        $this->reset(['reply', 'photo', 'audio']);
        $this->resetErrorBag();
        unset($this->selectedConversation);
    }

    public function closeConversation(): void
    {
        $this->selectedConversationId = null;
        $this->reset(['reply', 'photo', 'audio']);
        $this->resetErrorBag();
        unset($this->selectedConversation);
    }

    public function loadEarlier(): void
    {
        $this->messageLimit = min($this->messageLimit + 50, 250);
        unset($this->selectedConversation);
    }

    public function send(
        SendWhatsAppTextMessage $sendMessage,
        SendWhatsAppImage $sendImage,
        SendWhatsAppAudio $sendAudio,
    ): void {
        $user = $this->authorizeWhatsApp();
        $validated = $this->validate([
            'reply' => ['nullable', 'string', 'max:4096'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
            'audio' => [
                'nullable',
                'file',
                'mimes:aac,amr,mp3,m4a,mp4,ogg,oga',
                'mimetypes:audio/aac,audio/amr,audio/mpeg,audio/mp4,audio/ogg,application/ogg',
                'max:12288',
            ],
        ], [
            'reply.max' => 'El mensaje no puede exceder 4096 caracteres.',
            'photo.image' => 'Selecciona una foto válida.',
            'photo.mimes' => 'La foto debe ser JPG o PNG.',
            'photo.mimetypes' => 'La foto debe ser JPG o PNG.',
            'photo.max' => 'La foto no puede pesar más de 5 MB.',
            'audio.file' => 'Selecciona un audio válido.',
            'audio.mimes' => 'El audio debe ser AAC, AMR, MP3, M4A u OGG con Opus.',
            'audio.mimetypes' => 'El formato del audio no es compatible con WhatsApp.',
            'audio.max' => 'El audio no puede pesar más de 12 MB.',
        ]);

        $body = trim((string) ($validated['reply'] ?? ''));

        if ($this->photo === null && $this->audio === null && $body === '') {
            $this->addError('reply', 'Escribe un mensaje o selecciona una foto o audio.');

            return;
        }

        if ($this->photo !== null && $this->audio !== null) {
            $this->addError('audio', 'Envía la foto y el audio por separado.');

            return;
        }

        if ($this->audio !== null && $body !== '') {
            $this->addError('reply', 'Los audios no admiten texto. Envíalos por separado.');

            return;
        }

        if ($this->photo !== null && mb_strlen($body) > 1024) {
            $this->addError('reply', 'El texto de una foto no puede exceder 1024 caracteres.');

            return;
        }

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
            if ($this->audio !== null) {
                $sendAudio->execute($conversation, $this->audio, $user);
            } elseif ($this->photo !== null) {
                $sendImage->execute($conversation, $this->photo, $body !== '' ? $body : null, $user);
            } else {
                $sendMessage->execute($conversation, $body, $user);
            }
        } catch (Throwable $throwable) {
            report($throwable);

            $this->addError('reply', 'No fue posible enviar el mensaje. Inténtalo nuevamente.');
            Flux::toast(variant: 'danger', text: 'No fue posible enviar el mensaje por WhatsApp.');

            return;
        }

        $this->reset(['reply', 'photo', 'audio']);
        unset($this->conversations, $this->selectedConversation);
        $this->dispatch('whatsapp-message-sent');

        Flux::toast(variant: 'success', text: 'Mensaje enviado por WhatsApp.');
    }

    public function removePhoto(): void
    {
        $this->authorizeWhatsApp();
        $this->reset('photo');
        $this->resetValidation('photo');
    }

    public function removeAudio(): void
    {
        $this->authorizeWhatsApp();
        $this->reset('audio');
        $this->resetValidation('audio');
    }

    public function toggleBot(): void
    {
        $user = $this->authorizeWhatsApp();
        $conversation = $this->selectedConversation;

        if ($conversation === null) {
            return;
        }

        $isPausing = ! $conversation->isBotPaused();
        $conversation->update([
            'bot_paused_at' => $isPausing ? now() : null,
            'bot_paused_by_user_id' => $isPausing ? $user->id : null,
        ]);

        unset($this->conversations, $this->selectedConversation);

        Flux::toast(
            variant: $isPausing ? 'warning' : 'success',
            text: $isPausing ? 'Bot pausado para esta conversación.' : 'Bot reactivado para esta conversación.',
        );
    }

    public function react(int $messageId, string $emoji, SendWhatsAppReaction $sendReaction): void
    {
        $user = $this->authorizeWhatsApp();
        $conversation = $this->selectedConversation;

        if ($conversation === null || ! in_array($emoji, SendWhatsAppReaction::EMOJIS, true)) {
            abort(422);
        }

        $target = $conversation->messages()->findOrFail($messageId);

        if (! $target->canReceiveReaction()) {
            Flux::toast(variant: 'danger', text: 'Este mensaje ya no admite reacciones en Meta.');

            return;
        }

        try {
            $sendReaction->execute($conversation, $target, $emoji, $user);
        } catch (Throwable $throwable) {
            report($throwable);
            Flux::toast(variant: 'danger', text: 'No fue posible enviar la reacción.');

            return;
        }

        unset($this->selectedConversation);
        Flux::toast(variant: 'success', text: 'Reacción enviada.');
    }

    public function render(): View
    {
        return view('livewire.whats-app.inbox', ['standalone' => false])->layout('layouts.app');
    }

    protected function authorizeWhatsApp(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canManageWhatsApp(), 403);

        return $user;
    }
}
