<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="shopping-bag" :href="route('dashboard.sales.index')" :current="request()->routeIs('dashboard.sales.*')" wire:navigate>
                    {{ __('Ventas') }}
                </flux:navbar.item>
                <flux:navbar.item icon="book-open" :href="route('dashboard.beverages.index')" :current="request()->routeIs('dashboard.beverages.*') || request()->routeIs('dashboard.products.*') || request()->routeIs('dashboard.categories.*') || request()->routeIs('dashboard.sizes.*')" wire:navigate>
                    {{ __('Catálogo') }}
                </flux:navbar.item>
                <flux:navbar.item icon="cog-6-tooth" :href="route('dashboard.branches.index')" :current="request()->routeIs('dashboard.branches.*') || request()->routeIs('dashboard.team.*') || request()->routeIs('dashboard.customizations.*')" wire:navigate>
                    {{ __('Configuración') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users" :href="route('dashboard.customers.index')" :current="request()->routeIs('dashboard.customers.*')" wire:navigate>
                    {{ __('Clientes') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Turno')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="clock"
                        :href="route('dashboard.work-session.check-in')"
                        wire:navigate
                        :label="__('Turno')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Reportes')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="chart-bar-square"
                        :href="route('dashboard.reports.index')"
                        wire:navigate
                        :label="__('Reportes')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('dashboard.sales.index')" :current="request()->routeIs('dashboard.sales.*')" wire:navigate>
                        {{ __('Ventas')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('dashboard.customers.index')" :current="request()->routeIs('dashboard.customers.*')" wire:navigate>
                        {{ __('Clientes')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.group expandable :heading="__('Personalizaciones')" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('dashboard.customizations.types.index')" :current="request()->routeIs('dashboard.customizations.types.*') || request()->routeIs('dashboard.customizations.index') || request()->routeIs('dashboard.customizations.create')" wire:navigate>
                            {{ __('Tipos') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="sparkles" :href="route('dashboard.customizations.options.index')" :current="request()->routeIs('dashboard.customizations.options.*')" wire:navigate>
                            {{ __('Opciones') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="clock" :href="route('dashboard.work-session.check-in')" wire:navigate>
                    {{ __('Turno') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="chart-bar-square" :href="route('dashboard.reports.index')" wire:navigate>
                    {{ __('Reportes') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
