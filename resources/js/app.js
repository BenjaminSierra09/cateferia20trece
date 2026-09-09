import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/styles';
import { Passkeys } from '@laravel/passkeys';

window.qrScanner = (field) => ({
    field,
    open: false,
    manualValue: '',
    message: '',
    stream: null,
    scanTimer: null,
    detector: null,

    async openScanner() {
        this.open = true;
        this.message = '';

        if (! ('BarcodeDetector' in window)) {
            this.message = 'Tu navegador no soporta escaneo en vivo. Puedes cargar una imagen del QR o capturar el UUID manualmente.';

            return;
        }

        try {
            const supportedFormats = await window.BarcodeDetector.getSupportedFormats();

            if (! supportedFormats.includes('qr_code')) {
                this.message = 'Este navegador no soporta lectura de QR en vivo. Usa una imagen o captura manual.';

                return;
            }

            this.detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: {
                        ideal: 'environment',
                    },
                },
                audio: false,
            });

            this.$refs.video.srcObject = this.stream;
            this.startLoop();
        } catch (error) {
            console.error(error);
            this.message = 'No fue posible abrir la cámara. Revisa permisos o utiliza una imagen del QR.';
        }
    },

    closeScanner() {
        this.open = false;
        this.manualValue = '';
        this.stopScanner();
    },

    stopScanner() {
        if (this.scanTimer) {
            window.clearInterval(this.scanTimer);
            this.scanTimer = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }
    },

    startLoop() {
        this.scanTimer = window.setInterval(async () => {
            if (! this.detector || ! this.$refs.video || this.$refs.video.readyState < 2) {
                return;
            }

            try {
                const barcodes = await this.detector.detect(this.$refs.video);

                if (barcodes.length > 0 && barcodes[0].rawValue) {
                    this.applyValue(barcodes[0].rawValue);
                }
            } catch (error) {
                console.error(error);
            }
        }, 500);
    },

    async scanFile(event) {
        const [file] = event.target.files ?? [];

        if (! file) {
            return;
        }

        if (! ('BarcodeDetector' in window)) {
            this.message = 'Tu navegador no soporta lectura automática desde imagen. Ingresa el UUID manualmente.';

            return;
        }

        try {
            const imageBitmap = await createImageBitmap(file);
            const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            const barcodes = await detector.detect(imageBitmap);

            if (barcodes.length > 0 && barcodes[0].rawValue) {
                this.applyValue(barcodes[0].rawValue);

                return;
            }

            this.message = 'No se detectó un QR en la imagen seleccionada.';
        } catch (error) {
            console.error(error);
            this.message = 'No se pudo procesar la imagen del QR.';
        } finally {
            event.target.value = '';
        }
    },

    applyManualValue() {
        if (this.manualValue.trim() === '') {
            return;
        }

        this.applyValue(this.manualValue.trim());
    },

    applyValue(value) {
        this.manualValue = value;
        this.$wire.set(this.field, value);
        this.closeScanner();
    },
});

window.phoneInput = (config = {}) => {
    let itiInstance = null;
    let hiddenInput = null;
    let getNumber = null;
    let isValidNumber = null;
    let setNumber = null;

    return {
        hiddenInputId: config.hiddenInputId,
        initialCountry: config.initialCountry ?? 'mx',

        init() {
            hiddenInput = document.getElementById(this.hiddenInputId);

            if (! hiddenInput || ! this.$refs.input) {
                return;
            }

            itiInstance = intlTelInput(this.$refs.input, {
                initialCountry: this.initialCountry,
                countryOrder: ['mx', 'us', 'ca'],
                formatAsYouType: true,
                nationalMode: false,
                strictMode: true,
                loadUtils: () => import('intl-tel-input/utils'),
            });

            getNumber = itiInstance.getNumber.bind(itiInstance);
            isValidNumber = itiInstance.isValidNumber.bind(itiInstance);
            setNumber = itiInstance.setNumber.bind(itiInstance);

            if (hiddenInput.value) {
                setNumber(hiddenInput.value);
            }

            this.syncHiddenValue();
        },

        syncHiddenValue() {
            if (! hiddenInput || ! this.$refs.input) {
                return;
            }

            const rawValue = this.$refs.input.value.trim();
            const normalizedValue = rawValue === ''
                ? ''
                : (isValidNumber && isValidNumber() ? getNumber() : rawValue);

            hiddenInput.value = normalizedValue;
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },
    };
};

const csrfHeader = () => {
    const cookie = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith('XSRF-TOKEN='));

    if (! cookie) {
        return {};
    }

    return {
        'X-XSRF-TOKEN': decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)),
    };
};

window.passkeysLogin = async () => {
    const response = await Passkeys.verify();

    if (response?.redirect) {
        window.location.href = response.redirect;
    }

    return response;
};

