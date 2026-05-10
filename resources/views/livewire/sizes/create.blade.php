<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $size ? 'Editar tamaño' : 'Nuevo tamaño' }}</flux:heading>
            <flux:text>{{ $size ? 'Actualiza la presentación de este tamaño.' : 'Define una nueva presentación para las bebidas.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.sizes.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Nombre" />
            <flux:input wire:model="capacity_label" label="Capacidad" placeholder="12 oz" />
            <flux:input wire:model="capacity_ounces" label="Onzas" type="number" step="0.01" min="0" />
            <flux:field variant="inline" class="self-end">
                <flux:label>Activo</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">{{ $size ? 'Actualizar tamaño' : 'Guardar tamaño' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
