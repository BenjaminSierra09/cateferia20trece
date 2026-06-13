<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sales_count' => $this['sales_count'],
            'gross_revenue' => $this['gross_revenue'],
            'ticket_average' => $this['ticket_average'],
            'discount_total' => $this['discount_total'],
            'reward_redeemed_total' => $this['reward_redeemed_total'],
            'top_beverages' => $this['top_beverages'],
            'sales_by_branch' => $this['sales_by_branch'],
            'sales_by_payment_method' => $this['sales_by_payment_method'],
            'limited_by_permissions' => $this['limited_by_permissions'] ?? false,
            'permission_notice' => $this['permission_notice'] ?? null,
        ];
    }
}
