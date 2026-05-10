<?php

namespace App\Models;

use Database\Factories\BeverageSizePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['beverage_id', 'size_id', 'price', 'is_active'])]
class BeverageSizePrice extends Model
{
    /** @use HasFactory<BeverageSizePriceFactory> */
    use HasFactory;

    /**
     * Get the beverage associated with the price.
     */
    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    /**
     * Get the size associated with the price.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
