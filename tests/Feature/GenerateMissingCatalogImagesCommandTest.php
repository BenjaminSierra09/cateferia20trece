<?php

use App\Jobs\GenerateCatalogImage;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use Illuminate\Support\Facades\Bus;

test('it queues missing catalog images', function () {
    Bus::fake();

    $missingProduct = null;
    $productWithImage = null;
    $beverageCategory = null;
    $customizationType = null;

    Product::withoutEvents(function () use (&$missingProduct, &$productWithImage): void {
        $missingProduct = Product::factory()->create();
        $productWithImage = Product::factory()->create(['image_path' => 'catalog/products/example.png']);
    });

    BeverageCategory::withoutEvents(function () use (&$beverageCategory): void {
        $beverageCategory = BeverageCategory::factory()->create();
    });

    Beverage::withoutEvents(function () use ($beverageCategory): void {
        Beverage::factory()->create(['beverage_category_id' => $beverageCategory->getKey()]);
    });

    CustomizationType::withoutEvents(function () use (&$customizationType): void {
        $customizationType = CustomizationType::factory()->create();
    });

    CustomizationOption::withoutEvents(function () use ($customizationType): void {
        CustomizationOption::factory()->create(['customization_type_id' => $customizationType->getKey()]);
    });

    $this->artisan('catalog:generate-missing-images')->assertSuccessful();

    Bus::assertDispatchedTimes(GenerateCatalogImage::class, 5);
    Bus::assertDispatched(GenerateCatalogImage::class, function (GenerateCatalogImage $job) use ($missingProduct): bool {
        return $job->modelClass === Product::class && $job->modelId === $missingProduct->getKey();
    });
});
