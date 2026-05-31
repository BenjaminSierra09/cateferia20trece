{{-- Aviso de cookies. Vanilla JS + localStorage so it works on every public
     page (no Alpine/Flux dependency). Hidden by default; shown only when no
     choice is stored, so returning visitors never see a flash. --}}
<div
    id="cookie-banner"
    role="region"
    aria-label="Aviso de cookies"
    hidden
    class="fixed inset-x-0 bottom-0 z-40 px-4 pb-4 sm:px-6"
>
    <div class="u-card mx-auto flex max-w-3xl flex-col gap-4 p-5 sm:flex-row sm:items-center sm:gap-6">
        <div class="flex items-start gap-3">
            <span class="hidden size-10 shrink-0 items-center justify-center rounded-full bg-terracotta/12 text-terracotta sm:flex">
                <flux:icon.cake class="size-5" />
            </span>
            <p class="text-sm leading-6 text-mocha">
                Usamos cookies esenciales para el funcionamiento del sitio y, al registrarte, reCAPTCHA de Google para evitar abusos.
                <a href="{{ route('public.privacy') }}#cookies" class="font-semibold text-terracotta underline underline-offset-4">Más información</a>.
            </p>
        </div>

        <div class="flex shrink-0 gap-3">
            <button type="button" data-cookie-essential class="u-btn u-btn--outline px-4 py-2.5 text-sm">Solo esenciales</button>
            <button type="button" data-cookie-accept class="u-btn u-btn--accent px-4 py-2.5 text-sm">Aceptar</button>
        </div>
    </div>
</div>

<script>
    (() => {
        const KEY = 'c20t-cookie-consent';
        const banner = document.getElementById('cookie-banner');

        if (! banner) {
            return;
        }

        let stored = null;

        try {
            stored = localStorage.getItem(KEY);
        } catch (error) {
            stored = null;
        }

        if (stored) {
            banner.remove();
            return;
        }

        banner.hidden = false;

        const choose = (value) => {
            try {
                localStorage.setItem(KEY, value);
            } catch (error) {
                // Storage unavailable: just dismiss for this session.
            }

            banner.remove();
        };

        banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => choose('all'));
        banner.querySelector('[data-cookie-essential]')?.addEventListener('click', () => choose('essential'));
    })();
</script>
