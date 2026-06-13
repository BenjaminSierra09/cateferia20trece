<?php

namespace App\Livewire\Sales;

use App\Enums\PaymentMethod;
use App\Livewire\Concerns\SortsTables;
use App\Models\Sale;
use App\Services\ReportService;
use App\Support\InitialIndexViewModeResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Ventas')]
class Index extends Component
{
    use SortsTables;
    use WithPagination;

    #[Url(as: 'search', keep: true)]
    public string $search = '';

    #[Url(as: 'payment', keep: true)]
    public string $paymentMethod = '';

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    public function mount(InitialIndexViewModeResolver $initialIndexViewModeResolver): void
    {
        $this->viewMode = $initialIndexViewModeResolver->resolve(request());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    protected function salesQuery(): Builder
    {
        return Sale::query()
            ->with(['branch', 'customer', 'user', 'items'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($saleQuery) {
                    $saleQuery->whereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhere('discount_concept', 'like', '%'.$this->search.'%');
                });
            })
            ->when(auth()->user()?->hasLimitedAccountingView(), fn ($query) => app(ReportService::class)->excludeCashPayments($query))
            ->when($this->paymentMethod !== '', fn ($query) => $query->where('payment_method', $this->paymentMethod));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function stats(): array
    {
        $now = now();
        $currentStart = $now->copy()->subDays(30)->startOfDay();
        $previousStart = $currentStart->copy()->subDays(30);
        $previousEnd = $currentStart->copy()->subSecond();

        $currentSales = (clone $this->salesQuery())
            ->whereBetween('sold_at', [$currentStart, $now])
            ->get();

        $previousSales = (clone $this->salesQuery())
            ->whereBetween('sold_at', [$previousStart, $previousEnd])
            ->get();

        $currentRevenue = (float) $currentSales->sum('total');
        $previousRevenue = (float) $previousSales->sum('total');
        $currentTransactions = $currentSales->count();
        $previousTransactions = $previousSales->count();
        $currentCustomers = $currentSales->pluck('customer_id')->filter()->unique()->count();
        $previousCustomers = $previousSales->pluck('customer_id')->filter()->unique()->count();
        $currentAverage = $currentTransactions > 0 ? $currentRevenue / $currentTransactions : 0;
        $previousAverage = $previousTransactions > 0 ? $previousRevenue / $previousTransactions : 0;

        return [
            $this->buildStat('Ingresos 30 días', '$'.number_format($currentRevenue, 2), $currentRevenue, $previousRevenue),
            $this->buildStat('Transacciones', (string) $currentTransactions, $currentTransactions, $previousTransactions),
            $this->buildStat('Clientes con compra', (string) $currentCustomers, $currentCustomers, $previousCustomers),
            $this->buildStat('Ticket promedio', '$'.number_format($currentAverage, 2), $currentAverage, $previousAverage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildStat(string $title, string $value, float|int $current, float|int $previous): array
    {
        $trendValue = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        return [
            'title' => $title,
            'value' => $value,
            'trend' => number_format(abs($trendValue), 1).'%',
            'trendUp' => $trendValue >= 0,
        ];
    }

    public function render(): View
    {
        $query = $this->salesQuery();
        $sales = ($this->sortBy === '' ? $query->latest('sold_at') : $this->applySorting($query))
            ->paginate($this->perPage);

        return view('livewire.sales.index', [
            'sales' => $sales,
            'stats' => $this->stats(),
            'paymentMethods' => collect(PaymentMethod::cases())
                ->reject(fn (PaymentMethod $method): bool => auth()->user()?->hasLimitedAccountingView()
                    && in_array($method, [PaymentMethod::Cash, PaymentMethod::Mixed], true))
                ->values(),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'id' => 'id',
            'sold_at' => 'sold_at',
            'status' => 'status',
            'payment_method' => 'payment_method',
            'total' => 'total',
        ];
    }
}
