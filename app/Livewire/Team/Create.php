<?php

namespace App\Livewire\Team;

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nuevo colaborador')]
class Create extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'employee';

    public bool $is_active = true;

    public function mount(?User $user = null): void
    {
        $this->user = $user?->exists ? $user : null;

        if ($this->user !== null) {
            $this->name = $this->user->name;
            $this->username = $this->user->username;
            $this->email = $this->user->email;
            $this->role = $this->user->role->value;
            $this->is_active = $this->user->is_active;
        }
    }

    public function save(): void
    {
        $payload = [
            'name' => trim($this->name),
            'username' => str($this->username)->trim()->lower()->toString(),
            'email' => str($this->email)->trim()->lower()->toString(),
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];

        $validated = validator($payload, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'confirmed'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['boolean'],
        ])->validate();

        if (($validated['password'] ?? null) === null || $validated['password'] === '') {
            unset($validated['password']);
        }

        $user = User::query()->updateOrCreate(
            ['id' => $this->user?->id],
            $validated,
        );

        Flux::toast(variant: 'success', text: $this->user ? 'Colaborador actualizado.' : 'Colaborador creado.');

        $this->redirectRoute('dashboard.team.edit', ['user' => $user], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.team.create', [
            'roles' => UserRole::cases(),
        ])->layout('layouts.app');
    }
}
