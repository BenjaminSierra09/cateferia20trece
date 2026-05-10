<?php

namespace App\Models;

use Database\Factories\BranchCustomizationPriceOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['branch_id', 'customization_option_id', 'price'])]
class BranchCustomizationPriceOverride extends Model
{
    /** @use HasFactory<BranchCustomizationPriceOverrideFactory> */
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
}
