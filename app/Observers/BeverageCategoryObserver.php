<?php

namespace App\Observers;

use App\Jobs\GenerateCatalogImage;
use App\Models\BeverageCategory;
use App\Support\GeneratesUniqueSlugs;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BeverageCategoryObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the BeverageCategory "saving" event.
     */
    public function saving(BeverageCategory $beverageCategory): void
    {
        if ($beverageCategory->isDirty('name') || blank($beverageCategory->slug)) {
            $beverageCategory->slug = app(GeneratesUniqueSlugs::class)->forModel($beverageCategory, $beverageCategory->name);
        }
    }

    /**
     * Handle the BeverageCategory "created" event.
     */
    public function created(BeverageCategory $beverageCategory): void
    {
        $this->queueImageGeneration($beverageCategory);
    }

    /**
     * Handle the BeverageCategory "updated" event.
     */
    public function updated(BeverageCategory $beverageCategory): void
    {
        $this->queueImageGeneration($beverageCategory);
    }

    /**
     * Queue AI image generation when the category has no image.
     */
    protected function queueImageGeneration(BeverageCategory $beverageCategory): void
    {
        if (blank($beverageCategory->image_path)) {
            GenerateCatalogImage::dispatch($beverageCategory::class, $beverageCategory->getKey())
                ->onConnection('database');
        }
    }
}
