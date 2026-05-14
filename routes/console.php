<?php

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use App\Support\CatalogImageManager;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('catalog:generate-missing-images', function () {
    $queuedImages = 0;
    $catalogImageManager = app(CatalogImageManager::class);

    $queueMissingImages = function (string $modelClass) use ($catalogImageManager, &$queuedImages): void {
        $modelClass::query()
            ->where(function ($query): void {
                $query->whereNull('image_path')
                    ->orWhere('image_path', '');
            })
            ->select('id', 'name', 'image_path')
            ->chunkById(100, function ($models) use ($catalogImageManager, &$queuedImages): void {
                foreach ($models as $model) {
                    if ($catalogImageManager->queueImageGeneration($model)) {
                        $queuedImages++;
                    }
                }
            });
    };

    foreach ([
        Product::class,
        Beverage::class,
        BeverageCategory::class,
        CustomizationType::class,
        CustomizationOption::class,
    ] as $modelClass) {
        $queueMissingImages($modelClass);
    }

    $this->info("Se encolaron {$queuedImages} imágenes faltantes del catálogo.");

    return Command::SUCCESS;
})->purpose('Queue AI image generation for catalog records without images');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
