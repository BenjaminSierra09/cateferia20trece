@if (request()->routeIs('whatsapp.pwa.login'))
    <x-layouts::whatsapp-auth :title="$title ?? null">
        {{ $slot }}
    </x-layouts::whatsapp-auth>
@else
    <x-layouts::auth.split :title="$title ?? null">
        {{ $slot }}
    </x-layouts::auth.split>
@endif
