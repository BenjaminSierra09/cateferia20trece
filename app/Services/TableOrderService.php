<?php

namespace App\Services;

use App\Enums\TableOrderStatus;
use App\Models\DiningTable;
use App\Models\TableOrder;
use App\Models\TableOrderItem;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TableOrderService
{
    public function __construct(
        protected SaleService $saleService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function open(array $payload, User $user, WorkSession $workSession): TableOrder
    {
        return DB::transaction(function () use ($payload, $user, $workSession): TableOrder {
            $tables = $this->resolveTables($workSession->branch_id, $payload);

            $this->ensureTablesAreAvailable($tables);

            $order = TableOrder::create([
                'branch_id' => $workSession->branch_id,
                'user_id' => $user->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'work_session_id' => $workSession->id,
                'status' => TableOrderStatus::Open,
                'label' => $payload['label'] ?? null,
                'guest_count' => max(1, (int) ($payload['guest_count'] ?? 1)),
                'opened_at' => now(),
                'notes' => $payload['notes'] ?? null,
            ]);

            $order->tables()->sync($tables->pluck('id'));

            return $order->fresh($this->defaultRelations());
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function addItems(TableOrder $order, array $items): TableOrder
    {
        $this->ensureOrderIsOpen($order);

        return DB::transaction(function () use ($order, $items): TableOrder {
            $workSession = $order->workSession;

            if ($workSession === null) {
                throw new InvalidArgumentException('La cuenta no tiene turno asociado.');
            }

            $lineItems = $this->saleService->resolveLineItems($items, $workSession);

            foreach ($lineItems as $index => $lineItem) {
                /** @var TableOrderItem $orderItem */
                $orderItem = $order->items()->create([
                    ...Arr::except($lineItem, ['customizations']),
                    'customization_option_ids' => Arr::wrap($items[$index]['customization_option_ids'] ?? []),
                    'guest_name' => $items[$index]['guest_name'] ?? null,
                ]);

                foreach ($lineItem['customizations'] as $customization) {
                    $orderItem->customizations()->create($customization);
                }
            }

            return $order->fresh($this->defaultRelations());
        });
    }

    /**
     * @param  array<int, int>  $sourceOrderIds
     */
    public function merge(TableOrder $target, array $sourceOrderIds): TableOrder
    {
        $this->ensureOrderIsOpen($target);

        return DB::transaction(function () use ($target, $sourceOrderIds): TableOrder {
            $sources = TableOrder::query()
                ->with(['tables', 'items.customizations'])
                ->whereKey($sourceOrderIds)
                ->where('branch_id', $target->branch_id)
                ->get();

            foreach ($sources as $source) {
                $this->ensureOrderIsOpen($source);

                if ($source->id === $target->id) {
                    throw new InvalidArgumentException('No puedes unir una mesa consigo misma.');
                }

                foreach ($source->items as $item) {
                    $item->forceFill(['table_order_id' => $target->id])->save();
                }

                $target->tables()->syncWithoutDetaching($source->tables->pluck('id'));
                $source->tables()->detach();
                $source->forceFill([
                    'status' => TableOrderStatus::Merged,
                    'merged_into_id' => $target->id,
                    'closed_at' => now(),
                ])->save();
            }

            return $target->fresh($this->defaultRelations());
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{order: TableOrder, sales: array<int, \App\Models\Sale>}
     */
    public function close(TableOrder $order, array $payload, User $user): array
    {
        $this->ensureOrderIsOpen($order);

        return DB::transaction(function () use ($order, $payload, $user): array {
            $order->loadMissing(['items.customizations', 'workSession']);
            $workSession = $order->workSession;

            if ($workSession === null) {
                throw new InvalidArgumentException('La cuenta no tiene turno asociado.');
            }

            $splits = $this->normalizeSplits($order, $payload);
            $this->ensureSplitQuantitiesMatchOrder($order, $splits);
            $sales = [];

            foreach ($splits as $split) {
                $salePayload = [
                    'user_id' => $user->id,
                    'customer_id' => $split['customer_id'] ?? ($payload['customer_id'] ?? $order->customer_id),
                    'payment_method' => $split['payment_method'] ?? $payload['payment_method'],
                    'payment_breakdown' => $split['payment_breakdown'] ?? ($payload['payment_breakdown'] ?? null),
                    'discount_total' => $split['discount_total'] ?? 0,
                    'reward_redeemed_total' => $split['reward_redeemed_total'] ?? 0,
                    'discount_concept' => $split['discount_concept'] ?? null,
                    'notes' => $split['notes'] ?? $payload['notes'] ?? $order->notes,
                    'table_order_id' => $order->id,
                    'items' => $this->itemsForSplit($order, $split),
                ];

                $sales[] = $this->saleService->register($salePayload, $user, $workSession);
            }

            $order->forceFill([
                'status' => TableOrderStatus::Closed,
                'closed_at' => now(),
            ])->save();

            return [
                'order' => $order->fresh($this->defaultRelations()),
                'sales' => $sales,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, DiningTable>
     */
    protected function resolveTables(int $branchId, array $payload): Collection
    {
        $tableIds = collect(Arr::wrap($payload['table_ids'] ?? []))->filter()->values();

        if ($tableIds->isNotEmpty()) {
            return DiningTable::query()
                ->where('branch_id', $branchId)
                ->whereIn('id', $tableIds)
                ->get();
        }

        $tableName = trim((string) ($payload['table_name'] ?? $payload['label'] ?? 'Mesa'));

        return collect([
            DiningTable::query()->firstOrCreate(
                ['branch_id' => $branchId, 'name' => $tableName],
                ['is_active' => true],
            ),
        ]);
    }

    /**
     * @param  Collection<int, DiningTable>  $tables
     */
    protected function ensureTablesAreAvailable(Collection $tables): void
    {
        if ($tables->isEmpty()) {
            throw new InvalidArgumentException('Selecciona al menos una mesa.');
        }

        $busyTable = $tables->first(function (DiningTable $table): bool {
            return $table->tableOrders()
                ->where('status', TableOrderStatus::Open->value)
                ->exists();
        });

        if ($busyTable !== null) {
            throw new InvalidArgumentException(sprintf('La %s ya tiene una cuenta abierta.', $busyTable->name));
        }
    }

    protected function ensureOrderIsOpen(TableOrder $order): void
    {
        if (! $order->isOpen()) {
            throw new InvalidArgumentException('La cuenta de mesa ya no está abierta.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeSplits(TableOrder $order, array $payload): array
    {
        $splits = Arr::wrap($payload['splits'] ?? []);

        if ($splits !== []) {
            return $splits;
        }

        return [[
            'payment_method' => $payload['payment_method'],
            'payment_breakdown' => $payload['payment_breakdown'] ?? null,
            'discount_total' => $payload['discount_total'] ?? 0,
            'reward_redeemed_total' => $payload['reward_redeemed_total'] ?? 0,
            'discount_concept' => $payload['discount_concept'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'items' => $order->items->map(fn (TableOrderItem $item): array => [
                'item_id' => $item->id,
                'quantity' => $item->quantity,
            ])->all(),
        ]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $splits
     */
    protected function ensureSplitQuantitiesMatchOrder(TableOrder $order, array $splits): void
    {
        $expected = $order->items->mapWithKeys(fn (TableOrderItem $item): array => [$item->id => (int) $item->quantity])->all();
        $actual = [];

        foreach ($splits as $split) {
            foreach (Arr::wrap($split['items'] ?? []) as $item) {
                $itemId = (int) ($item['item_id'] ?? 0);
                $actual[$itemId] = ($actual[$itemId] ?? 0) + max(1, (int) ($item['quantity'] ?? 1));
            }
        }

        ksort($expected);
        ksort($actual);

        if ($actual !== $expected) {
            throw new InvalidArgumentException('La división de cuenta debe cubrir exactamente todos los artículos de la mesa.');
        }
    }

    /**
     * @param  array<string, mixed>  $split
     * @return array<int, array<string, mixed>>
     */
    protected function itemsForSplit(TableOrder $order, array $split): array
    {
        $itemsById = $order->items->keyBy('id');

        return collect(Arr::wrap($split['items'] ?? []))->map(function (array $splitItem) use ($itemsById): array {
            /** @var TableOrderItem|null $item */
            $item = $itemsById->get((int) ($splitItem['item_id'] ?? 0));

            if ($item === null) {
                throw new InvalidArgumentException('La división incluye un artículo que no pertenece a esta mesa.');
            }

            return [
                'beverage_id' => $item->beverage_id,
                'product_id' => $item->product_id,
                'size_id' => $item->size_id,
                'item_name' => $item->beverage_id === null && $item->product_id === null ? $item->item_name : null,
                'unit_price' => $item->beverage_id === null && $item->product_id === null ? (float) $item->unit_price : null,
                'quantity' => max(1, (int) ($splitItem['quantity'] ?? 1)),
                'customization_option_ids' => Arr::wrap($item->customization_option_ids ?? []),
                'special_instructions' => $item->special_instructions,
            ];
        })->all();
    }

    /**
     * @return array<int, string>
     */
    protected function defaultRelations(): array
    {
        return [
            'branch',
            'user',
            'customer',
            'tables',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
            'sales',
        ];
    }
}
