<?php

use App\Jobs\GenerateCatalogImage;
use App\Livewire\Categories\Create as CategoryCreate;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\WorkSessionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('reports page renders chart sections for active staff sessions', function () {
    $branch = Branch::factory()->create();
    $user = User::factory()->assignedToBranch($branch)->create();

    app(WorkSessionService::class)->start($user, $branch);

    $this->actingAs($user)
        ->get(route('dashboard.reports.index'))
        ->assertOk()
        ->assertSee('Ingresos por día')
        ->assertSee('Bebidas con mayor ingreso')
        ->assertSee('Por sucursal');
});

test('category form stores an uploaded image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CategoryCreate::class)
        ->set('name', 'Temporada')
        ->set('description', 'Bebidas de temporada')
        ->set('image', UploadedFile::fake()->image('temporada.png'))
        ->call('save');

    $category = BeverageCategory::query()->where('name', 'Temporada')->first();

    expect($category)->not->toBeNull();
    expect($category->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($category->image_path);

    [$width, $height] = getimagesize(Storage::disk('public')->path($category->image_path));

    expect($width)->toBe($height);
});

test('catalog models without image queue AI generation', function () {
    Queue::fake();

    $product = Product::factory()->create([
        'name' => 'Cold Brew Especial',
        'image_path' => null,
    ]);

    Queue::assertPushed(GenerateCatalogImage::class, function (GenerateCatalogImage $job) use ($product) {
        return $job->modelClass === Product::class
            && $job->modelId === $product->id;
    });
});
