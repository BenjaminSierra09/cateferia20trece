<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Traspaso de inventario')]
class Transfer extends Component
{
    public ?int $fromBranchId = null;

    public ?int $toBranchId = null;

    public string $notes = '';

    /**
     * @var array<int, array{inventory_item_id: ?string, quantity: ?string}>
     */
    public array $lines = [];

    public function mount(): void
    {
        $branchIds = Branch::query()->where('is_active', true)->orderBy('name')->pluck('id');

        $this->fromBranchId = $branchIds->first();
        $this->toBranchId = $branchIds->skip(1)->first() ?? $branchIds->first();
        $this->lines = [['inventory_item_id' => null, 'quantity' => null]];
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
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::query()->where('is_active', true)->orderBy('name')->get();
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

    public function save(InventoryService $service): void
    {
        $validated = $this->validate([
            'fromBranchId' => ['required', 'different:toBranchId', 'exists:branches,id'],
            'toBranchId' => ['required', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], messages: [
            'fromBranchId.different' => 'La sucursal de origen y destino deben ser diferentes.',
            'lines.*.inventory_item_id.required' => 'Selecciona un insumo.',
            'lines.*.quantity.required' => 'Captura la cantidad.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor a cero.',
        ], attributes: [
            'fromBranchId' => 'sucursal de origen',
            'toBranchId' => 'sucursal de destino',
        ]);

        try {
            $service->transfer(
                Branch::query()->findOrFail($validated['fromBranchId']),
                Branch::query()->findOrFail($validated['toBranchId']),
                collect($validated['lines'])
                    ->map(fn (array $line): array => [
                        'inventory_item_id' => (int) $line['inventory_item_id'],
                        'quantity' => (float) $line['quantity'],
                    ])
                    ->all(),
                auth()->user(),
                $this->notes !== '' ? $this->notes : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('lines', $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: 'Traspaso realizado.');

        $this->redirectRoute('dashboard.inventory.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.inventory.transfer')->layout('layouts.app');
    }
}
