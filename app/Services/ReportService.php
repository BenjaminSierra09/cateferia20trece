<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\WorkSessionStatus;
use App\Models\BranchInventoryStock;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Build sales metrics from optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = [], ?User $viewer = null): array
    {
        $query = Sale::query()
            ->with(['items', 'branch', 'user', 'customer'])
            ->when($filters['branch_id'] ?? null, fn (Builder $builder, int $branchId) => $builder->where('branch_id', $branchId))
            ->when($filters['payment_method'] ?? null, fn (Builder $builder, string $paymentMethod) => $builder->where('payment_method', $paymentMethod))
            ->when($filters['date_from'] ?? null, fn (Builder $builder, string $dateFrom) => $builder->whereDate('sold_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, string $dateTo) => $builder->whereDate('sold_at', '<=', $dateTo))
            ->when($viewer?->hasLimitedAccountingView(), fn (Builder $builder) => $this->excludeCashPayments($builder));

        $sales = $query->get();
        $completedSales = $sales->where('status.value', 'completed');

        return [
            'sales_count' => $completedSales->count(),
            'gross_revenue' => round($completedSales->sum('total'), 2),
            'ticket_average' => round($completedSales->avg('total') ?? 0, 2),
            'discount_total' => round($completedSales->sum('discount_total'), 2),
            'reward_redeemed_total' => round($completedSales->sum('reward_redeemed_total'), 2),
            'top_beverages' => $this->topBeverages($completedSales),
            'sales_by_branch' => $this->salesByBranch($completedSales),
            'sales_by_payment_method' => $this->salesByPaymentMethod($completedSales),
            'sales_timeline' => $this->salesTimeline($completedSales),
            'limited_by_permissions' => $viewer?->hasLimitedAccountingView() ?? false,
            'permission_notice' => $viewer?->hasLimitedAccountingView()
                ? 'Vista limitada por permisos: no incluye ventas ni movimientos de caja en efectivo.'
                : null,
        ];
    }

    /**
     * Get top beverages from completed sales.
     *
     * @param  Collection<int, Sale>  $sales
     * @return array<int, array<string, mixed>>
     */
    protected function topBeverages(Collection $sales): array
    {
        return $sales->flatMap->items
            ->groupBy('item_name')
            ->map(fn (Collection $items, string $itemName): array => [
                'item_name' => $itemName,
                'quantity' => $items->sum('quantity'),
                'revenue' => round($items->sum('line_total'), 2),
            ])
            ->sortByDesc('quantity')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Get sales aggregated by branch.
     *
     * @param  Collection<int, Sale>  $sales
     * @return array<int, array<string, mixed>>
     */
    protected function salesByBranch(Collection $sales): array
    {
        return $sales->groupBy(fn (Sale $sale): string => $sale->branch?->name ?? 'Sin sucursal')
            ->map(fn (Collection $items, string $branch): array => [
                'branch' => $branch,
                'total' => round($items->sum('total'), 2),
                'count' => $items->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * Get sales aggregated by payment method.
     *
     * @param  Collection<int, Sale>  $sales
     * @return array<int, array<string, mixed>>
     */
    protected function salesByPaymentMethod(Collection $sales): array
    {
        return $sales->groupBy(fn (Sale $sale): string => $sale->payment_method->label())
            ->map(fn (Collection $items, string $paymentMethod): array => [
                'payment_method' => $paymentMethod,
                'total' => round($items->sum('total'), 2),
                'count' => $items->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * Total completed income and sale count for a single day.
     *
     * @return array{income: float, sales: int}
     */
    public function incomeForDate(?int $branchId, CarbonInterface $date, ?User $viewer = null): array
    {
        $sales = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereDate('sold_at', $date->toDateString())
            ->when($branchId, fn (Builder $builder, int $id) => $builder->where('branch_id', $id))
            ->when($viewer?->hasLimitedAccountingView(), fn (Builder $builder) => $this->excludeCashPayments($builder))
            ->get(['total']);

        return [
            'income' => round((float) $sales->sum('total'), 2),
            'sales' => $sales->count(),
        ];
    }

    /**
     * Per-shift sales summary for a single day.
     *
     * @return array<int, array<string, mixed>>
     */
    public function salesByShiftForDate(?int $branchId, CarbonInterface $date, ?User $viewer = null): array
    {
        return WorkSession::query()
            ->with(['user', 'branch'])
            ->whereDate('work_date', $date->toDateString())
            ->when($branchId, fn (Builder $builder, int $id) => $builder->where('branch_id', $id))
            ->withCount(['sales as completed_sales_count' => fn (Builder $builder) => $builder
                ->where('status', SaleStatus::Completed->value)
                ->when($viewer?->hasLimitedAccountingView(), fn (Builder $salesBuilder) => $this->excludeCashPayments($salesBuilder))])
            ->withSum(['sales as completed_sales_total' => fn (Builder $builder) => $builder
                ->where('status', SaleStatus::Completed->value)
                ->when($viewer?->hasLimitedAccountingView(), fn (Builder $salesBuilder) => $this->excludeCashPayments($salesBuilder))], 'total')
            ->orderByDesc('clock_in_at')
            ->get()
            ->map(fn (WorkSession $session): array => [
                'id' => $session->id,
                'user' => $session->user?->name ?? 'Sin colaborador',
                'branch' => $session->branch?->name ?? 'Sin sucursal',
                'is_open' => $session->status === WorkSessionStatus::Open,
                'clock_in' => $session->clock_in_at,
                'clock_out' => $session->clock_out_at,
                'sales' => (int) $session->completed_sales_count,
                'total' => round((float) $session->completed_sales_total, 2),
            ])
            ->all();
    }

    public function excludeCashPayments(Builder $builder): Builder
    {
        return $builder->whereNotIn('payment_method', ['cash', 'mixed']);
    }

    /**
     * Branch inventory rows at or below their alert threshold (or negative).
     *
     * @return Collection<int, BranchInventoryStock>
     */
    public function lowStockAlerts(?int $branchId, int $limit = 50): Collection
    {
        return BranchInventoryStock::query()
            ->with(['item', 'branch'])
            ->when($branchId, fn (Builder $builder, int $id) => $builder->where('branch_id', $id))
            ->whereHas('item', fn (Builder $builder) => $builder->where('is_active', true))
            ->where(function (Builder $builder): void {
                $builder
                    ->where(fn (Builder $inner) => $inner->whereColumn('quantity', '<=', 'min_quantity')->where('min_quantity', '>', 0))
                    ->orWhere('quantity', '<', 0);
            })
            ->orderBy('quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales aggregated by day for charting.
     *
     * @param  Collection<int, Sale>  $sales
     * @return array<int, array<string, mixed>>
     */
    protected function salesTimeline(Collection $sales): array
    {
        return $sales->groupBy(fn (Sale $sale): string => $sale->sold_at?->format('Y-m-d') ?? now()->format('Y-m-d'))
            ->map(fn (Collection $items, string $date): array => [
                'date' => $date,
                'ventas' => $items->count(),
                'ingresos' => round($items->sum('total'), 2),
            ])
            ->sortBy('date')
            ->values()
            ->all();
    }
}
