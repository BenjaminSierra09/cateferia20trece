<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:select wire:model.live="branchId" size="sm" class="w-full sm:w-56">
                @foreach ($this->branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.300ms="search" size="sm" icon="magnifying-glass" placeholder="Buscar insumo" class="w-full sm:w-64" />
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <flux:button :href="route('dashboard.inventory.transfers.create')" variant="ghost" icon="arrows-right-left" wire:navigate class="w-full sm:w-auto">
                Traspaso
            </flux:button>
            <flux:button :href="route('dashboard.inventory.items.create')" variant="primary" icon="plus" wire:navigate class="w-full sm:w-auto">
                Nuevo insumo
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-5 overflow-hidden">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl">Inventario por sucursal</flux:heading>
                <flux:text class="mt-2">
                    Existencias de insumos. Cada entrada, ajuste y traspaso queda registrado en el historial.
                </flux:text>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-60 lg:text-right">
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Insumos</flux:subheading>
                    <flux:heading size="lg">{{ $this->rows->count() }}</flux:heading>
                </div>
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Bajo stock</flux:subheading>
                    <flux:heading size="lg">{{ $this->lowStockCount }}</flux:heading>
                </div>
            </div>
        </div>

        @if ($this->lowStockCount > 0)
            <flux:badge color="amber" icon="exclamation-triangle" inset="top bottom">
                {{ $this->lowStockCount }} insumo(s) en o por debajo del mínimo
            </flux:badge>
        @endif

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Insumo</flux:table.column>
                <flux:table.column class="max-md:hidden">Categoría</flux:table.column>
                <flux:table.column>Existencia</flux:table.column>
                <flux:table.column class="max-md:hidden">Mínimo</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->rows as $row)
                    <flux:table.row wire:key="inv-row-{{ $row['item']->id }}">
                        <flux:table.cell variant="strong">{{ $row['item']->name }}</flux:table.cell>

                        <flux:table.cell class="max-md:hidden">
                            {{ $row['item']->category ?: 'Sin categoría' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="font-semibold {{ $row['is_negative'] ? 'text-red-600 dark:text-red-400' : '' }}">
                                {{ (float) $row['quantity'] }} {{ $row['item']->unit->abbreviation() }}
                            </span>
                            @if ($row['is_negative'])
                                <flux:badge size="sm" color="red" class="ml-2">Negativo</flux:badge>
                            @elseif ($row['is_low'])
                                <flux:badge size="sm" color="amber" class="ml-2">Bajo</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="max-md:hidden">
                            {{ (float) $row['min_quantity'] }} {{ $row['item']->unit->abbreviation() }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                <flux:button size="xs" variant="ghost" icon="plus" wire:click="openAction({{ $row['item']->id }}, 'entrada')">
                                    Entrada
                                </flux:button>
                                <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openAction({{ $row['item']->id }}, 'ajuste')">
                                    Ajuste
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="py-6 text-center">
                                <flux:text>Aún no hay insumos. Crea el primero para empezar a controlar existencias.</flux:text>
                                <flux:button :href="route('dashboard.inventory.items.create')" variant="primary" icon="plus" wire:navigate size="sm" class="mt-3">
                                    Nuevo insumo
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Movimientos recientes</flux:heading>

        @forelse ($this->recentMovements as $movement)
            <div wire:key="mov-{{ $movement->id }}" class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                <div class="min-w-0">
                    <flux:text class="font-medium">{{ $movement->item?->name }}</flux:text>
                    <flux:subheading>
                        {{ $movement->type->label() }}
                        @if ($movement->notes)
                            · {{ $movement->notes }}
                        @endif
                        · {{ $movement->recorded_at?->diffForHumans() }}
                    </flux:subheading>
                </div>
                <div class="text-right">
                    <flux:text class="font-semibold {{ (float) $movement->quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ (float) $movement->quantity > 0 ? '+' : '' }}{{ (float) $movement->quantity }} {{ $movement->item?->unit->abbreviation() }}
                    </flux:text>
                    <flux:subheading>Quedan {{ (float) $movement->quantity_after }}</flux:subheading>
                </div>
            </div>
        @empty
            <flux:text>Sin movimientos registrados en esta sucursal.</flux:text>
        @endforelse
    </flux:card>

    {{-- Quick entrada / ajuste --}}
    <flux:modal name="inventory-action" class="md:w-96">
        <form wire:submit="saveAction" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $actionType === 'entrada' ? 'Registrar entrada' : 'Ajustar existencia' }}</flux:heading>
                <flux:text class="mt-1">{{ $this->actionItem?->name }}</flux:text>
            </div>

            <flux:input
                type="number"
                step="0.001"
                min="0"
                wire:model="actionQuantity"
                :label="$actionType === 'entrada' ? 'Cantidad a ingresar' : 'Existencia final (queda en este valor)'"
                :suffix="$this->actionItem?->unit->abbreviation()"
            />

            <flux:input wire:model="actionNotes" label="Nota (opcional)" placeholder="Ej. Compra a proveedor" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
