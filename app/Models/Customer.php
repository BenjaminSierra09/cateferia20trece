<?php

namespace App\Models;

use App\Enums\RewardTier;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'birthday', 'email', 'reward_balance', 'reward_year', 'annual_drink_count', 'reward_tier', 'is_active'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'reward_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'reward_tier' => RewardTier::class,
        ];
    }

    /**
     * Get the sales for the customer.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the QR codes assigned to the customer.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(CustomerQrCode::class);
    }

    /**
     * Get reward transactions for the customer.
     */
    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }
}
