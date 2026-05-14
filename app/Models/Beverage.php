<?php

namespace App\Models;

use App\Observers\BeverageObserver;
use Database\Factories\BeverageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([BeverageObserver::class])]
#[Fillable(['beverage_category_id', 'name', 'slug', 'description', 'image_path', 'base_price', 'is_active'])]
class Beverage extends Model
{
    /** @use HasFactory<BeverageFactory> */
    use HasFactory;

    /**
     * Get the category for the beverage.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BeverageCategory::class, 'beverage_category_id');
    }

    /**
     * Get the configured sizes for the beverage.
     */
    public function sizePrices(): HasMany
    {
        return $this->hasMany(BeverageSizePrice::class);
    }

    /**
     * Get the available customization options for the beverage.
     */
    public function customizationOptions(): BelongsToMany
    {
        return $this->belongsToMany(CustomizationOption::class)
            ->withTimestamps();
    }

    /**
     * Get the sale items registered for this beverage.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
