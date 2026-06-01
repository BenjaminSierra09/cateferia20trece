<?php

namespace App\Models;

use Database\Factories\CustomizationRecipeLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomizationRecipeLine extends Model
{
    /** @use HasFactory<CustomizationRecipeLineFactory> */
    use HasFactory;

    protected $fillable = [
        'customization_type_id',
        'customization_option_id',
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(CustomizationType::class, 'customization_type_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(CustomizationOption::class, 'customization_option_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
