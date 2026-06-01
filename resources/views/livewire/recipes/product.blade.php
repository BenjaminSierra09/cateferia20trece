<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Receta de {{ $product->name }}</flux:heading>
            <flux:text class="mt-2">Define los insumos que consume este producto al venderse.</flux:text>
        </div>
        <flux:button :href="route('dashboard.recipes.index')" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-4">
            <flux:label>Insumos</flux:label>

            @foreach ($lines as $index => $line)
                <div wire:key="prod-line-{{ $index }}" class="flex flex-col gap-2 rounded-2xl border border-zinc-100 p-3 dark:border-zinc-800 sm:flex-row sm:items-center">
                    <flux:select wire:model="lines.{{ $index }}.inventory_item_id" class="flex-1">
                        <option value="">Selecciona insumo</option>
                        @foreach ($this->items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit->abbreviation() }})</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.001" min="0" wire:model="lines.{{ $index }}.quantity" placeholder="Cantidad" class="w-full sm:w-32" />
                    <flux:checkbox wire:model="lines.{{ $index }}.scales_with_quantity" label="Por unidad" />
                    <flux:button type="button" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})" aria-label="Quitar" />
                </div>
            @endforeach

            <flux:text class="text-sm">"Por unidad" multiplica el consumo por la cantidad vendida; sin marcar, consume la cantidad fija por venta.</flux:text>

            <flux:button type="button" variant="ghost" icon="plus" wire:click="addLine" size="sm">Agregar insumo</flux:button>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Guardar receta</flux:button>
            </div>
        </form>
    </flux:card>
</div>
