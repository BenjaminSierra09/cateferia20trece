<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>Venta #{{ $sale->id }}</flux:heading>
            <flux:text>Detalles completos de la venta</flux:text>
        </div>
        <div class="flex items-center gap-3">
            @if ($sale->canBeCancelled())
                <flux:button
                    variant="danger"
                    icon="x-circle"
                    wire:click="cancelSale"
                    wire:confirm="¿Seguro que quieres cancelar esta venta? Se revertirá el saldo a favor y la deuda automática vinculados a ella."
                >
                    Cancelar venta
                </flux:button>
            @endif

            <flux:button :href="route('dashboard.sales.index')" variant="ghost" icon="arrow-left" wire:navigate>
                Volver
            </flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Sale Information -->
            <flux:card>
                <div class="space-y-4">
                    @if ($sale->status->value === 'cancelled')
                        <flux:callout icon="exclamation-triangle" color="amber">
                            Esta venta está cancelada y ya no debe considerarse para reportes, recompensas o adeudos automáticos.
                        </flux:callout>
                    @endif

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <flux:subheading>Folio</flux:subheading>
                            <flux:text class="font-mono text-lg">#{{ $sale->id }}</flux:text>
                        </div>
                        <div>
                            <flux:subheading>Estado</flux:subheading>
                            <flux:badge color="{{ $sale->status->value === 'completed' ? 'emerald' : 'zinc' }}" size="lg">
                                {{ $sale->status->label() }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:subheading>Fecha y hora</flux:subheading>
                            <flux:text>{{ $sale->sold_at?->format('d/m/Y H:i') ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:subheading>Método de pago</flux:subheading>
                            <flux:text>{{ $sale->payment_method->label() }}</flux:text>
                        </div>
                    </div>

                    @if ($sale->discount_concept)
                        <div>
                            <flux:subheading>Concepto de descuento</flux:subheading>
                            <flux:text>{{ $sale->discount_concept }}</flux:text>
                        </div>
                    @endif

                    @if ($sale->notes)
                        <div>
                            <flux:subheading>Notas</flux:subheading>
                            <flux:text>{{ $sale->notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Items -->
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Artículos</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Artículo</flux:table.column>
                            <flux:table.column class="text-right">Cantidad</flux:table.column>
                            <flux:table.column class="text-right">Precio unit.</flux:table.column>
                            <flux:table.column class="text-right">Subtotal</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse ($sale->items as $item)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <div>
                                            <div class="font-medium">{{ $item->item_name }}</div>
                                            @if ($item->size?->name)
                                                <div class="text-xs text-zinc-500">{{ $item->size->name }}</div>
                                            @endif
                                            @if ($item->customizations && count($item->customizations) > 0)
                                                <div class="text-xs text-zinc-500 space-y-1 mt-1">
                                                    @foreach ($item->customizations as $customization)
                                                        <div>• {{ $customization->customization_type_name }}: {{ $customization->customization_name }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">{{ $item->quantity }}</flux:table.cell>
                                    <flux:table.cell class="text-right">${{ number_format($item->unit_price, 2) }}</flux:table.cell>
                                    <flux:table.cell class="text-right font-medium">${{ number_format($item->line_total, 2) }}</flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="4" class="text-center text-zinc-500">
                                        Sin artículos
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Information -->
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Cliente</flux:heading>

                    @if ($sale->customer)
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$sale->customer->name" size="lg" />
                                <div>
                                    <div class="font-medium">{{ $sale->customer->name }}</div>
                                    @if ($sale->customer->email)
                                        <div class="text-sm text-zinc-500">{{ $sale->customer->email }}</div>
                                    @endif
                                    @if ($sale->customer->phone)
                                        <div class="text-sm text-zinc-500">{{ $sale->customer->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <flux:text>Público general</flux:text>
                    @endif
                </div>
            </flux:card>

            <!-- Branch Information -->
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Sucursal</flux:heading>

                    @if ($sale->branch)
                        <div class="space-y-2">
                            <div>
                                <flux:subheading>{{ $sale->branch->name }}</flux:subheading>
                                @if ($sale->branch->address)
                                    <flux:text class="text-sm">{{ $sale->branch->address }}</flux:text>
                                @endif
                            </div>
                        </div>
                    @else
                        <flux:text>Sin sucursal</flux:text>
                    @endif
                </div>
            </flux:card>

            <!-- User Information -->
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Colaborador</flux:heading>

                    @if ($sale->user)
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$sale->user->name" />
                            <div>
                                <div class="font-medium text-sm">{{ $sale->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $sale->user->email }}</div>
                            </div>
                        </div>
                    @else
                        <flux:text>Sin colaborador</flux:text>
                    @endif
                </div>
            </flux:card>

            <!-- Totals -->
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Resumen</flux:heading>

                    <div class="space-y-2 border-b border-zinc-200 pb-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                            <span>${{ number_format($sale->subtotal, 2) }}</span>
                        </div>

                        @if ($sale->discount_total > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Descuento</span>
                                <span class="text-rose-600 dark:text-rose-400">-${{ number_format($sale->discount_total, 2) }}</span>
                            </div>
                        @endif

                        @if ($sale->reward_redeemed_total > 0)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">Recompensas canjeadas</span>
                                <span class="text-amber-600 dark:text-amber-400">-${{ number_format($sale->reward_redeemed_total, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="font-semibold">Total</span>
                        <span class="text-lg font-bold">${{ number_format($sale->total, 2) }}</span>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
