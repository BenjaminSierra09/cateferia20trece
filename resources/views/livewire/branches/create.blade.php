<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $branch ? 'Editar sucursal' : 'Nueva sucursal' }}</flux:heading>
            <flux:text>{{ $branch ? 'Actualiza la información operativa de esta ubicación.' : 'Registra una nueva ubicación operativa.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.branches.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Nombre" class="md:col-span-2" />
            <flux:input wire:model="city" label="Ciudad" />
            <x-phone-input
                label="Teléfono"
                name="branch_phone"
                wire-model="phone"
                :value="$phone"
                placeholder="+52 415 123 4567"
            />
            <flux:input wire:model="address" label="Dirección" class="md:col-span-2" />
            <flux:input wire:model="operating_hours" label="Horario" placeholder="07:00 - 21:00" />
            <flux:field variant="inline" class="self-end">
                <flux:label>Activa</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">{{ $branch ? 'Actualizar sucursal' : 'Guardar sucursal' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
