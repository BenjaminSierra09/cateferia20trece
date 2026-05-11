<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Configuración de seguridad') }}</flux:heading>

    <x-settings.layout :heading="__('Actualizar contraseña')" :subheading="__('Asegúrate de usar una contraseña larga y segura para proteger tu cuenta')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Contraseña actual')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('Nueva contraseña')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">{{ __('Guardar') }}</flux:button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <flux:heading>{{ __('Autenticación de dos factores') }}</flux:heading>
                <flux:subheading>{{ __('Administra la configuración de autenticación de dos factores') }}</flux:subheading>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <flux:text>
                                {{ __('Durante el inicio de sesión se te solicitará un código seguro generado por tu aplicación autenticadora compatible con TOTP.') }}
                            </flux:text>

                            <div class="flex justify-start">
                                <flux:button
                                    variant="danger"
                                    wire:click="disable"
                                >
                                    {{ __('Desactivar 2FA') }}
                                </flux:button>
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>
                        </div>
                    @else
                        <div class="space-y-4">
                            <flux:text variant="subtle">
                                {{ __('Al activar la autenticación de dos factores, durante el inicio de sesión se te solicitará un código seguro generado por tu aplicación autenticadora compatible con TOTP.') }}
                            </flux:text>

                            <flux:button
                                variant="primary"
                                wire:click="enable"
                            >
                                {{ __('Activar 2FA') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            </section>

            <flux:modal
                name="two-factor-setup-modal"
                class="max-w-md md:min-w-md"
                @close="closeModal"
                wire:model="showModal"
            >
                <div class="space-y-6">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="p-0.5 w-auto rounded-full border border-stone-100 dark:border-stone-600 bg-white dark:bg-stone-800 shadow-sm">
                            <div class="p-2.5 rounded-full border border-stone-200 dark:border-stone-600 overflow-hidden bg-stone-100 dark:bg-stone-200 relative">
                                <div class="flex items-stretch absolute inset-0 w-full h-full divide-x [&>div]:flex-1 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <div></div>
                                    @endfor
                                </div>

                                <div class="flex flex-col items-stretch absolute w-full h-full divide-y [&>div]:flex-1 inset-0 divide-stone-200 dark:divide-stone-300 justify-around opacity-50">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <div></div>
                                    @endfor
                                </div>

                                <flux:icon.qr-code class="relative z-20 dark:text-accent-foreground"/>
                            </div>
                        </div>

                        <div class="space-y-2 text-center">
                            <flux:heading size="lg">{{ $this->modalConfig['title'] }}</flux:heading>
                            <flux:text>{{ $this->modalConfig['description'] }}</flux:text>
                        </div>
                    </div>

                    @if ($showVerificationStep)
                        <div class="space-y-6">
                            <div
                                class="flex flex-col items-center space-y-3 justify-center"
                                x-data
                                x-init="$nextTick(() => $el.querySelector('input')?.focus())"
                            >
                                <flux:otp
                                    name="code"
                                    wire:model="code"
                                    length="6"
                                    label="Código OTP"
                                    label:sr-only
                                    class="mx-auto"
                                />
                            </div>

                            <div class="flex items-center space-x-3">
                                <flux:button
                                    variant="outline"
                                    class="flex-1"
                                    wire:click="resetVerification"
                                >
                                    {{ __('Volver') }}
                                </flux:button>

                                <flux:button
                                    variant="primary"
                                    class="flex-1"
                                    wire:click="confirmTwoFactor"
                                    x-bind:disabled="$wire.code.length < 6"
                                >
                                    {{ __('Confirmar') }}
                                </flux:button>
                            </div>
                        </div>
                    @else
                        @error('setupData')
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}"/>
                        @enderror

                        <div class="flex justify-center">
                            <div class="relative w-64 overflow-hidden border rounded-lg border-stone-200 dark:border-stone-700 aspect-square">
                                @empty($qrCodeSvg)
                                    <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700 animate-pulse">
                                        <flux:icon.loading/>
                                    </div>
                                @else
                                <div x-data class="flex items-center justify-center h-full p-4">
                                    <div
                                        class="bg-white p-3 rounded"
                                        :style="($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'filter: invert(1) brightness(1.5)' : ''"
                                    >
                                            {!! $qrCodeSvg !!}
                                        </div>
                                    </div>
                                @endempty
                            </div>
                        </div>

                        <div>
                            <flux:button
                                :disabled="$errors->has('setupData')"
                                variant="primary"
                                class="w-full"
                                wire:click="showVerificationIfNecessary"
                            >
                                {{ $this->modalConfig['buttonText'] }}
                            </flux:button>
                        </div>

                        <div class="space-y-4">
                            <div class="relative flex items-center justify-center w-full">
                                <div class="absolute inset-0 w-full h-px top-1/2 bg-stone-200 dark:bg-stone-600"></div>
                                <span class="relative px-2 text-sm bg-white dark:bg-stone-800 text-stone-600 dark:text-stone-400">
                                    {{ __('o ingresa el código manualmente') }}
                                </span>
                            </div>

                            <div
                                class="flex items-center space-x-2"
                                x-data="{
                                    copied: false,
                                    async copy() {
                                        try {
                                            await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 1500);
                                        } catch (e) {
                                            console.warn('Could not copy to clipboard');
                                        }
                                    }
                                }"
                            >
                                <div class="flex items-stretch w-full border rounded-xl dark:border-stone-700">
                                    @empty($manualSetupKey)
                                        <div class="flex items-center justify-center w-full p-3 bg-stone-100 dark:bg-stone-700">
                                            <flux:icon.loading variant="mini"/>
                                        </div>
                                    @else
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ $manualSetupKey }}"
                                            class="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100"
                                        />

                                        <button
                                            @click="copy()"
                                            class="px-3 transition-colors border-l cursor-pointer border-stone-200 dark:border-stone-600"
                                        >
                                            <flux:icon.document-duplicate x-show="!copied" variant="outline"></flux:icon>
                                            <flux:icon.check
                                                x-show="copied"
                                                variant="solid"
                                                class="text-green-500"
                                            ></flux:icon>
                                        </button>
                                    @endempty
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:modal>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-12" x-data="passkeysManager()">
                <flux:heading>{{ __('Passkeys') }}</flux:heading>
                <flux:subheading>
                    {{ __('Registra claves de acceso para iniciar sesión con Face ID, Touch ID, Windows Hello o llaves físicas de seguridad.') }}
                </flux:subheading>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                        <flux:input
                            x-model="name"
                            :label="__('Nombre del dispositivo')"
                            :placeholder="__('Ej. MacBook de caja')"
                        />

                        <div class="flex items-end">
                            <flux:button
                                variant="primary"
                                type="button"
                                x-bind:disabled="isRegistering || name.trim() === ''"
                                x-on:click="register($wire)"
                            >
                                <span x-show="!isRegistering">{{ __('Registrar passkey') }}</span>
                                <span x-show="isRegistering">{{ __('Registrando...') }}</span>
                            </flux:button>
                        </div>
                    </div>

                    <template x-if="error !== ''">
                        <flux:callout color="red" icon="x-circle" x-text="error"></flux:callout>
                    </template>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($this->passkeys as $passkey)
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <div>
                                <div class="font-medium">{{ $passkey['name'] }}</div>
                                <div class="text-sm text-zinc-500">
                                    {{ $passkey['authenticator'] ?: __('Autenticador desconocido') }}
                                </div>
                                <div class="text-xs text-zinc-400 mt-1">
                                    {{ __('Creada: :date', ['date' => $passkey['created_at'] ?: __('Sin fecha')]) }}
                                    ·
                                    {{ __('Último uso: :date', ['date' => $passkey['last_used_at'] ?: __('Sin uso')]) }}
                                </div>
                            </div>

                            <flux:button
                                variant="danger"
                                size="sm"
                                type="button"
                                x-on:click="destroy({{ $passkey['id'] }}, $wire)"
                                x-bind:disabled="isDeletingId === {{ $passkey['id'] }}"
                            >
                                <span x-show="isDeletingId !== {{ $passkey['id'] }}">{{ __('Eliminar') }}</span>
                                <span x-show="isDeletingId === {{ $passkey['id'] }}">{{ __('Eliminando...') }}</span>
                            </flux:button>
                        </div>
                    @empty
                        <flux:callout color="sky" icon="information-circle">
                            {{ __('Aún no tienes passkeys registradas.') }}
                        </flux:callout>
                    @endforelse
                </div>
            </section>
        @endif
    </x-settings.layout>
</section>
