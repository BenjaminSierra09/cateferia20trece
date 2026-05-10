<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sale_id', 'beverage_id', 'product_id', 'size_id', 'item_name', 'quantity', 'base_price', 'unit_price', 'line_total', 'special_instructions'])]
class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * Get the sale for the item.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the beverage for the item.
     */
    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the size for the item.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    /**
     * Get the customizations for the sale item.
     */
    public function customizations(): HasMany
    {
        return $this->hasMany(SaleItemCustomization::class);
    }
}
