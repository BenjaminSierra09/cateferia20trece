<?php

namespace App\Http\Resources;

use App\Models\WorkSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkSession */
class WorkSessionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'work_date' => $this->work_date?->toDateString(),
            'clock_in_at' => $this->clock_in_at?->toIso8601String(),
            'clock_out_at' => $this->clock_out_at?->toIso8601String(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'branch' => $this->whenLoaded('branch', fn () => new BranchResource($this->branch)),
            'sales_count' => $this->whenCounted('sales'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
