<?php

namespace App\Http\Resources;

use App\Models\CustomizationOption;
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
        return [
            'id' => $this->id,
            'customization_type_id' => $this->customization_type_id,
            'name' => $this->name,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'price' => $this->price,
            'is_available' => $this->is_available,
            'type' => $this->whenLoaded('type', fn () => new CustomizationTypeResource($this->type)),
            'beverages_count' => $this->whenCounted('beverages'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
