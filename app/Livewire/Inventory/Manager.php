<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Inventario')]
class Manager extends Component
{
    #[Url(as: 'sucursal', keep: true)]
    public ?int $branchId = null;

    public string $search = '';

    // Quick action modal state.
    public ?int $actionItemId = null;

    public string $actionType = 'entrada';

    public ?string $actionQuantity = null;

    public string $actionNotes = '';

    public function mount(): void
    {
        if ($this->branchId === null) {
            $this->branchId = Branch::query()->where('is_active', true)->orderBy('name')->value('id');
        }
    }

    /**
     * @return Collection<int, Branch>
     */
    #[Computed]
    public function branches(): Collection
    {
        return Branch::query()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Inventory items with the selected branch's stock attached.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): Collection
    {
        if ($this->branchId === null) {
            return collect();
        }

        $stocks = BranchInventoryStock::query()
            ->where('branch_id', $this->branchId)
            ->get()
            ->keyBy('inventory_item_id');

        return InventoryItem::query()
            ->where('is_active', true)
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItem $item) use ($stocks): array {
                $stock = $stocks->get($item->id);
                $quantity = $stock ? (float) $stock->quantity : 0.0;
                $min = $stock ? (float) $stock->min_quantity : 0.0;

                return [
                    'item' => $item,
                    'quantity' => $quantity,
                    'min_quantity' => $min,
                    'is_low' => $quantity <= $min,
                    'is_negative' => $quantity < 0,
                ];
            });
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return $this->rows->where('is_low', true)->count();
    }

    /**
     * @return Collection<int, InventoryMovement>
     */
    #[Computed]
    public function recentMovements(): Collection
    {
        if ($this->branchId === null) {
            return collect();
        }

        return InventoryMovement::query()
            ->with('item')
            ->where('branch_id', $this->branchId)
            ->latest('recorded_at')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function actionItem(): ?InventoryItem
    {
        return $this->actionItemId !== null ? InventoryItem::query()->find($this->actionItemId) : null;
    }

    public function openAction(int $itemId, string $type): void
    {
        $this->actionItemId = $itemId;
        $this->actionType = in_array($type, ['entrada', 'ajuste'], true) ? $type : 'entrada';
        $this->actionQuantity = null;
        $this->actionNotes = '';
        $this->resetErrorBag();

        unset($this->actionItem);

        Flux::modal('inventory-action')->show();
    }

    public function saveAction(InventoryService $service): void
    {
        $validated = $this->validate([
            'actionQuantity' => ['required', 'numeric', $this->actionType === 'entrada' ? 'gt:0' : 'gte:0'],
            'actionNotes' => ['nullable', 'string', 'max:255'],
        ], attributes: ['actionQuantity' => 'cantidad', 'actionNotes' => 'nota']);

        $branch = Branch::query()->findOrFail($this->branchId);
        $item = InventoryItem::query()->findOrFail($this->actionItemId);
        $quantity = (float) $validated['actionQuantity'];
        $notes = ($validated['actionNotes'] ?? '') !== '' ? $validated['actionNotes'] : null;

        if ($this->actionType === 'entrada') {
            $service->receive($branch, $item, $quantity, auth()->user(), $notes);
        } else {
            $service->adjust($branch, $item, $quantity, auth()->user(), $notes);
        }

        unset($this->rows, $this->lowStockCount, $this->recentMovements);

        Flux::modal('inventory-action')->close();
        Flux::toast(variant: 'success', text: 'Inventario actualizado.');
    }

    public function render(): View
    {
        return view('livewire.inventory.manager')->layout('layouts.app');
    }
}
