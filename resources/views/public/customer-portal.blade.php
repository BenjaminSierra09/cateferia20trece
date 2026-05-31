<x-public-layout
    :title="'Mi cuenta - '.$customer->name"
    description="Consulta pública del saldo, recompensas e historial de cliente."
>
    <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="space-y-6">
            <article class="u-reveal u-card p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm font-medium text-sage">
                        <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-4" /> {{ session('status') }}</span>
                    </div>
                @endif

                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
                            <flux:icon.qr-code class="size-4" /> Cliente identificado por QR
                        </span>
                        <h1 class="mt-4 font-serif text-4xl font-semibold tracking-tight text-espresso">{{ $customer->name }}</h1>
                        <p class="mt-3 text-sm text-mocha">
                            {{ $customer->phone ?: 'Sin teléfono registrado' }}
                            @if ($customer->email)
                                <span class="mx-2 text-coffee/50">·</span>{{ $customer->email }}
                            @endif
                        </p>
                    </div>

                    <div class="rounded-2xl border border-terracotta/20 bg-terracotta/8 px-5 py-4 text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-terracotta">Nivel actual</p>
                        <p class="mt-1 font-serif text-3xl font-semibold text-espresso">{{ $customer->reward_tier->label() }}</p>
                        <p class="text-sm text-mocha">
                            {{ data_get(collect($rewardTiers)->firstWhere('name', $customer->reward_tier->label()), 'bonus', 'Programa activo') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-mocha"><flux:icon.wallet class="size-4 text-terracotta" /> Saldo a favor</p>
                        <p class="mt-2 font-serif text-3xl font-semibold text-sage">${{ number_format($customer->availableRewardBalance(), 2) }}</p>
                    </div>

                    <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-mocha"><flux:icon.calendar-days class="size-4 text-terracotta" /> Visitas del año</p>
                        <p class="mt-2 font-serif text-3xl font-semibold text-espresso">{{ (int) $customer->annual_drink_count }}</p>
                    </div>

                    <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-mocha"><flux:icon.shopping-bag class="size-4 text-terracotta" /> Compras registradas</p>
                        <p class="mt-2 font-serif text-3xl font-semibold text-espresso">{{ (int) $customer->sales_count }}</p>
                    </div>

                    <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-mocha"><flux:icon.banknotes class="size-4 text-terracotta" /> Deuda pendiente</p>
                        <p class="mt-2 font-serif text-3xl font-semibold text-espresso">${{ number_format((float) $customer->debtBalance(), 2) }}</p>
                    </div>
                </div>

                @if ($customer->welcome_reward_active)
                    <div class="mt-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm text-sage">
                        <span class="inline-flex items-center gap-2"><flux:icon.gift class="size-4" /> Tienes activa tu bonificación de bienvenida: durante tus primeros 3 días se suma 5% extra a tu nivel actual.</span>
                    </div>
                @endif

                @if ($primaryQrCode && $portalUrl && $customerCardImageUrl)
                    <div class="mt-6 rounded-[1.75rem] border border-coffee/12 bg-crema/50 p-6">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                            <div class="max-w-xl">
                                <h2 class="inline-flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.identification class="size-6 text-terracotta" /> Tu QR de cliente</h2>
                                <p class="mt-2 text-sm leading-7 text-mocha">
                                    Presenta esta tarjeta en sucursal para identificar tu cuenta. También puedes descargarla o compartir tu link público.
                                </p>
                                <div class="mt-4 space-y-2 text-sm text-mocha">
                                    <p><span class="font-semibold text-espresso">UUID:</span> {{ $primaryQrCode->uuid }}</p>
                                    <p class="break-all"><span class="font-semibold text-espresso">Link:</span> {{ $portalUrl }}</p>
                                </div>
                            </div>

                            <div class="flex w-full max-w-[24rem] flex-col items-center gap-3">
                                <img
                                    id="customer-card-image"
                                    src="{{ $customerCardImageUrl }}"
                                    alt="Tarjeta de cliente de {{ $customer->name }}"
                                    class="w-full rounded-[2rem] shadow-[0_30px_60px_-35px_rgba(36,23,18,0.6)]"
                                >

                                <div class="flex flex-wrap justify-center gap-3">
                                    <button
                                        type="button"
                                        id="download-customer-card-button"
                                        data-download-url="{{ $customerCardImageUrl }}"
                                        data-download-name="tarjeta-cliente-{{ $primaryQrCode->uuid }}.png"
                                        class="u-btn u-btn--primary px-4 py-2 text-sm"
                                    >
                                        <flux:icon.arrow-down-tray class="size-4" /> Descargar tarjeta
                                    </button>

                                    <button
                                        type="button"
                                        id="copy-customer-card-link-button"
                                        data-copy-value="{{ $portalUrl }}"
                                        class="u-btn u-btn--outline px-4 py-2 text-sm"
                                    >
                                        <flux:icon.clipboard-document class="size-4" /> Copiar link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </article>

            <article class="u-reveal u-card p-7" data-delay="1">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="inline-flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.receipt-percent class="size-6 text-terracotta" /> Compras recientes</h2>
                        <p class="mt-2 text-sm text-mocha">Tus últimas compras registradas en Café 20Trece.</p>
                    </div>
                    <a href="{{ route('public.lookup') }}" class="inline-flex shrink-0 items-center gap-2 rounded-full border border-coffee/25 px-4 py-2 text-sm font-semibold text-cacao transition hover:bg-coffee/5">
                        <flux:icon.arrow-path class="size-4" /> Escanear otro QR
                    </a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($recentSales as $sale)
                        <div class="rounded-[1.5rem] border border-coffee/12 bg-vanilla/70 p-5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-lg font-bold text-espresso">Compra registrada</p>
                                    <p class="text-sm text-mocha">
                                        {{ $sale->sold_at?->timezone('America/Mexico_City')->translatedFormat('d M Y, g:i a') }}
                                        @if ($sale->branch)
                                            <span class="mx-2 text-coffee/50">·</span>{{ $sale->branch->name }}
                                        @endif
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Total</p>
                                    <p class="font-serif text-2xl font-semibold text-espresso">${{ number_format((float) $sale->total, 2) }}</p>
                                    <p class="text-sm text-mocha">{{ $sale->payment_method->label() }}</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach ($sale->items as $item)
                                    <div class="rounded-2xl border border-coffee/10 bg-white/70 px-4 py-3">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="font-semibold text-espresso">{{ $item->item_name }}</p>
                                                <p class="text-sm text-mocha">
                                                    {{ $item->quantity }} x ${{ number_format((float) $item->unit_price, 2) }}
                                                </p>

                                                @if ($item->customizations->isNotEmpty())
                                                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-terracotta">Personalizaciones</p>
                                                    <p class="mt-1 text-sm text-mocha">
                                                        {{ $item->customizations->pluck('customization_name')->sort()->join(', ') }}
                                                    </p>
                                                @endif
                                            </div>

                                            <p class="text-base font-bold text-espresso">${{ number_format((float) $item->line_total, 2) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-coffee/20 bg-vanilla/60 px-5 py-8 text-center text-sm text-mocha">
                            <flux:icon.receipt-percent class="mx-auto mb-2 size-7 text-coffee/40" />
                            Todavía no hay compras registradas para este cliente.
                        </div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="space-y-6">
            <article class="u-reveal u-card p-7" data-delay="1">
                <h2 class="inline-flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.heart class="size-6 text-terracotta" /> Bebidas favoritas</h2>
                <p class="mt-2 text-sm text-mocha">Tus bebidas más pedidas con el tamaño y extras que más se repiten.</p>

                <div class="mt-6 space-y-4">
                    @forelse ($favoriteBeverages as $favorite)
                        <div class="rounded-[1.5rem] border border-coffee/12 bg-vanilla/70 p-4">
                            <div class="flex gap-4">
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-white ring-1 ring-coffee/10">
                                    @if ($favorite['beverage_image_url'])
                                        <img src="{{ $favorite['beverage_image_url'] }}" alt="{{ $favorite['beverage_name'] }}" class="h-full w-full object-cover">
                                    @else
                                        <img src="{{ asset('logotipo.png') }}" alt="Café 20Trece" class="h-full w-full object-contain p-2">
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-lg font-bold text-espresso">{{ $favorite['beverage_name'] }}</p>
                                    <p class="text-sm text-mocha">{{ $favorite['total_quantity'] }} bebidas registradas</p>

                                    @if ($favorite['top_size'])
                                        <p class="mt-2 text-sm text-mocha">
                                            Tamaño favorito:
                                            <span class="font-semibold text-espresso">
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
                                                <span class="rounded-full border border-coffee/15 bg-white px-3 py-1 text-xs font-semibold text-cacao">
                                                    {{ $customization['name'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-coffee/20 bg-vanilla/60 px-5 py-8 text-center text-sm text-mocha">
                            <flux:icon.heart class="mx-auto mb-2 size-7 text-coffee/40" />
                            Todavía no hay suficientes compras para calcular bebidas favoritas.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="u-reveal u-card p-7" data-delay="2">
                <h2 class="inline-flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.arrows-right-left class="size-6 text-terracotta" /> Movimientos recientes</h2>
                <p class="mt-2 text-sm text-mocha">Bonificaciones, usos de saldo y cuenta corriente.</p>

                @if ($customer->rewardTransactions->isNotEmpty())
                    <div class="mt-6 divide-y divide-coffee/10">
                        @foreach ($customer->rewardTransactions as $transaction)
                            <div class="flex items-center justify-between gap-3 py-3.5 first:pt-0">
                                <div>
                                    <p class="font-semibold text-espresso">{{ $transaction->type->label() }}</p>
                                    <p class="text-sm text-mocha">{{ $transaction->description }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-espresso">${{ number_format((float) $transaction->amount, 2) }}</p>
                                    <p class="text-xs text-mocha">Saldo: ${{ number_format((float) $transaction->balance_after, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-[1.5rem] border border-dashed border-coffee/20 bg-vanilla/60 px-5 py-8 text-center text-sm text-mocha">
                        <flux:icon.arrows-right-left class="mx-auto mb-2 size-7 text-coffee/40" />
                        Aún no hay movimientos de bonificación registrados.
                    </div>
                @endif
            </article>
        </div>
    </section>

    @if ($primaryQrCode && $portalUrl && $customerCardImageUrl)
        @push('scripts')
            <script>
                (() => {
                    const cardImage = document.getElementById('customer-card-image');
                    const copyButton = document.getElementById('copy-customer-card-link-button');
                    const downloadButton = document.getElementById('download-customer-card-button');

                    const downloadCard = async () => {
                        const downloadUrl = downloadButton?.dataset.downloadUrl;
                        const downloadName = downloadButton?.dataset.downloadName;

                        if (! downloadUrl || ! cardImage) {
                            return;
                        }

                        try {
                            const image = new Image();
                            image.decoding = 'async';
                            image.src = downloadUrl;

                            await new Promise((resolve, reject) => {
                                image.onload = resolve;
                                image.onerror = reject;
                            });

                            const canvas = document.createElement('canvas');
                            canvas.width = image.naturalWidth || 900;
                            canvas.height = image.naturalHeight || 1460;

                            const context = canvas.getContext('2d');

                            if (! context) {
                                throw new Error('No canvas context available.');
                            }

                            context.drawImage(image, 0, 0, canvas.width, canvas.height);

                            const pngDataUrl = canvas.toDataURL('image/png');

                            const anchor = document.createElement('a');
                            anchor.href = pngDataUrl;
                            anchor.download = downloadName || 'tarjeta-cliente.png';
                            document.body.appendChild(anchor);
                            anchor.click();
                            anchor.remove();
                        } catch (error) {
                            const anchor = document.createElement('a');
                            anchor.href = downloadUrl;
                            anchor.download = downloadName || 'tarjeta-cliente.png';
                            document.body.appendChild(anchor);
                            anchor.click();
                            anchor.remove();
                        }
                    };

                    downloadButton?.addEventListener('click', async () => {
                        await downloadCard();
                    });

                    copyButton?.addEventListener('click', async () => {
                        try {
                            await navigator.clipboard.writeText(copyButton.dataset.copyValue || '');
                            copyButton.innerHTML = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg> Link copiado';
                        } catch (error) {
                            copyButton.textContent = 'No se pudo copiar';
                        }
                    });
                })();
            </script>
        @endpush
    @endif
</x-public-layout>
