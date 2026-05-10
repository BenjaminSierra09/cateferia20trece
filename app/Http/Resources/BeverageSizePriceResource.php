<?php

namespace App\Http\Resources;

use App\Models\BeverageSizePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BeverageSizePrice */
class BeverageSizePriceResource extends JsonResource
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
            'beverage_id' => $this->beverage_id,
            'size_id' => $this->size_id,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'size' => $this->whenLoaded('size', fn () => new SizeResource($this->size)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
