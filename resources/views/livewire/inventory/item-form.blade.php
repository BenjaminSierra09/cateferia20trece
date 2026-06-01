<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl">{{ $item ? 'Editar insumo' : 'Nuevo insumo' }}</flux:heading>
        <flux:text class="mt-2">Define el insumo y su unidad de medida base. Las existencias se llevan por sucursal.</flux:text>
    </div>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="name" label="Nombre" placeholder="Ej. Leche entera, Vaso 12 oz, Café en grano" />

            <flux:select wire:model="unit" label="Unidad de medida">
                @foreach ($this->unitOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="category" label="Categoría (opcional)" placeholder="Ej. Lácteos, Café, Desechables, Jarabes" />

            <flux:switch wire:model="is_active" label="Insumo activo" />

            <div class="flex justify-end gap-2">
                <flux:button :href="route('dashboard.inventory.index')" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">{{ $item ? 'Guardar cambios' : 'Crear insumo' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
