<?php

namespace App\Services;

use App\Enums\CustomerDebtMovementType;
use App\Models\Customer;
use App\Models\CustomerDebtMovement;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerDebtService
{
    public function balanceFor(Customer $customer): float
    {
        return (float) ($customer->debtMovements()
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->value('balance_after') ?? 0);
    }

    public function register(
        Customer $customer,
        CustomerDebtMovementType $type,
        float $amount,
        ?string $notes = null,
        ?User $user = null,
        ?int $branchId = null,
        ?int $saleId = null,
        ?string $recordedAt = null,
    ): CustomerDebtMovement {
        return DB::transaction(function () use ($customer, $type, $amount, $notes, $user, $branchId, $saleId, $recordedAt): CustomerDebtMovement {
            $normalizedAmount = round($amount, 2);

            if ($normalizedAmount <= 0) {
                throw new InvalidArgumentException('El monto debe ser mayor a cero.');
            }

            $currentBalance = $this->balanceFor($customer);

            if ($type === CustomerDebtMovementType::Payment && $normalizedAmount > $customer->debtBalance()) {
                throw new InvalidArgumentException('El abono no puede ser mayor a la deuda actual después de aplicar el saldo a favor.');
            }

            $balanceAfter = $type === CustomerDebtMovementType::Debt
                ? round($currentBalance + $normalizedAmount, 2)
                : round($currentBalance - $normalizedAmount, 2);

            return CustomerDebtMovement::query()->create([
                'customer_id' => $customer->id,
                'sale_id' => $saleId,
                'user_id' => $user?->id,
                'branch_id' => $branchId,
                'type' => $type,
                'amount' => $normalizedAmount,
                'balance_after' => $balanceAfter,
                'notes' => filled($notes) ? $notes : null,
                'recorded_at' => $recordedAt ?? now(),
            ])->load(['user', 'branch']);
        });
    }

    public function removeSaleMovements(Sale $sale): void
    {
        if ($sale->customer_id === null) {
            return;
        }

        $saleMovements = CustomerDebtMovement::query()
            ->where('sale_id', $sale->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        if ($saleMovements->isEmpty()) {
            return;
        }

        $lastSaleMovement = $saleMovements->last();

        $hasSubsequentMovements = CustomerDebtMovement::query()
            ->where('customer_id', $sale->customer_id)
            ->where(function ($query) use ($lastSaleMovement) {
                $query->where('recorded_at', '>', $lastSaleMovement->recorded_at)
                    ->orWhere(function ($sameMomentQuery) use ($lastSaleMovement) {
                        $sameMomentQuery->where('recorded_at', $lastSaleMovement->recorded_at)
                            ->where('id', '>', $lastSaleMovement->id);
                    });
            })
            ->exists();

        if ($hasSubsequentMovements) {
            throw new InvalidArgumentException('No se puede cancelar esta venta porque la cuenta del cliente ya tiene movimientos de deuda posteriores.');
        }

        CustomerDebtMovement::query()
            ->whereIn('id', $saleMovements->pluck('id'))
            ->delete();

        $this->recomputeBalances($sale->customer()->firstOrFail());
    }

    public function recomputeBalances(Customer $customer): void
    {
        $balance = 0.0;

        CustomerDebtMovement::query()
            ->where('customer_id', $customer->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->each(function (CustomerDebtMovement $movement) use (&$balance): void {
                $amount = round((float) $movement->amount, 2);

                $balance = $movement->type === CustomerDebtMovementType::Debt
                    ? round($balance + $amount, 2)
                    : round($balance - $amount, 2);

                $movement->forceFill([
                    'balance_after' => $balance,
                ])->save();
            });
    }
}
