@props([
    'field',
    'label' => 'Escanear QR',
])

<div
    x-data="qrScanner(@js($field))"
    x-on:qr-scanner-open.window="
        if ($event.detail.field === @js($field)) {
            openScanner();
        }
    "
    class="contents"
>
    <flux:button type="button" variant="ghost" icon="camera" x-on:click="openScanner">
        {{ $label }}
    </flux:button>

    <div
        x-cloak
        x-show="open"
        x-on:keydown.escape.window="closeScanner"
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/70 p-4"
    >
        <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">Escanear QR</flux:heading>
                    <flux:text>Apunta la cámara al código o carga una imagen si tu navegador no soporta video.</flux:text>
                </div>

                <flux:button type="button" variant="ghost" icon="x-mark" x-on:click="closeScanner" />
            </div>

            <div class="mt-5 space-y-4">
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-950 dark:border-zinc-700">
                    <video x-ref="video" autoplay playsinline muted class="aspect-video w-full object-cover"></video>
                </div>

                <template x-if="message">
                    <flux:callout color="sky" icon="information-circle" x-text="message"></flux:callout>
                </template>

                <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                    <flux:input x-model="manualValue" label="UUID detectado" placeholder="e7d3c5c1-..." />
                    <flux:button type="button" variant="ghost" x-on:click="$refs.file.click()">Cargar imagen</flux:button>
                    <flux:button type="button" variant="primary" x-on:click="applyManualValue">Usar UUID</flux:button>
                </div>

                <input x-ref="file" type="file" accept="image/*" class="hidden" x-on:change="scanFile($event)" />
            </div>
        </div>
    </div>
</div>
