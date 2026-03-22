self.addEventListener('push', function (event) {
    if (!event.data) return;

    let data;
    try {
        data = event.data.json();
    } catch {
        data = { title: event.data.text(), body: '' };
    }

    const title = data.title ?? 'New notification';
    const options = {
        body: data.body ?? '',
        icon: data.icon ?? '/favicon.svg',
        badge: data.badge ?? '/favicon.svg',
        data: data.url ? { url: data.url } : {},
        tag: data.tag ?? 'notification',
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const url = event.notification.data?.url ?? '/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        }),
    );
});
