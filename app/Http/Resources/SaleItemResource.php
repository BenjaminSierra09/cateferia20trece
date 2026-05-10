<?php

namespace App\Http\Resources;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleItem */
class SaleItemResource extends JsonResource
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
            'sale_id' => $this->sale_id,
            'beverage_id' => $this->beverage_id,
            'product_id' => $this->product_id,
            'size_id' => $this->size_id,
            'item_name' => $this->item_name,
            'quantity' => $this->quantity,
            'base_price' => $this->base_price,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
            'special_instructions' => $this->special_instructions,
            'size' => $this->whenLoaded('size', fn () => new SizeResource($this->size)),
            'beverage' => $this->whenLoaded('beverage', fn () => [
                'id' => $this->beverage?->id,
                'name' => $this->beverage?->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
            ]),
            'customizations' => $this->whenLoaded('customizations', function () {
                return $this->customizations->map(fn ($customization) => [
                    'id' => $customization->id,
                    'customization_option_id' => $customization->customization_option_id,
                    'type' => $customization->customization_type_name,
                    'name' => $customization->customization_name,
                    'quantity' => $customization->quantity,
                    'price' => $customization->price,
                ])->values();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
