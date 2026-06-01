<?php

namespace App\Models;

use Database\Factories\ProductRecipeLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipeLine extends Model
{
    /** @use HasFactory<ProductRecipeLineFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'inventory_item_id',
        'quantity',
        'scales_with_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'scales_with_quantity' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
