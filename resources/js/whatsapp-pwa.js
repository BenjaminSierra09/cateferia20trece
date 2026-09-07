const serviceWorkerPath = '/whatsapp-pwa-sw.js';
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('whatsapp-pwa-installable'));
});

const registerServiceWorker = async () => {
    if (! ('serviceWorker' in navigator)) {
        return null;
    }

    return navigator.serviceWorker.register(serviceWorkerPath, { scope: '/whatsapp-app' });
};

window.addEventListener('load', () => {
    registerServiceWorker().catch((error) => console.error('No se pudo registrar la PWA de WhatsApp.', error));
});

const urlBase64ToUint8Array = (value) => {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const decoded = window.atob(base64);

    return Uint8Array.from(decoded, (character) => character.charCodeAt(0));
};

const requestJson = async (url, options) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const response = await fetch(url, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...options.headers,
        },
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.message ?? 'No se pudo actualizar la suscripción.');
    }

    return response.status === 204 ? null : response.json();
};

window.whatsappPwaNotifications = () => ({
    status: 'loading',
    statusText: 'Preparando notificaciones…',
    notificationButtonText: 'Notificaciones',
    notice: '',
    busy: false,
    registration: null,
    subscription: null,
    installable: deferredInstallPrompt !== null,

    async init() {
        window.addEventListener('whatsapp-pwa-installable', () => {
            this.installable = true;
        });

        if (! ('Notification' in window) || ! ('PushManager' in window) || ! ('serviceWorker' in navigator)) {
            this.setStatus('unsupported');

            return;
        }

        if (! this.vapidPublicKey()) {
            this.setStatus('unavailable');

            return;
        }

        try {
            this.registration = await registerServiceWorker();
            this.subscription = await this.registration.pushManager.getSubscription();

            if (this.subscription) {
                await this.storeSubscription(this.subscription);
                this.setStatus('enabled');

                return;
            }

            this.setStatus(Notification.permission === 'denied' ? 'denied' : 'available');

            if (this.isIos() && ! this.isStandalone()) {
                this.notice = 'En iPhone o iPad, agrega esta PWA a la pantalla de inicio antes de activar las notificaciones.';
            }
        } catch (error) {
            console.error(error);
            this.notice = error.message ?? 'No fue posible preparar las notificaciones.';
            this.setStatus('error');
        }
    },

    async toggleNotifications() {
        if (this.status === 'enabled') {
            await this.disableNotifications();

            return;
        }

        if (this.isIos() && ! this.isStandalone()) {
            await this.offerInstallation();

            return;
        }

        await this.enableNotifications();
    },

    async enableNotifications() {
        this.busy = true;
        this.notice = '';

        try {
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                this.setStatus(permission === 'denied' ? 'denied' : 'available');
                this.notice = 'El navegador no concedió permiso para mostrar notificaciones.';

                return;
            }

            this.registration ??= await registerServiceWorker();
            this.subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(this.vapidPublicKey()),
            });

            await this.storeSubscription(this.subscription);
            this.setStatus('enabled');
            this.notice = 'Las notificaciones de mensajes nuevos quedaron activadas en este dispositivo.';
        } catch (error) {
            console.error(error);
            this.notice = error.message ?? 'No fue posible activar las notificaciones.';
            this.setStatus('error');
        } finally {
            this.busy = false;
        }
    },

    async disableNotifications(silent = false) {
        this.busy = true;

        try {
            this.registration ??= await registerServiceWorker();
            this.subscription ??= await this.registration?.pushManager.getSubscription();

            if (this.subscription) {
                const endpoint = this.subscription.endpoint;

                try {
                    await requestJson(this.deleteUrl(), {
                        method: 'DELETE',
                        body: JSON.stringify({ endpoint }),
                    });
                } catch (error) {
                    console.error('El servidor no pudo eliminar la suscripción; se eliminará al detectar que expiró.', error);
                }

                await this.subscription.unsubscribe();
                this.subscription = null;
            }

            this.setStatus('available');

            if (! silent) {
                this.notice = 'Las notificaciones quedaron desactivadas en este dispositivo.';
            }
        } catch (error) {
            console.error(error);

            if (! silent) {
                this.notice = error.message ?? 'No fue posible desactivar las notificaciones.';
                this.setStatus('error');
            }
        } finally {
            this.busy = false;
        }
    },

    async logout(event) {
        event.preventDefault();

        try {
            await this.disableNotifications(true);
        } finally {
            event.currentTarget.submit();
        }
    },

    async offerInstallation() {
        if (deferredInstallPrompt) {
            await deferredInstallPrompt.prompt();
            const choice = await deferredInstallPrompt.userChoice;

            if (choice.outcome === 'accepted') {
                deferredInstallPrompt = null;
                this.installable = false;
                this.notice = 'Abre la app instalada para activar las notificaciones.';

                return;
            }
        }

        this.notice = 'En Safari, usa Compartir y luego “Agregar a pantalla de inicio”. Abre la app instalada para activar los avisos.';
    },

    async storeSubscription(subscription) {
        const payload = subscription.toJSON();

        await requestJson(this.storeUrl(), {
            method: 'POST',
            body: JSON.stringify({
                endpoint: payload.endpoint,
                keys: payload.keys,
                content_encoding: window.PushManager.supportedContentEncodings?.[0] ?? 'aes128gcm',
            }),
        });
    },

    setStatus(status) {
        const labels = {
            loading: ['Preparando notificaciones…', 'Notificaciones'],
            available: ['Notificaciones desactivadas', 'Activar avisos'],
            enabled: ['Notificaciones activadas', 'Desactivar avisos'],
            denied: ['Notificaciones bloqueadas por el navegador', 'Avisos bloqueados'],
            unsupported: ['Este navegador no admite notificaciones push', 'No disponible'],
            unavailable: ['Falta configurar Web Push en el servidor', 'No disponible'],
            error: ['No se pudieron preparar las notificaciones', 'Reintentar avisos'],
        };

        this.status = status;
        [this.statusText, this.notificationButtonText] = labels[status] ?? labels.error;
    },

    isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    },

    isIos() {
        return /iPad|iPhone|iPod/.test(window.navigator.userAgent)
            || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    },

    vapidPublicKey() {
        return this.$root.dataset.vapidPublicKey ?? '';
    },

    storeUrl() {
        return this.$root.dataset.subscriptionStoreUrl;
    },

    deleteUrl() {
        return this.$root.dataset.subscriptionDeleteUrl;
    },
});
