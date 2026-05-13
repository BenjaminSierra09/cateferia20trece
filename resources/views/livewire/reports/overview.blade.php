<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <flux:select size="sm" class="w-40">
                <option>Últimos 7 días</option>
                <option selected>Últimos 30 días</option>
                <option>Últimos 90 días</option>
            </flux:select>

            <flux:subheading class="max-md:hidden whitespace-nowrap">comparado con</flux:subheading>

            <flux:select size="sm" class="hidden w-56 md:block">
                <option selected>Periodo anterior</option>
                <option>Mismo periodo del año pasado</option>
                <option>Último trimestre</option>
            </flux:select>

            <flux:separator vertical class="hidden mx-1 my-2 lg:block" />

            <div class="hidden items-center gap-2 lg:flex">
                <flux:subheading class="whitespace-nowrap">Filtros:</flux:subheading>
                <flux:badge rounded color="zinc" icon="calendar-days" size="lg">Fechas</flux:badge>
                <flux:badge rounded color="zinc" icon="building-storefront" size="lg">Sucursal</flux:badge>
                <flux:badge rounded color="zinc" icon="credit-card" size="lg">Pago</flux:badge>
            </div>
        </div>

        <flux:tabs variant="segmented" class="w-auto! ml-2" size="sm">
            <flux:tab wire:click="$set('presentationMode', 'visual')" icon="chart-bar-square" icon:variant="outline" :data-current="$presentationMode === 'visual'" />
            <flux:tab wire:click="$set('presentationMode', 'detail')" icon="list-bullet" icon:variant="outline" :data-current="$presentationMode === 'detail'" />
        </flux:tabs>
    </div>

    <flux:card class="grid gap-4 xl:grid-cols-4">
        <flux:select wire:model.live="branch_id" label="Sucursal">
            <option value="">Todas</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </flux:select>

        <flux:radio.group wire:model.live="payment_method" label="Método de pago">
            <flux:radio value="" label="Todos" />
            @foreach ($paymentMethods as $method)
                <flux:radio value="{{ $method->value }}" label="{{ $method->label() }}" />
            @endforeach
        </flux:radio.group>

        <flux:date-picker wire:model.live="date_from" label="Desde" with-today />
        <flux:date-picker wire:model.live="date_to" label="Hasta" with-today />
    </flux:card>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <flux:card class="relative">
            <flux:subheading><span class="inline-flex items-center gap-2"><flux:icon.shopping-bag class="size-4" /> Ventas</span></flux:subheading>
            <flux:heading size="xl" class="mb-2">{{ $overview['sales_count'] }}</flux:heading>
            <div class="flex items-center gap-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                <flux:icon.arrow-trending-up variant="micro" />
                Actividad del periodo
            </div>
        </flux:card>

        <flux:card class="relative">
            <flux:subheading><span class="inline-flex items-center gap-2"><flux:icon.banknotes class="size-4" /> Ingresos</span></flux:subheading>
            <flux:heading size="xl" class="mb-2">${{ number_format($overview['gross_revenue'], 2) }}</flux:heading>
            <div class="flex items-center gap-1 text-sm font-medium text-sky-600 dark:text-sky-400">
                <flux:icon.chart-bar-square variant="micro" />
                Total facturado
            </div>
        </flux:card>

        <flux:card class="relative">
            <flux:subheading><span class="inline-flex items-center gap-2"><flux:icon.scale class="size-4" /> Ticket promedio</span></flux:subheading>
            <flux:heading size="xl" class="mb-2">${{ number_format($overview['ticket_average'], 2) }}</flux:heading>
            <div class="flex items-center gap-1 text-sm font-medium text-violet-600 dark:text-violet-400">
                <flux:icon.swatch variant="micro" />
                Valor por venta
            </div>
        </flux:card>

        <flux:card class="relative">
            <flux:subheading><span class="inline-flex items-center gap-2"><flux:icon.tag class="size-4" /> Descuentos</span></flux:subheading>
            <flux:heading size="xl" class="mb-2">${{ number_format($overview['discount_total'], 2) }}</flux:heading>
            <div class="flex items-center gap-1 text-sm font-medium text-amber-600 dark:text-amber-400">
                <flux:icon.receipt-percent variant="micro" />
                Ajustes del periodo
            </div>
        </flux:card>
    </div>

    @if ($presentationMode === 'visual')
    <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <flux:card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Ingresos por día</flux:heading>
                    <flux:text>Tendencia del periodo filtrado.</flux:text>
                </div>

                <flux:badge color="orange" icon="calendar-days" inset="top bottom">
                    {{ $this->activeDateRangeLabel() }}
                </flux:badge>
            </div>

            <flux:chart wire:model="salesTimelineChart" class="aspect-[3/1]">
                <flux:chart.svg>
                    <flux:chart.bar field="ingresos" class="text-orange-400 dark:text-orange-300" radius="0" width="80%" />
                    <flux:chart.axis axis="x" field="date">
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y" tick-prefix="$">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.cursor type="area" />
                </flux:chart.svg>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" />
                    <flux:chart.tooltip.value field="ingresos" label="Ingresos" prefix="$" :format="['useGrouping' => true]" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Bebidas con mayor ingreso</flux:heading>
                    <flux:text>Top del periodo actual.</flux:text>
                </div>

                <flux:badge color="sky" icon="sparkles" inset="top bottom">
                    {{ count($overview['top_beverages']) }} destacadas
                </flux:badge>
            </div>

            @php
                $maxTopRevenue = max(array_column($overview['top_beverages'], 'revenue') ?: [1]);
            @endphp

            <div class="space-y-3">
                @foreach ($overview['top_beverages'] as $item)
                    @php
                        $width = $maxTopRevenue > 0 ? max(($item['revenue'] / $maxTopRevenue) * 100, 8) : 8;
                    @endphp

                    <div wire:key="visual-top-beverage-{{ md5($item['item_name']) }}" class="space-y-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $item['item_name'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $item['quantity'] }} ventas
                                </div>
                            </div>

                            <div class="shrink-0 text-sm font-semibold text-sky-600 dark:text-sky-300">
                                ${{ number_format($item['revenue'], 2) }}
                            </div>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                            <div class="h-full rounded-full bg-sky-400 dark:bg-sky-300" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <flux:card class="space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Bebidas destacadas</flux:heading>
                    <flux:text>Resumen rápido de productos con mejor tracción.</flux:text>
                </div>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Bebida</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Ingresos</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($overview['top_beverages'] as $item)
                        <flux:table.row wire:key="report-beverage-{{ md5($item['item_name']) }}">
                            <flux:table.cell>{{ $item['item_name'] }}</flux:table.cell>
                            <flux:table.cell>{{ $item['quantity'] }}</flux:table.cell>
                            <flux:table.cell variant="strong">${{ number_format($item['revenue'], 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="space-y-3">
            <div>
                <flux:heading>Por sucursal</flux:heading>
                <flux:text>Comparativo entre tiendas.</flux:text>
            </div>

            <flux:chart wire:model="branchChart" class="h-64">
                <flux:chart.svg>
                    <flux:chart.bar field="total" class="text-emerald-400 dark:text-emerald-300" radius="0" width="80%" />
                    <flux:chart.axis axis="x" field="branch_corta">
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y" tick-prefix="$">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                </flux:chart.svg>
            </flux:chart>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($overview['sales_by_branch'] as $item)
                        <flux:table.row wire:key="report-branch-{{ md5($item['branch']) }}">
                            <flux:table.cell>{{ $item['branch'] }}</flux:table.cell>
                            <flux:table.cell>{{ $item['count'] }}</flux:table.cell>
                            <flux:table.cell variant="strong">${{ number_format($item['total'], 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="space-y-3">
            <div>
                <flux:heading>Por método de pago</flux:heading>
                <flux:text>Comportamiento del cobro en el periodo.</flux:text>
            </div>

            @php
                $maxPaymentTotal = max(array_column($overview['sales_by_payment_method'], 'total') ?: [1]);
            @endphp

            <div class="space-y-3">
                @foreach ($overview['sales_by_payment_method'] as $item)
                    @php
                        $width = $maxPaymentTotal > 0 ? max(($item['total'] / $maxPaymentTotal) * 100, 8) : 8;
                    @endphp

                    <div wire:key="visual-payment-method-{{ md5($item['payment_method']) }}" class="space-y-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $item['payment_method'] }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $item['count'] }} ventas
                                </div>
                            </div>

                            <div class="shrink-0 text-sm font-semibold text-violet-600 dark:text-violet-300">
                                ${{ number_format($item['total'], 2) }}
                            </div>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                            <div class="h-full rounded-full bg-violet-400 dark:bg-violet-300" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Método</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($overview['sales_by_payment_method'] as $item)
                        <flux:table.row wire:key="report-payment-{{ md5($item['payment_method']) }}">
                            <flux:table.cell>{{ $item['payment_method'] }}</flux:table.cell>
                            <flux:table.cell>{{ $item['count'] }}</flux:table.cell>
                            <flux:table.cell variant="strong">${{ number_format($item['total'], 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <flux:card class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
            <flux:heading>Turnos de empleados</flux:heading>
            <flux:text>Consulta turnos activos, historial por sucursal y cierra sesiones abiertas desde una vista dedicada.</flux:text>
        </div>

        <flux:button :href="route('dashboard.reports.shifts')" icon="clock" wire:navigate>
            Ver reporte de turnos
        </flux:button>
    </flux:card>
</div>
