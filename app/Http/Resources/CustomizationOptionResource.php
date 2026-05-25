<?php

namespace App\Http\Resources;

use App\Models\CustomizationOption;
use App\Support\CustomizationPriceResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin CustomizationOption */
class CustomizationOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $priceResolver = app(CustomizationPriceResolver::class);
        $branchId = $request->integer('branch_id') ?: null;
        $sizeId = $request->integer('size_id') ?: null;

        return [
            'id' => $this->id,
            'customization_type_id' => $this->customization_type_id,
            'name' => $this->name,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'price' => $priceResolver->resolve($this->resource, $sizeId, $branchId),
            'base_price' => round((float) $this->price, 2),
            'size_prices' => $this->whenLoaded('sizePrices', fn (): array => $priceResolver->sizePriceRows($this->resource, $branchId)),
            'is_available' => $this->is_available,
            'is_default' => $this->whenPivotLoaded('beverage_customization_option', fn (): bool => (bool) $this->pivot->is_default),
            'type' => $this->whenLoaded('type', fn () => new CustomizationTypeResource($this->type)),
            'beverages_count' => $this->whenCounted('beverages'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
