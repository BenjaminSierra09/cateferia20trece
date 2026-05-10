<?php

namespace App\Livewire\Branches;

use App\Models\Branch;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva sucursal')]
class Create extends Component
{
    protected const PHONE_REGEX = '/^\+[1-9]\d{7,14}$/';

    public ?Branch $branch = null;

    public string $name = '';

    public string $city = '';

    public string $address = '';

    public string $phone = '';

    public string $operating_hours = '07:00 - 21:00';

    public bool $is_active = true;

    public function mount(?Branch $branch = null): void
    {
        $this->branch = $branch?->exists ? $branch : null;

        if ($this->branch !== null) {
            $this->name = $this->branch->name;
            $this->city = $this->branch->city ?? '';
            $this->address = $this->branch->address ?? '';
            $this->phone = $this->branch->phone ?? '';
            $this->operating_hours = $this->branch->operating_hours ?? '07:00 - 21:00';
            $this->is_active = $this->branch->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:'.self::PHONE_REGEX],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], $this->messages(), $this->validationAttributes());

        $branch = Branch::query()->updateOrCreate(
            ['id' => $this->branch?->id],
            $validated,
        );

        Flux::toast(variant: 'success', text: $this->branch ? 'Sucursal actualizada.' : 'Sucursal creada.');

        $this->redirectRoute('dashboard.branches.edit', ['branch' => $branch], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.branches.create')->layout('layouts.app');
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => 'Captura el teléfono en formato internacional, por ejemplo +524151234567.',
        ];
    }

    /**
     * Get validation attribute labels.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'phone' => 'teléfono',
        ];
    }
}
