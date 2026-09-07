self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    const fallback = {
        title: 'Nuevo mensaje de WhatsApp',
        body: 'Tienes un mensaje nuevo en Café 20Trece.',
        icon: '/web-app-manifest-192x192.png',
        badge: '/favicon-96x96.png',
        data: { url: '/whatsapp-app' },
    };
    let payload = fallback;

    try {
        payload = event.data ? { ...fallback, ...event.data.json() } : fallback;
    } catch (error) {
        console.error('No se pudo leer la notificación push.', error);
    }

    const { title, ...options } = payload;
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil((async () => {
        const requestedUrl = new URL(event.notification.data?.url ?? '/whatsapp-app', self.location.origin);
        const targetUrl = requestedUrl.origin === self.location.origin
            ? requestedUrl.href
            : new URL('/whatsapp-app', self.location.origin).href;
        const windowClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

        for (const client of windowClients) {
            if ('navigate' in client && new URL(client.url).pathname.startsWith('/whatsapp-app')) {
                await client.navigate(targetUrl);

                return client.focus();
            }
        }

        return self.clients.openWindow(targetUrl);
    })());
});
