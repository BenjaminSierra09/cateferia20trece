<?php

namespace App\Http\Resources;

use App\Models\CashRegisterCut;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashRegisterCut */
class CashRegisterCutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'work_session_id' => $this->work_session_id,
            'period_start_at' => $this->period_start_at?->toIso8601String(),
            'cut_at' => $this->cut_at?->toIso8601String(),
            'opening_cash_amount' => $this->opening_cash_amount,
            'counted_cash_amount' => $this->counted_cash_amount,
            'expected_cash_amount' => $this->expected_cash_amount,
            'difference_amount' => $this->difference_amount,
            'cash_sales_total' => $this->cash_sales_total,
            'manual_income_total' => $this->manual_income_total,
            'manual_expense_total' => $this->manual_expense_total,
            'notes' => $this->notes,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'work_session' => $this->whenLoaded('workSession', fn () => new WorkSessionResource($this->workSession)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
