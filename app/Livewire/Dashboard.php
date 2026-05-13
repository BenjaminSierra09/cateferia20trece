<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
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
    public function getBranches()
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
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
    public function getSalesTimelineChartData(array $overview): array
    {
        return array_map(fn ($item) => [
            'date' => $item['date'],
            'ventas' => $item['ventas'],
            'ingresos' => $item['ingresos'],
        ], $overview['sales_timeline']);
    }

    /**
     * Prepare branch sales data for Flux bar chart.
     */
    public function getBranchSalesChartData(array $overview): array
    {
        return array_map(fn ($item) => [
            'branch' => $item['branch'],
            'total' => $item['total'],
            'count' => $item['count'],
        ], $overview['sales_by_branch']);
    }

    /**
     * Prepare payment method data for Flux stacked bar chart.
     */
    public function getPaymentMethodChartData(array $overview): array
    {
        return array_map(fn ($item) => [
            'method' => $item['payment_method'],
            'count' => $item['count'],
            'total' => $item['total'],
        ], $overview['sales_by_payment_method']);
    }

    /**
     * Prepare top beverages data for sparklines.
     */
    public function getTopBeveragesSparklineData(): array
    {
        $filters = [
            'branch_id' => $this->selectedBranch,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        $overview = app(ReportService::class)->overview($filters);

        return array_map(fn ($item) => [
            'name' => $item['item_name'],
            'quantity' => $item['quantity'],
            'revenue' => $item['revenue'],
            'sparklineData' => range($item['quantity'] - 3, $item['quantity']),
        ], $overview['top_beverages']);
    }

    /**
     * Render the dashboard page.
     */
    public function render(): View
    {
        $filters = [
            'branch_id' => $this->selectedBranch,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];

        $reportService = app(ReportService::class);
        $overview = $reportService->overview($filters);

        return view('livewire.dashboard', [
            'branches' => $this->getBranches(),
            'overview' => $overview,
            'salesTimelineChartData' => $this->getSalesTimelineChartData($overview),
            'branchSalesChartData' => $this->getBranchSalesChartData($overview),
            'paymentMethodChartData' => $this->getPaymentMethodChartData($overview),
            'topBeveragesSparklineData' => $this->getTopBeveragesSparklineData(),
            'recentSales' => Sale::query()
                ->with(['branch', 'customer'])
                ->when($this->selectedBranch, fn ($q) => $q->where('branch_id', $this->selectedBranch))
                ->when($this->dateFrom, fn ($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q) => $q->whereDate('sold_at', '<=', $this->dateTo))
                ->latest('sold_at')
                ->limit(8)
                ->get(),
            'recentCustomers' => Customer::query()->latest()->limit(5)->get(),
        ])->layout('layouts.app');
    }
}
