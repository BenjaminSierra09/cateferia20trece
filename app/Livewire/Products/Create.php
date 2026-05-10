<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Producto')]
class Create extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $description = '';

    public string $unit_type = 'piece';

    public float $base_price = 0;

    public bool $is_active = true;

    public $image;

    public function mount(?Product $product = null): void
    {
        $this->product = $product?->exists ? $product : null;

        if ($this->product !== null) {
            $this->name = $this->product->name;
            $this->description = $this->product->description ?? '';
            $this->unit_type = $this->product->unit_type;
            $this->base_price = (float) $this->product->base_price;
            $this->is_active = $this->product->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_type' => ['required', 'in:piece,gram,kilo'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
        ]);

        $imagePath = $this->product?->image_path;

        if ($this->image !== null) {
            $imagePath = app(CatalogImageManager::class)->storeSquareUpload($this->image, 'catalog/products');
        }

        $product = Product::query()->updateOrCreate([
            'id' => $this->product?->id,
        ], [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'unit_type' => $validated['unit_type'],
            'base_price' => $validated['base_price'],
            'is_active' => $validated['is_active'],
        ]);

        Flux::toast(variant: 'success', text: $this->product ? 'Producto actualizado.' : 'Producto creado.');

        $this->redirectRoute('dashboard.products.edit', ['product' => $product], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.products.create')->layout('layouts.app');
    }
}
