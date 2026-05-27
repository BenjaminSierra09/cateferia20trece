<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $branch ? 'Editar sucursal' : 'Nueva sucursal' }}</flux:heading>
            <flux:text>{{ $branch ? 'Actualiza la información operativa de esta ubicación.' : 'Registra una nueva ubicación operativa.' }}</flux:text>
        </div>

        <flux:button :href="route('dashboard.branches.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-5 md:grid-cols-2">
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

            <div class="md:col-span-2 border-t border-zinc-200 pt-5 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="md">Mercado Pago Point</flux:heading>
                        <flux:text>Guarda las credenciales de esta sucursal para consultar terminales y enviar cobros desde Android.</flux:text>
                    </div>

                    <flux:field variant="inline">
                        <flux:label>Activo</flux:label>
                        <flux:switch wire:model.live="mercado_pago_is_active" />
                        <flux:error name="mercado_pago_is_active" />
                    </flux:field>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input
                        wire:model="mercado_pago_access_token"
                        label="Access Token"
                        type="password"
                        autocomplete="new-password"
                        placeholder="{{ $branch?->mercado_pago_access_token ? 'Guardado, captura uno nuevo para reemplazarlo' : 'APP_USR-...' }}"
                    />
                    <flux:input
                        wire:model="mercado_pago_public_key"
                        label="Public Key"
                        type="password"
                        autocomplete="new-password"
                        placeholder="{{ $branch?->mercado_pago_public_key ? 'Guardada, captura una nueva para reemplazarla' : 'APP_USR-...' }}"
                    />
                    <flux:input
                        wire:model="mercado_pago_default_terminal_id"
                        label="Terminal predeterminada"
                        placeholder="NEWLAND_N950__..."
                    />
                    <flux:input
                        wire:model="mercado_pago_default_terminal_name"
                        label="Nombre de terminal"
                        placeholder="Point caja principal"
                    />
                </div>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">{{ $branch ? 'Actualizar sucursal' : 'Guardar sucursal' }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
