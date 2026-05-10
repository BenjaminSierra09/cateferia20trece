<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-28">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

            <flux:subheading class="max-md:hidden whitespace-nowrap">Catálogo seco</flux:subheading>
            <flux:badge rounded color="zinc" icon="cube" size="lg">Pan</flux:badge>
            <flux:badge rounded color="zinc" icon="scale" size="lg">Café en grano</flux:badge>
        </div>

        <div class="flex items-center gap-3">
            <flux:button :href="route('dashboard.products.create')" variant="primary" icon="plus" wire:navigate>Nuevo producto</flux:button>
            <flux:tabs variant="segmented" class="w-auto!" size="sm">
                <flux:tab wire:click="$set('viewMode', 'list')" icon="list-bullet" icon:variant="outline" :data-current="$viewMode === 'list'" />
                <flux:tab wire:click="$set('viewMode', 'grid')" icon="squares-2x2" icon:variant="outline" :data-current="$viewMode === 'grid'" />
            </flux:tabs>
        </div>
    </div>

    <flux:card class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Productos</flux:heading>
                <flux:text>Administra pan, café en grano y otros artículos que no son bebidas.</flux:text>
            </div>
        </div>

        @if ($viewMode === 'list')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Producto</flux:table.column>
                    <flux:table.column>Unidad</flux:table.column>
                    <flux:table.column>Precio base</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Estado</flux:table.column>
                    <flux:table.column>Acciones</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($products as $product)
                        <flux:table.row wire:key="product-row-{{ $product->id }}">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($product->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-12 w-12 rounded-xl object-cover" />
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/10">
                                            <flux:icon.cube class="size-5" />
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $product->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $product->description ?: 'Sin descripción' }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $product->unit_type === 'piece' ? 'Pieza' : ($product->unit_type === 'gram' ? 'Gramo' : 'Kilo') }}
                            </flux:table.cell>
                            <flux:table.cell variant="strong">${{ number_format($product->base_price, 2) }}</flux:table.cell>
                            <flux:table.cell>{{ $product->sale_items_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $product->is_active ? 'emerald' : 'zinc' }}">
                                    {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown position="bottom" align="end" offset="-15">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                    <flux:menu>
                                        <flux:menu.item :href="route('dashboard.products.edit', $product)" icon="pencil-square" wire:navigate>Editar</flux:menu.item>
                                        <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $product->id }})">
                                            {{ $product->is_active ? 'Desactivar' : 'Reactivar' }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay productos registrados.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($products as $product)
                    <flux:card wire:key="product-card-{{ $product->id }}" class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-2xl object-cover" />
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/10">
                                        <flux:icon.cube class="size-6" />
                                    </div>
                                @endif
                                <div>
                                    <flux:heading size="lg">{{ $product->name }}</flux:heading>
                                    <flux:text>{{ $product->description ?: 'Sin descripción' }}</flux:text>
                                </div>
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item :href="route('dashboard.products.edit', $product)" icon="pencil-square" wire:navigate>Editar</flux:menu.item>
                                    <flux:menu.item icon="archive-box" variant="danger" wire:click="toggleActive({{ $product->id }})">
                                        {{ $product->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <flux:badge color="zinc" inset="top bottom">
                                {{ $product->unit_type === 'piece' ? 'Pieza' : ($product->unit_type === 'gram' ? 'Gramo' : 'Kilo') }}
                            </flux:badge>
                            <flux:badge color="sky" inset="top bottom">{{ $product->sale_items_count }} ventas</flux:badge>
                            <flux:badge color="{{ $product->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </div>

                        <flux:heading size="lg">${{ number_format($product->base_price, 2) }}</flux:heading>
                    </flux:card>
                @empty
                    <flux:callout icon="information-circle" color="sky">Todavía no hay productos registrados.</flux:callout>
                @endforelse
            </div>
        @endif

        <flux:pagination :paginator="$products" />
    </flux:card>
</div>
