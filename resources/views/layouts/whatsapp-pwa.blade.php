<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['whatsappPwa' => true])
    </head>
    <body class="min-h-dvh overflow-hidden bg-zinc-100 antialiased dark:bg-zinc-950">
        <div
            x-data="whatsappPwaNotifications()"
            x-init="init()"
            data-vapid-public-key="{{ config('webpush.vapid.public_key') }}"
            data-subscription-store-url="{{ route('whatsapp.pwa.push-subscription.store') }}"
            data-subscription-delete-url="{{ route('whatsapp.pwa.push-subscription.destroy') }}"
            class="flex h-dvh flex-col"
        >
            <header class="z-20 border-b border-emerald-950/15 bg-emerald-800 px-3 pt-[env(safe-area-inset-top)] text-white shadow-sm sm:px-5">
                <div class="flex min-h-16 items-center gap-3">
                    <img src="/favicon-96x96.png" alt="" class="size-10 rounded-full bg-white object-cover" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">WhatsApp 20Trece</p>
                        <p class="truncate text-xs text-emerald-100" x-text="statusText">Preparando notificaciones…</p>
                    </div>

                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="bell"
                        class="text-white hover:bg-white/10"
                        x-on:click="toggleNotifications()"
                        x-bind:disabled="busy || ['unavailable', 'unsupported', 'denied'].includes(status)"
                        aria-label="Configurar notificaciones"
                    >
                        <span class="hidden sm:inline" x-text="notificationButtonText">Notificaciones</span>
                    </flux:button>

                    <form method="POST" action="{{ route('logout') }}" x-on:submit="logout($event)">
                        @csrf
                        <flux:button
                            type="submit"
                            variant="ghost"
                            size="sm"
                            icon="arrow-right-start-on-rectangle"
                            class="text-white hover:bg-white/10"
                            aria-label="Cerrar sesión"
                        >
                            <span class="hidden sm:inline">Salir</span>
                        </flux:button>
                    </form>
                </div>

                <div
                    x-cloak
                    x-show="notice !== ''"
                    x-transition.opacity
                    class="mb-3 rounded-xl bg-white/10 px-3 py-2 text-xs text-emerald-50"
                    role="status"
                    x-text="notice"
                ></div>
            </header>

            <main class="min-h-0 flex-1">
                {{ $slot }}
            </main>

            @persist('toast')
                <flux:toast.group>
                    <flux:toast />
                </flux:toast.group>
            @endpersist
        </div>

        @fluxScripts
    </body>
</html>
