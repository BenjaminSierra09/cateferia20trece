import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/styles';

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
