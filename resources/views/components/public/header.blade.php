@props([
    'home' => false,
])

@php
    $navLink = 'rounded-full px-3.5 py-2 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao';
@endphp

<header class="sticky top-0 z-30 border-b border-coffee/15 bg-vanilla/85 backdrop-blur-xl">
    <div class="mx-auto flex h-[68px] max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {{-- Brand: logo preserved exactly as-is --}}
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="Café 20Trece, inicio">
            <img
                src="{{ asset('logotipo.png') }}"
                alt="Café 20Trece"
                class="h-11 w-11 shrink-0 rounded-full object-contain ring-1 ring-coffee/25"
            >
            <span class="leading-tight">
                <span class="block font-serif text-lg font-semibold tracking-tight text-espresso">Café 20Trece</span>
                <span class="block text-[11px] font-medium uppercase tracking-[0.16em] text-mocha">San Miguel de Allende</span>
            </span>
        </a>

        {{-- Desktop nav: single line --}}
        <nav class="hidden items-center gap-1 lg:flex">
            @if ($home)
                <a href="#galeria" class="{{ $navLink }}">Galería</a>
                <a href="#visitanos" class="{{ $navLink }}">Visítanos</a>
            @else
                <a href="{{ route('home') }}" class="{{ $navLink }}">Inicio</a>
            @endif
            <a href="{{ route('public.rewards') }}" class="{{ $navLink }} {{ request()->routeIs('public.rewards') ? 'bg-cacao/10 text-cacao' : '' }}">Recompensas</a>
            <a href="{{ route('public.lookup') }}" class="{{ $navLink }} {{ request()->routeIs('public.lookup') ? 'bg-cacao/10 text-cacao' : '' }}">Mi cuenta</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('public.register') }}" class="u-btn u-btn--accent hidden px-5 py-2.5 text-sm sm:inline-flex">
                <flux:icon.qr-code class="size-4" /> Obtener mi QR
            </a>

            {{-- Mobile menu: no-JS disclosure so it works on every public page --}}
            <details class="group relative lg:hidden">
                <summary
                    class="flex size-11 cursor-pointer list-none items-center justify-center rounded-full border border-coffee/30 bg-white/60 text-cacao transition hover:bg-white [&::-webkit-details-marker]:hidden"
                    aria-label="Abrir menú de navegación"
                >
                    <flux:icon.bars-3 class="size-5 group-open:hidden" />
                    <flux:icon.x-mark class="hidden size-5 group-open:block" />
                </summary>

                <div class="u-card absolute right-0 top-[calc(100%+0.6rem)] z-40 w-64 overflow-hidden p-2">
                    @if ($home)
                        <a href="#galeria" class="block rounded-2xl px-4 py-2.5 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao">Galería</a>
                        <a href="#visitanos" class="block rounded-2xl px-4 py-2.5 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao">Visítanos</a>
                    @else
                        <a href="{{ route('home') }}" class="block rounded-2xl px-4 py-2.5 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao">Inicio</a>
                    @endif
                    <a href="{{ route('public.rewards') }}" class="block rounded-2xl px-4 py-2.5 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao">Recompensas</a>
                    <a href="{{ route('public.lookup') }}" class="block rounded-2xl px-4 py-2.5 text-sm font-semibold text-mocha transition hover:bg-cacao/10 hover:text-cacao">Mi cuenta QR</a>
                    <a href="{{ route('public.register') }}" class="u-btn u-btn--accent mt-1 w-full px-4 py-2.5 text-sm">
                        <flux:icon.qr-code class="size-4" /> Obtener mi QR
                    </a>
                </div>
            </details>
        </div>
    </div>
</header>
