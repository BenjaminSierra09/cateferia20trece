<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
            <flux:select wire:model.live="perPage" size="sm" class="w-full md:w-32">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <flux:tabs variant="segmented" class="w-full sm:w-auto!" size="sm">
                <flux:tab wire:click="$set('viewMode', 'list')" icon="list-bullet" icon:variant="outline" :data-current="$viewMode === 'list'" />
                <flux:tab wire:click="$set('viewMode', 'grid')" icon="squares-2x2" icon:variant="outline" :data-current="$viewMode === 'grid'" />
            </flux:tabs>

            <flux:button
                :href="route('dashboard.branches.create')"
                variant="primary"
                icon="plus"
                wire:navigate
                class="w-full sm:w-auto"
            >
                Nueva sucursal
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-5 overflow-hidden">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl">Sucursales</flux:heading>
                <flux:text class="mt-2">
                    Administra ubicaciones, operación diaria y rendimiento por tienda.
                </flux:text>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-60 lg:text-right">
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Total visibles</flux:subheading>
                    <flux:heading size="lg">{{ $branches->total() }}</flux:heading>
                </div>

                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Sucursales activas</flux:subheading>
                    <flux:heading size="lg">
                        {{ $branches->getCollection()->where('is_active', true)->count() }}
                    </flux:heading>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <flux:badge color="emerald" icon="signal" inset="top bottom">
                {{ $branches->getCollection()->sum('sales_count') }} ventas visibles
            </flux:badge>

            <flux:badge color="sky" icon="users" inset="top bottom">
                {{ $branches->getCollection()->sum('work_sessions_count') }} turnos registrados
            </flux:badge>

            @if ($selectedBranchIds !== [])
                <flux:separator vertical class="hidden md:block" />

                <flux:badge color="amber" icon="check-circle" inset="top bottom">
                    {{ count($selectedBranchIds) }} seleccionadas
                </flux:badge>

                <div class="grid gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <flux:button size="sm" variant="ghost" wire:click="clearSelection" class="w-full sm:w-auto">
                        Limpiar selección
                    </flux:button>

                    <flux:button size="sm" variant="ghost" wire:click="reactivateSelected" class="w-full sm:w-auto">
                        Reactivar seleccionadas
                    </flux:button>

                    <flux:button size="sm" variant="danger" wire:click="deactivateSelected" class="w-full sm:w-auto">
                        Desactivar seleccionadas
                    </flux:button>
                </div>
            @endif
        </div>

        @if ($viewMode === 'list')
            <div class="overflow-x-auto">
                <flux:table class="w-full">
                    <flux:table.columns>
                        <flux:table.column class="w-12">
                            <flux:checkbox :checked="$selectPage" wire:click="togglePageSelection" />
                        </flux:table.column>

                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Sucursal</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'city'" :direction="$sortDirection" wire:click="sort('city')">Ciudad</flux:table.column>
                        <flux:table.column class="max-lg:hidden">Horario</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'work_sessions_count'" :direction="$sortDirection" wire:click="sort('work_sessions_count')">Equipo</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'sales_count'" :direction="$sortDirection" wire:click="sort('sales_count')">Ventas</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')">Estado</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($branches as $branch)
                            <flux:table.row wire:key="branch-row-{{ $branch->id }}">
                                <flux:table.cell class="pr-2">
                                    <flux:checkbox wire:model.live="selectedBranchIds" value="{{ $branch->id }}" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                            <flux:icon.building-storefront class="size-5" />
                                        </div>

                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ $branch->name }}
                                            </div>

                                            <div class="truncate text-sm text-zinc-500">
                                                {{ $branch->phone ?: 'Sin teléfono registrado' }}
                                            </div>

                                            <div class="truncate text-xs text-zinc-400">
                                                {{ $branch->address ?: 'Sin dirección completa' }}
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <span class="block max-w-36 truncate">
                                        {{ $branch->city ?: 'Sin ciudad' }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell class="max-lg:hidden">
                                    <span class="block max-w-48 truncate">
                                        {{ $branch->operating_hours ?: 'Sin horario' }}
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="zinc" icon="clock" inset="top bottom">
                                        {{ $branch->work_sessions_count }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    {{ $branch->sales_count }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="{{ $branch->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                        {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex justify-end">
                                        <flux:dropdown position="bottom" align="end" offset="-15">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="ellipsis-horizontal"
                                                inset="top bottom"
                                            />

                                            <flux:menu>
                                                <flux:menu.item
                                                    :href="route('dashboard.branches.edit', $branch)"
                                                    icon="pencil-square"
                                                    wire:navigate
                                                >
                                                    Editar
                                                </flux:menu.item>

                                                <flux:menu.separator />

                                                <flux:menu.item
                                                    icon="archive-box"
                                                    variant="danger"
                                                    wire:click="toggleActive({{ $branch->id }})"
                                                >
                                                    {{ $branch->is_active ? 'Desactivar' : 'Reactivar' }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8">
                                    <flux:callout icon="information-circle" color="sky">
                                        Todavía no hay sucursales registradas.
                                    </flux:callout>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($branches as $branch)
                    <flux:card wire:key="branch-card-{{ $branch->id }}" class="space-y-4 overflow-hidden">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <flux:checkbox wire:model.live="selectedBranchIds" value="{{ $branch->id }}" />

                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                    <flux:icon.building-storefront class="size-5" />
                                </div>
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item
                                        :href="route('dashboard.branches.edit', $branch)"
                                        icon="pencil-square"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="toggleActive({{ $branch->id }})"
                                    >
                                        {{ $branch->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">
                                {{ $branch->name }}
                            </flux:heading>

                            <flux:text>
                                {{ $branch->city ?: 'Sin ciudad' }}
                            </flux:text>
                        </div>

                        <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <div class="truncate">
                                {{ $branch->phone ?: 'Sin teléfono registrado' }}
                            </div>

                            <div class="line-clamp-2">
                                {{ $branch->address ?: 'Sin dirección completa' }}
                            </div>

                            <div class="line-clamp-2">
                                {{ $branch->operating_hours ?: 'Sin horario' }}
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge color="zinc" icon="clock" inset="top bottom">
                                {{ $branch->work_sessions_count }} turnos
                            </flux:badge>

                            <flux:badge color="sky" icon="signal" inset="top bottom">
                                {{ $branch->sales_count }} ventas
                            </flux:badge>

                            <flux:badge color="{{ $branch->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </div>
                    </flux:card>
                @empty
                    <flux:callout icon="information-circle" color="sky">
                        Todavía no hay sucursales registradas.
                    </flux:callout>
                @endforelse
            </div>
        @endif

        <div class="overflow-x-auto">
            <flux:pagination :paginator="$branches" />
        </div>
    </flux:card>
</div>
