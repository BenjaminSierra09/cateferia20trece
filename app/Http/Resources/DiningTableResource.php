<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiningTableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'seats' => $this->seats,
            'is_active' => $this->is_active,
            'is_occupied' => $this->relationLoaded('tableOrders') && $this->tableOrders->isNotEmpty(),
            'open_table_order_id' => $this->relationLoaded('tableOrders') ? $this->tableOrders->first()?->id : null,
        ];
    }
}
