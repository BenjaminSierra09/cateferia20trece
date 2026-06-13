<?php

namespace App\Http\Resources;

use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashMovement */
class CashMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'work_session_id' => $this->work_session_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'direction' => $this->type->direction(),
            'amount' => $this->amount,
            'signed_amount' => $this->signedAmount(),
            'concept' => $this->concept,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'work_session' => $this->whenLoaded('workSession', fn () => new WorkSessionResource($this->workSession)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