window.passkeysManager = () => ({
    name: '',
    error: '',
    isRegistering: false,
    isDeletingId: null,

    async register($wire) {
        this.error = '';

        if (this.name.trim() === '') {
            return;
        }

        this.isRegistering = true;

        try {
            await Passkeys.register({ name: this.name.trim() });
            this.name = '';
            await $wire.$refresh();
        } catch (error) {
            this.error = error?.message ?? 'No se pudo registrar la passkey.';
        } finally {
            this.isRegistering = false;
        }
    },

    async destroy(passkeyId, $wire) {
        this.error = '';
        this.isDeletingId = passkeyId;

        try {
            const response = await fetch(`/user/passkeys/${passkeyId}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    ...csrfHeader(),
                },
            });

            if (! response.ok) {
                const body = await response.json().catch(() => ({}));
                throw new Error(body?.message ?? 'No se pudo eliminar la passkey.');
            }

            await $wire.$refresh();
        } catch (error) {
            this.error = error?.message ?? 'No se pudo eliminar la passkey.';
        } finally {
            this.isDeletingId = null;
        }
    },
});

window.whatsappAudioRecorder = ($wire) => ({
    recorder: null,
    stream: null,
    chunks: [],
    timer: null,
    recording: false,
    uploading: false,
    cancelled: false,
    duration: 0,
    progress: 0,
    error: '',

    get formattedDuration() {
        const minutes = Math.floor(this.duration / 60).toString().padStart(2, '0');
        const seconds = (this.duration % 60).toString().padStart(2, '0');

        return `${minutes}:${seconds}`;
    },

    supportedMimeType() {
        if (! ('MediaRecorder' in window)) {
            return null;
        }

        return [
            'audio/mp4;codecs=mp4a.40.2',
            'audio/mp4',
            'audio/ogg;codecs=opus',
        ].find((mimeType) => window.MediaRecorder.isTypeSupported(mimeType)) ?? null;
    },

    async start() {
        this.error = '';

        if (! navigator.mediaDevices?.getUserMedia) {
            this.error = 'Tu navegador no permite grabar audio. Puedes adjuntar un archivo.';

            return;
        }

        const mimeType = this.supportedMimeType();

        if (! mimeType) {
            this.error = 'Este navegador no graba en un formato compatible con WhatsApp. Puedes adjuntar un archivo MP3 o M4A.';

            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.recorder = new window.MediaRecorder(this.stream, {
                mimeType,
                audioBitsPerSecond: 96000,
            });
            this.chunks = [];
            this.cancelled = false;
            this.duration = 0;

            this.recorder.addEventListener('dataavailable', (event) => {
                if (event.data.size > 0) {
                    this.chunks.push(event.data);
                }
            });
            this.recorder.addEventListener('stop', () => this.finish(mimeType), { once: true });
            this.recorder.addEventListener('error', () => {
                this.error = 'La grabación se interrumpió. Inténtalo nuevamente.';
                this.dispose();
            }, { once: true });
            this.recorder.start(250);
            this.recording = true;
            this.timer = window.setInterval(() => {
                this.duration += 1;

                if (this.duration >= 300) {
                    this.stop();
                }
            }, 1000);
        } catch (error) {
            console.error(error);
            this.error = 'No fue posible usar el micrófono. Revisa el permiso del navegador.';
            this.dispose();
        }
    },

    stop() {
        if (this.recorder?.state === 'recording') {
            this.recorder.stop();
        }
    },

    cancel() {
        this.cancelled = true;

        if (this.recorder?.state === 'recording') {
            this.recorder.stop();

            return;
        }

        this.dispose();
    },

    finish(mimeType) {
        const wasCancelled = this.cancelled;
        const chunks = this.chunks;

        this.dispose();

        if (wasCancelled) {
            return;
        }

        const normalizedMimeType = mimeType.startsWith('audio/ogg') ? 'audio/ogg' : 'audio/mp4';
        const extension = normalizedMimeType === 'audio/ogg' ? 'ogg' : 'm4a';
        const blob = new Blob(chunks, { type: normalizedMimeType });

        if (blob.size === 0) {
            this.error = 'La grabación quedó vacía. Inténtalo nuevamente.';

            return;
        }

        if (blob.size > 12 * 1024 * 1024) {
            this.error = 'El audio no puede pesar más de 12 MB.';

            return;
        }

        const file = new File([blob], `audio-${Date.now()}.${extension}`, {
            type: normalizedMimeType,
        });

        this.uploading = true;
        this.progress = 0;
        $wire.upload(
            'audio',
            file,
            () => {
                this.uploading = false;
                this.progress = 100;
            },
            () => {
                this.uploading = false;
                this.error = 'No fue posible preparar el audio. Inténtalo nuevamente.';
            },
            (event) => {
                this.progress = event.detail.progress;
            },
        );
    },

    dispose() {
        this.recording = false;

        if (this.timer) {
            window.clearInterval(this.timer);
            this.timer = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        this.recorder = null;
        this.chunks = [];
    },

    destroy() {
        this.cancelled = true;
        this.dispose();
    },
});
