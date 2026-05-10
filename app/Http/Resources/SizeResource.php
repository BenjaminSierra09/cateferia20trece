<?php

namespace App\Http\Resources;

use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Size */
class SizeResource extends JsonResource
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
            'capacity_label' => $this->capacity_label,
            'capacity_ounces' => $this->capacity_ounces,
            'is_active' => $this->is_active,
            'beverage_prices_count' => $this->whenCounted('beveragePrices'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
