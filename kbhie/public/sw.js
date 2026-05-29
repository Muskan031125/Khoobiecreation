// Khoobie service worker — handles push notifications + future offline cache.
self.addEventListener('install', e => { self.skipWaiting(); });
self.addEventListener('activate', e => { e.waitUntil(self.clients.claim()); });

self.addEventListener('push', event => {
    let data = {};
    try { data = event.data.json(); } catch (e) { data = { title: 'Khoobie', body: event.data ? event.data.text() : '' }; }
    const title = data.title || 'Khoobie';
    const options = {
        body: data.body || '',
        icon: data.icon || '/assets/brand/logo.png',
        badge: '/assets/brand/logo.png',
        data: { url: data.url || '/' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(clients.openWindow(url));
});
