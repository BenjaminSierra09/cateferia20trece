<?php

namespace App\Livewire\Reports;

use App\Enums\WorkSessionStatus;
use App\Models\Branch;
use App\Models\WorkSession;
use App\Services\WorkSessionService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Turnos')]
class Shifts extends Component
{
    use WithPagination;

    public ?int $branch_id = null;

    #[Url(as: 'status', keep: true)]
    public string $status = '';

    #[Url(as: 'search', keep: true)]
    public string $search = '';

    #[Url(as: 'date_from', keep: true)]
    public string $date_from = '';

    #[Url(as: 'date_to', keep: true)]
    public string $date_to = '';

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 15;

    public function updated(string $property): void
    {
        if (in_array($property, ['branch_id', 'status', 'search', 'date_from', 'date_to', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function closeShift(int $workSessionId, WorkSessionService $workSessionService): void
    {
        $workSession = WorkSession::query()->findOrFail($workSessionId);

        if ($workSession->status === WorkSessionStatus::Closed) {
            Flux::toast(text: 'Ese turno ya estaba cerrado.');

            return;
        }

        $workSessionService->close($workSession);

        Flux::toast(variant: 'success', text: 'Turno cerrado correctamente.');
    }

    protected function shiftsQuery(): Builder
    {
        return WorkSession::query()
            ->with(['user', 'branch'])
            ->withCount('sales')
            ->when($this->branch_id, fn (Builder $query) => $query->where('branch_id', $this->branch_id))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->date_from !== '', fn (Builder $query) => $query->whereDate('work_date', '>=', $this->date_from))
            ->when($this->date_to !== '', fn (Builder $query) => $query->whereDate('work_date', '<=', $this->date_to))
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $searchQuery): void {
                    $searchQuery->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('name', 'like', '%'.$this->search.'%'));
                });
            });
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function activeSessions()
    {
        return $this->shiftsQuery()
            ->where('status', WorkSessionStatus::Open)
            ->orderByDesc('clock_in_at')
            ->get();
    }

    #[Computed]
    public function sessions()
    {
        return $this->shiftsQuery()
            ->latest('work_date')
            ->latest('clock_in_at')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.reports.shifts', [
            'statuses' => WorkSessionStatus::cases(),
        ])->layout('layouts.app');
    }
}
