<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Personalizaciones</flux:heading>
            <flux:text>Consulta tipos y opciones disponibles para el menú.</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-24">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>
            <flux:button :href="route('dashboard.customizations.create')" variant="primary" icon="plus" wire:navigate>Nueva personalización</flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="xl">Tipos de personalización</flux:heading>
                <flux:text>{{ $customizationTypes->count() }} configurados.</flux:text>
            </div>

            <flux:table :paginate="$customizationTypes">
                <flux:table.columns>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Selección</flux:table.column>
                    <flux:table.column>Opciones</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($customizationTypes as $type)
                        <flux:table.row wire:key="customization-type-row-{{ $type->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($type->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($type->image_path) }}" alt="{{ $type->name }}" class="h-12 w-12 rounded-xl object-cover" />
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/10">
                                            <flux:icon.adjustments-horizontal class="size-5" />
                                        </div>
                                    @endif
                                    <div>{{ $type->name }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $type->selection_mode === 'multiple' ? 'Múltiple' : 'Única' }}</flux:table.cell>
                            <flux:table.cell>{{ $type->options_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $type->is_active ? 'emerald' : 'zinc' }}">
                                    {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-2">
                                    <flux:button :href="route('dashboard.customizations.types.edit', $type)" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                                    <flux:button type="button" variant="danger" size="sm" wire:click="toggleTypeActive({{ $type->id }})">
                                        {{ $type->is_active ? 'Eliminar' : 'Reactivar' }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay tipos registrados.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="xl">Opciones disponibles</flux:heading>
                <flux:text>{{ $customizationOptions->count() }} opciones registradas.</flux:text>
            </div>

            <flux:table :paginate="$customizationOptions">
                <flux:table.columns>
                    <flux:table.column>Opción</flux:table.column>
                    <flux:table.column>Tipo</flux:table.column>
                    <flux:table.column>Precio</flux:table.column>
                    <flux:table.column>Disponibilidad</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($customizationOptions as $option)
                        <flux:table.row wire:key="customization-option-row-{{ $option->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($option->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($option->image_path) }}" alt="{{ $option->name }}" class="h-12 w-12 rounded-xl object-cover" />
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/10">
                                            <flux:icon.sparkles class="size-5" />
                                        </div>
                                    @endif
                                    <div>{{ $option->name }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $option->type?->name ?: 'Sin tipo' }}</flux:table.cell>
                            <flux:table.cell>${{ number_format($option->price, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $option->is_available ? 'emerald' : 'zinc' }}">
                                    {{ $option->is_available ? 'Disponible' : 'No disponible' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex justify-end gap-2">
                                    <flux:button :href="route('dashboard.customizations.options.edit', $option)" variant="ghost" size="sm" wire:navigate>Editar</flux:button>
                                    <flux:button type="button" variant="danger" size="sm" wire:click="toggleOptionAvailability({{ $option->id }})">
                                        {{ $option->is_available ? 'Eliminar' : 'Reactivar' }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay opciones registradas.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
