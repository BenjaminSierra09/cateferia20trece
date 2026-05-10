<?php

namespace App\Models;

use Database\Factories\SizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'capacity_label', 'capacity_ounces', 'is_active'])]
class Size extends Model
{
    /** @use HasFactory<SizeFactory> */
    use HasFactory;

    /**
     * Get the beverage prices using the size.
     */
    public function beveragePrices(): HasMany
    {
        return $this->hasMany(BeverageSizePrice::class);
    }
}
