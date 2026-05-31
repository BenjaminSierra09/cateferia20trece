<x-public-layout
    title="Recompensas"
    description="Cómo funciona el sistema de recompensas de Café 20Trece."
>
    {{-- Hero --}}
    <section class="u-reveal u-card overflow-hidden p-8 sm:p-12">
        <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
            <flux:icon.gift class="size-4" /> Programa de recompensas
        </span>

        <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.05] tracking-tight text-espresso sm:text-5xl">
            Así crece tu bonificación
        </h1>
        <p class="mt-4 max-w-2xl text-base leading-8 text-mocha">
            El programa avanza por visitas en días distintos. Tu nivel define el porcentaje que se convierte en saldo a favor para tus próximas compras.
        </p>
    </section>

    {{-- Tier ladder (progressive emphasis, Oro highlighted) --}}
    <section class="mt-8 grid gap-5 lg:grid-cols-3">
        @foreach ($rewardTiers as $tier)
            @php $isTop = $loop->last; @endphp
            <article
                @class([
                    'u-reveal relative overflow-hidden rounded-[1.75rem] p-7',
                    'u-card' => ! $isTop,
                    'border border-terracotta/30 bg-espresso text-vanilla shadow-[0_30px_60px_-40px_rgba(36,23,18,0.8)]' => $isTop,
                ])
                data-delay="{{ $loop->index }}"
            >
                @if ($isTop)
                    <span class="absolute right-5 top-5 rounded-full bg-terracotta px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-white">Nivel máximo</span>
                @endif

                <p @class([
                    'inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.18em]',
                    'text-terracotta' => ! $isTop,
                    'text-caramel' => $isTop,
                ])>
                    <flux:icon.star class="size-4" /> {{ $tier['name'] }}
                </p>

                <p class="mt-4 font-serif text-4xl font-semibold {{ $isTop ? 'text-vanilla' : 'text-espresso' }}">{{ $tier['bonus'] }}</p>
                <p class="mt-1 text-sm font-semibold {{ $isTop ? 'text-sand/80' : 'text-cacao' }}">{{ $tier['visits'] }}</p>
                <p class="mt-4 text-sm leading-7 {{ $isTop ? 'text-sand/75' : 'text-mocha' }}">{{ $tier['description'] }}</p>
            </article>
        @endforeach
    </section>

    {{-- Rules: two grouped cards --}}
    <section class="mt-8 grid gap-5 lg:grid-cols-2">
        <article class="u-reveal u-card p-7">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso">
                <flux:icon.check-badge class="size-6 text-sage" /> Reglas clave
            </h2>
            <ul class="mt-5 space-y-3.5 text-sm leading-7 text-mocha">
                @foreach ([
                    'Una visita cuenta por día, aunque compres varias veces ese mismo día.',
                    'Al registrarte entras a Cobre con 5% de bonificación.',
                    'Durante tus primeros 3 días recibes 5% extra de bienvenida.',
                    'Al llegar a 30 visitas pasas a Plata y a las 45 visitas pasas a Oro.',
                ] as $rule)
                    <li class="flex items-start gap-3">
                        <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" />
                        <span>{{ $rule }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="u-reveal u-card p-7" data-delay="1">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso">
                <flux:icon.no-symbol class="size-6 text-terracotta" /> Cuándo no aplica
            </h2>
            <ul class="mt-5 space-y-3.5 text-sm leading-7 text-mocha">
                @foreach ([
                    'Si pagas con saldo a favor, esa compra no genera visita ni bonificación.',
                    'Si la compra queda registrada totalmente como deuda, tampoco genera visita ni bonificación.',
                    'El saldo acumulado puede variar por redondeos operativos o ajustes manuales autorizados.',
                ] as $rule)
                    <li class="flex items-start gap-3">
                        <flux:icon.minus-circle class="mt-0.5 size-5 shrink-0 text-terracotta" />
                        <span>{{ $rule }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    </section>

    {{-- QR CTA band --}}
    <section class="u-reveal mt-8 overflow-hidden rounded-[2rem] bg-espresso p-8 text-vanilla sm:p-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold sm:text-3xl">
                    <flux:icon.qr-code class="size-7 text-caramel" /> Consulta tu cuenta con QR
                </h2>
                <p class="mt-3 text-sm leading-7 text-sand/80">
                    Escanea tu QR o captura tu UUID para ver tu saldo, visitas, historial reciente y bebidas favoritas.
                </p>
            </div>
            <a href="{{ route('public.lookup') }}" class="u-btn u-btn--accent shrink-0">
                Consultar mi cuenta <flux:icon.arrow-right class="size-5" />
            </a>
        </div>
    </section>
</x-public-layout>
