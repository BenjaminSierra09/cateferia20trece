<?php

namespace App\Livewire\AztecSymbols;

use App\Models\AztecSymbol;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Símbolos aztecas')]
class Manager extends Component
{
    public function render(): View
    {
        return view('livewire.aztec-symbols.manager', [
            'symbols' => AztecSymbol::query()
                ->orderBy('sort_order')
                ->get(),
        ])->layout('layouts.app');
    }
}
