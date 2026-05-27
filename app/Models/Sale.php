<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['branch_id', 'user_id', 'customer_id', 'work_session_id', 'sold_at', 'payment_method', 'payment_breakdown', 'status', 'subtotal', 'discount_total', 'reward_redeemed_total', 'total', 'discount_concept', 'notes'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'reward_redeemed_total' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'payment_breakdown' => 'array',
            'status' => SaleStatus::class,
        ];
    }

    /**
     * Get the branch for the sale.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the collaborator for the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer for the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the work session for the sale.
     */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    /**
     * Get the sale items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the debt movements automatically created from the sale.
     */
    public function debtMovements(): HasMany
    {
        return $this->hasMany(CustomerDebtMovement::class);
    }

    /**
     * Get the Mercado Pago Point order linked to the sale.
     */
    public function mercadoPagoPointOrder(): HasOne
    {
        return $this->hasOne(MercadoPagoPointOrder::class);
    }

    /**
     * Determine whether the sale can still be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === SaleStatus::Completed;
    }
}
