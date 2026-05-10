<?php

namespace App\Livewire\Catalog;

use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\Branch;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Size;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Catálogo')]
class Manager extends Component
{
    public string $branch_name = '';

    public string $branch_city = '';

    public string $category_name = '';

    public string $size_name = '';

    public string $size_capacity_label = '';

    public string $customization_type_name = '';

    public ?int $customization_type_id = null;

    public string $customization_option_name = '';

    public float $customization_option_price = 0;

    public string $beverage_name = '';

    public ?int $beverage_category_id = null;

    public ?int $beverage_size_id = null;

    public float $beverage_price = 0;

    /**
     * Create a branch.
     */
    public function createBranch(): void
    {
        $validated = $this->validate([
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_city' => ['nullable', 'string', 'max:255'],
        ]);

        Branch::create([
            'name' => $validated['branch_name'],
            'city' => $validated['branch_city'],
        ]);

        $this->reset('branch_name', 'branch_city');
        Flux::toast(text: 'Sucursal creada.');
    }

    /**
     * Create a category.
     */
    public function createCategory(): void
    {
        $validated = $this->validate([
            'category_name' => ['required', 'string', 'max:255'],
        ]);

        BeverageCategory::create([
            'name' => $validated['category_name'],
        ]);

        $this->reset('category_name');
        Flux::toast(text: 'Categoría creada.');
    }

    /**
     * Create a size.
     */
    public function createSize(): void
    {
        $validated = $this->validate([
            'size_name' => ['required', 'string', 'max:255'],
            'size_capacity_label' => ['required', 'string', 'max:255'],
        ]);

        Size::create([
            'name' => $validated['size_name'],
            'capacity_label' => $validated['size_capacity_label'],
        ]);

        $this->reset('size_name', 'size_capacity_label');
        Flux::toast(text: 'Tamaño creado.');
    }

    /**
     * Create a customization type.
     */
    public function createCustomizationType(): void
    {
        $validated = $this->validate([
            'customization_type_name' => ['required', 'string', 'max:255'],
        ]);

        CustomizationType::create([
            'name' => $validated['customization_type_name'],
        ]);

        $this->reset('customization_type_name');
        Flux::toast(text: 'Tipo de personalización creado.');
    }

    /**
     * Create a customization option.
     */
    public function createCustomizationOption(): void
    {
        $validated = $this->validate([
            'customization_type_id' => ['required', 'integer', 'exists:customization_types,id'],
            'customization_option_name' => ['required', 'string', 'max:255'],
            'customization_option_price' => ['required', 'numeric', 'min:0'],
        ]);

        CustomizationOption::create([
            'customization_type_id' => $validated['customization_type_id'],
            'name' => $validated['customization_option_name'],
            'price' => $validated['customization_option_price'],
        ]);

        $this->reset('customization_option_name', 'customization_option_price');
        Flux::toast(text: 'Opción creada.');
    }

    /**
     * Create a beverage with one active size price.
     */
    public function createBeverage(): void
    {
        $validated = $this->validate([
            'beverage_name' => ['required', 'string', 'max:255'],
            'beverage_category_id' => ['required', 'integer', 'exists:beverage_categories,id'],
            'beverage_size_id' => ['required', 'integer', 'exists:sizes,id'],
            'beverage_price' => ['required', 'numeric', 'min:0'],
        ]);

        $beverage = Beverage::create([
            'beverage_category_id' => $validated['beverage_category_id'],
            'name' => $validated['beverage_name'],
            'base_price' => $validated['beverage_price'],
        ]);

        $beverage->sizePrices()->create([
            'size_id' => $validated['beverage_size_id'],
            'price' => $validated['beverage_price'],
        ]);

        $this->reset('beverage_name', 'beverage_category_id', 'beverage_size_id', 'beverage_price');
        Flux::toast(text: 'Bebida creada.');
    }

    /**
     * Render the catalog page.
     */
    public function render()
    {
        return view('livewire.catalog.manager', [
            'branches' => Branch::query()->latest()->get(),
            'categories' => BeverageCategory::query()->latest()->get(),
            'sizes' => Size::query()->latest()->get(),
            'customizationTypes' => CustomizationType::query()->with('options')->latest()->get(),
            'beverages' => Beverage::query()->with(['category', 'sizePrices.size'])->latest()->get(),
        ])->layout('layouts.app');
    }
}
