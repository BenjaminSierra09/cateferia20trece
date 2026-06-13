<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\CashMovement;
use App\Models\CashRegisterCut;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashRegisterService
{
    public function __construct(
        protected WorkSessionService $workSessionService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordMovement(array $payload, User $user): CashMovement
    {
        $workSession = $this->resolveWorkSession($payload, $user);
        $branchId = $this->resolveBranchId($payload, $workSession, 'registrar movimientos de caja');

        return CashMovement::query()->create([
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'work_session_id' => $workSession?->id,
            'type' => $payload['type'],
            'amount' => round((float) $payload['amount'], 2),
            'concept' => $payload['concept'],
            'notes' => $payload['notes'] ?? null,
            'occurred_at' => isset($payload['occurred_at'])
                ? Carbon::parse($payload['occurred_at'])
                : now(),
        ])->load(['branch', 'user', 'workSession']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCut(array $payload, User $user): CashRegisterCut
    {
        return DB::transaction(function () use ($payload, $user): CashRegisterCut {
            $workSession = $this->resolveWorkSession($payload, $user);
            $branchId = $this->resolveBranchId($payload, $workSession, 'registrar un corte de caja');
            $cutAt = isset($payload['cut_at']) ? Carbon::parse($payload['cut_at']) : now();
            $previousCut = $this->previousCut($branchId, $workSession?->id, $cutAt);
            $periodStart = $this->periodStart($payload, $workSession, $previousCut);
            $openingCash = array_key_exists('opening_cash_amount', $payload)
                ? round((float) $payload['opening_cash_amount'], 2)
                : round((float) ($previousCut?->counted_cash_amount ?? 0), 2);
            $cashSalesTotal = $this->cashSalesTotal($branchId, $workSession?->id, $periodStart, $cutAt);
            $manualTotals = $this->manualMovementTotals($branchId, $workSession?->id, $periodStart, $cutAt);
            $expectedCash = round($openingCash + $cashSalesTotal + $manualTotals['income'] - $manualTotals['expense'], 2);
            $countedCash = round((float) $payload['counted_cash_amount'], 2);

            return CashRegisterCut::query()->create([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'work_session_id' => $workSession?->id,
                'period_start_at' => $periodStart,
                'cut_at' => $cutAt,
                'opening_cash_amount' => $openingCash,
                'counted_cash_amount' => $countedCash,
                'expected_cash_amount' => $expectedCash,
                'difference_amount' => round($countedCash - $expectedCash, 2),
                'cash_sales_total' => $cashSalesTotal,
                'manual_income_total' => $manualTotals['income'],
                'manual_expense_total' => $manualTotals['expense'],
                'notes' => $payload['notes'] ?? null,
            ])->load(['branch', 'user', 'workSession']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveWorkSession(array $payload, User $user): ?WorkSession
    {
        if (! empty($payload['work_session_id'])) {
            return WorkSession::query()->findOrFail($payload['work_session_id']);
        }

        return $this->workSessionService->currentFor($user);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveBranchId(array $payload, ?WorkSession $workSession, string $action): int
    {
        $branchId = (int) ($payload['branch_id'] ?? $workSession?->branch_id);

        if ($branchId <= 0) {
            throw new InvalidArgumentException("Selecciona una sucursal o inicia turno antes de {$action}.");
        }

        if ($workSession !== null && ! empty($payload['branch_id']) && (int) $payload['branch_id'] !== (int) $workSession->branch_id) {
            throw new InvalidArgumentException('La sucursal seleccionada no coincide con el turno de caja.');
        }

        return $branchId;
    }

    protected function previousCut(int $branchId, ?int $workSessionId, CarbonInterface $cutAt): ?CashRegisterCut
    {
        return CashRegisterCut::query()
            ->where('branch_id', $branchId)
            ->when($workSessionId, fn (Builder $query, int $id) => $query->where('work_session_id', $id))
            ->where('cut_at', '<', $cutAt)
            ->latest('cut_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function periodStart(array $payload, ?WorkSession $workSession, ?CashRegisterCut $previousCut): ?CarbonInterface
    {
        if (! empty($payload['period_start_at'])) {
            return Carbon::parse($payload['period_start_at']);
        }

        if (! array_key_exists('opening_cash_amount', $payload) && $previousCut !== null) {
            return $previousCut->cut_at;
        }

        return $workSession?->clock_in_at;
    }

    protected function cashSalesTotal(
        int $branchId,
        ?int $workSessionId,
        ?CarbonInterface $periodStart,
        CarbonInterface $cutAt,
    ): float {
        return round((float) Sale::query()
            ->where('branch_id', $branchId)
            ->where('status', SaleStatus::Completed->value)
            ->when($workSessionId, fn (Builder $query, int $id) => $query->where('work_session_id', $id))
            ->when($periodStart, fn (Builder $query, CarbonInterface $start) => $query->where('sold_at', '>=', $start))
            ->where('sold_at', '<=', $cutAt)
            ->get(['payment_method', 'payment_breakdown', 'total'])
            ->sum(fn (Sale $sale): float => $this->cashAmountForSale($sale)), 2);
    }

    protected function cashAmountForSale(Sale $sale): float
    {
        if ($sale->payment_method === PaymentMethod::Cash) {
            return (float) $sale->total;
        }

        if ($sale->payment_method === PaymentMethod::Mixed) {
            return round((float) ($sale->payment_breakdown['cash'] ?? 0), 2);
        }

        return 0.0;
    }

    /**
     * @return array{income: float, expense: float}
     */
    protected function manualMovementTotals(
        int $branchId,
        ?int $workSessionId,
        ?CarbonInterface $periodStart,
        CarbonInterface $cutAt,
    ): array {
        $movements = CashMovement::query()
            ->where('branch_id', $branchId)
            ->when($workSessionId, fn (Builder $query, int $id) => $query->where('work_session_id', $id))
            ->when($periodStart, fn (Builder $query, CarbonInterface $start) => $query->where('occurred_at', '>=', $start))
            ->where('occurred_at', '<=', $cutAt)
            ->get(['type', 'amount']);

        $income = $movements
            ->filter(fn (CashMovement $movement): bool => in_array($movement->type, [CashMovementType::Income, CashMovementType::CashIn], true))
            ->sum(fn (CashMovement $movement): float => (float) $movement->amount);
        $expense = $movements
            ->filter(fn (CashMovement $movement): bool => in_array($movement->type, [CashMovementType::Expense, CashMovementType::CashOut], true))
            ->sum(fn (CashMovement $movement): float => (float) $movement->amount);

        return [
            'income' => round((float) $income, 2),
            'expense' => round((float) $expense, 2),
        ];
    }
}
