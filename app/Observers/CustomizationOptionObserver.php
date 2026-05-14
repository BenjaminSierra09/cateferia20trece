<?php

namespace App\Observers;

use App\Models\CustomizationOption;
use App\Support\CatalogImageManager;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CustomizationOptionObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the CustomizationOption "created" event.
     */
    public function created(CustomizationOption $customizationOption): void
    {
        $this->queueImageGeneration($customizationOption);
    }

    /**
     * Handle the CustomizationOption "updated" event.
     */
    public function updated(CustomizationOption $customizationOption): void
    {
        $this->queueImageGeneration($customizationOption);
    }

    /**
     * Queue AI image generation when the option has no image.
     */
    protected function queueImageGeneration(CustomizationOption $customizationOption): void
    {
        if (blank($customizationOption->image_path)) {
            app(CatalogImageManager::class)->queueImageGeneration($customizationOption);
        }
    }
}
