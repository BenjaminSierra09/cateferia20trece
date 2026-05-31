<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>document.documentElement.classList.add('js');</script>

        <title>Café orgánico en San Miguel de Allende - {{ config('app.name', 'Café 20Trece') }}</title>
        <meta name="description" content="Café 20Trece: café orgánico en grano o molido, servido en el corazón de San Miguel de Allende. Programa de recompensas y cuenta de cliente por QR.">

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
        @vite('resources/css/app.css')

        <style>
            @keyframes home-gallery-scroll-left {
                from { transform: translate3d(0, 0, 0); }
                to { transform: translate3d(-50%, 0, 0); }
            }

            @keyframes home-gallery-scroll-right {
                from { transform: translate3d(-50%, 0, 0); }
                to { transform: translate3d(0, 0, 0); }
            }

            .home-gallery-track {
                width: max-content;
                will-change: transform;
            }

            .home-gallery-track--left { animation: home-gallery-scroll-left 50s linear infinite; }
            .home-gallery-track--right { animation: home-gallery-scroll-right 56s linear infinite; }
            .home-gallery-marquee:hover .home-gallery-track { animation-play-state: paused; }

            @media (prefers-reduced-motion: reduce) {
                .home-gallery-track { animation: none !important; }
                .home-gallery-marquee { overflow-x: auto; }
            }
        </style>
    </head>

    <body class="relative flex min-h-screen flex-col overflow-x-hidden bg-vanilla font-body text-espresso antialiased selection:bg-terracotta selection:text-white">
        {{-- Soft warm ambient wash --}}
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-caramel/20 blur-3xl"></div>
            <div class="absolute top-1/4 -right-24 h-96 w-96 rounded-full bg-terracotta/10 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-sage/10 blur-3xl"></div>
        </div>

        <x-public.header :home="true" />

        <main class="relative z-10 w-full flex-1">
            {{-- ===== Hero: asymmetric split ===== --}}
            <section class="mx-auto max-w-6xl px-4 pt-10 pb-12 sm:px-6 lg:px-8 lg:pt-16 lg:pb-20">
                <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14">
                    <div class="text-center lg:text-left">
                        <span class="u-eyebrow justify-center lg:justify-start">
                            <flux:icon.sparkles class="size-4" /> Café orgánico · San Miguel de Allende
                        </span>

                        <h1 class="mt-5 font-serif text-[2.6rem] font-semibold leading-[1.1] tracking-tight text-espresso sm:text-5xl xl:text-[3.25rem]">
                            Café orgánico,<br>
                            <span class="italic text-cacao">servido con calma.</span>
                        </h1>

                        <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-mocha lg:mx-0">
                            Grano o molido, servido en el corazón de San Miguel de Allende.
                        </p>

                        <div class="mt-9 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center lg:justify-start">
                            <a href="{{ route('public.register') }}" class="u-btn u-btn--accent">
                                <flux:icon.qr-code class="size-5" /> Obtener mi QR
                            </a>
                            <a href="tel:+524151194612" class="u-btn u-btn--outline">
                                <flux:icon.phone class="size-5" /> Llamar
                            </a>
                        </div>
                    </div>

                    {{-- Product visual --}}
                    <div>
                        <div class="relative mx-auto max-w-md lg:max-w-none">
                            <div class="relative overflow-hidden rounded-[2.25rem] border border-white/70 bg-gradient-to-br from-crema via-sand/70 to-caramel/40 p-6 shadow-[0_40px_80px_-50px_rgba(36,23,18,0.6)]">
                                <img
                                    src="{{ asset('hero-cappuccino.webp') }}"
                                    alt="Cappuccino artesanal de Café 20Trece"
                                    class="mx-auto h-72 w-full object-contain drop-shadow-[0_24px_30px_rgba(36,23,18,0.28)] sm:h-80 lg:h-[26rem]"
                                    loading="eager"
                                >
                            </div>

                            {{-- Floating organic badge (sage = organic semantic) --}}
                            <div class="u-card absolute -bottom-4 left-4 flex items-center gap-3 px-4 py-3 sm:left-6">
                                <span class="flex size-10 items-center justify-center rounded-full bg-sage/15 text-sage">
                                    <flux:icon.sparkles class="size-5" />
                                </span>
                                <span class="leading-tight">
                                    <span class="block text-sm font-bold text-espresso">100% orgánico</span>
                                    <span class="block text-xs text-mocha">En grano o molido</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Quick facts band ===== --}}
            <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="u-reveal grid gap-px overflow-hidden rounded-[1.75rem] border border-coffee/15 bg-coffee/15 sm:grid-cols-3">
                    <div class="flex items-center gap-4 bg-vanilla/90 px-6 py-6">
                        <flux:icon.clock class="size-6 shrink-0 text-terracotta" />
                        <span class="leading-tight">
                            <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Horario</span>
                            <span class="block text-lg font-bold text-espresso">6:30 a 22:00 h</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 bg-vanilla/90 px-6 py-6">
                        <flux:icon.map-pin class="size-6 shrink-0 text-terracotta" />
                        <span class="leading-tight">
                            <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Ubicación</span>
                            <span class="block text-lg font-bold text-espresso">Juárez 21, Centro</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 bg-vanilla/90 px-6 py-6">
                        <flux:icon.gift class="size-6 shrink-0 text-terracotta" />
                        <span class="leading-tight">
                            <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Programa</span>
                            <span class="block text-lg font-bold text-espresso">Saldo a favor + QR</span>
                        </span>
                    </div>
                </div>
            </section>

            {{-- ===== Gallery marquee ===== --}}
            @if (! empty($galleryImages ?? []))
                <section id="galeria" class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="u-reveal mx-auto mb-9 max-w-2xl text-center">
                        <h2 class="font-serif text-3xl font-semibold tracking-tight text-espresso sm:text-4xl">Momentos y rincones de 20Trece</h2>
                        <p class="mt-3 text-base leading-7 text-mocha">
                            Bebidas, ambiente y los pequeños detalles que acompañan cada visita.
                        </p>
                    </div>

                    @php
                        $galleryLoop = array_merge($galleryImages, $galleryImages);
                    @endphp

                    <div class="home-gallery-marquee space-y-4 overflow-hidden">
                        <div class="home-gallery-track home-gallery-track--left flex gap-4 pr-4">
                            @foreach ($galleryLoop as $index => $image)
                                <div class="h-40 w-32 shrink-0 overflow-hidden rounded-[1.5rem] border border-white/60 shadow-[0_24px_40px_-30px_rgba(36,23,18,0.5)] sm:h-48 sm:w-40 lg:h-56 lg:w-44">
                                    <img src="{{ $image }}" alt="Galería Café 20Trece {{ $index + 1 }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>

                        <div class="home-gallery-track home-gallery-track--right flex gap-4 pr-4">
                            @foreach (array_reverse($galleryLoop) as $index => $image)
                                <div class="h-32 w-40 shrink-0 overflow-hidden rounded-[1.5rem] border border-white/60 shadow-[0_24px_40px_-30px_rgba(36,23,18,0.5)] sm:h-40 sm:w-48 lg:h-44 lg:w-56">
                                    <img src="{{ $image }}" alt="Galería Café 20Trece, vista {{ $index + 1 }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- ===== Visit: 3 contact cards ===== --}}
            <section id="visitanos" class="relative mt-20 overflow-hidden bg-gradient-to-b from-transparent via-crema/55 to-transparent py-16 lg:py-20">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="u-reveal mb-10 max-w-2xl">
                        <h2 class="font-serif text-3xl font-semibold tracking-tight text-espresso sm:text-4xl">Visítanos en Zona Centro</h2>
                        <p class="mt-3 text-base leading-7 text-mocha">
                            Estamos a unos pasos del jardín principal de San Miguel de Allende.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div class="u-reveal u-card p-7" data-delay="1">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-terracotta/12 text-terracotta">
                                <flux:icon.map-pin class="size-6" />
                            </span>
                            <h3 class="mt-5 text-lg font-bold text-espresso">Dirección</h3>
                            <p class="mt-2 text-sm leading-7 text-mocha">
                                Juárez 21, Zona Centro<br>
                                37700 San Miguel de Allende, Gto.
                            </p>
                        </div>

                        <div class="u-reveal u-card p-7" data-delay="2">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-terracotta/12 text-terracotta">
                                <flux:icon.phone class="size-6" />
                            </span>
                            <h3 class="mt-5 text-lg font-bold text-espresso">Teléfono</h3>
                            <p class="mt-2 text-sm leading-7 text-mocha">Llámanos para pedidos y reservas.</p>
                            <a href="tel:+524151194612" class="mt-2 inline-flex font-bold text-terracotta underline-offset-4 transition hover:underline">415 119 4612</a>
                        </div>

                        <div class="u-reveal u-card p-7" data-delay="3">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-terracotta/12 text-terracotta">
                                <flux:icon.clock class="size-6" />
                            </span>
                            <h3 class="mt-5 text-lg font-bold text-espresso">Horario</h3>
                            <p class="mt-2 text-sm leading-7 text-mocha">
                                Lunes a domingo<br>
                                6:30 a.m. a 10:00 p.m.<br>
                                <span class="text-xs text-mocha/80">Los horarios pueden variar.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Rewards / loyalty band ===== --}}
            <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="u-reveal overflow-hidden rounded-[2.25rem] bg-espresso text-vanilla">
                    <div class="grid items-center gap-8 p-8 sm:p-12 lg:grid-cols-[1.3fr_1fr]">
                        <div>
                            <span class="u-eyebrow text-caramel">
                                <flux:icon.gift class="size-4" /> Programa de recompensas
                            </span>
                            <h2 class="mt-5 font-serif text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                                Cada visita suma saldo a tu favor.
                            </h2>
                            <p class="mt-4 max-w-lg text-base leading-7 text-sand/80">
                                Regístrate una vez, recibe tu QR y consulta tu saldo, nivel y bebidas favoritas cuando quieras.
                            </p>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('public.register') }}" class="u-btn u-btn--accent">
                                    <flux:icon.qr-code class="size-5" /> Obtener mi QR
                                </a>
                                <a href="{{ route('public.rewards') }}" class="u-btn border border-white/25 bg-white/5 text-vanilla hover:bg-white/10">
                                    Ver recompensas <flux:icon.arrow-right class="size-5" />
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            @foreach ([['Cobre', '5%'], ['Plata', '10%'], ['Oro', '15%']] as $tier)
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-5 text-center">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-caramel">{{ $tier[0] }}</p>
                                    <p class="mt-2 font-serif text-2xl font-semibold">{{ $tier[1] }}</p>
                                    <p class="mt-1 text-[11px] text-sand/70">bonificación</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Community / social ===== --}}
            <section class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="u-reveal mb-8 max-w-2xl">
                    <h2 class="font-serif text-3xl font-semibold tracking-tight text-espresso sm:text-4xl">Síguenos y compártenos</h2>
                    <p class="mt-3 text-base leading-7 text-mocha">Reseñas, fotos y novedades de la comunidad 20Trece.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $socials = [
                            ['Instagram', 'instagram', 'https://www.instagram.com/20trecesma/'],
                            ['Facebook', 'facebook', 'https://www.facebook.com/p/Cafeter%C3%ADa-20-Trece-61554300671726/'],
                            ['TripAdvisor', 'tripadvisor', 'https://www.tripadvisor.com.mx/Restaurant_Review-g151932-d27092190-Reviews-Cafeteria_20trece-San_Miguel_de_Allende_Central_Mexico_and_Gulf_Coast.html'],
                            ['Google', 'google', 'https://search.google.com/local/writereview?placeid=ChIJ-YDfJtlRK4QRi4sqs3hbzBA'],
                        ];
                    @endphp

                    @foreach ($socials as $i => $social)
                        <a
                            href="{{ $social[2] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="u-reveal u-card group flex items-center justify-between px-5 py-5"
                            data-delay="{{ min($i + 1, 3) }}"
                        >
                            <span class="inline-flex items-center gap-3 font-bold text-espresso">
                                <span class="flex size-10 items-center justify-center rounded-full bg-cacao/8 transition group-hover:bg-terracotta/12">
                                    <img src="https://cdn.simpleicons.org/{{ $social[1] }}/6b4226" alt="" class="size-5" loading="lazy">
                                </span>
                                {{ $social[0] }}
                            </span>
                            <flux:icon.arrow-up-right class="size-5 text-mocha transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-terracotta" />
                        </a>
                    @endforeach
                </div>
            </section>
        </main>

        <x-public.footer />

        <x-public.cookie-banner />

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
