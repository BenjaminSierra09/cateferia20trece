<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-32">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

            <flux:subheading class="max-md:hidden whitespace-nowrap">Operación por ubicación</flux:subheading>

            <flux:separator vertical class="max-lg:hidden mx-1 my-2" />

            <div class="hidden items-center gap-2 md:flex">
                <flux:button :href="route('dashboard.branches.index')" :variant="request()->routeIs('dashboard.branches.*') ? 'primary' : 'ghost'" size="sm" icon="building-storefront" wire:navigate>
                    Sucursales
                </flux:button>
                <flux:button :href="route('dashboard.team.index')" :variant="request()->routeIs('dashboard.team.*') ? 'primary' : 'ghost'" size="sm" icon="user-group" wire:navigate>
                    Equipo
                </flux:button>
                <flux:button :href="route('dashboard.work-session.check-in')" :variant="request()->routeIs('dashboard.work-session.*') ? 'primary' : 'ghost'" size="sm" icon="clock" wire:navigate>
                    Turnos
                </flux:button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:tabs variant="segmented" class="w-auto!" size="sm">
                <flux:tab wire:click="$set('viewMode', 'list')" icon="list-bullet" icon:variant="outline" :data-current="$viewMode === 'list'" />
                <flux:tab wire:click="$set('viewMode', 'grid')" icon="squares-2x2" icon:variant="outline" :data-current="$viewMode === 'grid'" />
            </flux:tabs>

            <flux:button :href="route('dashboard.branches.create')" variant="primary" icon="plus" wire:navigate>
                Nueva sucursal
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Sucursales</flux:heading>
                <flux:text class="mt-2">Administra ubicaciones, operación diaria y rendimiento por tienda.</flux:text>
            </div>

            <div class="grid min-w-60 gap-2 text-right sm:grid-cols-2">
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Total visibles</flux:subheading>
                    <flux:heading size="lg">{{ $branches->total() }}</flux:heading>
                </div>
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Sucursales activas</flux:subheading>
                    <flux:heading size="lg">{{ $branches->getCollection()->where('is_active', true)->count() }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:badge color="emerald" icon="signal" inset="top bottom">
                {{ $branches->getCollection()->sum('sales_count') }} ventas visibles
            </flux:badge>
            <flux:badge color="sky" icon="users" inset="top bottom">
                {{ $branches->getCollection()->sum('users_count') }} colaboradores asignados
            </flux:badge>

            @if ($selectedBranchIds !== [])
                <flux:separator vertical class="hidden md:block" />

                <flux:badge color="amber" icon="check-circle" inset="top bottom">
                    {{ count($selectedBranchIds) }} seleccionadas
                </flux:badge>

                <flux:button size="sm" variant="ghost" wire:click="clearSelection">Limpiar selección</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="reactivateSelected">Reactivar seleccionadas</flux:button>
                <flux:button size="sm" variant="danger" wire:click="deactivateSelected">Desactivar seleccionadas</flux:button>
            @endif
        </div>

        @if ($viewMode === 'list')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column class="w-12">
                        <flux:checkbox :checked="$selectPage" wire:click="togglePageSelection" />
                    </flux:table.column>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Ciudad</flux:table.column>
                    <flux:table.column class="max-lg:hidden">Horario</flux:table.column>
                    <flux:table.column>Equipo</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($branches as $branch)
                        <flux:table.row wire:key="branch-row-{{ $branch->id }}">
                            <flux:table.cell class="pr-2">
                                <flux:checkbox wire:model.live="selectedBranchIds" value="{{ $branch->id }}" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="flex size-11 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                        <flux:icon.building-storefront class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ $branch->name }}</div>
                                        <div class="truncate text-sm text-zinc-500">{{ $branch->phone ?: 'Sin teléfono registrado' }}</div>
                                        <div class="truncate text-xs text-zinc-400">{{ $branch->address ?: 'Sin dirección completa' }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $branch->city ?: 'Sin ciudad' }}</flux:table.cell>
                            <flux:table.cell class="max-lg:hidden">{{ $branch->operating_hours ?: 'Sin horario' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" icon="user-group" inset="top bottom">{{ $branch->users_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell variant="strong">{{ $branch->sales_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $branch->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                    {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown position="bottom" align="end" offset="-15">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                    <flux:menu>
                                        <flux:menu.item :href="route('dashboard.branches.edit', $branch)" icon="pencil-square" wire:navigate>
                                            Editar
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $branch->id }})">
                                            {{ $branch->is_active ? 'Desactivar' : 'Reactivar' }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay sucursales registradas.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($branches as $branch)
                    <flux:card wire:key="branch-card-{{ $branch->id }}" class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <flux:checkbox wire:model.live="selectedBranchIds" value="{{ $branch->id }}" />
                                <div class="flex size-11 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                    <flux:icon.building-storefront class="size-5" />
                                </div>
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item :href="route('dashboard.branches.edit', $branch)" icon="pencil-square" wire:navigate>
                                        Editar
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $branch->id }})">
                                        {{ $branch->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div>
                            <flux:heading size="lg">{{ $branch->name }}</flux:heading>
                            <flux:text>{{ $branch->city ?: 'Sin ciudad' }}</flux:text>
                        </div>

                        <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <div>{{ $branch->phone ?: 'Sin teléfono registrado' }}</div>
                            <div>{{ $branch->address ?: 'Sin dirección completa' }}</div>
                            <div>{{ $branch->operating_hours ?: 'Sin horario' }}</div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge color="zinc" icon="user-group" inset="top bottom">{{ $branch->users_count }} equipo</flux:badge>
                            <flux:badge color="sky" icon="signal" inset="top bottom">{{ $branch->sales_count }} ventas</flux:badge>
                            <flux:badge color="{{ $branch->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </div>
                    </flux:card>
                @empty
                    <flux:callout icon="information-circle" color="sky">Todavía no hay sucursales registradas.</flux:callout>
                @endforelse
            </div>
        @endif

        <flux:pagination :paginator="$branches" />
    </flux:card>
</div>
