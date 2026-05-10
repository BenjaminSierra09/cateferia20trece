<?php

namespace App\Models;

use App\Enums\RewardTransactionType;
use Database\Factories\RewardTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'sale_id', 'type', 'amount', 'balance_after', 'description', 'transacted_at'])]
class RewardTransaction extends Model
{
    /** @use HasFactory<RewardTransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transacted_at' => 'datetime',
            'type' => RewardTransactionType::class,
        ];
    }

    /**
     * Get the customer for the transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sale associated with the transaction.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
