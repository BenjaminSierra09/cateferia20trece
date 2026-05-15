<x-public-layout
    title="Registro de cliente"
    description="Regístrate como cliente y obtén tu QR público para consultar saldo, recompensas e historial."
>
    <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-[2rem] border border-white/60 bg-white/80 p-8 shadow-xl shadow-[#8B5E34]/10 backdrop-blur">
            <span class="inline-flex rounded-full border border-[#8B5E34]/15 bg-[#8B5E34]/10 px-4 py-2 text-sm font-semibold text-[#6F4324]">
                <flux:icon.user-plus class="mr-2 size-4" /> Registro público
            </span>

            <h1 class="mt-4 text-4xl font-black tracking-tight">Obtén tu QR de cliente</h1>
            <p class="mt-4 max-w-2xl text-base leading-8 text-[#6B5B4A]">
                Regístrate una sola vez para consultar tu cuenta, recompensas y compras recientes desde cualquier dispositivo.
            </p>

            @if ($errors->any())
                <div class="mt-6 rounded-[1.5rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                    Revisa la información capturada e intenta nuevamente.
                </div>
            @endif

            <form method="POST" action="{{ route('public.register.store') }}" id="public-customer-registration-form" class="mt-8 space-y-5">
                @csrf

                <flux:input
                    name="name"
                    label="Nombre completo"
                    :value="old('name')"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="María López"
                />

                <x-phone-input
                    label="Teléfono"
                    name="phone"
                    :value="old('phone')"
                    placeholder="+52 415 123 4567"
                />

                <flux:input
                    name="email"
                    label="Correo electrónico"
                    :value="old('email')"
                    type="email"
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                />

                <flux:input
                    name="birthday"
                    label="Fecha de nacimiento"
                    :value="old('birthday')"
                    type="date"
                    autocomplete="bday"
                    max="{{ now()->toDateString() }}"
                />

                <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="{{ old('recaptcha_token') }}">

                <div class="flex items-start gap-3 rounded-[1.5rem] border border-[#8B5E34]/10 bg-[#F7F1E8]/80 px-4 py-4">
                    <input
                        id="privacy_consent"
                        name="privacy_consent"
                        type="checkbox"
                        value="1"
                        @checked(old('privacy_consent'))
                        class="mt-1 size-4 rounded border border-[#8B5E34]/30 text-[#6F4324] accent-[#6F4324] focus:ring-2 focus:ring-[#8B5E34]/30"
                    >
                    <label for="privacy_consent" class="cursor-pointer text-sm leading-7 text-[#6B5B4A]">
                        Acepto el <a href="{{ route('public.privacy') }}" class="font-semibold text-[#6F4324] underline underline-offset-4">aviso de privacidad</a> y autorizo el uso de mis datos para administrar mi cuenta de cliente.
                    </label>
                </div>

                @error('privacy_consent')
                    <p class="text-sm font-medium text-rose-700">{{ $message }}</p>
                @enderror

                @error('recaptcha')
                    <p class="text-sm font-medium text-rose-700">{{ $message }}</p>
                @enderror

                <div id="recaptcha-status" class="hidden rounded-[1.5rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"></div>

                <div class="flex flex-wrap gap-3">
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto" data-test="public-register-button">
                        Obtener mi QR
                    </flux:button>

                    <a
                        href="{{ route('public.lookup') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-[#8B5E34]/20 bg-white px-5 py-3 text-sm font-bold text-[#6F4324] transition hover:bg-[#8B5E34]/5 sm:w-auto"
                    >
                        <flux:icon.qr-code class="size-4" /> Ya tengo QR
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.sparkles class="size-6 text-[#8B5E34]" /> Qué recibes al registrarte</h2>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-[#6B5B4A]">
                    <li>Tu QR personal listo para mostrar en sucursal.</li>
                    <li>Acceso a saldo a favor, visitas y recompensas.</li>
                    <li>Historial reciente de compras y bebidas favoritas.</li>
                </ul>
            </article>

            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.shield-check class="size-6 text-[#8B5E34]" /> Seguridad</h2>
                <p class="mt-4 text-sm leading-7 text-[#6B5B4A]">
                    Este formulario puede validar Recaptcha de Google para reducir registros automatizados. En ambientes locales o de prueba puede permanecer desactivado.
                </p>
            </article>
        </div>
    </section>

    @if ($recaptchaSiteKey)
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
            <script>
                (() => {
                    const form = document.getElementById('public-customer-registration-form');
                    const tokenInput = document.getElementById('recaptcha_token');
                    const statusBox = document.getElementById('recaptcha-status');
                    let isSubmitting = false;

                    form?.addEventListener('submit', (event) => {
                        if (isSubmitting) {
                            return;
                        }

                        event.preventDefault();

                        if (! window.grecaptcha) {
                            statusBox.textContent = 'No fue posible cargar la validación de seguridad. Intenta de nuevo.';
                            statusBox.classList.remove('hidden');

                            return;
                        }

                        window.grecaptcha.ready(() => {
                            window.grecaptcha.execute('{{ $recaptchaSiteKey }}', {
                                action: '{{ \App\Http\Requests\PublicCustomerRegistrationRequest::RECAPTCHA_ACTION }}',
                            }).then((token) => {
                                tokenInput.value = token;
                                isSubmitting = true;
                                form.submit();
                            }).catch(() => {
                                statusBox.textContent = 'No fue posible completar la validación de seguridad. Intenta de nuevo.';
                                statusBox.classList.remove('hidden');
                            });
                        });
                    });
                })();
            </script>
        @endpush
    @endif
</x-public-layout>
