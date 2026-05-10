<?php

namespace App\Http\Resources;

use App\Models\RewardTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RewardTransaction */
class RewardTransactionResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'sale_id' => $this->sale_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'transacted_at' => $this->transacted_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'sale' => $this->whenLoaded('sale', fn () => [
                'id' => $this->sale?->id,
                'total' => $this->sale?->total,
                'sold_at' => $this->sale?->sold_at?->toIso8601String(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
