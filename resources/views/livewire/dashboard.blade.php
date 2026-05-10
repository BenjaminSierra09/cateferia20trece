<div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-4">
            <flux:card>
                <flux:subheading>Ventas</flux:subheading>
                <flux:heading size="xl">{{ $overview['sales_count'] }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:subheading>Ingresos</flux:subheading>
                <flux:heading size="xl">${{ number_format($overview['gross_revenue'], 2) }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:subheading>Ticket promedio</flux:subheading>
                <flux:heading size="xl">${{ number_format($overview['ticket_average'], 2) }}</flux:heading>
            </flux:card>
            <flux:card>
                <flux:subheading>Saldo usado</flux:subheading>
                <flux:heading size="xl">${{ number_format($overview['reward_redeemed_total'], 2) }}</flux:heading>
            </flux:card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading>Ventas recientes</flux:heading>
                    <flux:text>Últimos movimientos del día.</flux:text>
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
                            <flux:table.cell>{{ $sale->sold_at?->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>${{ number_format($sale->total, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay ventas registradas.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading>Clientes recientes</flux:heading>
                    <flux:text>Personas añadidas recientemente al programa.</flux:text>
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
                            <flux:table.cell>{{ $customer->reward_tier->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->annual_drink_count }}</flux:table.cell>
                            <flux:table.cell>${{ number_format($customer->reward_balance, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <flux:callout icon="information-circle" color="sky">Todavía no hay clientes registrados.</flux:callout>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        </div>
    </div>
