<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public ?int $selectedBranch = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    /**
     * Get available branches for filtering.
     */
    #[Computed]
    public function branches()
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get normalized filters for all dashboard widgets.
     *
     * @return array<string, int|string|null>
     */
    protected function dashboardFilters(): array
    {
        return [
            'branch_id' => $this->selectedBranch,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    #[Computed]
    public function overview(): array
    {
        return app(ReportService::class)->overview($this->dashboardFilters(), auth()->user());
    }

    /**
     * Today's income and sale count for the selected branch (ignores the date range).
     *
     * @return array{income: float, sales: int}
     */
    #[Computed]
    public function todayIncome(): array
    {
        return app(ReportService::class)->incomeForDate($this->selectedBranch, today(), auth()->user());
    }

    /**
     * Today's per-shift sales summary.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function todayShifts(): array
    {
        return app(ReportService::class)->salesByShiftForDate($this->selectedBranch, today(), auth()->user());
    }

    /**
     * Inventory rows at or below their alert threshold for the selected branch.
     */
    #[Computed]
    public function lowStock()
    {
        return app(ReportService::class)->lowStockAlerts($this->selectedBranch, 8);
    }

    /**
     * Reset all filters to default values.
     */
    public function resetFilters(): void
    {
        $this->selectedBranch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
    }

    /**
     * Set date range to last 7 days.
     */
    public function setLast7Days(): void
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Set date range to current month.
     */
    public function setCurrentMonth(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Set date range to last 30 days.
     */
    public function setLast30Days(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Prepare sales timeline data for Flux line chart.
     */
    #[Computed]
    public function salesTimelineChartData(): array
    {
        $overview = $this->overview;

        return array_map(fn ($item) => [
            'date' => $item['date'],
            'ventas' => $item['ventas'],
            'ingresos' => $item['ingresos'],
        ], $overview['sales_timeline']);
    }

    /**
     * Prepare branch sales data for Flux bar chart.
     */
    #[Computed]
    public function branchSalesChartData(): array
    {
        $overview = $this->overview;

        return array_map(fn ($item) => [
            'branch' => $item['branch'],
            'total' => $item['total'],
            'count' => $item['count'],
        ], $overview['sales_by_branch']);
    }

    /**
     * Prepare payment method data for Flux stacked bar chart.
     */
    #[Computed]
    public function paymentMethodChartData(): array
    {
        $overview = $this->overview;

        return array_map(fn ($item) => [
            'method' => $item['payment_method'],
            'count' => $item['count'],
            'total' => $item['total'],
        ], $overview['sales_by_payment_method']);
    }

    /**
     * Prepare top beverages data for sparklines.
     */
    #[Computed]
    public function topBeveragesSparklineData(): array
    {
        $overview = $this->overview;

        return array_map(fn ($item) => [
            'name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'revenue' => $item['revenue'],
            'sparklineData' => range($item['quantity'] - 3, $item['quantity']),
        ], $overview['top_beverages']);
    }

    #[Computed]
    public function recentSales()
    {
        return Sale::query()
            ->with(['branch', 'customer'])
            ->when($this->selectedBranch, fn ($q) => $q->where('branch_id', $this->selectedBranch))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('sold_at', '<=', $this->dateTo))
            ->when(auth()->user()?->hasLimitedAccountingView(), fn ($q) => app(ReportService::class)->excludeCashPayments($q))
            ->latest('sold_at')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function recentCustomers()
    {
        return Customer::query()->latest()->limit(5)->get();
    }

    /**
     * Render the dashboard page.
     */
    public function render(): View
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
