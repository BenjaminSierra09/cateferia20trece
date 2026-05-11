<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Product */
class ProductResource extends JsonResource
{
    protected function resolveImageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $storageUrl = Storage::url($this->image_path);

        if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
            return $storageUrl;
        }

        return url($storageUrl);
    }

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
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->resolveImageUrl(),
            'unit_type' => $this->unit_type,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'sale_items_count' => $this->whenCounted('saleItems'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
