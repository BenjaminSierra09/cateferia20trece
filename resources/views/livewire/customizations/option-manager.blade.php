<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <flux:heading size="xl">Opciones de personalización</flux:heading>
            <flux:text>Administra cada variante disponible y su precio adicional.</flux:text>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:select wire:model.live="perPage" size="sm" class="w-full sm:w-24">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>

            <flux:button
                :href="route('dashboard.customizations.options.create')"
                variant="primary"
                icon="plus"
                wire:navigate
                class="w-full sm:w-auto"
            >
                Nueva opción
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
        <flux:card class="space-y-3">
            <div class="min-w-0">
                <flux:heading size="sm">Personalizaciones</flux:heading>
                <flux:text>Separa la estructura del catálogo y sus variantes.</flux:text>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <flux:button
                    :href="route('dashboard.customizations.types.index')"
                    variant="ghost"
                    class="justify-start"
                    wire:navigate
                    icon="squares-2x2"
                >
                    Tipos
                </flux:button>

                <flux:button
                    :href="route('dashboard.customizations.options.index')"
                    variant="primary"
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
                <flux:heading>Opciones registradas</flux:heading>
                <flux:text>{{ $customizationOptions->total() }} disponibles.</flux:text>
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$customizationOptions" class="w-full">
                    <flux:table.columns>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Opción</flux:table.column>
                        <flux:table.column>Tipo</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'price'" :direction="$sortDirection" wire:click="sort('price')">Precio</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'is_available'" :direction="$sortDirection" wire:click="sort('is_available')">Disponibilidad</flux:table.column>
                        <flux:table.column>Acciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($customizationOptions as $option)
                            <flux:table.row wire:key="customization-option-row-{{ $option->id }}">
                                <flux:table.cell>
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if ($option->image_path)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::url($option->image_path) }}"
                                                alt="{{ $option->name }}"
                                                class="h-12 w-12 shrink-0 rounded-xl object-cover"
                                            />
                                        @else
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/10">
                                                <flux:icon.sparkles class="size-5" />
                                            </div>
                                        @endif

                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ $option->name }}
                                            </div>
                                            <div class="truncate text-sm text-zinc-500">
                                                Se suma al precio final cuando aplica.
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="block max-w-40 truncate">
                                        {{ $option->type?->name ?: 'Sin tipo' }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    ${{ number_format($option->price, 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="{{ $option->is_available ? 'emerald' : 'zinc' }}">
                                        {{ $option->is_available ? 'Disponible' : 'No disponible' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                        <flux:button
                                            :href="route('dashboard.customizations.options.edit', $option)"
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
                                            wire:click="toggleOptionAvailability({{ $option->id }})"
                                            class="w-full sm:w-auto"
                                        >
                                            {{ $option->is_available ? 'Desactivar' : 'Reactivar' }}
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5">
                                    <flux:callout icon="information-circle" color="sky">
                                        Todavía no hay opciones registradas.
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
