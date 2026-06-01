<?php

namespace App\Livewire\Recipes;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductRecipeLine;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Receta de producto')]
class ProductRecipe extends Component
{
    public Product $product;

    /**
     * @var array<int, array{inventory_item_id: ?string, quantity: ?string, scales_with_quantity: bool}>
     */
    public array $lines = [];

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->loadLines();
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function addLine(): void
    {
        $this->lines[] = ['inventory_item_id' => null, 'quantity' => null, 'scales_with_quantity' => true];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        if ($this->lines === []) {
            $this->addLine();
        }
    }

    public function save(): void
    {
        $this->validate([
            'lines.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $clean = collect($this->lines)
            ->filter(fn (array $line): bool => filled($line['inventory_item_id'] ?? null) && (float) ($line['quantity'] ?? 0) > 0)
            ->keyBy('inventory_item_id');

        DB::transaction(function () use ($clean): void {
            ProductRecipeLine::query()->where('product_id', $this->product->id)->delete();

            foreach ($clean as $itemId => $line) {
                ProductRecipeLine::query()->create([
                    'product_id' => $this->product->id,
                    'inventory_item_id' => (int) $itemId,
                    'quantity' => round((float) $line['quantity'], 3),
                    'scales_with_quantity' => (bool) ($line['scales_with_quantity'] ?? true),
                ]);
            }
        });

        Flux::toast(variant: 'success', text: 'Receta guardada.');

        $this->loadLines();
    }

    protected function loadLines(): void
    {
        $this->lines = ProductRecipeLine::query()
            ->where('product_id', $this->product->id)
            ->get()
            ->map(fn (ProductRecipeLine $line): array => [
                'inventory_item_id' => (string) $line->inventory_item_id,
                'quantity' => (string) (float) $line->quantity,
                'scales_with_quantity' => (bool) $line->scales_with_quantity,
            ])
            ->all();

        if ($this->lines === []) {
            $this->lines = [['inventory_item_id' => null, 'quantity' => null, 'scales_with_quantity' => true]];
        }
    }

    public function render(): View
    {
        return view('livewire.recipes.product')->layout('layouts.app');
    }
}
