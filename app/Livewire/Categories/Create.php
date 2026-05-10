<?php

namespace App\Livewire\Categories;

use App\Models\BeverageCategory;
use App\Support\CatalogImageManager;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Nueva categoría')]
class Create extends Component
{
    use WithFileUploads;

    public ?BeverageCategory $category = null;

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public $image;

    public function mount(?BeverageCategory $category = null): void
    {
        $this->category = $category?->exists ? $category : null;

        if ($this->category !== null) {
            $this->name = $this->category->name;
            $this->description = $this->category->description ?? '';
            $this->is_active = $this->category->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:3072'],
        ]);

        $imagePath = $this->category?->image_path;

        if ($this->image !== null) {
            $imagePath = app(CatalogImageManager::class)->storeSquareUpload($this->image, 'catalog/categories');
        }

        $category = BeverageCategory::query()->updateOrCreate([
            'id' => $this->category?->id,
        ], [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'],
            'image_path' => $imagePath,
        ]);

        Flux::toast(variant: 'success', text: $this->category ? 'Categoría actualizada.' : 'Categoría creada.');

        $this->redirectRoute('dashboard.categories.edit', ['category' => $category], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.categories.create')->layout('layouts.app');
    }
}
