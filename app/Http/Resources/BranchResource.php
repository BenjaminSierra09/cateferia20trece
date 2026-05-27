<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Branch */
class BranchResource extends JsonResource
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
            'address' => $this->address,
            'city' => $this->city,
            'phone' => $this->phone,
            'operating_hours' => $this->operating_hours,
            'is_active' => $this->is_active,
            'mercado_pago_enabled' => $this->mercado_pago_is_active && filled($this->mercado_pago_access_token),
            'mercado_pago_default_terminal_id' => $this->mercado_pago_default_terminal_id,
            'mercado_pago_default_terminal_name' => $this->mercado_pago_default_terminal_name,
            'work_sessions_count' => $this->whenCounted('workSessions'),
            'sales_count' => $this->whenCounted('sales'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
