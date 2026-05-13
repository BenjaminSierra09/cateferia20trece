<?php

use App\Jobs\GenerateCatalogImage;
use App\Livewire\Categories\Create as CategoryCreate;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('reports page renders chart sections for active staff sessions', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('dashboard.reports.index'))
        ->assertOk()
        ->assertSee('Ingresos por día')
        ->assertSee('Bebidas con mayor ingreso')
        ->assertSee('Por sucursal')
        ->assertSee('Ver reporte de turnos');
});

test('reports page shows employee shift details with branch and closing time', function () {
    $branch = Branch::factory()->create(['name' => 'Matriz Centro']);
    $admin = User::factory()->admin()->create();
    $user = User::factory()->employee()->create(['name' => 'Caja Uno']);
    $workSession = WorkSession::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'work_date' => '2026-05-11',
        'clock_in_at' => '2026-05-11 08:00:00',
        'clock_out_at' => '2026-05-11 16:30:00',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.reports.shifts'))
        ->assertOk()
        ->assertSee('Caja Uno')
        ->assertSee('Matriz Centro')
        ->assertSee($workSession->work_date->format('d/m/Y'))
        ->assertSee('08:00')
        ->assertSee('16:30');
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
