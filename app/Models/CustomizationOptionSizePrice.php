<?php

namespace App\Models;

use Database\Factories\CustomizationOptionSizePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customization_option_id', 'size_id', 'price'])]
class CustomizationOptionSizePrice extends Model
{
    /** @use HasFactory<CustomizationOptionSizePriceFactory> */
    use HasFactory;

    /**
     * Get the customization option for this size price.
     */
    public function customizationOption(): BelongsTo
    {
        return $this->belongsTo(CustomizationOption::class);
    }

    /**
     * Get the size for this price.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
