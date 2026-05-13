<?php

namespace App\Services;

use App\Enums\CustomerDebtMovementType;
use App\Models\Customer;
use App\Models\CustomerDebtMovement;
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
        ?string $recordedAt = null,
    ): CustomerDebtMovement {
        return DB::transaction(function () use ($customer, $type, $amount, $notes, $user, $branchId, $recordedAt): CustomerDebtMovement {
            $normalizedAmount = round($amount, 2);

            if ($normalizedAmount <= 0) {
                throw new InvalidArgumentException('El monto debe ser mayor a cero.');
            }

            $currentBalance = $this->balanceFor($customer);

            if ($type === CustomerDebtMovementType::Payment && $normalizedAmount > $currentBalance) {
                throw new InvalidArgumentException('El abono no puede ser mayor a la deuda actual.');
            }

            $balanceAfter = $type === CustomerDebtMovementType::Debt
                ? round($currentBalance + $normalizedAmount, 2)
                : round($currentBalance - $normalizedAmount, 2);

            return CustomerDebtMovement::query()->create([
                'customer_id' => $customer->id,
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
}
