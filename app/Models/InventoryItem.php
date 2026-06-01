<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'unit',
        'category',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => MeasurementUnit::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Per-branch stock rows for this item.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(BranchInventoryStock::class);
    }

    /**
     * Movement ledger entries for this item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
