<?php

namespace App\Livewire\Recipes;

use App\Models\Beverage;
use App\Models\BeverageRecipeLine;
use App\Models\InventoryItem;
use App\Models\Size;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Receta de bebida')]
class BeverageRecipe extends Component
{
    public Beverage $beverage;

    public ?int $sizeId = null;

    /**
     * @var array<int, array{inventory_item_id: ?string, quantity: ?string}>
     */
    public array $lines = [];

    public function mount(Beverage $beverage): void
    {
        $this->beverage = $beverage;
        $this->sizeId = $this->sizes->first()?->id;
        $this->loadLines();
    }

    /**
     * @return Collection<int, Size>
     */
    #[Computed]
    public function sizes(): Collection
    {
        return Size::query()->where('is_active', true)->orderBy('capacity_ounces')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function updatedSizeId(): void
    {
        $this->loadLines();
    }

    public function addLine(): void
    {
        $this->lines[] = ['inventory_item_id' => null, 'quantity' => null];
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
            'sizeId' => ['required', 'exists:sizes,id'],
            'lines.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $clean = collect($this->lines)
            ->filter(fn (array $line): bool => filled($line['inventory_item_id'] ?? null) && (float) ($line['quantity'] ?? 0) > 0)
            ->keyBy('inventory_item_id');

        DB::transaction(function () use ($clean): void {
            BeverageRecipeLine::query()
                ->where('beverage_id', $this->beverage->id)
                ->where('size_id', $this->sizeId)
                ->delete();

            foreach ($clean as $itemId => $line) {
                BeverageRecipeLine::query()->create([
                    'beverage_id' => $this->beverage->id,
                    'size_id' => $this->sizeId,
                    'inventory_item_id' => (int) $itemId,
                    'quantity' => round((float) $line['quantity'], 3),
                ]);
            }
        });

        Flux::toast(variant: 'success', text: 'Receta guardada.');

        $this->loadLines();
    }

    protected function loadLines(): void
    {
        if ($this->sizeId === null) {
            $this->lines = [['inventory_item_id' => null, 'quantity' => null]];

            return;
        }

        $this->lines = BeverageRecipeLine::query()
            ->where('beverage_id', $this->beverage->id)
            ->where('size_id', $this->sizeId)
            ->get()
            ->map(fn (BeverageRecipeLine $line): array => [
                'inventory_item_id' => (string) $line->inventory_item_id,
                'quantity' => (string) (float) $line->quantity,
            ])
            ->all();

        if ($this->lines === []) {
            $this->lines = [['inventory_item_id' => null, 'quantity' => null]];
        }
    }

    public function render(): View
    {
        return view('livewire.recipes.beverage')->layout('layouts.app');
    }
}
