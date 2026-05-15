@props([
    'title' => config('app.name'),
    'description' => 'Café 20Trece',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} - {{ config('app.name', 'Café 20Trece') }}</title>
        <meta name="description" content="{{ $description }}">

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="20Trece" />
        <link rel="manifest" href="/site.webmanifest" />

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-[#F7F1E8] text-[#2A2118] selection:bg-[#8B5E34] selection:text-white">
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-[#C49A6C]/25 blur-3xl"></div>
            <div class="absolute top-40 -right-20 h-72 w-72 rounded-full bg-[#8B5E34]/15 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-[#E9D8C3]/50 blur-3xl"></div>
        </div>

        <header class="sticky top-0 z-20 border-b border-[#8B5E34]/10 bg-white/85 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('logotipo.png') }}" alt="Café 20Trece" class="h-12 w-12 rounded-full object-contain ring-4 ring-white/70">
                    <div>
                        <p class="text-sm font-semibold text-[#8B5E34]">Café 20Trece</p>
                        <p class="text-xs text-[#6B5B4A]">San Miguel de Allende</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-3 text-sm font-medium text-[#6B5B4A] md:flex">
                    <a href="{{ route('public.register') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]"><flux:icon.user-plus class="size-4" /> Obtener mi QR</a>
                    <a href="{{ route('public.lookup') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]"><flux:icon.qr-code class="size-4" /> Mi cuenta QR</a>
                    <a href="{{ route('public.rewards') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]"><flux:icon.gift class="size-4" /> Recompensas</a>
                    <a href="{{ route('public.terms') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]"><flux:icon.document-text class="size-4" /> Términos</a>
                    <a href="{{ route('public.privacy') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]"><flux:icon.shield-check class="size-4" /> Privacidad</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-[#8B5E34]/10 bg-white/60 backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-6 text-sm text-[#6B5B4A] sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <p>© 2026 Café 20Trece. Información pública para clientes.</p>

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('public.register') }}" class="inline-flex items-center gap-2 transition hover:text-[#6F4324]"><flux:icon.user-plus class="size-4" /> Obtener mi QR</a>
                    <a href="{{ route('public.lookup') }}" class="inline-flex items-center gap-2 transition hover:text-[#6F4324]"><flux:icon.qr-code class="size-4" /> Mi cuenta QR</a>
                    <a href="{{ route('public.rewards') }}" class="inline-flex items-center gap-2 transition hover:text-[#6F4324]"><flux:icon.gift class="size-4" /> Recompensas</a>
                    <a href="{{ route('public.terms') }}" class="inline-flex items-center gap-2 transition hover:text-[#6F4324]"><flux:icon.document-text class="size-4" /> Términos y condiciones</a>
                    <a href="{{ route('public.privacy') }}" class="inline-flex items-center gap-2 transition hover:text-[#6F4324]"><flux:icon.shield-check class="size-4" /> Aviso de privacidad</a>
                </div>
            </div>
        </footer>

        @fluxScripts
        @stack('scripts')
    </body>
</html>
