<?php

namespace App\Livewire\Sizes;

use App\Models\Size;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nuevo tamaño')]
class Create extends Component
{
    public ?Size $size = null;

    public string $name = '';

    public string $capacity_label = '';

    public ?float $capacity_ounces = null;

    public bool $is_active = true;

    public function mount(?Size $size = null): void
    {
        $this->size = $size?->exists ? $size : null;

        if ($this->size !== null) {
            $this->name = $this->size->name;
            $this->capacity_label = $this->size->capacity_label;
            $this->capacity_ounces = $this->size->capacity_ounces;
            $this->is_active = $this->size->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity_label' => ['required', 'string', 'max:255'],
            'capacity_ounces' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $size = Size::query()->updateOrCreate(
            ['id' => $this->size?->id],
            $validated,
        );

        Flux::toast(variant: 'success', text: $this->size ? 'Tamaño actualizado.' : 'Tamaño creado.');

        $this->redirectRoute('dashboard.sizes.edit', ['size' => $size], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.sizes.create')->layout('layouts.app');
    }
}
