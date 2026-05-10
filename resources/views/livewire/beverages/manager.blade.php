<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-32">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

            <flux:subheading class="max-md:hidden whitespace-nowrap">Menú de bebidas</flux:subheading>

            <flux:separator vertical class="max-lg:hidden mx-1 my-2" />

            <div class="hidden items-center gap-2 md:flex">
                <flux:button :href="route('dashboard.beverages.index')" :variant="request()->routeIs('dashboard.beverages.*') ? 'primary' : 'ghost'" size="sm" icon="beaker" wire:navigate>
                    Bebidas
                </flux:button>
                <flux:button :href="route('dashboard.sizes.index')" :variant="request()->routeIs('dashboard.sizes.*') ? 'primary' : 'ghost'" size="sm" icon="arrows-right-left" wire:navigate>
                    Tamaños
                </flux:button>
                <flux:button :href="route('dashboard.customizations.options.index')" :variant="request()->routeIs('dashboard.customizations.options.*') ? 'primary' : 'ghost'" size="sm" icon="sparkles" wire:navigate>
                    Extras
                </flux:button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:tabs variant="segmented" class="w-auto!" size="sm">
                <flux:tab wire:click="$set('viewMode', 'list')" icon="list-bullet" icon:variant="outline" :data-current="$viewMode === 'list'" />
                <flux:tab wire:click="$set('viewMode', 'grid')" icon="squares-2x2" icon:variant="outline" :data-current="$viewMode === 'grid'" />
            </flux:tabs>

            <flux:button :href="route('dashboard.beverages.create')" variant="primary" icon="plus" wire:navigate>
                Nueva bebida
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">Bebidas</flux:heading>
                <flux:text class="mt-2">Visualiza el menú, sus tamaños y las configuraciones activas por producto.</flux:text>
            </div>

            <div class="grid min-w-60 gap-2 text-right sm:grid-cols-2">
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Total visibles</flux:subheading>
                    <flux:heading size="lg">{{ $beverages->total() }}</flux:heading>
                </div>
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Activas</flux:subheading>
                    <flux:heading size="lg">{{ $beverages->getCollection()->where('is_active', true)->count() }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:badge color="orange" icon="photo" inset="top bottom">
                {{ $beverages->getCollection()->whereNotNull('image_path')->count() }} con imagen
            </flux:badge>
            <flux:badge color="sky" icon="cube" inset="top bottom">
                {{ $beverages->getCollection()->sum(fn ($beverage) => $beverage->sizePrices->count()) }} tamaños visibles
            </flux:badge>
            <flux:badge color="violet" icon="sparkles" inset="top bottom">
                {{ $beverages->getCollection()->sum(fn ($beverage) => $beverage->customizationOptions->count()) }} extras vinculados
            </flux:badge>

            @if ($selectedBeverageIds !== [])
                <flux:separator vertical class="hidden md:block" />
                <flux:badge color="amber" icon="check-circle" inset="top bottom">
                    {{ count($selectedBeverageIds) }} seleccionadas
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
                    <flux:table.column>Bebida</flux:table.column>
                    <flux:table.column>Categoría</flux:table.column>
                    <flux:table.column class="max-lg:hidden">Tamaños</flux:table.column>
                    <flux:table.column class="max-xl:hidden">Extras</flux:table.column>
                    <flux:table.column>Base</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($beverages as $beverage)
                        <flux:table.row wire:key="beverage-row-{{ $beverage->id }}">
                            <flux:table.cell class="pr-2">
                                <flux:checkbox wire:model.live="selectedBeverageIds" value="{{ $beverage->id }}" />
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($beverage->image_path)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($beverage->image_path) }}"
                                            alt="{{ $beverage->name }}"
                                            class="h-14 w-14 rounded-2xl object-cover"
                                        />
                                    @else
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                            <flux:icon.beaker class="size-6" />
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ $beverage->name }}</div>
                                        <div class="truncate text-sm text-zinc-500">{{ $beverage->description ?: 'Sin descripción' }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="zinc" inset="top bottom">
                                    {{ $beverage->category?->name ?: 'Sin categoría' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="max-lg:hidden">
                                {{ $beverage->sizePrices->map(fn ($price) => $price->size?->name)->filter()->implode(', ') ?: 'Sin tamaños' }}
                            </flux:table.cell>
                            <flux:table.cell class="max-xl:hidden">
                                {{ $beverage->customizationOptions->map(fn ($option) => $option->name)->implode(', ') ?: 'Sin extras' }}
                            </flux:table.cell>
                            <flux:table.cell variant="strong">${{ number_format($beverage->base_price, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $beverage->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                    {{ $beverage->is_active ? 'Activa' : 'Inactiva' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown position="bottom" align="end" offset="-15">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                    <flux:menu>
                                        <flux:menu.item :href="route('dashboard.beverages.edit', $beverage)" icon="pencil-square" wire:navigate>
                                            Editar
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $beverage->id }})">
                                            {{ $beverage->is_active ? 'Desactivar' : 'Reactivar' }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay bebidas registradas.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($beverages as $beverage)
                    <flux:card wire:key="beverage-card-{{ $beverage->id }}" class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <flux:checkbox wire:model.live="selectedBeverageIds" value="{{ $beverage->id }}" />

                                @if ($beverage->image_path)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($beverage->image_path) }}"
                                        alt="{{ $beverage->name }}"
                                        class="h-14 w-14 rounded-2xl object-cover"
                                    />
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-600 dark:bg-orange-500/10">
                                        <flux:icon.beaker class="size-6" />
                                    </div>
                                @endif
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item :href="route('dashboard.beverages.edit', $beverage)" icon="pencil-square" wire:navigate>
                                        Editar
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $beverage->id }})">
                                        {{ $beverage->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div>
                            <flux:heading size="lg">{{ $beverage->name }}</flux:heading>
                            <flux:text>{{ $beverage->description ?: 'Sin descripción' }}</flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge color="zinc" inset="top bottom">{{ $beverage->category?->name ?: 'Sin categoría' }}</flux:badge>
                            <flux:badge color="sky" inset="top bottom">{{ $beverage->sizePrices->count() }} tamaños</flux:badge>
                            <flux:badge color="violet" inset="top bottom">{{ $beverage->customizationOptions->count() }} extras</flux:badge>
                        </div>

                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">${{ number_format($beverage->base_price, 2) }}</flux:heading>
                            <flux:badge color="{{ $beverage->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                {{ $beverage->is_active ? 'Activa' : 'Inactiva' }}
                            </flux:badge>
                        </div>
                    </flux:card>
                @empty
                    <flux:callout icon="information-circle" color="sky">Todavía no hay bebidas registradas.</flux:callout>
                @endforelse
            </div>
        @endif

        <flux:pagination :paginator="$beverages" />
    </flux:card>
</div>
