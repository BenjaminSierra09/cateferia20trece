<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\CatalogImageManager;
use App\Support\GeneratesUniqueSlugs;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ProductObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Product "saving" event.
     */
    public function saving(Product $product): void
    {
        if ($product->isDirty('name') || blank($product->slug)) {
            $product->slug = app(GeneratesUniqueSlugs::class)->forModel($product, $product->name);
        }
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->queueImageGeneration($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->queueImageGeneration($product);
    }

    /**
     * Queue AI image generation when the product has no image.
     */
    protected function queueImageGeneration(Product $product): void
    {
        if (blank($product->image_path)) {
            app(CatalogImageManager::class)->queueImageGeneration($product);
        }
    }
}
