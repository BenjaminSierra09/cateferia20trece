<div class="space-y-6">
    <!-- Filters -->
    <div class="space-y-4">
        <div>
            <flux:heading>Dashboard</flux:heading>
            <flux:text>Análisis integral de todas las sucursales y métricas clave</flux:text>
        </div>

        <div class="space-y-3">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>Sucursal</flux:label>
                    <flux:select wire:model.live="selectedBranch" placeholder="Todas las sucursales">
                        @foreach ($branches as $branch)
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
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:subheading>Ventas</flux:subheading>
            <flux:heading size="xl" class="mt-2">{{ $overview['sales_count'] }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">transacciones completadas</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Ingresos</flux:subheading>
            <flux:heading size="xl" class="mt-2">${{ number_format($overview['gross_revenue'], 2) }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">ingresos brutos</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Ticket Promedio</flux:subheading>
            <flux:heading size="xl" class="mt-2">${{ number_format($overview['ticket_average'], 2) }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">por transacción</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>Saldo Recompensa</flux:subheading>
            <flux:heading size="xl" class="mt-2">${{ number_format($overview['reward_redeemed_total'], 2) }}</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">recompensas canjeadas</flux:text>
        </flux:card>
    </div>

    <!-- Sales Timeline - Line Chart -->
    <flux:card class="space-y-4">
        <div>
            <flux:heading>Tendencia de Ventas</flux:heading>
            <flux:text>Ingresos diarios durante el período seleccionado</flux:text>
        </div>

        @if (count($salesTimelineChartData) > 0)
            <flux:chart :value="$salesTimelineChartData" class="aspect-[3/1]">
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

    <!-- Sales by Branch & Payment Methods -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Bar Chart - Sales by Branch -->
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Ventas por Sucursal</flux:heading>
                <flux:text>Comparativa de ingresos por ubicación</flux:text>
            </div>

            @if (count($branchSalesChartData) > 0)
                <flux:chart :value="$branchSalesChartData" class="aspect-[3/1]">
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

            @if (count($paymentMethodChartData) > 0)
                <div class="space-y-3">
                    @php
                        $totalPayments = array_sum(array_column($paymentMethodChartData, 'count'));
                    @endphp
                    @foreach ($paymentMethodChartData as $payment)
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

    <!-- Top Beverages with Sparklines -->
    <flux:card class="space-y-4">
        <div>
            <flux:heading>Bebidas Más Vendidas</flux:heading>
            <flux:text>Top 5 productos con gráficos de tendencia</flux:text>
        </div>

        @if (count($overview['top_beverages']) > 0)
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
                        $totalBeverageRevenue = array_sum(array_column($overview['top_beverages'], 'revenue'));
                    @endphp
                    @foreach ($topBeveragesSparklineData as $beverage)
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

    <!-- Recent Sales & Customers -->
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
                    @forelse ($recentSales as $sale)
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
                    @forelse ($recentCustomers as $customer)
                        <flux:table.row wire:key="dashboard-customer-row-{{ $customer->id }}">
                            <flux:table.cell>{{ $customer->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge>{{ $customer->reward_tier->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $customer->annual_drink_count }}</flux:table.cell>
                            <flux:table.cell class="font-semibold">${{ number_format($customer->reward_balance, 2) }}</flux:table.cell>
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
</div>
