<x-public-layout
    title="Mi cuenta QR"
    description="Consulta tu cuenta de cliente escaneando tu QR o capturando tu UUID."
>
    <section class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[2rem] border border-white/60 bg-white/80 p-8 shadow-xl shadow-[#8B5E34]/10 backdrop-blur">
            <span class="inline-flex rounded-full border border-[#8B5E34]/15 bg-[#8B5E34]/10 px-4 py-2 text-sm font-semibold text-[#6F4324]">
                <flux:icon.qr-code class="mr-2 size-4" /> Consulta pública
            </span>

            <h1 class="mt-4 text-4xl font-black tracking-tight">Escanea tu tarjeta QR</h1>
            <p class="mt-4 max-w-2xl text-base leading-8 text-[#6B5B4A]">
                Aquí puedes revisar tu nivel, saldo a favor, compras recientes y bebidas favoritas de Café 20Trece.
            </p>

            <form class="mt-8 space-y-4" method="GET" id="customer-qr-form">
                <label for="uuid" class="block text-sm font-semibold text-[#6F4324]">UUID del QR</label>
                <div class="relative">
                    <flux:icon.qr-code class="pointer-events-none absolute left-5 top-1/2 size-5 -translate-y-1/2 text-[#8B5E34]" />
                    <input
                        id="uuid"
                        name="uuid"
                        type="text"
                        inputmode="text"
                        placeholder="e7d3c5c1-...."
                        class="w-full rounded-2xl border border-[#8B5E34]/20 bg-white py-4 pr-5 pl-12 text-base shadow-sm outline-none transition focus:border-[#8B5E34] focus:ring-4 focus:ring-[#8B5E34]/10"
                    >
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        id="scan-qr-button"
                        class="inline-flex items-center gap-2 rounded-full border border-[#8B5E34]/20 bg-white px-5 py-3 text-sm font-bold text-[#6F4324] transition hover:bg-[#8B5E34]/5"
                    >
                        <flux:icon.camera class="size-4" /> Escanear con cámara
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-full bg-[#6F4324] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#6F4324]/20 transition hover:bg-[#5D351C]"
                    >
                        <flux:icon.arrow-right class="size-4" /> Ver mi cuenta
                    </button>
                </div>
            </form>

            <div id="scanner-panel" class="mt-6 hidden rounded-[1.5rem] border border-[#8B5E34]/15 bg-[#F7F1E8] p-4">
                <p class="inline-flex items-center gap-2 text-sm font-semibold text-[#6F4324]"><flux:icon.viewfinder-circle class="size-4" /> Apunta tu cámara al QR de cliente.</p>
                <video id="scanner-video" class="mt-4 aspect-video w-full rounded-2xl bg-[#2A2118]/10" autoplay playsinline muted></video>
                <p id="scanner-status" class="mt-3 text-sm text-[#6B5B4A]">Esperando permiso de cámara…</p>
            </div>
        </div>

        <div class="space-y-6">
            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.sparkles class="size-6 text-[#8B5E34]" /> Qué podrás ver</h2>
                <ul class="mt-4 space-y-3 text-sm leading-7 text-[#6B5B4A]">
                    <li>Saldo a favor actual y nivel de recompensa.</li>
                    <li>Visitas acumuladas en el año.</li>
                    <li>Historial reciente de compras.</li>
                    <li>Tus bebidas favoritas y personalizaciones más frecuentes.</li>
                </ul>
            </article>

            <article class="rounded-[1.75rem] border border-white/60 bg-white/80 p-7 shadow-lg shadow-[#8B5E34]/5">
                <h2 class="inline-flex items-center gap-3 text-2xl font-bold"><flux:icon.exclamation-circle class="size-6 text-[#8B5E34]" /> Si tu cámara no abre</h2>
                <p class="mt-4 text-sm leading-7 text-[#6B5B4A]">
                    No te preocupes. Puedes visitarnos en cualquiera de nuestras sucursales y nuestro equipo te ayudará a consultar tu saldo, detalles de recompensas y compras recientes de forma rápida y segura.
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
