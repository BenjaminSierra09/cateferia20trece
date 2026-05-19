<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
            <flux:select wire:model.live="perPage" size="sm" class="w-full md:w-32">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </flux:select>

        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
            <flux:tabs variant="segmented" class="w-full sm:w-auto!" size="sm">
                <flux:tab
                    wire:click="$set('viewMode', 'list')"
                    icon="list-bullet"
                    icon:variant="outline"
                    :data-current="$viewMode === 'list'"
                />

                <flux:tab
                    wire:click="$set('viewMode', 'grid')"
                    icon="squares-2x2"
                    icon:variant="outline"
                    :data-current="$viewMode === 'grid'"
                />
            </flux:tabs>

            <flux:button
                :href="route('dashboard.customers.create')"
                variant="primary"
                icon="plus"
                wire:navigate
                class="w-full sm:w-auto"
            >
                Nuevo cliente
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-5 overflow-hidden">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <flux:heading size="xl">Clientes</flux:heading>
                <flux:text class="mt-2">
                    Consulta historial, saldo, QR y el perfil de consumo de cada cliente.
                </flux:text>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-60 lg:text-right">
                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Total visibles</flux:subheading>
                    <flux:heading size="lg">{{ $customers->total() }}</flux:heading>
                </div>

                <div class="rounded-2xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800/80">
                    <flux:subheading>Página actual</flux:subheading>
                    <flux:heading size="lg">{{ $customers->count() }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="w-full min-w-0 sm:min-w-72 sm:flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar por nombre, teléfono o correo"
                />
            </div>

            <flux:badge color="lime" icon="heart" inset="top bottom">
                {{ $customers->getCollection()->where('is_active', true)->count() }} activos
            </flux:badge>

            <flux:badge color="amber" icon="calendar-days" inset="top bottom">
                {{ $customers->getCollection()->whereNotNull('birthday')->count() }} con cumpleaños
            </flux:badge>

            @if ($selectedCustomerIds !== [])
                <flux:badge color="violet" icon="check-circle" inset="top bottom">
                    {{ count($selectedCustomerIds) }} seleccionados
                </flux:badge>

                <div class="grid gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="clearSelection"
                        class="w-full sm:w-auto"
                    >
                        Limpiar selección
                    </flux:button>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        wire:click="reactivateSelected"
                        class="w-full sm:w-auto"
                    >
                        Reactivar seleccionados
                    </flux:button>

                    <flux:button
                        size="sm"
                        variant="danger"
                        wire:click="deactivateSelected"
                        class="w-full sm:w-auto"
                    >
                        Desactivar seleccionados
                    </flux:button>
                </div>
            @endif
        </div>

        @if ($viewMode === 'list')
            <div class="overflow-x-auto">
                <flux:table class="min-w-[1100px]">
                    <flux:table.columns>
                        <flux:table.column class="w-12">
                            <flux:checkbox :checked="$selectPage" wire:click="togglePageSelection" />
                        </flux:table.column>

                        <flux:table.column>Cliente</flux:table.column>
                        <flux:table.column class="max-lg:hidden">Tonalpohualli</flux:table.column>
                        <flux:table.column class="max-md:hidden">Nivel</flux:table.column>
                        <flux:table.column>Saldo</flux:table.column>
                        <flux:table.column>Adeudo</flux:table.column>
                        <flux:table.column class="max-md:hidden">QR</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($customers as $customer)
                            <flux:table.row wire:key="customer-row-{{ $customer->id }}">
                                <flux:table.cell class="pr-2">
                                    <flux:checkbox wire:model.live="selectedCustomerIds" value="{{ $customer->id }}" />
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <flux:avatar
                                            size="sm"
                                            :name="$customer->name"
                                            :initials="\Illuminate\Support\Str::of($customer->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('')"
                                        />

                                        <div class="min-w-0">
                                            <div class="truncate font-medium">
                                                {{ $customer->name }}
                                            </div>

                                            <div class="truncate text-sm text-zinc-500">
                                                {{ $customer->phone ?: 'Sin teléfono registrado' }}
                                            </div>

                                            <div class="truncate text-xs text-zinc-400">
                                                {{ $customer->email ?: 'Sin correo electrónico' }}
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="max-lg:hidden">
                                    @if ($customer->birthday && filled($tonalliByCustomerId[$customer->id]['tonalli'] ?? null))
                                        <div class="max-w-56 space-y-1">
                                            <div class="truncate font-medium">
                                                @if(! empty($tonalliByCustomerId[$customer->id]['icon']))
                                                    <img
                                                        src="{{ $tonalliByCustomerId[$customer->id]['icon'] }}"
                                                        alt=""
                                                        class="mr-1 inline-block h-4 w-4 align-text-bottom"
                                                    />
                                                @endif

                                                {{ $tonalliByCustomerId[$customer->id]['tonalli'] }}
                                            </div>

                                            <div class="truncate text-sm text-zinc-500">
                                                {{ $tonalliByCustomerId[$customer->id]['espanol'] }}
                                            </div>

                                            <div class="truncate text-xs text-zinc-400">
                                                @if(! empty($tonalliByCustomerId[$customer->id]['trecena_icon']))
                                                    <img
                                                        src="{{ $tonalliByCustomerId[$customer->id]['trecena_icon'] }}"
                                                        alt=""
                                                        class="mr-1 inline-block h-3 w-3 align-text-bottom"
                                                    />
                                                @endif

                                                Trecena líder: {{ $tonalliByCustomerId[$customer->id]['trecena'] }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-zinc-500">
                                            Sin cumpleaños
                                        </span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="max-md:hidden">
                                    <div class="space-y-1">
                                        <flux:badge
                                            :color="match ($customer->reward_tier->value) {
                                                'gold' => 'amber',
                                                'silver' => 'sky',
                                                default => 'zinc',
                                            }"
                                            inset="top bottom"
                                        >
                                            {{ $customer->reward_tier->label() }}
                                        </flux:badge>

                                        <div class="text-sm text-zinc-500">
                                            {{ $customer->annual_drink_count }} bebidas
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell variant="strong">
                                    ${{ number_format($customer->availableRewardBalance(), 2) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="space-y-1">
                                        <div class="font-medium">
                                            ${{ number_format($customer->debtBalance(), 2) }}
                                        </div>

                                        <flux:badge :color="$customer->hasDebt() ? 'rose' : 'emerald'" inset="top bottom">
                                            {{ $customer->hasDebt() ? 'Debe' : 'Al corriente' }}
                                        </flux:badge>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="max-md:hidden">
                                    <flux:badge color="zinc" icon="qr-code" inset="top bottom">
                                        {{ $customer->qrCodes->count() }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge color="{{ $customer->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                        {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex justify-end">
                                        <flux:dropdown position="bottom" align="end" offset="-15">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="ellipsis-horizontal"
                                                inset="top bottom"
                                            />

                                            <flux:menu>
                                                <flux:menu.item
                                                    icon="paper-airplane"
                                                    wire:click="sendWelcomeMessage({{ $customer->id }})"
                                                    wire:confirm="¿Enviar de nuevo el mensaje de bienvenida por WhatsApp a este cliente?"
                                                >
                                                    Enviar bienvenida
                                                </flux:menu.item>

                                                <flux:menu.separator />

                                                <flux:menu.item
                                                    :href="route('dashboard.customers.edit', $customer)"
                                                    icon="pencil-square"
                                                    wire:navigate
                                                >
                                                    Editar
                                                </flux:menu.item>

                                                <flux:menu.separator />

                                                <flux:menu.item
                                                    icon="archive-box"
                                                    variant="danger"
                                                    wire:click="toggleActive({{ $customer->id }})"
                                                >
                                                    {{ $customer->is_active ? 'Desactivar' : 'Reactivar' }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="9">
                                    <flux:callout icon="information-circle" color="sky">
                                        Todavía no hay clientes registrados.
                                    </flux:callout>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($customers as $customer)
                    <flux:card wire:key="customer-card-{{ $customer->id }}" class="space-y-4 overflow-hidden">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <flux:checkbox wire:model.live="selectedCustomerIds" value="{{ $customer->id }}" />

                                <flux:avatar
                                    size="lg"
                                    :name="$customer->name"
                                    :initials="\Illuminate\Support\Str::of($customer->name)->explode(' ')->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('')"
                                />
                            </div>

                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item
                                        icon="paper-airplane"
                                        wire:click="sendWelcomeMessage({{ $customer->id }})"
                                        wire:confirm="¿Enviar de nuevo el mensaje de bienvenida por WhatsApp a este cliente?"
                                    >
                                        Enviar bienvenida
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item
                                        :href="route('dashboard.customers.edit', $customer)"
                                        icon="pencil-square"
                                        wire:navigate
                                    >
                                        Editar
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item
                                        icon="archive-box"
                                        variant="danger"
                                        wire:click="toggleActive({{ $customer->id }})"
                                    >
                                        {{ $customer->is_active ? 'Desactivar' : 'Reactivar' }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">
                                {{ $customer->name }}
                            </flux:heading>

                            <flux:text class="truncate">
                                {{ $customer->phone ?: 'Sin teléfono registrado' }}
                            </flux:text>

                            <flux:text class="truncate">
                                {{ $customer->email ?: 'Sin correo electrónico' }}
                            </flux:text>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <flux:badge color="emerald" inset="top bottom">
                                ${{ number_format($customer->availableRewardBalance(), 2) }}
                            </flux:badge>

                            <flux:badge color="zinc" icon="gift" inset="top bottom">
                                {{ $customer->reward_tier->label() }}
                            </flux:badge>

                            <flux:badge color="sky" icon="qr-code" inset="top bottom">
                                {{ $customer->qrCodes->count() }} QR
                            </flux:badge>

                            <flux:badge :color="$customer->hasDebt() ? 'rose' : 'emerald'" icon="banknotes" inset="top bottom">
                                ${{ number_format($customer->debtBalance(), 2) }}
                            </flux:badge>

                            <flux:badge color="{{ $customer->is_active ? 'emerald' : 'zinc' }}" inset="top bottom">
                                {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                            </flux:badge>
                        </div>

                        <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                            @if ($customer->birthday && filled($tonalliByCustomerId[$customer->id]['tonalli'] ?? null))
                                <div class="truncate font-medium">
                                    @if(! empty($tonalliByCustomerId[$customer->id]['icon']))
                                        <img
                                            src="{{ $tonalliByCustomerId[$customer->id]['icon'] }}"
                                            alt=""
                                            class="mr-1 inline-block h-5 w-5 align-text-bottom"
                                        />
                                    @endif

                                    {{ $tonalliByCustomerId[$customer->id]['tonalli'] }}
                                </div>

                                <div class="truncate">
                                    {{ $tonalliByCustomerId[$customer->id]['espanol'] }}
                                </div>

                                <div class="truncate text-sm text-zinc-500">
                                    @if(! empty($tonalliByCustomerId[$customer->id]['trecena_icon']))
                                        <img
                                            src="{{ $tonalliByCustomerId[$customer->id]['trecena_icon'] }}"
                                            alt=""
                                            class="mr-1 inline-block h-4 w-4 align-text-bottom"
                                        />
                                    @endif

                                    Trecena líder: {{ $tonalliByCustomerId[$customer->id]['trecena'] }}
                                </div>
                            @else
                                <div>Sin cumpleaños registrado</div>
                            @endif
                        </div>
                    </flux:card>
                @empty
                    <flux:callout icon="information-circle" color="sky">
                        Todavía no hay clientes registrados.
                    </flux:callout>
                @endforelse
            </div>
        @endif

        <div class="overflow-x-auto">
            <flux:pagination :paginator="$customers" />
        </div>
    </flux:card>
</div>
