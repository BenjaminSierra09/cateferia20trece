<?php

namespace App\Models;

use Database\Factories\SaleItemCustomizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_item_id', 'customization_option_id', 'customization_type_name', 'customization_name', 'quantity', 'price'])]
class SaleItemCustomization extends Model
{
    /** @use HasFactory<SaleItemCustomizationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Get the sale item for the customization.
     */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /**
     * Get the customization option for the customization.
     */
    public function customizationOption(): BelongsTo
    {
        return $this->belongsTo(CustomizationOption::class);
    }
}
