<?php

namespace App\Livewire\WhatsApp;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;

#[Title('WhatsApp')]
class PwaInbox extends Inbox
{
    public function render(): View
    {
        return view('livewire.whats-app.inbox', ['standalone' => true])
            ->layout('layouts.whatsapp-pwa');
    }
}
