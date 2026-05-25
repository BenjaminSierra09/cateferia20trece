<?php

namespace App\Models;

use App\Observers\CustomizationOptionObserver;
use Database\Factories\CustomizationOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CustomizationOptionObserver::class])]
#[Fillable(['customization_type_id', 'name', 'image_path', 'price', 'is_available'])]
class CustomizationOption extends Model
{
    /** @use HasFactory<CustomizationOptionFactory> */
    use HasFactory;

    /**
     * Get the type for the customization option.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomizationType::class, 'customization_type_id');
    }

    /**
     * Get the beverages that allow this customization option.
     */
    public function beverages(): BelongsToMany
    {
        return $this->belongsToMany(Beverage::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Get the size-specific base prices for this option.
     */
    public function sizePrices(): HasMany
    {
        return $this->hasMany(CustomizationOptionSizePrice::class);
    }

    /**
     * Get the branch and size-specific price overrides for this option.
     */
    public function branchSizePriceOverrides(): HasMany
    {
        return $this->hasMany(BranchCustomizationSizePriceOverride::class);
    }
}
