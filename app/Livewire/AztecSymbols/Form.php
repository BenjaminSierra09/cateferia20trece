<?php

namespace App\Livewire\AztecSymbols;

use App\Models\AztecSymbol;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar símbolo azteca')]
class Form extends Component
{
    public AztecSymbol $aztecSymbol;

    public string $name = '';

    public ?string $spanishName = null;

    public ?string $deity = null;

    public ?string $bodyArea = null;

    public ?string $meaning = null;

    public ?string $serviceDescription = null;

    public ?string $customerGreeting = null;

    public ?string $tasteProfile = null;

    public string $recommendedItemsText = '';

    public bool $isActive = true;

    public function mount(AztecSymbol $aztecSymbol): void
    {
        $this->aztecSymbol = $aztecSymbol;
        $this->fillFromSymbol();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('aztec_symbols', 'name')->ignore($this->aztecSymbol)],
            'spanishName' => ['nullable', 'string', 'max:255'],
            'deity' => ['nullable', 'string', 'max:255'],
            'bodyArea' => ['nullable', 'string', 'max:255'],
            'meaning' => ['nullable', 'string'],
            'serviceDescription' => ['nullable', 'string'],
            'customerGreeting' => ['nullable', 'string'],
            'tasteProfile' => ['nullable', 'string'],
            'recommendedItemsText' => ['nullable', 'string'],
            'isActive' => ['boolean'],
        ]);

        $this->aztecSymbol->update([
            'name' => $validated['name'],
            'slug' => str($validated['name'])->slug()->toString(),
            'spanish_name' => $validated['spanishName'],
            'deity' => $validated['deity'],
            'body_area' => $validated['bodyArea'],
            'meaning' => $validated['meaning'],
            'service_description' => $validated['serviceDescription'],
            'customer_greeting' => $validated['customerGreeting'],
            'taste_profile' => $validated['tasteProfile'],
            'recommended_items' => $this->recommendedItems(),
            'is_active' => $validated['isActive'],
        ]);

        $this->aztecSymbol = $this->aztecSymbol->fresh();
        $this->fillFromSymbol();

        Flux::toast(variant: 'success', text: 'Símbolo actualizado.');
    }

    public function render(): View
    {
        return view('livewire.aztec-symbols.form')->layout('layouts.app');
    }

    private function fillFromSymbol(): void
    {
        $this->name = $this->aztecSymbol->name;
        $this->spanishName = $this->aztecSymbol->spanish_name;
        $this->deity = $this->aztecSymbol->deity;
        $this->bodyArea = $this->aztecSymbol->body_area;
        $this->meaning = $this->aztecSymbol->meaning;
        $this->serviceDescription = $this->aztecSymbol->service_description;
        $this->customerGreeting = $this->aztecSymbol->customer_greeting;
        $this->tasteProfile = $this->aztecSymbol->taste_profile;
        $this->recommendedItemsText = collect($this->aztecSymbol->recommended_items ?? [])->join("\n");
        $this->isActive = $this->aztecSymbol->is_active;
    }

    /**
     * @return list<string>
     */
    private function recommendedItems(): array
    {
        return str($this->recommendedItemsText)
            ->replace("\r\n", "\n")
            ->explode("\n")
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
