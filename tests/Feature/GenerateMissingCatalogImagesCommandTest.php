<?php

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Image;

test('it queues missing catalog images', function () {
    Storage::fake('public');

    config()->set('ai.providers.openai.key', 'fake-openai-key');
    config()->set('ai.providers.gemini.key', null);
    config()->set('ai.providers.xai.key', null);

    $missingProduct = Product::withoutEvents(fn (): Product => Product::factory()->create());
    $productWithImage = Product::withoutEvents(fn (): Product => Product::factory()->create(['image_path' => 'catalog/products/example.png']));
    $beverageCategory = BeverageCategory::withoutEvents(fn (): BeverageCategory => BeverageCategory::factory()->create());
    Beverage::withoutEvents(fn (): Beverage => Beverage::factory()->create(['beverage_category_id' => $beverageCategory->getKey()]));
    $customizationType = CustomizationType::withoutEvents(fn (): CustomizationType => CustomizationType::factory()->create());
    CustomizationOption::withoutEvents(fn (): CustomizationOption => CustomizationOption::factory()->create(['customization_type_id' => $customizationType->getKey()]));

    $fixture = UploadedFile::fake()->image('generated.png', 1200, 900);

    Image::fake(array_fill(0, 5, base64_encode((string) file_get_contents($fixture->getRealPath()))))
        ->preventStrayImages();

    expect(Artisan::call('catalog:generate-missing-images'))->toBe(0);

    $missingProduct->refresh();
    $productWithImage->refresh();

    expect($missingProduct->image_path)->not->toBeNull();
    expect($productWithImage->image_path)->toBe('catalog/products/example.png');

    expect(Storage::disk('public')->exists($missingProduct->image_path))->toBeTrue();
});
