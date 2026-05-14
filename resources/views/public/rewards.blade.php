<x-public-layout
    title="Recompensas"
    description="Cómo funciona el sistema de recompensas de Café 20Trece."
>
    <section class="rounded-[2rem] border border-white/60 bg-white/80 p-8 shadow-xl shadow-[#8B5E34]/10 backdrop-blur">
        <span class="inline-flex rounded-full border border-[#8B5E34]/15 bg-[#8B5E34]/10 px-4 py-2 text-sm font-semibold text-[#6F4324]">
            <flux:icon.gift class="mr-2 size-4" /> Programa de recompensas
        </span>

        <h1 class="mt-4 text-4xl font-black tracking-tight">Así funciona tu bonificación</h1>
        <p class="mt-4 max-w-3xl text-base leading-8 text-[#6B5B4A]">
            El programa avanza por visitas en días distintos. Tu nivel determina el porcentaje de bonificación que se convierte en saldo a favor para próximas compras.
        </p>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-3">
        @foreach ($rewardTiers as $tier)
            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <p class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-[#8B5E34]">
                    <flux:icon.star class="size-4" /> {{ $tier['name'] }}
                </p>
                <h2 class="mt-3 text-3xl font-black">{{ $tier['bonus'] }}</h2>
                <p class="mt-2 text-sm font-medium text-[#6F4324]">{{ $tier['visits'] }}</p>
                <p class="mt-4 text-sm leading-7 text-[#6B5B4A]">{{ $tier['description'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
        <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
            <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.check-badge class="size-6 text-[#8B5E34]" /> Reglas clave</h2>
            <ul class="mt-4 space-y-3 text-sm leading-7 text-[#6B5B4A]">
                <li>Una visita cuenta por día, aunque compres varias veces ese mismo día.</li>
                <li>Al registrarte entras a Cobre con 5% de bonificación.</li>
                <li>Durante tus primeros 3 días recibes 5% extra de bienvenida.</li>
                <li>Al llegar a 30 visitas pasas a Plata y al llegar a 45 visitas pasas a Oro.</li>
            </ul>
        </article>

        <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
            <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.no-symbol class="size-6 text-[#8B5E34]" /> Cuándo no aplica</h2>
            <ul class="mt-4 space-y-3 text-sm leading-7 text-[#6B5B4A]">
                <li>Si pagas con saldo a favor, esa compra no genera visita ni bonificación.</li>
                <li>Si la compra queda registrada totalmente como deuda, tampoco genera visita ni bonificación.</li>
                <li>El saldo acumulado puede variar por redondeos operativos o ajustes manuales autorizados.</li>
            </ul>
        </article>
    </section>

    <section class="mt-8 rounded-[1.75rem] border border-[#8B5E34]/10 bg-[#8B5E34]/5 p-7">
        <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.qr-code class="size-6 text-[#8B5E34]" /> Consulta tu cuenta con QR</h2>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-[#6B5B4A]">
            Escanea tu QR o captura tu UUID para ver tu saldo, visitas, historial reciente de compras y bebidas favoritas.
        </p>
        <div class="mt-5">
            <a href="{{ route('public.lookup') }}" class="inline-flex items-center gap-2 rounded-full bg-[#6F4324] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#6F4324]/20 transition hover:bg-[#5D351C]">
                <flux:icon.arrow-right class="size-4" /> Consultar mi cuenta
            </a>
        </div>
    </section>
</x-public-layout>
