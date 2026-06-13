<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['table_order_item_id', 'customization_option_id', 'customization_type_name', 'customization_name', 'quantity', 'price'])]
class TableOrderItemCustomization extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function tableOrderItem(): BelongsTo
    {
        return $this->belongsTo(TableOrderItem::class);
    }
}
