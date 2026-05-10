<?php

namespace App\Services;

use App\Enums\RewardTier;
use App\Enums\RewardTransactionType;
use App\Models\Customer;
use App\Models\RewardTransaction;
use App\Models\Sale;
use Carbon\CarbonInterface;

class RewardProgramService
{
    /**
     * Reset annual progress if the reward year changed.
     */
    public function synchronizeAnnualProgress(Customer $customer, ?CarbonInterface $date = null): Customer
    {
        $date ??= now();

        if ($customer->reward_year !== (int) $date->format('Y')) {
            $customer->forceFill([
                'reward_year' => (int) $date->format('Y'),
                'annual_drink_count' => 0,
                'reward_tier' => RewardTier::Bronze,
            ])->save();
        }

        return $customer->refresh();
    }

    /**
     * Determine the reward tier for the amount of drinks.
     */
    public function determineTier(int $annualDrinkCount): RewardTier
    {
        return match (true) {
            $annualDrinkCount >= 24 => RewardTier::Gold,
            $annualDrinkCount >= 12 => RewardTier::Silver,
            default => RewardTier::Bronze,
        };
    }

    /**
     * Apply earned rewards to the customer from a sale.
     */
    public function applyEarnedRewards(Customer $customer, Sale $sale, int $drinkCount): ?RewardTransaction
    {
        $customer = $this->synchronizeAnnualProgress($customer, $sale->sold_at);

        $annualDrinkCount = $customer->annual_drink_count + $drinkCount;
        $rewardTier = $this->determineTier($annualDrinkCount);
        $earnedAmount = round(((float) $sale->total * $rewardTier->percentage()) / 100, 2);

        $customer->forceFill([
            'annual_drink_count' => $annualDrinkCount,
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
            'description' => sprintf('Abono del %d%% por venta #%d', $rewardTier->percentage(), $sale->id),
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
}
