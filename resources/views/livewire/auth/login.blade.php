<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Inicia sesión en tu cuenta')" :description="__('Ingresa tu usuario y contraseña para continuar')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" x-data="{ passkeyError: '', loadingPasskey: false }">
            @csrf

            <!-- Username -->
            <flux:input
                name="username"
                :label="__('Usuario')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username webauthn"
                placeholder="cafeteria20trece"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Contraseña')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Recordarme')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Entrar') }}
                </flux:button>
            </div>

            <flux:separator text="o" />

            <flux:button
                variant="outline"
                type="button"
                class="w-full"
                x-bind:disabled="loadingPasskey"
                x-on:click="
                    passkeyError = '';
                    loadingPasskey = true;
                    window.passkeysLogin()
                        .catch((error) => passkeyError = error?.message ?? 'No se pudo iniciar sesión con passkey.')
                        .finally(() => loadingPasskey = false);
                "
            >
                <span x-show="!loadingPasskey">Entrar con passkey</span>
                <span x-show="loadingPasskey">Verificando passkey...</span>
            </flux:button>

            <template x-if="passkeyError !== ''">
                <flux:callout color="red" icon="x-circle" x-text="passkeyError"></flux:callout>
            </template>
        </form>
    </div>
</x-layouts::auth>
