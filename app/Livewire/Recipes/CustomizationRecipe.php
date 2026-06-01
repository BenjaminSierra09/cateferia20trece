<?php

namespace App\Livewire\Recipes;

use App\Models\CustomizationOption;
use App\Models\CustomizationRecipeLine;
use App\Models\CustomizationType;
use App\Models\InventoryItem;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Receta de personalización')]
class CustomizationRecipe extends Component
{
    public CustomizationType $type;

    /**
     * "default" applies to every option of the category; otherwise the option id.
     */
    public string $scope = 'default';

    /**
     * @var array<int, array{inventory_item_id: ?string, quantity: ?string}>
     */
    public array $lines = [];

    public function mount(CustomizationType $customizationType): void
    {
        $this->type = $customizationType;
        $this->loadLines();
    }

    /**
     * @return Collection<int, CustomizationOption>
     */
    #[Computed]
    public function options(): Collection
    {
        return $this->type->options()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function updatedScope(): void
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
            'lines.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $optionId = $this->scope === 'default' ? null : (int) $this->scope;

        $clean = collect($this->lines)
            ->filter(fn (array $line): bool => filled($line['inventory_item_id'] ?? null) && (float) ($line['quantity'] ?? 0) > 0)
            ->keyBy('inventory_item_id');

        DB::transaction(function () use ($clean, $optionId): void {
            $query = CustomizationRecipeLine::query()->where('customization_type_id', $this->type->id);
            $optionId === null ? $query->whereNull('customization_option_id') : $query->where('customization_option_id', $optionId);
            $query->delete();

            foreach ($clean as $itemId => $line) {
                CustomizationRecipeLine::query()->create([
                    'customization_type_id' => $this->type->id,
                    'customization_option_id' => $optionId,
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
        $optionId = $this->scope === 'default' ? null : (int) $this->scope;

        $query = CustomizationRecipeLine::query()->where('customization_type_id', $this->type->id);
        $optionId === null ? $query->whereNull('customization_option_id') : $query->where('customization_option_id', $optionId);

        $this->lines = $query->get()
            ->map(fn (CustomizationRecipeLine $line): array => [
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
        return view('livewire.recipes.customization')->layout('layouts.app');
    }
}
