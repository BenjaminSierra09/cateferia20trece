<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="perPage" size="sm" class="w-28">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

            <flux:subheading class="max-md:hidden whitespace-nowrap">Filtrar por:</flux:subheading>

            <flux:badge rounded color="zinc" icon="magnifying-glass" size="lg">Búsqueda</flux:badge>
            <flux:badge rounded color="zinc" icon="credit-card" size="lg">Pago</flux:badge>
        </div>

        <div class="flex items-center gap-3">
            <flux:button :href="route('dashboard.sales.create')" variant="ghost" wire:navigate>Venta manual</flux:button>
            <flux:button :href="route('dashboard.sales.pos')" variant="primary" wire:navigate>POS</flux:button>

            <flux:tabs variant="segmented" class="w-auto!" size="sm">
                <flux:tab wire:click="$set('viewMode', 'list')" icon="list-bullet" icon:variant="outline" :data-current="$viewMode === 'list'" />
                <flux:tab wire:click="$set('viewMode', 'compact')" icon="squares-2x2" icon:variant="outline" :data-current="$viewMode === 'compact'" />
            </flux:tabs>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="relative rounded-2xl bg-zinc-50 px-6 py-4 dark:bg-zinc-700/70">
                <flux:subheading>{{ $stat['title'] }}</flux:subheading>
                <flux:heading size="xl" class="mb-2">{{ $stat['value'] }}</flux:heading>
                <div class="flex items-center gap-1 text-sm font-medium {{ $stat['trendUp'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }}">
                    <flux:icon :icon="$stat['trendUp'] ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" />
                    {{ $stat['trend'] }}
                </div>
            </div>
        @endforeach
    </div>

    <flux:card class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading>Ventas</flux:heading>
                <flux:text>Consulta el historial y entra rápido al flujo manual o al POS.</flux:text>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
            <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Cliente, sucursal, colaborador o descuento" />
            <flux:select wire:model.live="paymentMethod" label="Método de pago">
                <option value="">Todos</option>
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->value }}">{{ $method->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:table :paginate="$sales">
            <flux:table.columns>
                <flux:table.column class="max-md:hidden">Folio</flux:table.column>
                <flux:table.column class="max-md:hidden">Fecha</flux:table.column>
                <flux:table.column class="max-md:hidden">Estado</flux:table.column>
                <flux:table.column>Cliente</flux:table.column>
                <flux:table.column>Compra</flux:table.column>
                <flux:table.column>Ingreso</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($sales as $sale)
                    <flux:table.row wire:key="sale-row-{{ $sale->id }}">
                        <flux:table.cell class="max-md:hidden">#{{ $sale->id }}</flux:table.cell>
                        <flux:table.cell class="max-md:hidden">{{ $sale->sold_at?->format('d/m/Y H:i') }}</flux:table.cell>
                        <flux:table.cell class="max-md:hidden">
                            <flux:badge color="{{ $sale->status->value === 'completed' ? 'emerald' : 'zinc' }}" size="sm" inset="top bottom">
                                {{ $sale->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$sale->customer?->name ?? 'Publico general'" :initials="$sale->customer?->name ? \Illuminate\Support\Str::of($sale->customer->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') : 'PG'" />
                                <div>
                                    <div>{{ $sale->customer?->name ?? 'Público general' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $sale->branch?->name ?? 'Sin sucursal' }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-6 truncate">
                            {{ $sale->items->sum('quantity') }} artículos · {{ $sale->payment_method->label() }}
                        </flux:table.cell>
                        <flux:table.cell variant="strong">${{ number_format($sale->total, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:dropdown position="bottom" align="end" offset="-15">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                <flux:menu>
                                    <flux:menu.item :href="route('dashboard.sales.create')" icon="plus" wire:navigate>Nueva venta manual</flux:menu.item>
                                    <flux:menu.item :href="route('dashboard.sales.pos')" icon="credit-card" wire:navigate>Abrir POS</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <flux:callout icon="information-circle" color="sky">Todavía no hay ventas registradas.</flux:callout>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <flux:pagination :paginator="$sales" />
    </flux:card>
</div>
