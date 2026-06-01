<?php

namespace App\Models;

use Database\Factories\BeverageRecipeLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeverageRecipeLine extends Model
{
    /** @use HasFactory<BeverageRecipeLineFactory> */
    use HasFactory;

    protected $fillable = [
        'beverage_id',
        'size_id',
        'inventory_item_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function beverage(): BelongsTo
    {
        return $this->belongsTo(Beverage::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
