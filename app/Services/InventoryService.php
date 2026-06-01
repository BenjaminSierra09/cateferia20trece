<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\BranchInventoryStock;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Receive new stock into a branch (entrada).
     */
    public function receive(Branch $branch, InventoryItem $item, float $quantity, mixed $user = null, ?string $notes = null): BranchInventoryStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a ingresar debe ser mayor a cero.');
        }

        return $this->record($branch, $item, $quantity, InventoryMovementType::Entrada, [
            'user' => $user,
            'notes' => $notes,
        ]);
    }

    /**
     * Set on-hand stock to an exact value, recording the difference as an adjustment.
     */
    public function adjust(Branch $branch, InventoryItem $item, float $newQuantity, mixed $user = null, ?string $notes = null): BranchInventoryStock
    {
        return DB::transaction(function () use ($branch, $item, $newQuantity, $user, $notes): BranchInventoryStock {
            $stock = $this->stockFor($branch, $item, lock: true);
            $delta = round($newQuantity - (float) $stock->quantity, 3);

            return $this->record($branch, $item, $delta, InventoryMovementType::Ajuste, [
                'user' => $user,
                'notes' => $notes,
                'stock' => $stock,
            ]);
        });
    }

    /**
     * Move stock from one branch to another, recording paired movements.
     *
     * @param  array<int, array{inventory_item_id: int|string, quantity: float|int|string}>  $lines
     */
    public function transfer(Branch $from, Branch $to, array $lines, mixed $user = null, ?string $notes = null): InventoryTransfer
    {
        if ($from->id === $to->id) {
            throw new InvalidArgumentException('La sucursal de origen y destino deben ser diferentes.');
        }

        $normalized = collect($lines)
            ->map(fn (array $line): array => [
                'inventory_item_id' => (int) $line['inventory_item_id'],
                'quantity' => round((float) $line['quantity'], 3),
            ])
            ->filter(fn (array $line): bool => $line['quantity'] > 0)
            ->values();

        if ($normalized->isEmpty()) {
            throw new InvalidArgumentException('Agrega al menos un insumo con cantidad para traspasar.');
        }

        return DB::transaction(function () use ($from, $to, $normalized, $user, $notes): InventoryTransfer {
            $transfer = InventoryTransfer::create([
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'user_id' => $user?->id,
                'notes' => $notes,
                'transferred_at' => now(),
            ]);

            foreach ($normalized as $line) {
                $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);

                $transfer->lines()->create($line);
                $this->record($from, $item, -$line['quantity'], InventoryMovementType::TraspasoSalida, ['user' => $user, 'transfer' => $transfer]);
                $this->record($to, $item, $line['quantity'], InventoryMovementType::TraspasoEntrada, ['user' => $user, 'transfer' => $transfer]);
            }

            return $transfer->load(['lines.item', 'fromBranch', 'toBranch']);
        });
    }

    /**
     * Get (or lazily create) the stock row for a branch + item.
     */
    public function stockFor(Branch $branch, InventoryItem $item, bool $lock = false): BranchInventoryStock
    {
        $query = BranchInventoryStock::query()
            ->where('branch_id', $branch->id)
            ->where('inventory_item_id', $item->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?? BranchInventoryStock::create([
            'branch_id' => $branch->id,
            'inventory_item_id' => $item->id,
            'quantity' => 0,
            'min_quantity' => 0,
        ]);
    }

    /**
     * Apply a signed delta to a branch's stock and append a movement.
     * Stock is allowed to go negative on purpose (we only alert, never block sales).
     *
     * @param  array{user?: mixed, notes?: ?string, sale?: Sale|null, transfer?: InventoryTransfer|null, stock?: BranchInventoryStock, recordedAt?: mixed}  $options
     */
    public function record(Branch $branch, InventoryItem $item, float $delta, InventoryMovementType $type, array $options = []): BranchInventoryStock
    {
        return DB::transaction(function () use ($branch, $item, $delta, $type, $options): BranchInventoryStock {
            $stock = $options['stock'] ?? $this->stockFor($branch, $item, lock: true);
            $newQuantity = round((float) $stock->quantity + $delta, 3);

            $stock->update(['quantity' => $newQuantity]);

            InventoryMovement::create([
                'branch_id' => $branch->id,
                'inventory_item_id' => $item->id,
                'type' => $type,
                'quantity' => round($delta, 3),
                'quantity_after' => $newQuantity,
                'user_id' => ($options['user'] ?? null)?->id,
                'sale_id' => ($options['sale'] ?? null)?->id,
                'inventory_transfer_id' => ($options['transfer'] ?? null)?->id,
                'notes' => $options['notes'] ?? null,
                'recorded_at' => $options['recordedAt'] ?? now(),
            ]);

            return $stock;
        });
    }
}
