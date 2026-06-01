<?php

namespace App\Livewire\Inventory;

use App\Enums\MeasurementUnit;
use App\Models\InventoryItem;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Insumo de inventario')]
class ItemForm extends Component
{
    public ?InventoryItem $item = null;

    public string $name = '';

    public string $unit = 'ml';

    public string $category = '';

    public bool $is_active = true;

    public function mount(?InventoryItem $inventoryItem = null): void
    {
        $this->item = $inventoryItem?->exists ? $inventoryItem : null;

        if ($this->item !== null) {
            $this->name = $this->item->name;
            $this->unit = $this->item->unit->value;
            $this->category = $this->item->category ?? '';
            $this->is_active = $this->item->is_active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::in(array_column(MeasurementUnit::cases(), 'value'))],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], attributes: ['name' => 'nombre', 'unit' => 'unidad', 'category' => 'categoría']);

        $item = InventoryItem::query()->updateOrCreate(
            ['id' => $this->item?->id],
            [
                'name' => $validated['name'],
                'slug' => $this->item?->slug ?? $this->uniqueSlug($validated['name']),
                'unit' => $validated['unit'],
                'category' => ($validated['category'] ?? '') !== '' ? $validated['category'] : null,
                'is_active' => $validated['is_active'],
            ],
        );

        Flux::toast(variant: 'success', text: $this->item !== null ? 'Insumo actualizado.' : 'Insumo creado.');

        $this->redirectRoute('dashboard.inventory.index', navigate: true);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'insumo';
        $slug = $base;
        $suffix = 1;

        while (InventoryItem::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function unitOptions(): array
    {
        return array_map(fn (MeasurementUnit $unit): array => [
            'value' => $unit->value,
            'label' => $unit->label(),
        ], MeasurementUnit::cases());
    }

    public function render(): View
    {
        return view('livewire.inventory.item-form')->layout('layouts.app');
    }
}
