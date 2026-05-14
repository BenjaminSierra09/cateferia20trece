<?php

namespace App\Services;

use App\Enums\RewardTier;
use App\Enums\RewardTransactionType;
use App\Models\Customer;
use App\Models\RewardTransaction;
use App\Models\Sale;

class RewardProgramService
{
    /**
     * Determine the reward tier for the amount of qualifying visits.
     */
    public function determineTier(int $visitCount): RewardTier
    {
        return match (true) {
            $visitCount >= 45 => RewardTier::Gold,
            $visitCount >= 30 => RewardTier::Silver,
            default => RewardTier::Bronze,
        };
    }

    /**
     * Apply earned rewards to the customer from a sale.
     */
    public function applyEarnedRewards(Customer $customer, Sale $sale): ?RewardTransaction
    {
        if (! $this->qualifiesForRewards($sale)) {
            return null;
        }

        $visitCount = (int) $customer->annual_drink_count;
        $countedVisit = false;

        if (! $this->hasQualifiedVisitOnDate($customer, $sale)) {
            $visitCount++;
            $countedVisit = true;
        }

        $rewardTier = $this->determineTier($visitCount);
        $rewardPercentage = $rewardTier->percentage() + $this->welcomeBonusPercentage($customer, $sale);
        $earnedAmount = round(((float) $sale->total * $rewardPercentage) / 100, 2);

        $customer->forceFill([
            'reward_year' => (int) $sale->sold_at->format('Y'),
            'annual_drink_count' => $visitCount,
            'reward_tier' => $rewardTier,
            'reward_balance' => round(((float) $customer->reward_balance + $earnedAmount), 2),
        ])->save();

        if ($earnedAmount <= 0) {
            return null;
        }

        return RewardTransaction::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'type' => RewardTransactionType::Earned,
            'amount' => $earnedAmount,
            'balance_after' => $customer->reward_balance,
            'description' => $this->buildEarnedDescription(
                rewardPercentage: $rewardPercentage,
                rewardTier: $rewardTier,
                sale: $sale,
                countedVisit: $countedVisit,
            ),
            'transacted_at' => $sale->sold_at,
        ]);
    }

    /**
     * Redeem customer balance on a sale.
     */
    public function redeem(Customer $customer, Sale $sale, float $requestedAmount): RewardTransaction
    {
        $amount = min(round($requestedAmount, 2), (float) $customer->reward_balance);

        $customer->forceFill([
            'reward_balance' => round(((float) $customer->reward_balance - $amount), 2),
        ])->save();

        return RewardTransaction::create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'type' => RewardTransactionType::Redeemed,
            'amount' => -$amount,
            'balance_after' => $customer->reward_balance,
            'description' => sprintf('Uso de saldo en venta #%d', $sale->id),
            'transacted_at' => $sale->sold_at,
        ]);
    }

    /**
     * Determine if the sale can earn rewards and count as a visit.
     */
    protected function qualifiesForRewards(Sale $sale): bool
    {
        return (float) $sale->reward_redeemed_total <= 0;
    }

    /**
     * Determine whether the customer already has a qualifying visit on the sale date.
     */
    protected function hasQualifiedVisitOnDate(Customer $customer, Sale $sale): bool
    {
        return $customer->sales()
            ->where('status', 'completed')
            ->where('reward_redeemed_total', '<=', 0)
            ->whereDate('sold_at', $sale->sold_at->toDateString())
            ->whereKeyNot($sale->id)
            ->exists();
    }

    /**
     * Resolve the temporary welcome percentage for the customer.
     */
    protected function welcomeBonusPercentage(Customer $customer, Sale $sale): int
    {
        if ($customer->created_at === null) {
            return 0;
        }

        return $sale->sold_at->lt($customer->created_at->copy()->addDays(3)) ? 5 : 0;
    }

    /**
     * Build a readable reward description for the ledger.
     */
    protected function buildEarnedDescription(
        int $rewardPercentage,
        RewardTier $rewardTier,
        Sale $sale,
        bool $countedVisit,
    ): string {
        $description = sprintf(
            'Abono del %d%% (%s) por venta #%d',
            $rewardPercentage,
            $rewardTier->label(),
            $sale->id,
        );

        if ($countedVisit) {
            return $description.' y visita del día';
        }

        return $description.' sin visita adicional del día';
    }
}
