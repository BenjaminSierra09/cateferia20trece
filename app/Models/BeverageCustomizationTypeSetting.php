<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['beverage_id', 'customization_type_id', 'sort_order', 'is_open_by_default'])]
class BeverageCustomizationTypeSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_open_by_default' => 'boolean',
        ];
    }

    /**
     * Get the beverage for this category setting.
     */
    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    /**
     * Get the customization type for this category setting.
     */
    public function customizationType(): BelongsTo
    {
        return $this->belongsTo(CustomizationType::class);
    }
}
