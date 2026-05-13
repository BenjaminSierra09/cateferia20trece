<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Turnos de empleados</flux:heading>
            <flux:text>Revisa sesiones activas, historial por sucursal y cierra turnos abiertos desde el dashboard.</flux:text>
        </div>

        <flux:button :href="route('dashboard.reports.index')" variant="ghost" icon="chart-bar-square" wire:navigate>
            Volver a reportes
        </flux:button>
    </div>

    <flux:card class="grid gap-4 xl:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" label="Buscar" placeholder="Empleado o sucursal" />

        <flux:select wire:model.live="branch_id" label="Sucursal">
            <option value="">Todas</option>
            @foreach ($this->branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" label="Estatus">
            <option value="">Todos</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
            @endforeach
        </flux:select>

        <flux:date-picker wire:model.live="date_from" label="Desde" with-today />
        <flux:date-picker wire:model.live="date_to" label="Hasta" with-today />
    </flux:card>

    @island(name: 'shift-summary', defer: true, always: true)
        @placeholder
            <div class="grid gap-4 md:grid-cols-3">
                @foreach (range(1, 3) as $summaryPlaceholder)
                    <flux:card wire:key="shift-summary-placeholder-{{ $summaryPlaceholder }}" class="space-y-3">
                        <div class="h-4 w-28 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        <div class="h-8 w-20 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                    </flux:card>
                @endforeach
            </div>
        @endplaceholder

        <div class="grid gap-4 md:grid-cols-3">
            <flux:card>
                <flux:subheading>Turnos activos</flux:subheading>
                <flux:heading size="xl">{{ $this->activeSessions->count() }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:subheading>Registros visibles</flux:subheading>
                <flux:heading size="xl">{{ $this->sessions->total() }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:subheading>Ventas en activos</flux:subheading>
                <flux:heading size="xl">{{ $this->activeSessions->sum('sales_count') }}</flux:heading>
            </flux:card>
        </div>
    @endisland

    @island(name: 'active-shifts', defer: true, always: true)
        @placeholder
            <flux:card class="space-y-4">
                <div class="h-5 w-40 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach (range(1, 2) as $activePlaceholder)
                        <div wire:key="active-placeholder-{{ $activePlaceholder }}" class="h-44 animate-pulse rounded-2xl bg-zinc-100 dark:bg-zinc-800"></div>
                    @endforeach
                </div>
            </flux:card>
        @endplaceholder

        <flux:card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Turnos activos</flux:heading>
                    <flux:text>Los administradores pueden cerrar aquí sesiones que sigan abiertas.</flux:text>
                </div>

                <flux:badge color="amber" icon="clock" inset="top bottom">
                    {{ $this->activeSessions->count() }} abiertos
                </flux:badge>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($this->activeSessions as $session)
                    <flux:card wire:key="active-session-{{ $session->id }}" class="space-y-4">
                        <div class="space-y-1">
                            <flux:heading size="lg">{{ $session->user?->name ?? 'Sin empleado' }}</flux:heading>
                            <flux:text>{{ $session->branch?->name ?? 'Sin sucursal' }} · {{ $session->work_date?->format('d/m/Y') ?? 'Sin fecha' }}</flux:text>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <flux:subheading>Inició</flux:subheading>
                                <flux:text>{{ $session->clock_in_at?->format('H:i') ?? 'Sin apertura' }}</flux:text>
                            </div>
                            <div>
                                <flux:subheading>Ventas</flux:subheading>
                                <flux:text>{{ $session->sales_count }}</flux:text>
                            </div>
                            <div>
                                <flux:subheading>Estatus</flux:subheading>
                                <flux:badge color="emerald" inset="top bottom">{{ $session->status?->label() ?? 'Sin estatus' }}</flux:badge>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <flux:button wire:click="closeShift({{ $session->id }})" variant="danger" icon="stop-circle">
                                Cerrar turno
                            </flux:button>
                        </div>
                    </flux:card>
                @empty
                    <flux:callout icon="check-circle" color="emerald">
                        No hay turnos activos con los filtros actuales.
                    </flux:callout>
                @endforelse
            </div>
        </flux:card>
    @endisland

    @island(name: 'shift-history', always: true)
        <flux:card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Historial de turnos</flux:heading>
                    <flux:text>Consulta aperturas, cierres, sucursal y ventas por sesión.</flux:text>
                </div>

                <flux:badge color="zinc" icon="archive-box" inset="top bottom">
                    {{ $this->sessions->total() }} registros
                </flux:badge>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Empleado</flux:table.column>
                    <flux:table.column>Sucursal</flux:table.column>
                    <flux:table.column>Fecha</flux:table.column>
                    <flux:table.column>Inició</flux:table.column>
                    <flux:table.column>Cerró</flux:table.column>
                    <flux:table.column>Ventas</flux:table.column>
                    <flux:table.column>Estatus</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->sessions as $session)
                        <flux:table.row wire:key="session-history-{{ $session->id }}">
                            <flux:table.cell>{{ $session->user?->name ?? 'Sin empleado' }}</flux:table.cell>
                            <flux:table.cell>{{ $session->branch?->name ?? 'Sin sucursal' }}</flux:table.cell>
                            <flux:table.cell>{{ $session->work_date?->format('d/m/Y') ?? 'Sin fecha' }}</flux:table.cell>
                            <flux:table.cell>{{ $session->clock_in_at?->format('H:i') ?? 'Sin apertura' }}</flux:table.cell>
                            <flux:table.cell>{{ $session->clock_out_at?->format('H:i') ?? 'Abierto' }}</flux:table.cell>
                            <flux:table.cell>{{ $session->sales_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $session->status === \App\Enums\WorkSessionStatus::Open ? 'amber' : 'emerald' }}" inset="top bottom">
                                    {{ $session->status?->label() ?? 'Sin estatus' }}
                                </flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                No hay turnos registrados para los filtros actuales.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <flux:pagination :paginator="$this->sessions" />
        </flux:card>
    @endisland
</div>
