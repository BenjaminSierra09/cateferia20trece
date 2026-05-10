<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ProductObserver::class])]
#[Fillable(['name', 'slug', 'description', 'image_path', 'unit_type', 'base_price', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the sale items for the product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
