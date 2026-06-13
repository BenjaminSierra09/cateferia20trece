<?php

namespace App\Http\Resources;

use App\Models\TableOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = $this->whenLoaded('items', fn () => round((float) $this->items->sum('line_total'), 2), 0.0);

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'work_session_id' => $this->work_session_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'label' => $this->label,
            'guest_count' => $this->guest_count,
            'subtotal' => $subtotal,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'notes' => $this->notes,
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'tables' => DiningTableResource::collection($this->whenLoaded('tables')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (TableOrderItem $item): array => [
                'id' => $item->id,
                'beverage_id' => $item->beverage_id,
                'product_id' => $item->product_id,
                'size_id' => $item->size_id,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'base_price' => $item->base_price,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'customization_option_ids' => $item->customization_option_ids ?? [],
                'guest_name' => $item->guest_name,
                'special_instructions' => $item->special_instructions,
                'size' => $item->relationLoaded('size') && $item->size ? new SizeResource($item->size) : null,
                'customizations' => $item->relationLoaded('customizations')
                    ? $item->customizations->map(fn ($customization) => [
                        'id' => $customization->id,
                        'customization_option_id' => $customization->customization_option_id,
                        'type' => $customization->customization_type_name,
                        'name' => $customization->customization_name,
                        'quantity' => $customization->quantity,
                        'price' => $customization->price,
                    ])->values()
                    : [],
            ])->values()),
            'sales' => SaleResource::collection($this->whenLoaded('sales')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
