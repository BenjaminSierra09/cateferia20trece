<div class="space-y-6">
    <flux:card class="space-y-4">
        <div>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="xl">Tamaños</flux:heading>
                    <flux:text>Define capacidad y disponibilidad de cada presentación.</flux:text>
                </div>

                <div class="flex items-center gap-3">
                    <flux:select wire:model.live="perPage" size="sm" class="w-24">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </flux:select>
                    <flux:button :href="route('dashboard.sizes.create')" variant="primary" wire:navigate>Nuevo tamaño</flux:button>
                </div>
            </div>
        </div>

        <flux:table :paginate="$sizes">
            <flux:table.columns>
                <flux:table.column>Tamaño</flux:table.column>
                <flux:table.column>Etiqueta</flux:table.column>
                <flux:table.column>Onzas</flux:table.column>
                <flux:table.column>Usos</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($sizes as $size)
                    <flux:table.row wire:key="size-row-{{ $size->id }}">
                        <flux:table.cell>{{ $size->name }}</flux:table.cell>
                        <flux:table.cell>{{ $size->capacity_label }}</flux:table.cell>
                        <flux:table.cell>{{ $size->capacity_ounces ?: 'Sin dato' }}</flux:table.cell>
                        <flux:table.cell>{{ $size->beverage_prices_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $size->is_active ? 'emerald' : 'zinc' }}">
                                {{ $size->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button :href="route('dashboard.sizes.edit', $size)" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                                <flux:button type="button" variant="danger" size="sm" wire:click="toggleActive({{ $size->id }})">
                                    {{ $size->is_active ? 'Eliminar' : 'Reactivar' }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:callout icon="information-circle" color="sky">Todavía no hay tamaños registrados.</flux:callout>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
