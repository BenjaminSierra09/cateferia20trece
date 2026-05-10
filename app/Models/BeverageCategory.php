<?php

namespace App\Models;

use App\Observers\BeverageCategoryObserver;
use Database\Factories\BeverageCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([BeverageCategoryObserver::class])]
#[Fillable(['name', 'slug', 'description', 'image_path', 'is_active'])]
class BeverageCategory extends Model
{
    /** @use HasFactory<BeverageCategoryFactory> */
    use HasFactory;

    /**
     * Get the beverages in the category.
     */
    public function beverages(): HasMany
    {
        return $this->hasMany(Beverage::class);
    }
}
