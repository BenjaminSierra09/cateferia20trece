<x-public-layout
    title="Mi cuenta QR"
    description="Consulta tu cuenta de cliente escaneando tu QR o capturando tu UUID."
>
    <section class="grid items-start gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="u-reveal u-card p-8 sm:p-10">
            <span class="inline-flex items-center gap-2 rounded-full border border-coffee/15 bg-coffee/10 px-4 py-2 text-sm font-semibold text-cacao">
                <flux:icon.qr-code class="size-4" /> Consulta pública
            </span>

            <h1 class="mt-6 font-serif text-4xl font-semibold leading-[1.05] tracking-tight text-espresso sm:text-5xl">Escanea tu tarjeta QR</h1>
            <p class="mt-4 max-w-xl text-base leading-8 text-mocha">
                Revisa tu nivel, saldo a favor, compras recientes y bebidas favoritas de Café 20Trece.
            </p>

            <div class="mt-6">
                <a href="{{ route('public.register') }}" class="inline-flex items-center gap-2 rounded-full border border-coffee/25 bg-coffee/5 px-5 py-2.5 text-sm font-semibold text-cacao transition hover:bg-coffee/10">
                    <flux:icon.user-plus class="size-4" /> Aún no tengo QR
                </a>
            </div>

            <form class="mt-8 space-y-4" method="GET" id="customer-qr-form">
                <label for="uuid" class="block text-sm font-semibold text-cacao">UUID del QR</label>
                <div class="relative">
                    <flux:icon.qr-code class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-coffee" />
                    <input
                        id="uuid"
                        name="uuid"
                        type="text"
                        inputmode="text"
                        placeholder="e7d3c5c1-...."
                        class="w-full rounded-2xl border border-coffee/25 bg-white py-4 pr-5 pl-12 text-base text-espresso shadow-sm outline-none transition placeholder:text-mocha/55 focus:border-terracotta focus:ring-4 focus:ring-terracotta/15"
                    >
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <button type="button" id="scan-qr-button" class="u-btn u-btn--outline">
                        <flux:icon.camera class="size-5" /> Escanear con cámara
                    </button>

                    <button type="submit" class="u-btn u-btn--primary">
                        <flux:icon.arrow-right class="size-5" /> Ver mi cuenta
                    </button>
                </div>
            </form>

            <div id="scanner-panel" class="mt-6 hidden rounded-[1.5rem] border border-coffee/15 bg-crema/70 p-4">
                <p class="inline-flex items-center gap-2 text-sm font-semibold text-cacao"><flux:icon.viewfinder-circle class="size-4" /> Apunta tu cámara al QR de cliente.</p>
                <video id="scanner-video" class="mt-4 aspect-video w-full rounded-2xl bg-espresso/10" autoplay playsinline muted></video>
                <p id="scanner-status" class="mt-3 text-sm text-mocha">Esperando permiso de cámara…</p>
            </div>
        </div>

        <div class="space-y-6">
            <article class="u-reveal u-card p-7" data-delay="1">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.sparkles class="size-6 text-terracotta" /> Qué podrás ver</h2>
                <ul class="mt-5 space-y-3 text-sm leading-7 text-mocha">
                    @foreach ([
                        'Saldo a favor actual y nivel de recompensa.',
                        'Visitas acumuladas en el año.',
                        'Historial reciente de compras.',
                        'Tus bebidas favoritas y personalizaciones más frecuentes.',
                    ] as $item)
                        <li class="flex items-start gap-3">
                            <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-sage" />
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="u-reveal u-card p-7" data-delay="2">
                <h2 class="flex items-center gap-3 font-serif text-2xl font-semibold text-espresso"><flux:icon.lifebuoy class="size-6 text-terracotta" /> Si tu cámara no abre</h2>
                <p class="mt-4 text-sm leading-7 text-mocha">
                    No te preocupes. Visítanos en sucursal y nuestro equipo te ayudará a consultar tu saldo, recompensas y compras recientes de forma rápida y segura.
                </p>
            </article>
        </div>
    </section>

    @push('scripts')
        <script>
            (() => {
                const form = document.getElementById('customer-qr-form');
                const uuidInput = document.getElementById('uuid');
                const scanButton = document.getElementById('scan-qr-button');
                const scannerPanel = document.getElementById('scanner-panel');
                const scannerVideo = document.getElementById('scanner-video');
                const scannerStatus = document.getElementById('scanner-status');
                const uuidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
                let stream = null;
                let detector = null;
                let animationFrame = null;

                form.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const uuid = uuidInput.value.trim();

                    if (! uuidPattern.test(uuid)) {
                        uuidInput.focus();
                        scannerStatus.textContent = 'Captura un UUID válido para continuar.';
                        return;
                    }

                    window.location.href = `{{ url('/qr') }}/${uuid}`;
                });

                scanButton.addEventListener('click', async () => {
                    if (! ('BarcodeDetector' in window) || ! navigator.mediaDevices?.getUserMedia) {
                        scannerStatus.textContent = 'Tu navegador no soporta escaneo directo. Puedes pegar el UUID manualmente.';
                        scannerPanel.classList.remove('hidden');
                        return;
                    }

                    detector = new BarcodeDetector({ formats: ['qr_code'] });
                    scannerPanel.classList.remove('hidden');

                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: { ideal: 'environment' },
                            },
                            audio: false,
                        });

                        scannerVideo.srcObject = stream;
                        scannerStatus.textContent = 'Buscando QR…';

                        const scanFrame = async () => {
                            try {
                                const barcodes = await detector.detect(scannerVideo);

                                if (Array.isArray(barcodes) && barcodes.length > 0) {
                                    const rawValue = (barcodes[0].rawValue || '').trim();

                                    if (uuidPattern.test(rawValue)) {
                                        uuidInput.value = rawValue;
                                        stream?.getTracks().forEach((track) => track.stop());
                                        scannerStatus.textContent = 'QR detectado. Abriendo tu cuenta…';
                                        form.requestSubmit();

                                        return;
                                    }
                                }
                            } catch (error) {
                                scannerStatus.textContent = 'No se pudo leer el QR. Intenta de nuevo o captura el UUID manualmente.';
                            }

                            animationFrame = window.requestAnimationFrame(scanFrame);
                        };

                        animationFrame = window.requestAnimationFrame(scanFrame);
                    } catch (error) {
                        scannerStatus.textContent = 'No fue posible acceder a la cámara en este momento.';
                    }
                });

                window.addEventListener('beforeunload', () => {
                    if (animationFrame) {
                        window.cancelAnimationFrame(animationFrame);
                    }

                    stream?.getTracks().forEach((track) => track.stop());
                });
            })();
        </script>
    @endpush
</x-public-layout>
