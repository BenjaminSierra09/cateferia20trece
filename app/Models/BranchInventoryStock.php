<?php

namespace App\Models;

use Database\Factories\BranchInventoryStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchInventoryStock extends Model
{
    /** @use HasFactory<BranchInventoryStockFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'inventory_item_id',
        'quantity',
        'min_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'min_quantity' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Whether the stock has reached or fallen below its alert threshold.
     */
    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->min_quantity;
    }
}
