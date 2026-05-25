<?php

namespace App\Http\Resources;

use App\Models\CustomizationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin CustomizationType */
class CustomizationTypeResource extends JsonResource
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
            'slug' => $this->slug,
            'selection_mode' => $this->selection_mode,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->when($this->getAttribute('sort_order') !== null, fn (): int => (int) $this->getAttribute('sort_order')),
            'is_open_by_default' => $this->when(
                array_key_exists('is_open_by_default', $this->getAttributes()),
                fn (): bool => (bool) $this->getAttribute('is_open_by_default'),
            ),
            'options_count' => $this->whenCounted('options'),
            'options' => CustomizationOptionResource::collection($this->whenLoaded('options')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
