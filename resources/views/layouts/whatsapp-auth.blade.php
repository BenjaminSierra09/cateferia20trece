<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['whatsappPwa' => true])
    </head>
    <body class="min-h-dvh bg-emerald-950 antialiased">
        <main class="relative grid min-h-dvh place-items-center overflow-hidden px-5 py-10 sm:px-8">
            <div class="absolute inset-x-0 top-0 h-72 bg-linear-to-b from-emerald-700/70 to-transparent"></div>
            <div class="absolute -start-24 top-16 size-72 rounded-full bg-emerald-400/15 blur-3xl"></div>
            <div class="absolute -end-24 bottom-10 size-80 rounded-full bg-orange-400/15 blur-3xl"></div>

            <section class="relative w-full max-w-md rounded-3xl border border-white/15 bg-white p-6 shadow-2xl shadow-black/30 sm:p-8 dark:bg-zinc-900">
                <div class="mb-7 flex flex-col items-center gap-3 text-center">
                    <img
                        src="/web-app-manifest-192x192.png"
                        alt="Café 20Trece"
                        class="size-20 rounded-2xl shadow-lg"
                    />
                    <div>
                        <p class="text-lg font-semibold text-zinc-950 dark:text-white">WhatsApp Café 20Trece</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Acceso privado para administradores</p>
                    </div>
                </div>

                {{ $slot }}
            </section>
        </main>

        @fluxScripts
    </body>
</html>
