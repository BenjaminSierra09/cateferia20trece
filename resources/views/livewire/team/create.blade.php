<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $user ? 'Editar colaborador' : 'Nuevo colaborador' }}</flux:heading>
            <flux:text>{{ $user ? 'Actualiza los datos del colaborador y su acceso.' : 'Registra al equipo con su rol y acceso.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.team.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Nombre" />
            <flux:input wire:model="username" label="Usuario" />
            <flux:input wire:model="email" label="Correo" type="email" class="md:col-span-2" />
            <flux:input wire:model="password" label="Contraseña" type="password" viewable />
            <flux:input wire:model="password_confirmation" label="Confirmar contraseña" type="password" viewable />
            <flux:select wire:model="role" label="Rol">
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </flux:select>
            <flux:field variant="inline" class="self-end">
                <flux:label>Activo</flux:label>
                <flux:switch wire:model.live="is_active" />
                <flux:error name="is_active" />
            </flux:field>

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">{{ $user ? 'Actualizar colaborador' : 'Guardar colaborador' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
