<?php

namespace App\Livewire\Team;

use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Equipo')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);

        Flux::toast(text: $user->is_active ? 'Colaborador reactivado.' : 'Colaborador desactivado.');
    }

    /**
     * Render the team page.
     */
    public function render(): View
    {
        return view('livewire.team.manager', [
            'users' => User::query()->latest()->paginate($this->perPage),
        ])->layout('layouts.app');
    }
}
