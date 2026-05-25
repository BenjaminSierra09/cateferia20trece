<?php

namespace App\Models;

use Database\Factories\BranchCustomizationSizePriceOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'customization_option_id', 'size_id', 'price'])]
class BranchCustomizationSizePriceOverride extends Model
{
    /** @use HasFactory<BranchCustomizationSizePriceOverrideFactory> */
    use HasFactory;

    /**
     * Get the branch for the override.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the customization option for the override.
     */
    public function customizationOption(): BelongsTo
    {
        return $this->belongsTo(CustomizationOption::class);
    }

    /**
     * Get the size for the override.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
