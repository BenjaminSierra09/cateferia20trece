<?php

namespace App\Observers;

use App\Jobs\GenerateCatalogImage;
use App\Models\CustomizationType;
use App\Support\GeneratesUniqueSlugs;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CustomizationTypeObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the CustomizationType "saving" event.
     */
    public function saving(CustomizationType $customizationType): void
    {
        if ($customizationType->isDirty('name') || blank($customizationType->slug)) {
            $customizationType->slug = app(GeneratesUniqueSlugs::class)->forModel($customizationType, $customizationType->name);
        }
    }

    /**
     * Handle the CustomizationType "created" event.
     */
    public function created(CustomizationType $customizationType): void
    {
        $this->queueImageGeneration($customizationType);
    }

    /**
     * Handle the CustomizationType "updated" event.
     */
    public function updated(CustomizationType $customizationType): void
    {
        $this->queueImageGeneration($customizationType);
    }

    /**
     * Queue AI image generation when the type has no image.
     */
    protected function queueImageGeneration(CustomizationType $customizationType): void
    {
        if (blank($customizationType->image_path)) {
            GenerateCatalogImage::dispatch($customizationType::class, $customizationType->getKey())
                ->onConnection('database');
        }
    }
}
