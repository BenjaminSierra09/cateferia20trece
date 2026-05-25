<?php

namespace App\Livewire\Team;

use App\Livewire\Concerns\SortsTables;
use App\Models\User;
use App\Support\InitialIndexViewModeResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Equipo')]
class Manager extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    public function mount(InitialIndexViewModeResolver $initialIndexViewModeResolver): void
    {
        $this->viewMode = $initialIndexViewModeResolver->resolve(request());
    }

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
        $query = User::query();

        return view('livewire.team.manager', [
            'users' => ($this->sortBy === '' ? $query->latest() : $this->applySorting($query))->paginate($this->perPage),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
        ];
    }
}
