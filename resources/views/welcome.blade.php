<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="20Trece" />
        <link rel="manifest" href="/site.webmanifest" />

        @fonts

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

        @vite('resources/css/app.css')

        <style>
            @keyframes home-gallery-scroll-left {
                from {
                    transform: translate3d(0, 0, 0);
                }

                to {
                    transform: translate3d(-50%, 0, 0);
                }
            }

            @keyframes home-gallery-scroll-right {
                from {
                    transform: translate3d(-50%, 0, 0);
                }

                to {
                    transform: translate3d(0, 0, 0);
                }
            }

            .home-gallery-track {
                width: max-content;
                will-change: transform;
            }

            .home-gallery-track--left {
                animation: home-gallery-scroll-left 42s linear infinite;
            }

            .home-gallery-track--right {
                animation: home-gallery-scroll-right 48s linear infinite;
            }

            .home-gallery-marquee:hover .home-gallery-track {
                animation-play-state: paused;
            }
        </style>
    </head>

    <body class="relative min-h-screen flex flex-col overflow-x-hidden bg-[#F7F1E8] text-[#2A2118] dark:bg-[#0f0b08] dark:text-[#F8EFE5] selection:bg-[#8B5E34] selection:text-white">


        <!-- Background decoration -->
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-[#C49A6C]/25 blur-3xl"></div>
            <div class="absolute top-40 -right-32 h-96 w-96 rounded-full bg-[#8B5E34]/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-[#E9D8C3]/40 blur-3xl dark:bg-[#3A2417]/40"></div>
        </div>

        <header class="sticky top-0 z-30 border-b border-[#8B5E34]/10 bg-white/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img
                        src="{{ asset('logotipo.png') }}"
                        alt="Café 20Trece Logo"
                        class="h-12 w-12 rounded-full object-contain ring-4 ring-white/70"
                    >
                    <div>
                        <p class="text-base font-black text-[#2A2118]">Café 20Trece</p>
                        <p class="text-xs font-medium text-[#8B5E34]">Café orgánico en San Miguel</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-2 lg:flex">
                    <a href="#galeria" class="rounded-full px-4 py-2 text-sm font-semibold text-[#6B5B4A] transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]">Galería</a>
                    <a href="#visitanos" class="rounded-full px-4 py-2 text-sm font-semibold text-[#6B5B4A] transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]">Visítanos</a>
                    <a href="{{ route('public.lookup') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#6B5B4A] transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]">Mi cuenta QR</a>
                    <a href="{{ route('public.rewards') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-[#6B5B4A] transition hover:bg-[#8B5E34]/10 hover:text-[#6F4324]">Recompensas</a>
                </nav>

                <a
                    href="tel:+524151194612"
                    class="hidden rounded-full bg-[#6F4324] px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-[#6F4324]/20 transition hover:bg-[#5D351C] sm:inline-flex"
                >
                    Llamar
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 flex-1 w-full">
            <!-- Hero Section -->
            <section class="mb-12  border border-white/60 bg-white/70 ">
                <div class="mx-auto max-w-6xl">
                    <div class="relative overflow-hidden dark:bg-white/[0.04]">

                        <div class="grid lg:grid-cols-2 gap-0">
                            <!-- Left Content -->
                            <div class="px-6 py-14 text-center sm:px-10 lg:py-20 lg:text-left flex flex-col justify-center">

                                <p class="mb-6 inline-flex items-center justify-center rounded-full border border-[#8B5E34]/20 bg-[#8B5E34]/10 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-[#6F4324] dark:border-[#C49A6C]/30 dark:bg-[#C49A6C]/10 dark:text-[#E6C39E] lg:justify-start">
                                    ✨ Café Premium Orgánico
                                </p>

                                <p class="mb-6 text-lg font-semibold text-[#8B5E34] dark:text-[#E6C39E] lg:mx-0">
                                    Hecho para los que aprecian la calidad
                                </p>

                                <h1 class="mx-auto text-5xl font-black tracking-tight text-[#2A2118] dark:text-white sm:text-6xl lg:text-7xl lg:mx-0 leading-tight">
                                    Café 20Trece
                                </h1>

                                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-[#6B5B4A] dark:text-[#D8C8B8] lg:mx-0">
                                    Café orgánico en grano o molido, servido en el corazón de San Miguel de Allende.
                                </p>

                                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row lg:justify-start">
                                    <a
                                        href="tel:+524151194612"
                                        class="inline-flex w-full items-center justify-center rounded-full bg-[#6F4324] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#6F4324]/25 transition hover:-translate-y-0.5 hover:bg-[#5D351C] sm:w-auto"
                                    >
                                        Llamar ahora
                                    </a>

                                    <a
                                        href="https://search.google.com/local/writereview?placeid=ChIJ-YDfJtlRK4QRi4sqs3hbzBA"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex w-full items-center justify-center rounded-full border border-[#8B5E34]/30 bg-white/70 px-7 py-3.5 text-sm font-bold text-[#6F4324] transition hover:-translate-y-0.5 hover:bg-white dark:border-white/15 dark:bg-white/5 dark:text-[#E6C39E] dark:hover:bg-white/10 sm:w-auto"
                                    >
                                        Dejar reseña
                                    </a>
                                </div>

                                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                                    <div class="rounded-2xl border border-white/60 bg-white/70 px-4 py-4 text-center lg:text-left">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]">Horario</p>
                                        <p class="mt-2 text-lg font-black">6:30 a.m. - 10:00 p.m.</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/60 bg-white/70 px-4 py-4 text-center lg:text-left">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]">Ubicación</p>
                                        <p class="mt-2 text-lg font-black">Juárez 21</p>
                                    </div>
                                    <div class="rounded-2xl border border-white/60 bg-white/70 px-4 py-4 text-center lg:text-left">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8B5E34]">Programa</p>
                                        <p class="mt-2 text-lg font-black">Saldo + QR</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Image -->
                            <div class="hidden lg:flex items-center justify-center overflow-hidden">
                                <img
                                    src="{{ asset('—Pngtree—delicious cappuccino coffee cup with_19991380.webp') }}"
                                    alt="Cappuccino Café 20Trece"
                                    class="h-full w-full object-contain transition-transform duration-300 hover:scale-105"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if (! empty($galleryImages ?? []))
                <section id="galeria" class="px-6 pb-14 lg:px-8">
                    <div class="mx-auto max-w-6xl">
                        <div class="mb-6 flex flex-col gap-2 text-center">
                            <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#8B5E34]">Galería</p>
                            <h2 class="text-3xl font-black tracking-tight sm:text-4xl">Momentos y rincones de 20Trece</h2>
                            <p class="mx-auto max-w-2xl text-sm leading-7 text-[#6B5B4A]">
                                Una franja viva de la cafetería: bebidas, ambiente y pequeños detalles que acompañan la experiencia.
                            </p>
                        </div>

                        @php
                            $galleryLoop = array_merge($galleryImages, $galleryImages);
                        @endphp

                        <div class="home-gallery-marquee space-y-4 overflow-hidden">
                            <div class="home-gallery-track home-gallery-track--left flex gap-4 pr-4">
                                @foreach ($galleryLoop as $index => $image)
                                    <div class="h-36 w-28 shrink-0 overflow-hidden rounded-[1.5rem] border border-white/60 bg-white/70 shadow-lg shadow-[#8B5E34]/10 sm:h-44 sm:w-36 lg:h-52 lg:w-40">
                                        <img
                                            src="{{ $image }}"
                                            alt="Galería Café 20Trece {{ $index + 1 }}"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                @endforeach
                            </div>

                            <div class="home-gallery-track home-gallery-track--right flex gap-4 pr-4">
                                @foreach (array_reverse($galleryLoop) as $index => $image)
                                    <div class="h-28 w-36 shrink-0 overflow-hidden rounded-[1.5rem] border border-white/60 bg-white/70 shadow-lg shadow-[#8B5E34]/10 sm:h-36 sm:w-44 lg:h-40 lg:w-52">
                                        <img
                                            src="{{ $image }}"
                                            alt="Galería Café 20Trece alternativa {{ $index + 1 }}"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <!-- Contact Section -->
<!-- Info Background Wrapper -->
<div class="relative overflow-hidden">
    <!-- Coffee background image -->
    <div
        class="pointer-events-none absolute inset-0 z-[-1] bg-center bg-no-repeat opacity-35 dark:opacity-20"
        style="
            background-image: url('{{ asset('fondo-coffe.webp') }}');
            background-size: cover;
        "
    ></div>

    <!-- Soft overlay so text/cards stay readable -->
    <div class="pointer-events-none absolute inset-0 z-0"></div>

    <!-- Contact Section -->
    <section id="visitanos" class="relative z-10 px-6 lg:px-8 py-12">
        <div class="mx-auto max-w-5xl">
            <div class="mb-10 text-center">
                <span class="text-sm font-bold uppercase tracking-[0.25em] text-[#8B5E34] dark:text-[#C49A6C]">
                    Visítanos
                </span>
                <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                    Estamos en Zona Centro
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Address -->
                <div class="group rounded-3xl border border-white/70 bg-white/75 p-8 text-center shadow-xl shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#8B5E34]/10 dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#8B5E34]/10 text-3xl transition group-hover:scale-110 dark:bg-[#C49A6C]/10">
                        📍
                    </div>
                    <h3 class="mb-3 text-lg font-black">Dirección</h3>
                    <p class="text-sm leading-7 text-[#6B5B4A] dark:text-[#D8C8B8]">
                        Juárez 21<br>
                        Zona Centro<br>
                        37700 San Miguel de Allende, Gto.
                    </p>
                </div>

                <!-- Phone -->
                <div class="group rounded-3xl border border-white/70 bg-white/75 p-8 text-center shadow-xl shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#8B5E34]/10 dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#8B5E34]/10 text-3xl transition group-hover:scale-110 dark:bg-[#C49A6C]/10">
                        ☎️
                    </div>
                    <h3 class="mb-3 text-lg font-black">Teléfono</h3>
                    <a
                        href="tel:+524151194612"
                        class="font-bold text-[#6F4324] underline-offset-4 transition hover:text-[#8B5E34] hover:underline dark:text-[#E6C39E]"
                    >
                        415 119 4612
                    </a>
                </div>

                <!-- Hours -->
                <div class="group rounded-3xl border border-white/70 bg-white/75 p-8 text-center shadow-xl shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#8B5E34]/10 dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#8B5E34]/10 text-3xl transition group-hover:scale-110 dark:bg-[#C49A6C]/10">
                        ⏰
                    </div>
                    <h3 class="mb-3 text-lg font-black">Horario</h3>
                    <p class="text-sm leading-7 text-[#6B5B4A] dark:text-[#D8C8B8]">
                        Lunes a Domingo<br>
                        6:30 a.m. – 10:00 p.m.<br>
                        <span class="text-xs text-[#8B7A68] dark:text-[#A89888]">
                            *Horarios pueden variar
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Links -->
    <section class="relative z-10 px-6 lg:px-8 py-12 pb-20">
        <div class="mx-auto max-w-5xl">
            <div class="mb-8 text-center">
                <span class="text-sm font-bold uppercase tracking-[0.25em] text-[#8B5E34] dark:text-[#C49A6C]">
                    Comunidad
                </span>
                <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                    Síguenos y compártenos
                </h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    href="https://www.instagram.com/20trecesma/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center justify-between rounded-2xl border border-white/70 bg-white/75 px-5 py-4 font-bold shadow-lg shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:bg-white hover:shadow-xl dark:border-white/10 dark:bg-white/[0.04] dark:hover:bg-white/10"
                >
                    <span class="inline-flex items-center gap-3">
                        <span class="text-2xl"><i class="fa-brands fa-instagram"></i></span>
                        Instagram
                    </span>
                    <span class="text-[#8B5E34] transition group-hover:translate-x-1 dark:text-[#C49A6C]">→</span>
                </a>

                <a
                    href="https://www.facebook.com/p/Cafeter%C3%ADa-20-Trece-61554300671726/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center justify-between rounded-2xl border border-white/70 bg-white/75 px-5 py-4 font-bold shadow-lg shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:bg-white hover:shadow-xl dark:border-white/10 dark:bg-white/[0.04] dark:hover:bg-white/10"
                >
                    <span class="inline-flex items-center gap-3">
                        <span class="text-2xl"><i class="fa-brands fa-facebook-f"></i></span>
                        Facebook
                    </span>
                    <span class="text-[#8B5E34] transition group-hover:translate-x-1 dark:text-[#C49A6C]">→</span>
                </a>

                <a
                    href="https://www.tripadvisor.com.mx/Restaurant_Review-g151932-d27092190-Reviews-Cafeteria_20trece-San_Miguel_de_Allende_Central_Mexico_and_Gulf_Coast.html"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center justify-between rounded-2xl border border-white/70 bg-white/75 px-5 py-4 font-bold shadow-lg shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:bg-white hover:shadow-xl dark:border-white/10 dark:bg-white/[0.04] dark:hover:bg-white/10"
                >
                    <span class="inline-flex items-center gap-3">
                        <span class="text-2xl"><i class="fa-brands fa-tripadvisor"></i></span>
                        TripAdvisor
                    </span>
                    <span class="text-[#8B5E34] transition group-hover:translate-x-1 dark:text-[#C49A6C]">→</span>
                </a>

                <a
                    href="https://search.google.com/local/writereview?placeid=ChIJ-YDfJtlRK4QRi4sqs3hbzBA"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center justify-between rounded-2xl border border-white/70 bg-white/75 px-5 py-4 font-bold shadow-lg shadow-[#8B5E34]/5 backdrop-blur transition hover:-translate-y-1 hover:bg-white hover:shadow-xl dark:border-white/10 dark:bg-white/[0.04] dark:hover:bg-white/10"
                >
                    <span class="inline-flex items-center gap-3">
                        <span class="text-2xl"><i class="fa-brands fa-google"></i></span>
                        Google
                    </span>
                    <span class="text-[#8B5E34] transition group-hover:translate-x-1 dark:text-[#C49A6C]">→</span>
                </a>
            </div>
        </div>
    </section>
</div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-[#8B5E34]/10 bg-white/40 backdrop-blur dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto max-w-5xl px-6 py-8 text-center text-sm text-[#6B5B4A] dark:text-[#D8C8B8] lg:px-8">
                <p>© 2026 Café 20Trece. Todos los derechos reservados.</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs font-semibold uppercase tracking-[0.14em] text-[#8B5E34] dark:text-[#C49A6C]">
                    <a href="{{ route('public.lookup') }}" class="transition hover:text-[#6F4324]">Mi cuenta QR</a>
                    <a href="{{ route('public.rewards') }}" class="transition hover:text-[#6F4324]">Recompensas</a>
                    <a href="{{ route('public.terms') }}" class="transition hover:text-[#6F4324]">Términos</a>
                    <a href="{{ route('public.privacy') }}" class="transition hover:text-[#6F4324]">Privacidad</a>
                </div>
            </div>
        </footer>

    </body>
</html>
