@php
    $isPosRoute = request()->routeIs('dashboard.sales.pos');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body
        x-data="{
            posSidebarOpen: @js(! $isPosRoute),
            isPosRoute: @js($isPosRoute),
            syncPosSidebar(open = null) {
                if (! this.isPosRoute) {
                    this.posSidebarOpen = true
                    return
                }

                this.posSidebarOpen = open ?? ! this.posSidebarOpen
                window.localStorage.setItem('posSidebarOpen', JSON.stringify(this.posSidebarOpen))
            },
            init() {
                if (this.isPosRoute) {
                    const stored = window.localStorage.getItem('posSidebarOpen')
                    this.posSidebarOpen = stored === null ? false : JSON.parse(stored)
                }
            }
        }"
        x-on:toggle-pos-sidebar.window="syncPosSidebar($event.detail?.open)"
        class="min-h-screen bg-white dark:bg-zinc-800"
    >
        <flux:sidebar
            sticky
            collapsible="mobile"
            x-bind:class="isPosRoute && ! posSidebarOpen ? 'hidden lg:hidden' : ''"
            class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
        >
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('General')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shopping-bag" :href="route('dashboard.sales.index')" :current="request()->routeIs('dashboard.sales.*')" wire:navigate>
                        {{ __('Ventas') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Menú')" class="grid">
                    <flux:sidebar.item icon="building-storefront" :href="route('dashboard.branches.index')" :current="request()->routeIs('dashboard.branches.*')" wire:navigate>
                        {{ __('Sucursales') }}
                    </flux:sidebar.item>
                    <flux:sidebar.group expandable :heading="__('Bebidas')" class="grid">
                    <flux:sidebar.item icon="sparkles" :href="route('dashboard.beverages.index')" :current="request()->routeIs('dashboard.beverages.*')" wire:navigate>
                        {{ __('Bebidas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('dashboard.categories.index')" :current="request()->routeIs('dashboard.categories.*')" wire:navigate>
                        {{ __('Categorías') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="beaker" :href="route('dashboard.sizes.index')" :current="request()->routeIs('dashboard.sizes.*')" wire:navigate>
                        {{ __('Tamaños') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard.customizations.types.index')" :current="request()->routeIs('dashboard.customizations.types.*') || request()->routeIs('dashboard.customizations.index') || request()->routeIs('dashboard.customizations.create')" wire:navigate>
                            {{ __('Complementos') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                    <flux:sidebar.item icon="cube" :href="route('dashboard.products.index')" :current="request()->routeIs('dashboard.products.*')" wire:navigate>
                        {{ __('Productos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Relaciones')" class="grid">
                    <flux:sidebar.item icon="users" :href="route('dashboard.customers.index')" :current="request()->routeIs('dashboard.customers.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="route('dashboard.aztec-symbols.index')" :current="request()->routeIs('dashboard.aztec-symbols.*')" wire:navigate>
                        {{ __('Símbolos aztecas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('dashboard.team.index')" :current="request()->routeIs('dashboard.team.*')" wire:navigate>
                        {{ __('Equipo') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Análisis')" class="grid">
                    <flux:sidebar.item icon="chart-bar-square" :href="route('dashboard.reports.index')" :current="request()->routeIs('dashboard.reports.index')" wire:navigate>
                        {{ __('Reportes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('dashboard.reports.shifts')" :current="request()->routeIs('dashboard.reports.shifts')" wire:navigate>
                        {{ __('Turnos') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
