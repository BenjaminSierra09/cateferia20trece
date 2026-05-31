<x-public-layout
    title="Registro de cliente"
    description="Regístrate como cliente y obtén tu QR público para consultar saldo, recompensas e historial."
>
    <section class="grid items-start gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="u-reveal u-card p-8 sm:p-10">
            <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
                <flux:icon.user-plus class="size-4" /> Registro público
            </span>

            <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.05] tracking-tight text-espresso sm:text-5xl">Obtén tu QR de cliente</h1>
            <p class="mt-4 max-w-xl text-base leading-8 text-mocha">
                Regístrate una sola vez para consultar tu cuenta, recompensas y compras recientes desde cualquier dispositivo.
            </p>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-300/70 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                    <span class="inline-flex items-center gap-2"><flux:icon.exclamation-triangle class="size-4" /> Revisa la información capturada e intenta nuevamente.</span>
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

                <div class="flex items-start gap-3 rounded-2xl border border-coffee/15 bg-crema/60 px-4 py-4">
                    <input
                        id="privacy_consent"
                        name="privacy_consent"
                        type="checkbox"
                        value="1"
                        @checked(old('privacy_consent'))
                        class="mt-1 size-4 rounded border border-coffee/40 text-cacao accent-[#6b4226] focus:ring-2 focus:ring-terracotta/30"
                    >
                    <label for="privacy_consent" class="cursor-pointer text-sm leading-7 text-mocha">
                        Acepto el <a href="{{ route('public.privacy') }}" class="font-semibold text-terracotta underline underline-offset-4">aviso de privacidad</a> y autorizo el uso de mis datos para administrar mi cuenta de cliente.
                    </label>
                </div>

                @error('privacy_consent')
                    <p class="text-sm font-medium text-rose-700">{{ $message }}</p>
                @enderror

                @error('recaptcha')
                    <p class="text-sm font-medium text-rose-700">{{ $message }}</p>
                @enderror

                <div id="recaptcha-status" class="hidden rounded-2xl border border-rose-300/70 bg-rose-50 px-5 py-4 text-sm text-rose-800"></div>

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <button type="submit" class="u-btn u-btn--accent" data-test="public-register-button">
                        <flux:icon.qr-code class="size-5" /> Obtener mi QR
                    </button>

                    <a href="{{ route('public.lookup') }}" class="u-btn u-btn--outline">
                        <flux:icon.qr-code class="size-5" /> Ya tengo QR
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <article class="u-reveal u-card p-7" data-delay="1">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.sparkles class="size-6 text-terracotta" /> Qué recibes al registrarte</h2>
                <ul class="mt-5 space-y-3 text-sm leading-7 text-mocha">
                    @foreach ([
                        'Tu QR personal listo para mostrar en sucursal.',
                        'Acceso a saldo a favor, visitas y recompensas.',
                        'Historial reciente de compras y bebidas favoritas.',
                    ] as $perk)
                        <li class="flex items-start gap-3">
                            <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" />
                            <span>{{ $perk }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="u-reveal u-card p-7" data-delay="2">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.shield-check class="size-6 text-terracotta" /> Seguridad</h2>
                <p class="mt-4 text-sm leading-7 text-mocha">
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
