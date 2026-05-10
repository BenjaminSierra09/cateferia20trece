<?php

namespace App\Livewire\WorkSession;

use App\Models\Branch;
use App\Services\WorkSessionService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sucursal del día')]
class CheckIn extends Component
{
    public ?int $branch_id = null;

    public string $notes = '';

    /**
     * Mount the component.
     */
    public function mount(WorkSessionService $workSessionService): void
    {
        $currentSession = $workSessionService->currentFor(auth()->user());

        if ($currentSession !== null) {
            $this->branch_id = $currentSession->branch_id;
            $this->notes = $currentSession->notes ?? '';
        }
    }

    /**
     * Start today's work session.
     */
    public function start(WorkSessionService $workSessionService): void
    {
        $validated = $this->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = Branch::query()->findOrFail($validated['branch_id']);
        $workSessionService->start(auth()->user(), $branch, $validated['notes'] ?? null);

        Flux::toast(variant: 'success', text: 'Sucursal confirmada.');

        $this->redirectRoute('dashboard', navigate: true);
    }

    /**
     * Close today's work session.
     */
    public function close(WorkSessionService $workSessionService): void
    {
        $currentSession = $workSessionService->currentFor(auth()->user());

        if ($currentSession !== null) {
            $workSessionService->close($currentSession);
            Flux::toast(text: 'Sesión cerrada.');
        }

        $this->branch_id = null;
        $this->notes = '';
    }

    /**
     * Render the work session page.
     */
    public function render()
    {
        return view('livewire.work-session.check-in', [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'currentSession' => app(WorkSessionService::class)->currentFor(auth()->user()),
        ])->layout('layouts.app');
    }
}
