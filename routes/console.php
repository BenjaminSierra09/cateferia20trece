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
    $processedImages = 0;
    $catalogImageManager = app(CatalogImageManager::class);

    $processMissingImages = function (string $modelClass) use ($catalogImageManager, &$processedImages): void {
        $modelClass::query()
            ->where(function ($query): void {
                $query->whereNull('image_path')
                    ->orWhere('image_path', '');
            })
            ->select('id', 'name', 'image_path')
            ->chunkById(100, function ($models) use ($catalogImageManager, &$processedImages): void {
                foreach ($models as $model) {
                    if ($catalogImageManager->generateImage($model)) {
                        $processedImages++;
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
        $processMissingImages($modelClass);
    }

    $this->info("Se procesaron {$processedImages} imágenes faltantes del catálogo.");

    return Command::SUCCESS;
})->purpose('Process catalog records without images');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
