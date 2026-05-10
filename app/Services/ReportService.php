<?php

namespace App\Services;

use App\Models\Sale;
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
    public function overview(array $filters = []): array
    {
        $query = Sale::query()
            ->with(['items', 'branch', 'user', 'customer'])
            ->when($filters['branch_id'] ?? null, fn (Builder $builder, int $branchId) => $builder->where('branch_id', $branchId))
            ->when($filters['payment_method'] ?? null, fn (Builder $builder, string $paymentMethod) => $builder->where('payment_method', $paymentMethod))
            ->when($filters['date_from'] ?? null, fn (Builder $builder, string $dateFrom) => $builder->whereDate('sold_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $builder, string $dateTo) => $builder->whereDate('sold_at', '<=', $dateTo));

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
