@php
    $footerLink = 'text-sm text-sand/75 transition hover:text-vanilla';
    $socialLink = 'flex size-10 items-center justify-center rounded-full border border-white/15 text-sand/80 transition hover:-translate-y-0.5 hover:border-white/35 hover:text-vanilla';
@endphp

<footer class="relative z-10 mt-20 bg-espresso text-vanilla">
    <div class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.5fr_1fr_1.1fr] lg:px-8">
        {{-- Brand --}}
        <div>
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('logotipo.png') }}"
                    alt="Café 20Trece"
                    class="h-12 w-12 rounded-full object-contain ring-1 ring-white/20"
                >
                <span class="font-serif text-xl font-semibold tracking-tight">Café 20Trece</span>
            </div>
            <p class="mt-5 max-w-xs text-sm leading-7 text-sand/75">
                Café orgánico en grano o molido, tostado y servido con calma en el corazón de San Miguel de Allende.
            </p>
            <div class="mt-6 flex gap-3">
                <a href="https://www.instagram.com/20trecesma/" target="_blank" rel="noopener noreferrer" class="{{ $socialLink }}" aria-label="Instagram"><img src="https://cdn.simpleicons.org/instagram/e3d0b3" alt="" class="size-5" loading="lazy"></a>
                <a href="https://www.facebook.com/p/Cafeter%C3%ADa-20-Trece-61554300671726/" target="_blank" rel="noopener noreferrer" class="{{ $socialLink }}" aria-label="Facebook"><img src="https://cdn.simpleicons.org/facebook/e3d0b3" alt="" class="size-5" loading="lazy"></a>
                <a href="https://www.tripadvisor.com.mx/Restaurant_Review-g151932-d27092190-Reviews-Cafeteria_20trece-San_Miguel_de_Allende_Central_Mexico_and_Gulf_Coast.html" target="_blank" rel="noopener noreferrer" class="{{ $socialLink }}" aria-label="TripAdvisor"><img src="https://cdn.simpleicons.org/tripadvisor/e3d0b3" alt="" class="size-5" loading="lazy"></a>
                <a href="https://search.google.com/local/writereview?placeid=ChIJ-YDfJtlRK4QRi4sqs3hbzBA" target="_blank" rel="noopener noreferrer" class="{{ $socialLink }}" aria-label="Google"><img src="https://cdn.simpleicons.org/google/e3d0b3" alt="" class="size-5" loading="lazy"></a>
            </div>
        </div>

        {{-- Explore --}}
        <nav aria-label="Enlaces del sitio">
            <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-caramel">Explora</h3>
            <ul class="mt-5 space-y-3">
                <li><a href="{{ route('home') }}" class="{{ $footerLink }}">Inicio</a></li>
                <li><a href="{{ route('public.rewards') }}" class="{{ $footerLink }}">Recompensas</a></li>
                <li><a href="{{ route('public.lookup') }}" class="{{ $footerLink }}">Mi cuenta QR</a></li>
                <li><a href="{{ route('public.register') }}" class="{{ $footerLink }}">Obtener mi QR</a></li>
                <li><a href="{{ route('public.invoice') }}" class="{{ $footerLink }}">Facturación</a></li>
            </ul>
        </nav>

        {{-- Visit --}}
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-caramel">Visítanos</h3>
            <ul class="mt-5 space-y-3 text-sm text-sand/75">
                <li class="flex items-start gap-3">
                    <flux:icon.map-pin class="mt-0.5 size-4 shrink-0 text-caramel" />
                    <span>Juárez 21, Zona Centro<br>37700 San Miguel de Allende, Gto.</span>
                </li>
                <li>
                    <a href="tel:+524151194612" class="inline-flex items-center gap-3 transition hover:text-vanilla">
                        <flux:icon.phone class="size-4 shrink-0 text-caramel" /> 415 119 4612
                    </a>
                </li>
                <li class="flex items-start gap-3">
                    <flux:icon.clock class="mt-0.5 size-4 shrink-0 text-caramel" />
                    <span>Lunes a domingo<br>6:30 a.m. a 10:00 p.m.</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 text-xs text-sand/60 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>© {{ now()->year }} Café 20Trece. Todos los derechos reservados.</p>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                <a href="{{ route('public.terms') }}" class="transition hover:text-vanilla">Términos y condiciones</a>
                <a href="{{ route('public.privacy') }}" class="transition hover:text-vanilla">Aviso de privacidad</a>
                <a href="{{ route('public.privacy') }}#arco" class="transition hover:text-vanilla">Derechos ARCO</a>
            </div>
        </div>
    </div>
</footer>
