<?php

namespace App\Models;

use Database\Factories\BranchBeveragePriceOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'beverage_id', 'size_id', 'price'])]
class BranchBeveragePriceOverride extends Model
{
    /** @use HasFactory<BranchBeveragePriceOverrideFactory> */
    use HasFactory;

    /**
     * Get the branch for the override.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the beverage for the override.
     */
    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    /**
     * Get the size for the override.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
