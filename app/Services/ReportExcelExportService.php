<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\WorkSessionStatus;
use App\Models\Branch;
use App\Models\WorkSession;
use App\Support\XlsxExporter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReportExcelExportService
{
    private const BUSINESS_TIMEZONE = 'America/Mexico_City';

    public function __construct(
        private readonly ReportService $reportService,
        private readonly XlsxExporter $xlsxExporter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function overview(array $filters = []): string
    {
        $overview = $this->reportService->overview($filters);

        return $this->xlsxExporter->build([
            [
                'name' => 'Resumen',
                'rows' => [
                    ['Reporte de ventas'],
                    ['Generado', now()->timezone(self::BUSINESS_TIMEZONE)->format('Y-m-d H:i')],
                    ['Sucursal', $this->branchLabel($filters['branch_id'] ?? null)],
                    ['Método de pago', $this->paymentMethodLabel($filters['payment_method'] ?? null)],
                    ['Desde', $filters['date_from'] ?? 'Sin filtro'],
                    ['Hasta', $filters['date_to'] ?? 'Sin filtro'],
                    [],
                    ['Métrica', 'Valor'],
                    ['Ventas', $overview['sales_count']],
                    ['Ingresos', $overview['gross_revenue']],
                    ['Ticket promedio', $overview['ticket_average']],
                    ['Descuentos', $overview['discount_total']],
                    ['Saldo usado', $overview['reward_redeemed_total']],
                ],
            ],
            [
                'name' => 'Bebidas destacadas',
                'rows' => [
                    ['Bebida', 'Ventas', 'Ingresos'],
                    ...collect($overview['top_beverages'])
                        ->map(fn (array $item): array => [
                            $item['item_name'],
                            $item['quantity'],
                            $item['revenue'],
                        ])
                        ->all(),
                ],
            ],
            [
                'name' => 'Por sucursal',
                'rows' => [
                    ['Sucursal', 'Ventas', 'Total'],
                    ...collect($overview['sales_by_branch'])
                        ->map(fn (array $item): array => [
                            $item['branch'],
                            $item['count'],
                            $item['total'],
                        ])
                        ->all(),
                ],
            ],
            [
                'name' => 'Métodos de pago',
                'rows' => [
                    ['Método', 'Ventas', 'Total'],
                    ...collect($overview['sales_by_payment_method'])
                        ->map(fn (array $item): array => [
                            $item['payment_method'],
                            $item['count'],
                            $item['total'],
                        ])
                        ->all(),
                ],
            ],
            [
                'name' => 'Línea diaria',
                'rows' => [
                    ['Fecha', 'Ventas', 'Ingresos'],
                    ...collect($overview['sales_timeline'])
                        ->map(fn (array $item): array => [
                            $item['date'],
                            $item['ventas'],
                            $item['ingresos'],
                        ])
                        ->all(),
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function shifts(array $filters = []): string
    {
        $activeSessions = (clone $this->shiftsQuery($filters))
            ->where('status', WorkSessionStatus::Open)
            ->orderByDesc('clock_in_at')
            ->get();

        $sessions = $this->shiftsQuery($filters)
            ->latest('work_date')
            ->latest('clock_in_at')
            ->get();

        return $this->xlsxExporter->build([
            [
                'name' => 'Resumen',
                'rows' => [
                    ['Reporte de turnos'],
                    ['Generado', now()->timezone(self::BUSINESS_TIMEZONE)->format('Y-m-d H:i')],
                    ['Sucursal', $this->branchLabel($filters['branch_id'] ?? null)],
                    ['Estatus', $this->workSessionStatusLabel($filters['status'] ?? null)],
                    ['Desde', $filters['date_from'] ?? 'Sin filtro'],
                    ['Hasta', $filters['date_to'] ?? 'Sin filtro'],
                    ['Búsqueda', $filters['search'] ?? 'Sin filtro'],
                    [],
                    ['Métrica', 'Valor'],
                    ['Turnos activos', $activeSessions->count()],
                    ['Registros visibles', $sessions->count()],
                    ['Ventas en activos', $activeSessions->sum('sales_count')],
                ],
            ],
            [
                'name' => 'Turnos activos',
                'rows' => [
                    ['Empleado', 'Sucursal', 'Fecha', 'Inició', 'Cerró', 'Ventas', 'Estatus'],
                    ...$activeSessions
                        ->map(fn (WorkSession $session): array => $this->shiftRow($session))
                        ->all(),
                ],
            ],
            [
                'name' => 'Historial',
                'rows' => [
                    ['Empleado', 'Sucursal', 'Fecha', 'Inició', 'Cerró', 'Ventas', 'Estatus'],
                    ...$sessions
                        ->map(fn (WorkSession $session): array => $this->shiftRow($session))
                        ->all(),
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function shiftsQuery(array $filters): Builder
    {
        return WorkSession::query()
            ->with(['user', 'branch'])
            ->withCount('sales')
            ->when($filters['branch_id'] ?? null, fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $dateFrom) => $query->whereDate('work_date', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $dateTo) => $query->whereDate('work_date', '<=', $dateTo))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('branch', fn (Builder $branchQuery) => $branchQuery->where('name', 'like', '%'.$search.'%'));
                });
            });
    }

    /**
     * @return array<int, mixed>
     */
    private function shiftRow(WorkSession $session): array
    {
        return [
            $session->user?->name ?? 'Sin empleado',
            $session->branch?->name ?? 'Sin sucursal',
            $this->formatBusinessDate($session->work_date),
            $this->formatBusinessTime($session, 'clock_in_at'),
            $this->formatBusinessTime($session, 'clock_out_at', 'Abierto'),
            $session->sales_count,
            $session->status?->label() ?? 'Sin estatus',
        ];
    }

    private function branchLabel(mixed $branchId): string
    {
        if (blank($branchId)) {
            return 'Todas';
        }

        return Branch::query()->whereKey($branchId)->value('name') ?? 'Sucursal '.$branchId;
    }

    private function paymentMethodLabel(mixed $paymentMethod): string
    {
        if (blank($paymentMethod)) {
            return 'Todos';
        }

        return PaymentMethod::tryFrom((string) $paymentMethod)?->label() ?? (string) $paymentMethod;
    }

    private function workSessionStatusLabel(mixed $status): string
    {
        if (blank($status)) {
            return 'Todos';
        }

        return WorkSessionStatus::tryFrom((string) $status)?->label() ?? (string) $status;
    }

    private function formatBusinessDate(?CarbonInterface $value): string
    {
        return $value?->timezone(self::BUSINESS_TIMEZONE)->format('Y-m-d') ?? 'Sin fecha';
    }

    private function formatBusinessTime(WorkSession $session, string $attribute, string $fallback = 'Sin apertura'): string
    {
        $rawTimestamp = $session->getRawOriginal($attribute);

        if (blank($rawTimestamp)) {
            return $fallback;
        }

        return Carbon::parse($rawTimestamp, 'UTC')
            ->timezone(self::BUSINESS_TIMEZONE)
            ->format('H:i');
    }
}
