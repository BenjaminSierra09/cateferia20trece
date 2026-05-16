<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'birthday' => $this->birthday?->toDateString(),
            'email' => $this->email,
            'reward_balance' => $this->availableRewardBalance(),
            'gross_reward_balance' => $this->reward_balance,
            'reward_year' => $this->reward_year,
            'annual_drink_count' => $this->annual_drink_count,
            'annual_visit_count' => $this->annual_drink_count,
            'reward_tier' => $this->reward_tier->value,
            'reward_tier_label' => $this->reward_tier->label(),
            'welcome_reward_active' => $this->created_at?->addDays(3)->isFuture() ?? false,
            'debt_balance' => $this->debtBalance(),
            'gross_debt_balance' => $this->grossDebtBalance(),
            'has_debt' => $this->hasDebt(),
            'tonalpohualli' => $this->birthday ? $this->tonalpohualli() : null,
            'is_active' => $this->is_active,
            'sales_count' => $this->whenCounted('sales'),
            'qr_codes' => CustomerQrCodeResource::collection($this->whenLoaded('qrCodes')),
            'debt_movements' => CustomerDebtMovementResource::collection($this->whenLoaded('debtMovements')),
            'reward_transactions' => $this->whenLoaded('rewardTransactions', function () {
                return $this->rewardTransactions->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'type' => $transaction->type->value,
                    'type_label' => $transaction->type->label(),
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'transacted_at' => $transaction->transacted_at?->toIso8601String(),
                ])->values();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
