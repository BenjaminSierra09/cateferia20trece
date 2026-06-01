<x-public-layout
    title="Facturación"
    description="Solicita tu factura (CFDI) de Café 20Trece con tus datos fiscales y el código de facturación."
>
    @php
        $inputClass = 'w-full rounded-2xl border border-coffee/25 bg-white px-4 py-3 text-base text-espresso shadow-sm outline-none transition placeholder:text-mocha/55 focus:border-terracotta focus:ring-4 focus:ring-terracotta/15';
        $tokenValue = old('billing_token', $billingToken);
        $paymentFormValue = old('invoice_payment_method', $suggestedPaymentForm);
    @endphp

    <section class="grid items-start gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="u-card p-8 sm:p-10">
            <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
                <flux:icon.document-text class="size-4" /> Facturación
            </span>

            <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.05] tracking-tight text-espresso sm:text-5xl">Solicita tu factura</h1>
            <p class="mt-4 max-w-xl text-base leading-8 text-mocha">
                Captura tus datos fiscales y el código de facturación de tu ticket. Emitiremos tu CFDI y lo enviaremos al correo que indiques.
            </p>

            @if (session('invoice_status'))
                <div class="mt-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm font-medium text-sage">
                    <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-5" /> {{ session('invoice_status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-300/70 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                    <span class="inline-flex items-center gap-2"><flux:icon.exclamation-triangle class="size-5" /> Revisa los campos marcados e intenta nuevamente.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('public.invoice.store') }}" class="mt-8 space-y-5" data-invoice-form novalidate>
                @csrf

                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <label>No llenar este campo
                        <input type="text" name="website" tabindex="-1" autocomplete="off" value="{{ old('website') }}">
                    </label>
                </div>

                <div>
                    <label for="inv-token" class="mb-2 block text-sm font-semibold text-cacao">Código de facturación</label>
                    <input id="inv-token" name="billing_token" type="text" value="{{ $tokenValue }}" required maxlength="7" autocapitalize="none" autocomplete="off" placeholder="MfiYIvI" class="{{ $inputClass }}">
                    @error('billing_token')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    @if ($paymentMethod)
                        <p class="mt-2 inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-3 py-1.5 text-sm font-semibold text-cacao">
                            <flux:icon.credit-card class="size-4" /> Método registrado en venta: {{ $paymentMethod }}
                        </p>
                    @endif
                </div>

                <div>
                    <label for="inv-payment-method" class="mb-2 block text-sm font-semibold text-cacao">Método de pago</label>
                    <select id="inv-payment-method" name="invoice_payment_method" required class="{{ $inputClass }}">
                        <option value="" disabled @selected(! $paymentFormValue)>Selecciona cómo pagaste</option>
                        @foreach ($paymentForms as $code => $label)
                            <option value="{{ $code }}" @selected($paymentFormValue === $code)>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm leading-6 text-mocha">Si pagaste con tarjeta, selecciona si fue crédito o débito.</p>
                    @error('invoice_payment_method')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="inv-rfc" class="mb-2 block text-sm font-semibold text-cacao">RFC</label>
                        <input id="inv-rfc" name="rfc" type="text" value="{{ old('rfc') }}" required maxlength="13" autocapitalize="characters" placeholder="XAXX010101000" class="{{ $inputClass }} uppercase placeholder:normal-case">
                        @error('rfc')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="inv-cp" class="mb-2 block text-sm font-semibold text-cacao">Código postal</label>
                        <input id="inv-cp" name="codigo_postal" type="text" inputmode="numeric" maxlength="5" value="{{ old('codigo_postal') }}" required placeholder="37700" class="{{ $inputClass }}">
                        @error('codigo_postal')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="inv-razon" class="mb-2 block text-sm font-semibold text-cacao">Razón social</label>
                    <input id="inv-razon" name="razon_social" type="text" value="{{ old('razon_social') }}" required placeholder="Como aparece en tu Constancia de Situación Fiscal" class="{{ $inputClass }}">
                    @error('razon_social')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="inv-regimen" class="mb-2 block text-sm font-semibold text-cacao">Régimen fiscal</label>
                    <select id="inv-regimen" name="regimen_fiscal" required class="{{ $inputClass }}">
                        <option value="" disabled @selected(! old('regimen_fiscal'))>Selecciona tu régimen</option>
                        @foreach ($regimenes as $code => $label)
                            <option value="{{ $code }}" @selected(old('regimen_fiscal') === $code)>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                    @error('regimen_fiscal')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="inv-email" class="mb-2 block text-sm font-semibold text-cacao">Correo electrónico</label>
                        <input id="inv-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="correo@ejemplo.com" class="{{ $inputClass }}">
                        @error('email')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-phone-input
                            label="Teléfono"
                            name="telefono"
                            :value="old('telefono')"
                            placeholder="+52 415 123 4567"
                        />
                        <label for="telefono" class="sr-only">Teléfono</label>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit" class="u-btn u-btn--accent"><flux:icon.paper-airplane class="size-5" /> Enviar solicitud</button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <article class="u-card p-7">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.clipboard-document-list class="size-6 text-terracotta" /> Qué necesitas</h2>
                <ul class="mt-5 space-y-3 text-sm leading-7 text-mocha">
                    <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> RFC, razón social y régimen tal como aparecen en tu Constancia de Situación Fiscal.</li>
                    <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> Código postal de tu domicilio fiscal.</li>
                    <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> El código de facturación impreso en tu ticket o precargado desde el QR.</li>
                </ul>
            </article>

            <article class="u-card p-7">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.clock class="size-6 text-terracotta" /> Plazo y dudas</h2>
                <p class="mt-4 text-sm leading-7 text-mocha">
                    Solicita tu factura dentro del mes en curso de tu compra. Si tienes dudas, llámanos al
                    <a href="tel:+524151194612" class="font-semibold text-terracotta underline underline-offset-4">415 119 4612</a>
                    o pregunta en sucursal.
                </p>
            </article>
        </div>
    </section>

    <script>
        (() => {
            const storageKey = 'cafe20trece.invoiceData.v1';
            const form = document.querySelector('[data-invoice-form]');

            if (! form) {
                return;
            }

            const fields = ['rfc', 'razon_social', 'regimen_fiscal', 'codigo_postal', 'email', 'telefono'];

            try {
                const storage = window.localStorage;
                const stored = JSON.parse(storage.getItem(storageKey) || '{}');

                fields.forEach((name) => {
                    const input = form.elements[name];

                    if (input && ! input.value && stored[name]) {
                        input.value = stored[name];
                    }
                });
            } catch (error) {
                return;
            }

            form.addEventListener('submit', () => {
                const data = {};

                fields.forEach((name) => {
                    const input = form.elements[name];

                    if (input && input.value) {
                        data[name] = input.value;
                    }
                });

                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(data));
                } catch (error) {
                    return;
                }
            });
        })();
    </script>
</x-public-layout>
