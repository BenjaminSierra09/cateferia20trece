<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $customer ? 'Editar cliente' : 'Nuevo cliente' }}</flux:heading>
            <flux:text>Captura sus datos y vincula códigos QR cuando ya exista el registro.</flux:text>
        </div>

        <flux:button :href="route('dashboard.customers.index')" variant="ghost" wire:navigate>Volver</flux:button>
    </div>

    <flux:card class="space-y-4">
        <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="name" label="Nombre" class="md:col-span-2" />
            <x-phone-input
                label="Teléfono"
                name="customer_phone"
                wire-model="phone"
                :value="$phone"
                placeholder="+52 415 123 4567"
            />
            <flux:date-picker wire:model.live="birthday" label="Cumpleaños" max="today" selectable-header />
            <flux:input wire:model="email" label="Correo" type="email" class="md:col-span-2" />

            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">Guardar cliente</flux:button>
            </div>
        </form>
    </flux:card>

    @if ($tonalpohualli)
        <flux:card class="space-y-4">
            <div class="flex items-center gap-3">
                <flux:icon.sparkles class="size-5 text-orange-500" />
                <div>
                    <flux:heading>Tonalpohualli</flux:heading>
                    <flux:text>Lectura calculada desde el cumpleaños del cliente.</flux:text>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-sm text-zinc-500">Tonalli</div>
                    <div class="mt-2 text-lg font-semibold">{{ $tonalpohualli['tonalli'] }}</div>
                    <div class="text-sm text-zinc-500">{{ $tonalpohualli['espanol'] }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-sm text-zinc-500">Deidad patrona</div>
                    <div class="mt-2 text-lg font-semibold">{{ $tonalpohualli['deidad'] }}</div>
                </div>
                <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-sm text-zinc-500">Zona del cuerpo</div>
                    <div class="mt-2 text-lg font-semibold">{{ $tonalpohualli['cuerpo'] }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4 dark:border-orange-500/20 dark:bg-orange-500/10">
                <div class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Significado oracular</div>
                <div class="mt-2 text-zinc-800 dark:text-zinc-100">{{ $tonalpohualli['significado'] }}</div>
                <div class="mt-3 text-sm text-zinc-500">Trecena lider: {{ $tonalpohualli['trecena'] }}</div>
            </div>
        </flux:card>
    @endif

    @if ($customer)
        <div class="grid gap-6 lg:grid-cols-[minmax(0,360px)_1fr]">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading>Vincular QR</flux:heading>
                    <flux:text>Agrega el UUID leído desde la tarjeta del cliente.</flux:text>
                </div>

                <form wire:submit="attachQrCode" class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                        <flux:input wire:model="qr_uuid" label="UUID de QR" placeholder="e7d3c5c1-..." />
                        <div class="flex items-end">
                            <x-qr-scanner-button field="qr_uuid" label="Cámara" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Vincular QR</flux:button>
                    </div>
                </form>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading>QR vinculados</flux:heading>
                    <flux:text>{{ $linkedQrCodes->count() }} registros asociados.</flux:text>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>UUID</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Último escaneo</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($linkedQrCodes as $qrCode)
                            <flux:table.row wire:key="linked-qr-{{ $qrCode->id }}">
                                <flux:table.cell>{{ $qrCode->uuid }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="{{ $qrCode->is_active ? 'emerald' : 'zinc' }}">
                                        {{ $qrCode->is_active ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $qrCode->last_scanned_at?->format('d/m/Y H:i') ?: 'Sin escaneos' }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3">
                                    <flux:callout icon="information-circle" color="sky">Este cliente todavía no tiene QR vinculados.</flux:callout>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    @endif
</div>
