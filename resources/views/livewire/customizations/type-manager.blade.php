<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <flux:heading size="xl">Tipos de personalización</flux:heading>
            <flux:text>Define familias como leche, azúcar, temperatura o toppings.</flux:text>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:select wire:model.live="perPage" size="sm" class="w-full sm:w-24">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>

            <flux:button
                :href="route('dashboard.customizations.types.create')"
                variant="primary"
                icon="plus"
                wire:navigate
                class="w-full sm:w-auto"
            >
                Nuevo tipo
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
        <flux:card class="space-y-3">
            <div class="min-w-0">
                <flux:heading size="sm">Personalizaciones</flux:heading>
                <flux:text>Administra cada capa por separado.</flux:text>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <flux:button
                    :href="route('dashboard.customizations.types.index')"
                    variant="primary"
                    class="justify-start"
                    wire:navigate
                    icon="squares-2x2"
                >
                    Tipos
                </flux:button>

                <flux:button
                    :href="route('dashboard.customizations.options.index')"
                    variant="ghost"
                    class="justify-start"
                    wire:navigate
                    icon="sparkles"
                >
                    Opciones
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="space-y-4 overflow-hidden">
            <div class="min-w-0">
                <flux:heading>Tipos registrados</flux:heading>
                <flux:text>{{ $customizationTypes->total() }} configurados.</flux:text>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$customizationTypes" class="w-full">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Tipo</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'selection_mode'" :direction="$sortDirection" wire:click="sort('selection_mode')">Selección</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'options_count'" :direction="$sortDirection" wire:click="sort('options_count')">Opciones</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Estado</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($customizationTypes as $type)
                            <flux:table.row wire:key="customization-type-row-{{ $type->id }}">
                                <flux:table.cell>
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if ($type->image_path)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::url($type->image_path) }}"
                                                alt="{{ $type->name }}"
                                                class="h-12 w-12 shrink-0 rounded-xl object-cover"
                                            />
                                        @else
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-500/10">
                                                <flux:icon.adjustments-horizontal class="size-5" />
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ $type->name }}
                                            </div>
                                            <div class="truncate text-sm text-zinc-500">
                                                Organiza opciones del mismo tipo.
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $type->selection_mode === 'multiple' ? 'Múltiple' : 'Única' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $type->options_count }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="{{ $type->is_active ? 'emerald' : 'zinc' }}">
                                        {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        <flux:button
                                            :href="route('dashboard.customizations.types.edit', $type)"
                                            variant="ghost"
                                            size="sm"
                                            wire:navigate
                                            class="w-full sm:w-auto"
                                        >
                                            Editar
                                        </flux:button>

                                        <flux:button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            wire:click="toggleTypeActive({{ $type->id }})"
                                            class="w-full sm:w-auto"
                                        >
                                            {{ $type->is_active ? 'Desactivar' : 'Reactivar' }}
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5">
                                    <flux:callout icon="information-circle" color="sky">
                                        Todavía no hay tipos registrados.
                                    </flux:callout>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    </div>
</div>
