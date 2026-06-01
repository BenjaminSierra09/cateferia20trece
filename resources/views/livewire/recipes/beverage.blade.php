<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Receta de {{ $beverage->name }}</flux:heading>
            <flux:text class="mt-2">Define los insumos que consume cada tamaño de esta bebida.</flux:text>
        </div>
        <flux:button :href="route('dashboard.recipes.index')" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-6">
        <flux:select wire:model.live="sizeId" label="Tamaño">
            @foreach ($this->sizes as $size)
                <option value="{{ $size->id }}">{{ $size->name }} @if ($size->capacity_label) ({{ $size->capacity_label }}) @endif</option>
            @endforeach
        </flux:select>

        <form wire:submit="save" class="space-y-4">
            <flux:label>Insumos por unidad</flux:label>

            @foreach ($lines as $index => $line)
                <div wire:key="bev-line-{{ $index }}" class="flex items-start gap-3">
                    <flux:select wire:model="lines.{{ $index }}.inventory_item_id" class="flex-1">
                        <option value="">Selecciona insumo</option>
                        @foreach ($this->items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit->abbreviation() }})</option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" step="0.001" min="0" wire:model="lines.{{ $index }}.quantity" placeholder="Cantidad" class="w-32" />
                    <flux:button type="button" variant="ghost" icon="trash" wire:click="removeLine({{ $index }})" aria-label="Quitar" />
                </div>
            @endforeach

            <flux:button type="button" variant="ghost" icon="plus" wire:click="addLine" size="sm">Agregar insumo</flux:button>

            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Guardar receta</flux:button>
            </div>
        </form>
    </flux:card>
</div>
