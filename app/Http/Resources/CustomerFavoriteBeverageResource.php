<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerFavoriteBeverageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'beverage_id' => $this->resource['beverage_id'],
            'beverage_name' => $this->resource['beverage_name'],
            'beverage_image_url' => $this->resource['beverage_image_url'],
            'total_quantity' => $this->resource['total_quantity'],
            'line_count' => $this->resource['line_count'],
            'last_ordered_at' => $this->resource['last_ordered_at'],
            'top_size' => $this->resource['top_size'],
            'frequent_customizations' => $this->resource['frequent_customizations'],
        ];
    }
}
