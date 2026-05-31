@props([
    'title' => config('app.name'),
    'description' => 'Café 20Trece',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>document.documentElement.classList.add('js');</script>
        <title>{{ $title }} - {{ config('app.name', 'Café 20Trece') }}</title>
        <meta name="description" content="{{ $description }}">

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="20Trece" />
        <link rel="manifest" href="/site.webmanifest" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-screen bg-vanilla font-body text-espresso antialiased selection:bg-terracotta selection:text-white">
        {{-- Soft warm ambient wash (fixed, non-painting on scroll) --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-caramel/20 blur-3xl"></div>
            <div class="absolute top-1/3 -right-24 h-80 w-80 rounded-full bg-terracotta/10 blur-3xl"></div>
            <div class="absolute bottom-10 left-1/3 h-72 w-72 rounded-full bg-sage/10 blur-3xl"></div>
        </div>

        <x-public.header />

        <main class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            {{ $slot }}
        </main>

        <x-public.footer />

        <x-public.cookie-banner />

        @fluxScripts
        @stack('scripts')

        <script>
            (() => {
                const reveals = document.querySelectorAll('.u-reveal');

                if (! reveals.length) {
                    return;
                }

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || ! ('IntersectionObserver' in window)) {
                    reveals.forEach((el) => el.classList.add('is-visible'));
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

                reveals.forEach((el) => observer.observe(el));
            })();
        </script>
    </body>
</html>
