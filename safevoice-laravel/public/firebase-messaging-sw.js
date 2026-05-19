// firebase-messaging-sw.js
// ─────────────────────────────────────────────────────────────
// SafeVoice — Firebase Cloud Messaging Service Worker
// Browser বন্ধ থাকলেও push notification আসবে
// ─────────────────────────────────────────────────────────────

importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');


firebase.initializeApp({
    apiKey:            "AIzaSyCwa8ptUUZGoZ77AyovXvxDI0fu1mSVglw",
    authDomain:        "safevoice-3c9c5.firebaseapp.com",
    projectId:         "safevoice-3c9c5",
    storageBucket:     "safevoice-3c9c5.firebasestorage.app",
    messagingSenderId: "161598345842",
    appId:             "1:161598345842:web:20938f6ee631a5c0f6a8eb",
});

const messaging = firebase.messaging();

// Background message handler — app বন্ধ/background এ থাকলে
messaging.onBackgroundMessage(function(payload) {
    console.log('[SW] Background message received:', payload);

    const notif   = payload.notification || {};
    const data    = payload.data || {};
    const title   = notif.title || data.title || 'SafeVoice';
    const body    = notif.body  || data.body  || 'You have a new notification.';
    const url     = data.url || '/dashboard';
    const type    = data.type || '';

    // Type অনুযায়ী icon আলাদা
    const icons = {
        sos:               '/images/sos-icon.png',
        evidence_request:  '/images/evidence-icon.png',
        evidence_expired:  '/images/warning-icon.png',
        default:           '/images/logo.png',
    };
    const icon = icons[type] || icons.default;

    const options = {
        body,
        icon,
        badge:   '/images/badge-72.png',
        vibrate: type === 'sos' ? [200, 100, 200, 100, 200] : [200, 100, 200],
        tag:     type || 'safevoice',
        renotify: true,
        data:    { url },
        actions: type === 'sos'
            ? [{ action: 'view', title: '🚨 View SOS' }]
            : [{ action: 'view', title: '👀 View' }],
    };

    return self.registration.showNotification(title, options);
});

// Notification click — সঠিক page এ যাও
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/dashboard';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Already open tab থাকলে সেটাই focus করো
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            // নতুন tab খোলো
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
