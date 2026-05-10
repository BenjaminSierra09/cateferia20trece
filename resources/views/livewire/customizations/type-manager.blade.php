<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading>Tipos de personalización</flux:heading>
            <flux:text>Define familias como leche, azúcar, temperatura o toppings.</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-24">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>
            <flux:button :href="route('dashboard.customizations.types.create')" variant="primary" icon="plus" wire:navigate>Nuevo tipo</flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
        <flux:card class="space-y-3">
            <flux:heading size="sm">Personalizaciones</flux:heading>
            <flux:text>Administra cada capa por separado.</flux:text>
            <div class="grid gap-2">
                <flux:button :href="route('dashboard.customizations.types.index')" variant="primary" class="justify-start" wire:navigate icon="squares-2x2">Tipos</flux:button>
                <flux:button :href="route('dashboard.customizations.options.index')" variant="ghost" class="justify-start" wire:navigate icon="sparkles">Opciones</flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading>Tipos registrados</flux:heading>
                <flux:text>{{ $customizationTypes->total() }} configurados.</flux:text>
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
                                    <div>
                                        <div class="font-medium">{{ $type->name }}</div>
                                        <div class="text-sm text-zinc-500">Organiza opciones del mismo tipo.</div>
                                    </div>
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
                                        {{ $type->is_active ? 'Desactivar' : 'Reactivar' }}
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
    </div>
</div>
