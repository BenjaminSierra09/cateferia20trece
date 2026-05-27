<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MercadoPagoWebhookEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mercado_pago_point_order_id',
        'event_id',
        'topic',
        'type',
        'action',
        'resource_id',
        'external_reference',
        'mercado_pago_order_id',
        'headers',
        'payload',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function pointOrder(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoPointOrder::class, 'mercado_pago_point_order_id');
    }
}
