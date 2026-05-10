<?php

namespace App\Observers;

use App\Jobs\GenerateCatalogImage;
use App\Models\Beverage;
use App\Support\GeneratesUniqueSlugs;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BeverageObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the Beverage "saving" event.
     */
    public function saving(Beverage $beverage): void
    {
        if ($beverage->isDirty('name') || blank($beverage->slug)) {
            $beverage->slug = app(GeneratesUniqueSlugs::class)->forModel($beverage, $beverage->name);
        }
    }

    /**
     * Handle the Beverage "created" event.
     */
    public function created(Beverage $beverage): void
    {
        $this->queueImageGeneration($beverage);
    }

    /**
     * Handle the Beverage "updated" event.
     */
    public function updated(Beverage $beverage): void
    {
        $this->queueImageGeneration($beverage);
    }

    /**
     * Queue AI image generation when the beverage has no image.
     */
    protected function queueImageGeneration(Beverage $beverage): void
    {
        if (blank($beverage->image_path)) {
            GenerateCatalogImage::dispatch($beverage::class, $beverage->getKey())
                ->onConnection('database');
        }
    }
}
