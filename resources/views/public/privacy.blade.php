<x-public-layout
    title="Aviso de privacidad"
    description="Aviso de privacidad integral de Café 20Trece conforme a la LFPDPPP, uso de cookies y solicitud de derechos ARCO."
>
    @php
        $privacyEmail = config('services.privacy.email');
        $updatedAt = now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        $arcoRights = [
            'acceso' => ['Acceso', 'Conocer qué datos personales tenemos y cómo los usamos.'],
            'rectificacion' => ['Rectificación', 'Corregir datos inexactos o incompletos.'],
            'cancelacion' => ['Cancelación (olvido)', 'Eliminar tus datos cuando proceda conforme a la ley.'],
            'oposicion' => ['Oposición', 'Oponerte al uso de tus datos para fines específicos.'],
            'revocacion' => ['Revocación', 'Retirar el consentimiento que nos otorgaste.'],
        ];
        $oldDerechos = (array) old('derechos', []);
        $inputClass = 'w-full rounded-2xl border border-coffee/25 bg-white px-4 py-3 text-base text-espresso shadow-sm outline-none transition placeholder:text-mocha/55 focus:border-terracotta focus:ring-4 focus:ring-terracotta/15';
    @endphp

    {{-- Hero (renders immediately, not scroll-gated) --}}
    <section class="u-card overflow-hidden p-8 sm:p-12">
        <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
            <flux:icon.shield-check class="size-4" /> Aviso de privacidad
        </span>

        <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.05] tracking-tight text-espresso sm:text-5xl">Aviso de Privacidad Integral</h1>
        <p class="mt-4 max-w-3xl text-base leading-8 text-mocha">
            En cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), su Reglamento y los Lineamientos del Aviso de Privacidad, ponemos a tu disposición el presente aviso sobre el tratamiento de tus datos personales.
        </p>
        <p class="mt-4 text-sm font-medium text-mocha">Última actualización: {{ $updatedAt }}</p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="#arco" class="u-btn u-btn--accent px-5 py-2.5 text-sm"><flux:icon.identification class="size-4" /> Ejercer derechos ARCO</a>
            <a href="#cookies" class="u-btn u-btn--outline px-5 py-2.5 text-sm"><flux:icon.cake class="size-4" /> Aviso de cookies</a>
        </div>
    </section>

    {{-- 1. Responsable --}}
    <section class="u-reveal u-card mt-8 p-8">
        <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.building-storefront class="size-6 text-terracotta" /> 1. Responsable de tus datos</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7 text-mocha">
            El responsable del tratamiento y resguardo de tus datos personales, así como del establecimiento comercial Café 20Trece, es:
        </p>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Responsable</dt>
                <dd class="mt-1 font-semibold text-espresso">José Benjamín Sierra Rangel</dd>
                <dd class="text-sm text-mocha">Persona física (RFC: SIRB960209272)</dd>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Domicilio</dt>
                <dd class="mt-1 text-sm leading-6 text-espresso">Juárez 21, Zona Centro,<br>37700 San Miguel de Allende, Guanajuato, México.</dd>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Correo de privacidad</dt>
                <dd class="mt-1 text-sm text-espresso"><a href="mailto:{{ $privacyEmail }}" class="font-semibold text-terracotta underline underline-offset-4">{{ $privacyEmail }}</a></dd>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-mocha">Teléfono</dt>
                <dd class="mt-1 text-sm text-espresso"><a href="tel:+524151194612" class="font-semibold text-terracotta underline underline-offset-4">415 119 4612</a></dd>
            </div>
        </dl>
    </section>

    {{-- 2. Datos que tratamos --}}
    <section class="u-reveal u-card mt-8 p-8">
        <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.circle-stack class="size-6 text-terracotta" /> 2. Datos personales que tratamos</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7 text-mocha">Para las finalidades descritas en este aviso podemos recabar y tratar las siguientes categorías de datos:</p>

        <div class="mt-6 grid gap-5 md:grid-cols-3">
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <h3 class="text-base font-bold text-espresso">Identificación y contacto</h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-mocha">
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Nombre completo</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Teléfono y correo electrónico</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Fecha de nacimiento</li>
                </ul>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <h3 class="text-base font-bold text-espresso">Programa de cliente</h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-mocha">
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> UUID y lecturas de tu código QR</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Nivel, visitas y saldo a favor</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Deudas y movimientos de bonificación</li>
                </ul>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <h3 class="text-base font-bold text-espresso">Actividad de compra</h3>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-mocha">
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Historial de compras y sucursal</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Bebidas favoritas y personalizaciones</li>
                    <li class="flex gap-2"><flux:icon.chevron-right class="mt-0.5 size-4 shrink-0 text-coffee" /> Forma de pago utilizada</li>
                </ul>
            </div>
        </div>

        <p class="mt-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm leading-7 text-espresso">
            <span class="font-semibold">No solicitamos datos personales sensibles.</span> Usamos tu fecha de nacimiento únicamente para felicitaciones y experiencias personalizadas (como lecturas tonalpohualli). No tratamos datos de menores de edad sin consentimiento de quien ejerza la patria potestad.
        </p>
    </section>

    {{-- 3. Finalidades --}}
    <section class="u-reveal mt-8 grid gap-5 lg:grid-cols-2">
        <article class="u-card p-7">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.check-badge class="size-6 text-sage" /> 3. Finalidades primarias</h2>
            <p class="mt-3 text-sm leading-7 text-mocha">Necesarias para la relación con el cliente. No requieren tu consentimiento adicional:</p>
            <ul class="mt-4 space-y-3 text-sm leading-7 text-mocha">
                <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> Identificarte en el punto de venta mediante tu QR.</li>
                <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> Administrar recompensas, saldo a favor, deudas y visitas.</li>
                <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> Mostrar tu historial de compras y bebidas favoritas.</li>
                <li class="flex items-start gap-3"><flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" /> Atender aclaraciones y cumplir obligaciones aplicables.</li>
            </ul>
        </article>

        <article class="u-card p-7">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.sparkles class="size-6 text-terracotta" /> Finalidades secundarias</h2>
            <p class="mt-3 text-sm leading-7 text-mocha">No son necesarias para el servicio; puedes negarte sin afectar tu cuenta:</p>
            <ul class="mt-4 space-y-3 text-sm leading-7 text-mocha">
                <li class="flex items-start gap-3"><flux:icon.minus-circle class="mt-0.5 size-5 shrink-0 text-terracotta" /> Felicitaciones de cumpleaños y lecturas tonalpohualli.</li>
                <li class="flex items-start gap-3"><flux:icon.minus-circle class="mt-0.5 size-5 shrink-0 text-terracotta" /> Comunicación de promociones y novedades.</li>
                <li class="flex items-start gap-3"><flux:icon.minus-circle class="mt-0.5 size-5 shrink-0 text-terracotta" /> Encuestas de calidad y satisfacción.</li>
            </ul>
            <p class="mt-5 rounded-2xl border border-coffee/12 bg-vanilla/70 px-4 py-3 text-sm leading-6 text-mocha">
                Para negar el uso con fines secundarios, escríbenos a
                <a href="mailto:{{ $privacyEmail }}" class="font-semibold text-terracotta underline underline-offset-4">{{ $privacyEmail }}</a>
                o usa el formulario al final de esta página.
            </p>
        </article>
    </section>

    {{-- 4. Transferencias --}}
    <section class="u-reveal u-card mt-8 p-8">
        <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.arrows-right-left class="size-6 text-terracotta" /> 4. Encargados y transferencias</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7 text-mocha">
            <span class="font-semibold text-espresso">No vendemos ni comercializamos tus datos personales.</span> Para operar el servicio nos apoyamos en encargados que tratan datos por cuenta y bajo instrucciones del responsable, sujetos a confidencialidad: proveedores de tecnología y hospedaje, servicios de mensajería para notificaciones y Google reCAPTCHA para prevenir abusos en el registro.
        </p>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-mocha">
            No realizamos transferencias que requieran tu consentimiento, salvo las legalmente exceptuadas (por ejemplo, requerimientos de autoridades competentes o las necesarias para mantener la relación con el cliente).
        </p>
    </section>

    {{-- 5. Cookies --}}
    <section id="cookies" class="u-reveal u-card mt-8 scroll-mt-24 p-8">
        <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.cake class="size-6 text-terracotta" /> 5. Aviso de cookies</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7 text-mocha">Este sitio utiliza cookies y tecnologías similares con los siguientes propósitos:</p>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <h3 class="text-base font-bold text-espresso">Esenciales</h3>
                <p class="mt-2 text-sm leading-6 text-mocha">Sesión y seguridad (token CSRF) indispensables para que el sitio funcione. No pueden desactivarse sin afectar el servicio.</p>
            </div>
            <div class="rounded-2xl border border-coffee/12 bg-vanilla/70 p-5">
                <h3 class="text-base font-bold text-espresso">Seguridad de terceros</h3>
                <p class="mt-2 text-sm leading-6 text-mocha">Google reCAPTCHA en el formulario de registro para prevenir abusos, sujeto a las políticas de privacidad de Google.</p>
            </div>
        </div>
        <p class="mt-5 max-w-3xl text-sm leading-7 text-mocha">
            No usamos cookies de publicidad ni de rastreo con fines mercadológicos. Puedes administrar o eliminar las cookies desde la configuración de tu navegador; al hacerlo, algunas funciones podrían dejar de operar. El banner de cookies del sitio te permite registrar tu preferencia.
        </p>
    </section>

    {{-- 6. Derechos ARCO --}}
    <section class="u-reveal u-card mt-8 p-8">
        <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.identification class="size-6 text-terracotta" /> 6. Tus derechos ARCO y revocación</h2>
        <p class="mt-4 max-w-3xl text-sm leading-7 text-mocha">
            Tienes derecho a <span class="font-semibold text-espresso">Acceder</span>, <span class="font-semibold text-espresso">Rectificar</span>, <span class="font-semibold text-espresso">Cancelar</span> u <span class="font-semibold text-espresso">Oponerte</span> al tratamiento de tus datos (derechos ARCO), así como a revocar tu consentimiento.
        </p>
        <ul class="mt-4 space-y-2.5 text-sm leading-7 text-mocha">
            <li class="flex items-start gap-3"><flux:icon.clock class="mt-0.5 size-5 shrink-0 text-terracotta" /> Responderemos tu solicitud en un máximo de <span class="font-semibold text-espresso">20 días hábiles</span> y, de proceder, la haremos efectiva dentro de los <span class="font-semibold text-espresso">15 días</span> siguientes.</li>
            <li class="flex items-start gap-3"><flux:icon.user class="mt-0.5 size-5 shrink-0 text-terracotta" /> Para proteger tu cuenta, te pediremos acreditar tu identidad antes de ejecutar cualquier acción.</li>
            <li class="flex items-start gap-3"><flux:icon.envelope class="mt-0.5 size-5 shrink-0 text-terracotta" /> Puedes enviar tu solicitud con el formulario de abajo o al correo <a href="mailto:{{ $privacyEmail }}" class="font-semibold text-terracotta underline underline-offset-4">{{ $privacyEmail }}</a>.</li>
        </ul>
        <div class="mt-6">
            <a href="#arco" class="u-btn u-btn--accent"><flux:icon.identification class="size-5" /> Ir al formulario ARCO</a>
        </div>
    </section>

    {{-- 7. Cambios + autoridad --}}
    <section class="u-reveal mt-8 grid gap-5 lg:grid-cols-2">
        <article class="u-card p-7">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.arrow-path class="size-6 text-terracotta" /> 7. Cambios a este aviso</h2>
            <p class="mt-4 text-sm leading-7 text-mocha">
                Podemos actualizar este aviso de privacidad por cambios legales, operativos o en nuestros servicios. Cualquier modificación se publicará en esta misma página, indicando la fecha de la última actualización.
            </p>
        </article>
        <article class="u-card p-7">
            <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.scale class="size-6 text-terracotta" /> 8. Autoridad</h2>
            <p class="mt-4 text-sm leading-7 text-mocha">
                Si consideras que tu derecho a la protección de datos personales fue vulnerado, puedes acudir al Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales (INAI) en
                <a href="https://www.inai.org.mx" target="_blank" rel="noopener noreferrer" class="font-semibold text-terracotta underline underline-offset-4">inai.org.mx</a>.
            </p>
        </article>
    </section>

    {{-- ARCO form --}}
    <section id="arco" class="u-reveal u-card mt-8 scroll-mt-24 p-8 sm:p-10">
        <h2 class="flex items-center gap-3 font-serif text-3xl font-semibold tracking-tight text-espresso">
            <flux:icon.identification class="size-7 text-terracotta" /> Solicitud de derechos ARCO
        </h2>
        <p class="mt-3 max-w-3xl text-sm leading-7 text-mocha">
            Completa este formulario para ejercer tus derechos de Acceso, Rectificación, Cancelación (olvido) u Oposición, o para revocar tu consentimiento. Recibirás respuesta al correo que proporciones.
        </p>

        @if (session('arco_status'))
            <div class="mt-6 rounded-2xl border border-sage/30 bg-sage/10 px-5 py-4 text-sm font-medium text-sage">
                <span class="inline-flex items-center gap-2"><flux:icon.check-circle class="size-5" /> {{ session('arco_status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-rose-300/70 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                <span class="inline-flex items-center gap-2"><flux:icon.exclamation-triangle class="size-5" /> Revisa los campos marcados e intenta nuevamente.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('public.arco.store') }}" class="mt-8 space-y-6" novalidate>
            @csrf

            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label>No llenar este campo
                    <input type="text" name="website" tabindex="-1" autocomplete="off" value="{{ old('website') }}">
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="arco-nombre" class="mb-2 block text-sm font-semibold text-cacao">Nombre completo</label>
                    <input id="arco-nombre" name="nombre" type="text" value="{{ old('nombre') }}" autocomplete="name" required class="{{ $inputClass }}">
                    @error('nombre')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="arco-email" class="mb-2 block text-sm font-semibold text-cacao">Correo electrónico de contacto</label>
                    <input id="arco-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="{{ $inputClass }}">
                    @error('email')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="arco-telefono" class="mb-2 block text-sm font-semibold text-cacao">Teléfono <span class="font-normal text-mocha">(opcional)</span></label>
                    <input id="arco-telefono" name="telefono" type="tel" value="{{ old('telefono') }}" autocomplete="tel" class="{{ $inputClass }}">
                    @error('telefono')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="arco-cuenta" class="mb-2 block text-sm font-semibold text-cacao">Dato de tu cuenta <span class="font-normal text-mocha">(opcional)</span></label>
                    <input id="arco-cuenta" name="cuenta_identificador" type="text" value="{{ old('cuenta_identificador') }}" placeholder="Correo, teléfono o UUID con el que te registraste" class="{{ $inputClass }}">
                    @error('cuenta_identificador')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <fieldset>
                <legend class="text-sm font-semibold text-cacao">Derechos que deseas ejercer</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($arcoRights as $value => [$label, $hint])
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-coffee/15 bg-vanilla/60 px-4 py-3 transition hover:border-coffee/30">
                            <input type="checkbox" name="derechos[]" value="{{ $value }}" @checked(in_array($value, $oldDerechos, true)) class="mt-0.5 size-4 shrink-0 rounded border-coffee/40 text-cacao accent-[#6b4226] focus:ring-2 focus:ring-terracotta/30">
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold text-espresso">{{ $label }}</span>
                                <span class="block text-xs leading-5 text-mocha">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('derechos')<p class="mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
            </fieldset>

            <div>
                <label for="arco-detalle" class="mb-2 block text-sm font-semibold text-cacao">Detalle de tu solicitud</label>
                <textarea id="arco-detalle" name="detalle" rows="5" required placeholder="Describe con claridad qué deseas y, si aplica, qué datos corregir o eliminar." class="{{ $inputClass }} min-h-[8rem] resize-y">{{ old('detalle') }}</textarea>
                @error('detalle')<p class="mt-1.5 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-start gap-3 rounded-2xl border border-coffee/15 bg-crema/50 px-4 py-4">
                <input id="arco-identidad" name="identidad_consent" type="checkbox" value="1" @checked(old('identidad_consent')) required class="mt-1 size-4 shrink-0 rounded border-coffee/40 text-cacao accent-[#6b4226] focus:ring-2 focus:ring-terracotta/30">
                <label for="arco-identidad" class="cursor-pointer text-sm leading-7 text-mocha">
                    Declaro que la información es veraz y que acreditaré mi identidad cuando se me solicite, conforme al <a href="#arco" class="font-semibold text-terracotta underline underline-offset-4">procedimiento ARCO</a>.
                </label>
            </div>
            @error('identidad_consent')<p class="-mt-2 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror

            <div>
                <button type="submit" class="u-btn u-btn--accent"><flux:icon.paper-airplane class="size-5" /> Enviar solicitud</button>
            </div>
        </form>
    </section>
</x-public-layout>
