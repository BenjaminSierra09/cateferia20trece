<div class="space-y-6">
    <!-- Filters -->
    <div class="space-y-4">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text>Análisis integral de todas las sucursales y métricas clave</flux:text>
        </div>

        @if ($this->overview['limited_by_permissions'] ?? false)
            <flux:callout icon="shield-check" color="amber">
                {{ $this->overview['permission_notice'] }}
            </flux:callout>
        @endif

        {{-- Resumen de hoy --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:card>
                <flux:subheading>Ingreso de hoy</flux:subheading>
                <flux:heading size="xl" class="mt-2">${{ number_format($this->todayIncome['income'], 2) }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">{{ $this->todayIncome['sales'] }} ventas {{ $selectedBranch ? 'en la sucursal' : 'en todas las sucursales' }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:subheading>Turnos de hoy</flux:subheading>
                <flux:heading size="xl" class="mt-2">{{ count($this->todayShifts) }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">{{ collect($this->todayShifts)->where('is_open', true)->count() }} abiertos</flux:text>
            </flux:card>

            <flux:card>
                <flux:subheading>Inventario en alerta</flux:subheading>
                <flux:heading size="xl" class="mt-2">{{ $this->lowStock->count() }}</flux:heading>
                <flux:text size="sm" class="mt-1">
                    <flux:link :href="route('dashboard.inventory.index')" wire:navigate>Ver inventario</flux:link>
                </flux:text>
            </flux:card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Ventas por turno (hoy)</flux:heading>
                    <flux:button :href="route('dashboard.reports.shifts')" variant="ghost" size="xs" icon="arrow-up-right" wire:navigate>Reporte</flux:button>
                </div>
                @forelse ($this->todayShifts as $shift)
                    <div wire:key="shift-{{ $shift['id'] }}" class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                        <div class="min-w-0">
                            <flux:text class="font-medium">{{ $shift['user'] }}</flux:text>
                            <flux:subheading>
                                {{ $shift['branch'] }} · {{ $shift['sales'] }} ventas
                                @if ($shift['is_open'])
                                    <flux:badge size="sm" color="emerald" inset="top bottom">Abierto</flux:badge>
                                @endif
                            </flux:subheading>
                        </div>
                        <flux:heading size="sm">${{ number_format($shift['total'], 2) }}</flux:heading>
                    </div>
                @empty
                    <flux:text>Aún no hay turnos registrados hoy.</flux:text>
                @endforelse
            </flux:card>

            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Inventario bajo</flux:heading>
                    <flux:button :href="route('dashboard.inventory.index')" variant="ghost" size="xs" icon="arrow-up-right" wire:navigate>Inventario</flux:button>
                </div>
                @forelse ($this->lowStock as $stock)
                    <div wire:key="low-{{ $stock->id }}" class="flex items-center justify-between gap-4 border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                        <div class="min-w-0">
                            <flux:text class="font-medium">{{ $stock->item?->name }}</flux:text>
                            <flux:subheading>{{ $stock->branch?->name }}</flux:subheading>
                        </div>
                        <div class="text-right">
                            <flux:text class="font-semibold {{ (float) $stock->quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ (float) $stock->quantity }} {{ $stock->item?->unit->abbreviation() }}
                            </flux:text>
                            <flux:subheading>mín {{ (float) $stock->min_quantity }}</flux:subheading>
                        </div>
                    </div>
                @empty
                    <flux:text>Todo el inventario está por encima del mínimo.</flux:text>
                @endforelse
            </flux:card>
        </div>

        <div class="space-y-3">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>Sucursal</flux:label>
                    <flux:select wire:model.live="selectedBranch" placeholder="Todas las sucursales">
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <div class="space-y-1">
                    <flux:label>Rango de Fechas</flux:label>
                    <div class="flex gap-2">
                        <flux:date-picker wire:model.live="dateFrom" placeholder="Desde" />
                        <flux:date-picker wire:model.live="dateTo" placeholder="Hasta" />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="resetFilters" variant="subtle" size="sm" icon="x-mark">Limpiar</flux:button>
                <flux:button wire:click="setLast7Days" variant="subtle" size="sm" icon="calendar">Últimos 7 días</flux:button>
                <flux:button wire:click="setCurrentMonth" variant="subtle" size="sm" icon="calendar">Este mes</flux:button>
                <flux:button wire:click="setLast30Days" variant="subtle" size="sm" icon="calendar">Últimos 30 días</flux:button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    @island(name: 'dashboard-metrics', defer: true, always: true)
        @placeholder
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach (range(1, 4) as $metricPlaceholder)
                    <flux:card wire:key="metric-placeholder-{{ $metricPlaceholder }}" class="space-y-3">
                        <div class="h-4 w-24 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-8 w-28 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-3 w-32 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    </flux:card>
                @endforeach
            </div>
        @endplaceholder

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:subheading>Ventas</flux:subheading>
                <flux:heading size="xl" class="mt-2">{{ $this->overview['sales_count'] }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">transacciones completadas</flux:text>
            </flux:card>

            <flux:card>
                <flux:subheading>Ingresos</flux:subheading>
                <flux:heading size="xl" class="mt-2">${{ number_format($this->overview['gross_revenue'], 2) }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">ingresos brutos</flux:text>
            </flux:card>

            <flux:card>
                <flux:subheading>Ticket Promedio</flux:subheading>
                <flux:heading size="xl" class="mt-2">${{ number_format($this->overview['ticket_average'], 2) }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">por transacción</flux:text>
            </flux:card>

            <flux:card>
                <flux:subheading>Saldo Recompensa</flux:subheading>
                <flux:heading size="xl" class="mt-2">${{ number_format($this->overview['reward_redeemed_total'], 2) }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-zinc-500">recompensas canjeadas</flux:text>
            </flux:card>
        </div>
    @endisland

    <!-- Sales Timeline - Line Chart -->
    @island(name: 'dashboard-timeline', defer: true, always: true)
        @placeholder
            <flux:card class="space-y-4">
                <div class="space-y-2">
                    <div class="h-5 w-48 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    <div class="h-4 w-72 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                </div>
                <div class="h-72 animate-pulse rounded-2xl bg-zinc-100 dark:bg-zinc-800"></div>
            </flux:card>
        @endplaceholder

        <flux:card class="space-y-4">
            <div>
                <flux:heading>Tendencia de Ventas</flux:heading>
                <flux:text>Ingresos diarios durante el período seleccionado</flux:text>
            </div>

            @if (count($this->salesTimelineChartData) > 0)
                <flux:chart :value="$this->salesTimelineChartData" class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.line field="ingresos" class="text-blue-500 dark:text-blue-400" />
                        <flux:chart.area field="ingresos" class="text-blue-100/50 dark:text-blue-400/30" />
                        <flux:chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD', 'minimumFractionDigits' => 0]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor />
                    </flux:chart.svg>
                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" :format="['month' => 'short', 'day' => 'numeric', 'year' => 'numeric']" />
                        <flux:chart.tooltip.value field="ingresos" label="Ingresos" :format="['style' => 'currency', 'currency' => 'USD']" />
                        <flux:chart.tooltip.value field="ventas" label="Transacciones" />
                    </flux:chart.tooltip>
                </flux:chart>
            @else
                <flux:callout icon="information-circle" color="sky">
                    No hay datos disponibles para el rango de fechas seleccionado.
                </flux:callout>
            @endif
        </flux:card>
    @endisland

    <!-- Sales by Branch & Payment Methods -->
    @island(name: 'dashboard-breakdowns', defer: true, always: true)
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Bar Chart - Sales by Branch -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Ventas por Sucursal</flux:heading>
                <flux:text>Comparativa de ingresos por ubicación</flux:text>
            </div>

            @if (count($this->branchSalesChartData) > 0)
                <flux:chart :value="$this->branchSalesChartData" class="aspect-[3/1]">
                    <flux:chart.svg>
                        <flux:chart.bar field="total" class="text-amber-500" width="70%" />
                        <flux:chart.axis axis="x" field="branch">
                            <flux:chart.axis.tick />
                            <flux:chart.axis.line />
                        </flux:chart.axis>
                        <flux:chart.axis axis="y" :format="['style' => 'currency', 'currency' => 'USD', 'minimumFractionDigits' => 0]">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>
                        <flux:chart.cursor type="area" />
                    </flux:chart.svg>
                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="branch" />
                        <flux:chart.tooltip.value field="total" label="Ingresos" :format="['style' => 'currency', 'currency' => 'USD']" />
                        <flux:chart.tooltip.value field="count" label="Transacciones" />
                    </flux:chart.tooltip>
                </flux:chart>
            @else
                <flux:callout icon="information-circle" color="sky">
                    No hay datos disponibles.
                </flux:callout>
            @endif
        </flux:card>

        <!-- Stacked Bar Chart - Payment Methods -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Métodos de Pago</flux:heading>
                <flux:text>Desglose por tipo de pago</flux:text>
            </div>

            @if (count($this->paymentMethodChartData) > 0)
                <div class="space-y-3">
                    @php
                        $totalPayments = array_sum(array_column($this->paymentMethodChartData, 'count'));
                    @endphp
                    @foreach ($this->paymentMethodChartData as $payment)
                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <flux:text size="sm" class="font-medium">{{ $payment['method'] }}</flux:text>
                                <flux:badge color="emerald">
                                    {{ round(($payment['count'] / $totalPayments) * 100) }}%
                                </flux:badge>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                                <div
                                    class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-full"
                                    style="width: {{ ($payment['count'] / $totalPayments) * 100 }}%"
                                ></div>
                            </div>
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $payment['count'] }} transacciones · ${{ number_format($payment['total'], 2) }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:callout icon="information-circle" color="sky">
                    Sin métodos de pago registrados.
                </flux:callout>
            @endif
        </flux:card>
    </div>
    @endisland

    <!-- Top Beverages with Sparklines -->
    @island(name: 'dashboard-top-beverages', lazy: true, always: true)
        @placeholder
            <flux:card class="space-y-4">
                <div class="space-y-2">
                    <div class="h-5 w-56 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    <div class="h-4 w-64 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                </div>
                <div class="h-56 animate-pulse rounded-2xl bg-zinc-100 dark:bg-zinc-800"></div>
            </flux:card>
        @endplaceholder

        <flux:card class="space-y-4">
            <div>
                <flux:heading>Bebidas Más Vendidas</flux:heading>
                <flux:text>Top 5 productos con gráficos de tendencia</flux:text>
            </div>

            @if (count($this->overview['top_beverages']) > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Bebida</flux:table.column>
                        <flux:table.column>Cantidad</flux:table.column>
                        <flux:table.column>Ingresos</flux:table.column>
                        <flux:table.column>Tendencia</flux:table.column>
                        <flux:table.column>% del Total</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @php
                            $totalBeverageRevenue = array_sum(array_column($this->overview['top_beverages'], 'revenue'));
                        @endphp
                        @foreach ($this->topBeveragesSparklineData as $beverage)
                            <flux:table.row>
                                <flux:table.cell>{{ $beverage['name'] }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge color="blue">{{ $beverage['quantity'] }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>${{ number_format($beverage['revenue'], 2) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:chart :value="$beverage['sparklineData']" class="w-16 aspect-[3/1]">
                                        <flux:chart.svg gutter="0">
                                            <flux:chart.line class="text-green-500 dark:text-green-400" />
                                        </flux:chart.svg>
                                    </flux:chart>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ round(($beverage['revenue'] / $totalBeverageRevenue) * 100, 1) }}%
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout icon="information-circle" color="sky">
                    No hay bebidas vendidas en el período seleccionado.
                </flux:callout>
            @endif
        </flux:card>
    @endisland

    <!-- Recent Sales & Customers -->
    @island(name: 'dashboard-recents', lazy: true, always: true)
    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Ventas Recientes</flux:heading>
                <flux:text>Últimas transacciones</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Cliente</flux:table.column>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->recentSales as $sale)
                        <flux:table.row wire:key="dashboard-sale-row-{{ $sale->id }}">
                            <flux:table.cell>{{ $sale->customer?->name ?? 'Público general' }}</flux:table.cell>
                            <flux:table.cell>{{ $sale->branch?->name ?: 'Sin sucursal' }}</flux:table.cell>
                            <flux:table.cell class="text-xs">{{ $sale->sold_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="font-semibold">${{ number_format($sale->total, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <flux:callout icon="information-circle" color="sky">No hay ventas registradas.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading>Clientes Recientes</flux:heading>
                <flux:text>Personas añadidas al programa</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Cliente</flux:table.column>
                    <flux:table.column>Nivel</flux:table.column>
                    <flux:table.column>Bebidas</flux:table.column>
                    <flux:table.column>Saldo</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->recentCustomers as $customer)
                        <flux:table.row wire:key="dashboard-customer-row-{{ $customer->id }}">
                            <flux:table.cell>{{ $customer->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge>{{ $customer->reward_tier->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $customer->annual_drink_count }}</flux:table.cell>
                            <flux:table.cell class="font-semibold">${{ number_format($customer->availableRewardBalance(), 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <flux:callout icon="information-circle" color="sky">
                                    No hay clientes registrados.
                                </flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
    @endisland
</div>
