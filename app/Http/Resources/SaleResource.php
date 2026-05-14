<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'work_session_id' => $this->work_session_id,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'payment_breakdown' => $this->payment_breakdown,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'reward_redeemed_total' => $this->reward_redeemed_total,
            'total' => $this->total,
            'discount_concept' => $this->discount_concept,
            'notes' => $this->notes,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
