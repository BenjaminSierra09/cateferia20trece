<?php

namespace App\Http\Resources;

use App\Models\Beverage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Beverage */
class BeverageResource extends JsonResource
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
            'beverage_category_id' => $this->beverage_category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn () => new BeverageCategoryResource($this->category)),
            'sizes' => BeverageSizePriceResource::collection($this->whenLoaded('sizePrices')),
            'customizations' => CustomizationOptionResource::collection($this->whenLoaded('customizationOptions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
