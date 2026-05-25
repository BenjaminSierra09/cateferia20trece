<?php

namespace App\Livewire\Reports;

use App\Enums\WorkSessionStatus;
use App\Livewire\Concerns\SortsTables;
use App\Models\Branch;
use App\Models\WorkSession;
use App\Services\ReportExcelExportService;
use App\Services\WorkSessionService;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Turnos')]
class Shifts extends Component
{
    use SortsTables;
    use WithPagination;

    private const BUSINESS_TIMEZONE = 'America/Mexico_City';

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

    public function exportExcel(ReportExcelExportService $reportExcelExportService): StreamedResponse
    {
        $contents = $reportExcelExportService->shifts($this->reportFilters());

        return response()->streamDownload(
            fn () => print $contents,
            'reporte-turnos-'.now()->format('Y-m-d-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
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
        $query = $this->shiftsQuery();

        return ($this->sortBy === '' ? $query->latest('work_date')->latest('clock_in_at') : $this->applySorting($query))
            ->paginate($this->perPage);
    }

    public function formatBusinessDate(?CarbonInterface $value): string
    {
        return $value?->timezone(self::BUSINESS_TIMEZONE)->format('d/m/Y') ?? 'Sin fecha';
    }

    public function formatBusinessTime(WorkSession $session, string $attribute, string $fallback = 'Sin apertura'): string
    {
        $rawTimestamp = $session->getRawOriginal($attribute);

        if (blank($rawTimestamp)) {
            return $fallback;
        }

        return Carbon::parse($rawTimestamp, 'UTC')
            ->timezone(self::BUSINESS_TIMEZONE)
            ->format('g:i a');
    }

    public function render(): View
    {
        return view('livewire.reports.shifts', [
            'statuses' => WorkSessionStatus::cases(),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportFilters(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'status' => $this->status !== '' ? $this->status : null,
            'search' => $this->search !== '' ? $this->search : null,
            'date_from' => $this->date_from !== '' ? $this->date_from : null,
            'date_to' => $this->date_to !== '' ? $this->date_to : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'work_date' => 'work_date',
            'clock_in_at' => 'clock_in_at',
            'clock_out_at' => 'clock_out_at',
            'sales_count' => 'sales_count',
            'status' => 'status',
        ];
    }
}
