<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">Traspaso entre sucursales</flux:heading>
        <flux:text class="mt-2">Mueve existencias de una sucursal a otra. Se registra una salida en el origen y una entrada en el destino.</flux:text>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="fromBranchId" label="Sucursal de origen">
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="toBranchId" label="Sucursal de destino">
                    @foreach ($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            @error('fromBranchId')
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            <div class="space-y-3">
                <flux:label>Insumos a traspasar</flux:label>

                @foreach ($lines as $index => $line)
                    <div wire:key="transfer-line-{{ $index }}" class="space-y-1">
                        <div class="flex items-start gap-3">
                            <flux:select wire:model="lines.{{ $index }}.inventory_item_id" class="flex-1">
                                <option value="">Selecciona insumo</option>
                                @foreach ($this->items as $inventoryItem)
                                    <option value="{{ $inventoryItem->id }}">{{ $inventoryItem->name }} ({{ $inventoryItem->unit->abbreviation() }})</option>
                                @endforeach
                            </flux:select>

                            <flux:input type="number" step="0.001" min="0" wire:model="lines.{{ $index }}.quantity" placeholder="Cantidad" class="w-32" />

                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})" aria-label="Quitar" />
                        </div>

                        @error('lines.'.$index.'.inventory_item_id')
                            <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                        @error('lines.'.$index.'.quantity')
                            <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                @endforeach

                @error('lines')
                    <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <flux:button type="button" variant="ghost" icon="plus" wire:click="addLine" size="sm">Agregar insumo</flux:button>
            </div>

            <flux:input wire:model="notes" label="Nota (opcional)" placeholder="Motivo del traspaso" />

            <div class="flex justify-end gap-2">
                <flux:button :href="route('dashboard.inventory.index')" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Realizar traspaso</flux:button>
            </div>
        </form>
    </flux:card>
</div>
