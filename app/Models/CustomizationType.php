<?php

namespace App\Models;

use App\Observers\CustomizationTypeObserver;
use Database\Factories\CustomizationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CustomizationTypeObserver::class])]
#[Fillable(['name', 'slug', 'selection_mode', 'image_path', 'is_active'])]
class CustomizationType extends Model
{
    /** @use HasFactory<CustomizationTypeFactory> */
    use HasFactory;

    /**
     * Get the options for the customization type.
     */
    public function options(): HasMany
    {
        return $this->hasMany(CustomizationOption::class);
    }

    /**
     * Get the recipe lines (inventory consumption) for this customization category.
     */
    public function recipeLines(): HasMany
    {
        return $this->hasMany(CustomizationRecipeLine::class);
    }
}
