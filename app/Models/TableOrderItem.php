<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['table_order_id', 'beverage_id', 'product_id', 'size_id', 'item_name', 'quantity', 'base_price', 'unit_price', 'line_total', 'customization_option_ids', 'guest_name', 'special_instructions'])]
class TableOrderItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'customization_option_ids' => 'array',
        ];
    }

    public function tableOrder(): BelongsTo
    {
        return $this->belongsTo(TableOrder::class);
    }

    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(TableOrderItemCustomization::class);
    }
}
