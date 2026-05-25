<?php

namespace App\Http\Resources;

use App\Models\AztecSymbol;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AztecSymbol */
class AztecSymbolResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'name' => $this->name,
            'slug' => $this->slug,
            'spanish_name' => $this->spanish_name,
            'deity' => $this->deity,
            'body_area' => $this->body_area,
            'meaning' => $this->meaning,
            'service_description' => $this->service_description,
            'customer_greeting' => $this->customer_greeting,
            'taste_profile' => $this->taste_profile,
            'recommended_items' => $this->recommended_items ?? [],
            'is_active' => $this->is_active,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
