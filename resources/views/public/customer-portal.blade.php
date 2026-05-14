<x-public-layout
    :title="'Mi cuenta - '.$customer->name"
    description="Consulta pública del saldo, recompensas e historial de cliente."
>
    <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="space-y-6">
            <article class="rounded-[2rem] border border-white/60 bg-white/80 p-8 shadow-xl shadow-[#8B5E34]/10 backdrop-blur">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <span class="inline-flex rounded-full border border-[#8B5E34]/15 bg-[#8B5E34]/10 px-4 py-2 text-sm font-semibold text-[#6F4324]">
                            <flux:icon.qr-code class="mr-2 size-4" /> Cliente identificado por QR
                        </span>
                        <h1 class="mt-4 text-4xl font-black tracking-tight">{{ $customer->name }}</h1>
                        <p class="mt-3 text-sm text-[#6B5B4A]">
                            {{ $customer->phone ?: 'Sin teléfono registrado' }}
                            @if ($customer->email)
                                <span class="mx-2">·</span>{{ $customer->email }}
                            @endif
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#8B5E34]/5 px-5 py-4 text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]">Nivel actual</p>
                        <p class="mt-2 text-3xl font-black">{{ $customer->reward_tier->label() }}</p>
                        <p class="text-sm text-[#6B5B4A]">
                            {{ data_get(collect($rewardTiers)->firstWhere('name', $customer->reward_tier->label()), 'bonus', 'Programa activo') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[1.5rem] border border-white/60 bg-white/80 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]"><flux:icon.wallet class="size-4" /> Saldo a favor</p>
                        <p class="mt-2 text-3xl font-black">${{ number_format((float) $customer->reward_balance, 2) }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/60 bg-white/80 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]"><flux:icon.calendar-days class="size-4" /> Visitas del año</p>
                        <p class="mt-2 text-3xl font-black">{{ (int) $customer->annual_drink_count }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/60 bg-white/80 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]"><flux:icon.shopping-bag class="size-4" /> Compras registradas</p>
                        <p class="mt-2 text-3xl font-black">{{ (int) $customer->sales_count }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-white/60 bg-white/80 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]"><flux:icon.banknotes class="size-4" /> Deuda pendiente</p>
                        <p class="mt-2 text-3xl font-black">${{ number_format((float) $customer->debtBalance(), 2) }}</p>
                    </div>
                </div>

                @if ($customer->welcome_reward_active)
                    <div class="mt-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                        <span class="inline-flex items-center gap-2"><flux:icon.gift class="size-4" /> Tienes activa tu bonificación de bienvenida: durante tus primeros 3 días se suma 5% extra a tu nivel actual.</span>
                    </div>
                @endif
            </article>

            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.receipt-percent class="size-6 text-[#8B5E34]" /> Compras recientes</h2>
                        <p class="mt-2 text-sm text-[#6B5B4A]">Tus últimas compras registradas en Café 20Trece.</p>
                    </div>
                    <a href="{{ route('public.lookup') }}" class="inline-flex items-center gap-2 rounded-full border border-[#8B5E34]/20 px-4 py-2 text-sm font-semibold text-[#6F4324] transition hover:bg-[#8B5E34]/5">
                        <flux:icon.arrow-path class="size-4" /> Escanear otro QR
                    </a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($recentSales as $sale)
                        <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 p-5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-lg font-bold">Venta #{{ $sale->id }}</p>
                                    <p class="text-sm text-[#6B5B4A]">
                                        {{ $sale->sold_at?->timezone('America/Mexico_City')->translatedFormat('d M Y, g:i a') }}
                                        @if ($sale->branch)
                                            <span class="mx-2">·</span>{{ $sale->branch->name }}
                                        @endif
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]">Total</p>
                                    <p class="text-2xl font-black">${{ number_format((float) $sale->total, 2) }}</p>
                                    <p class="text-sm text-[#6B5B4A]">{{ $sale->payment_method->label() }}</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach ($sale->items as $item)
                                    <div class="rounded-2xl border border-white/60 bg-white/70 px-4 py-3">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="font-semibold">{{ $item->item_name }}</p>
                                                <p class="text-sm text-[#6B5B4A]">
                                                    {{ $item->quantity }} x ${{ number_format((float) $item->unit_price, 2) }}
                                                </p>

                                                @if ($item->customizations->isNotEmpty())
                                                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-[#8B5E34]">Personalizaciones</p>
                                                    <p class="mt-1 text-sm text-[#6B5B4A]">
                                                        {{ $item->customizations->pluck('customization_name')->sort()->join(', ') }}
                                                    </p>
                                                @endif
                                            </div>

                                            <p class="text-base font-bold">${{ number_format((float) $item->line_total, 2) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 px-5 py-4 text-sm text-[#6B5B4A]">
                            Todavía no hay compras registradas para este cliente.
                        </div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="space-y-6">
            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.heart class="size-6 text-[#8B5E34]" /> Bebidas favoritas</h2>
                <p class="mt-2 text-sm text-[#6B5B4A]">Tus bebidas más pedidas con el tamaño y extras que más se repiten.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($favoriteBeverages as $favorite)
                        <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 p-4">
                            <div class="flex gap-4">
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-white ring-1 ring-[#8B5E34]/10">
                                    @if ($favorite['beverage_image_url'])
                                        <img src="{{ $favorite['beverage_image_url'] }}" alt="{{ $favorite['beverage_name'] }}" class="h-full w-full object-cover">
                                    @else
                                        <img src="{{ asset('logotipo.png') }}" alt="Café 20Trece" class="h-full w-full object-contain p-2">
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-lg font-bold">{{ $favorite['beverage_name'] }}</p>
                                    <p class="text-sm text-[#6B5B4A]">{{ $favorite['total_quantity'] }} bebidas registradas</p>

                                    @if ($favorite['top_size'])
                                        <p class="mt-2 text-sm text-[#6B5B4A]">
                                            Tamaño favorito:
                                            <span class="font-semibold text-[#2A2118]">
                                                {{ $favorite['top_size']['size_name'] }}
                                                @if ($favorite['top_size']['capacity_label'])
                                                    ({{ $favorite['top_size']['capacity_label'] }})
                                                @endif
                                            </span>
                                        </p>
                                    @endif

                                    @if (! empty($favorite['frequent_customizations']))
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($favorite['frequent_customizations'] as $customization)
                                                <span class="rounded-full border border-[#8B5E34]/15 bg-white px-3 py-1 text-xs font-semibold text-[#6F4324]">
                                                    {{ $customization['name'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 px-5 py-4 text-sm text-[#6B5B4A]">
                            Todavía no hay suficientes compras para calcular bebidas favoritas.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.arrows-right-left class="size-6 text-[#8B5E34]" /> Movimientos recientes</h2>
                <p class="mt-2 text-sm text-[#6B5B4A]">Bonificaciones, usos de saldo y cuenta corriente.</p>

                <div class="mt-6 space-y-4">
                    @foreach ($customer->rewardTransactions as $transaction)
                        <div class="rounded-2xl border border-[#8B5E34]/10 bg-[#F7F1E8]/80 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $transaction->type->label() }}</p>
                                    <p class="text-sm text-[#6B5B4A]">{{ $transaction->description }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold">${{ number_format((float) $transaction->amount, 2) }}</p>
                                    <p class="text-xs text-[#6B5B4A]">Saldo: ${{ number_format((float) $transaction->balance_after, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if ($customer->rewardTransactions->isEmpty())
                        <div class="rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 px-5 py-4 text-sm text-[#6B5B4A]">
                            Aún no hay movimientos de bonificación registrados.
                        </div>
                    @endif
                </div>
            </article>
        </div>
    </section>
</x-public-layout>
