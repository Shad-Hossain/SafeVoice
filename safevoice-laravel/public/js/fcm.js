// fcm.js — SafeVoice Push Notification + Location Tracking + SOS Sidebar
// ─────────────────────────────────────────────────────────────

const FIREBASE_CONFIG = {
    apiKey:            "AIzaSyCwa8ptUUZGoZ77AyovXvxDI0fu1mSVglw",
    authDomain:        "safevoice-3c9c5.firebaseapp.com",
    projectId:         "safevoice-3c9c5",
    storageBucket:     "safevoice-3c9c5.firebasestorage.app",
    messagingSenderId: "161598345842",
    appId:             "1:161598345842:web:20938f6ee631a5c0f6a8eb",
};

const VAPID_KEY = "BAJk5NSbwoJZOsJsI7Sdg0ypuUddh5bLy9quVjPWDhwImPpDOlGHBrEDsImTxiSqqaxtCt2SCQ2Eqkn7pGOkC_4";

let fcmApp         = null;
let fcmMessaging   = null;
let locationTimer  = null;

// ─────────────────────────────────────────────────────────────
// Main init
// ─────────────────────────────────────────────────────────────
async function initFCM() {
    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        console.log('[FCM] Not supported in this browser.');
        return;
    }

    try {
        const { initializeApp }  = await import('https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js');
        const { getMessaging, getToken, onMessage } = await import('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging.js');

        if (!fcmApp) {
            fcmApp       = initializeApp(FIREBASE_CONFIG, 'safevoice-fcm');
            fcmMessaging = getMessaging(fcmApp);
        }

        const swReg = await navigator.serviceWorker.register('/firebase-messaging-sw.js', { scope: '/' });

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            console.log('[FCM] Notification permission denied.');
            return;
        }

        const token = await getToken(fcmMessaging, {
            vapidKey: VAPID_KEY,
            serviceWorkerRegistration: swReg,
        });

        if (!token) {
            console.log('[FCM] No token received.');
            return;
        }

        const savedToken = localStorage.getItem('sv-fcm-token');
        if (token !== savedToken) {
            await saveFcmToken(token);
            localStorage.setItem('sv-fcm-token', token);
        }

        // Foreground message handler
        onMessage(fcmMessaging, (payload) => {
            handleForegroundNotification(payload);
            // SOS notification এলে sidebar badge update করো
            if (payload.data?.type === 'sos') {
                incrementSosBadge();
            }
        });

        // Location tracking শুরু করো
        startLocationTracking();

        // Sidebar SOS button তৈরি করো
        injectSosSidebarButton();

    } catch(err) {
        console.error('[FCM] Init error:', err);
    }
}

// ─────────────────────────────────────────────────────────────
// Location Tracking — প্রতি 30 সেকেন্ডে backend এ update
// ─────────────────────────────────────────────────────────────
function startLocationTracking() {
    if (!navigator.geolocation) return;

    // প্রথমবার সাথে সাথে
    sendCurrentLocation();

    // তারপর প্রতি 30 সেকেন্ডে
    locationTimer = setInterval(sendCurrentLocation, 30000);
}

function sendCurrentLocation() {
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
            const userId = svUser.id || svUser.user_id;
            if (!userId) return;

            try {
                await fetch('/api/user/update-location', {
                    method:      'POST',
                    credentials: 'include',
                    headers:     { 'Content-Type': 'application/json' },
                    body:        JSON.stringify({
                        latitude:  pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        user_id:   userId,
                    }),
                });
            } catch(e) { /* silent fail */ }
        },
        (err) => console.log('[Location] Error:', err.message),
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// Page বন্ধ হলে timer clear করো
window.addEventListener('beforeunload', () => {
    if (locationTimer) clearInterval(locationTimer);
});

// ─────────────────────────────────────────────────────────────
// Token save
// ─────────────────────────────────────────────────────────────
async function saveFcmToken(token) {
    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        await fetch('/api/fcm/register-token', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify({
                token:       token,
                device_type: 'web',
                user_id:     svUser.id || svUser.user_id || null,
            }),
        });
    } catch(e) {
        console.error('[FCM] Token save failed:', e);
    }
}

async function unregisterFcmToken() {
    const token = localStorage.getItem('sv-fcm-token');
    if (!token) return;
    try {
        await fetch('/api/fcm/unregister-token', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify({ token }),
        });
        localStorage.removeItem('sv-fcm-token');
    } catch(e) { /* silent */ }
}

// ─────────────────────────────────────────────────────────────
// Sidebar SOS Button + Floating Modal
// ─────────────────────────────────────────────────────────────
function injectSosSidebarButton() {
    // Already injected?
    if (document.getElementById('sv-sos-sidebar-btn')) return;

    // Floating button — screen এর right side এ
    const btn = document.createElement('div');
    btn.id = 'sv-sos-sidebar-btn';
    btn.innerHTML = `
        <div id="sv-sos-fab" title="View SOS Alerts" onclick="openSosModal()">
            🚨
            <span id="sv-sos-badge" style="display:none;">0</span>
        </div>
    `;
    btn.innerHTML += `
        <style>
            #sv-sos-fab {
                position: fixed;
                bottom: 30px;
                right: 24px;
                z-index: 99998;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: linear-gradient(135deg, #e63946, #9b1d20);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(230,57,70,0.5);
                transition: transform .2s, box-shadow .2s;
                user-select: none;
            }
            #sv-sos-fab:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 28px rgba(230,57,70,0.7);
            }
            #sv-sos-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #ff0;
                color: #111;
                font-size: 11px;
                font-weight: 800;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }

            /* Modal */
            #sv-sos-modal-overlay {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 99999;
                background: rgba(0,0,0,0.7);
                backdrop-filter: blur(4px);
                align-items: center;
                justify-content: center;
            }
            #sv-sos-modal-overlay.open {
                display: flex;
            }
            #sv-sos-modal {
                background: #0d1526;
                border: 1px solid #e63946;
                border-radius: 18px;
                width: min(480px, 95vw);
                max-height: 80vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            }
            #sv-sos-modal-header {
                padding: 18px 20px;
                border-bottom: 1px solid #1e2d45;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            #sv-sos-modal-header h3 {
                color: #f87171;
                font-size: 16px;
                font-weight: 700;
                margin: 0;
            }
            #sv-sos-modal-close {
                color: #4a5568;
                font-size: 22px;
                cursor: pointer;
                line-height: 1;
                padding: 0 4px;
            }
            #sv-sos-modal-close:hover { color: #fff; }
            #sv-sos-modal-body {
                overflow-y: auto;
                padding: 12px;
                flex: 1;
            }
            .sv-sos-card {
                background: #111d2e;
                border: 1px solid #1e2d45;
                border-radius: 12px;
                padding: 14px;
                margin-bottom: 10px;
                transition: border-color .2s;
            }
            .sv-sos-card:hover { border-color: #e63946; }
            .sv-sos-card-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 8px;
            }
            .sv-sos-victim { color: #f87171; font-weight: 700; font-size: 14px; }
            .sv-sos-time   { color: #4a5568; font-size: 11px; }
            .sv-sos-loc    { color: #a0b4cc; font-size: 12px; margin-bottom: 6px; }
            .sv-sos-type   { display: inline-block; background: #1e2d45; color: #4f9eff; font-size: 11px; padding: 2px 8px; border-radius: 20px; margin-bottom: 8px; }
            .sv-sos-respond-btn {
                width: 100%;
                padding: 8px;
                background: linear-gradient(135deg, #e63946, #9b1d20);
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: opacity .2s;
            }
            .sv-sos-respond-btn:hover   { opacity: .85; }
            .sv-sos-respond-btn:disabled { opacity: .4; cursor: default; }
            #sv-sos-empty {
                text-align: center;
                color: #4a5568;
                padding: 40px 20px;
                font-size: 14px;
            }
        </style>
    `;

    // Modal HTML
    const modal = document.createElement('div');
    modal.id = 'sv-sos-modal-overlay';
    modal.innerHTML = `
        <div id="sv-sos-modal">
            <div id="sv-sos-modal-header">
                <h3>🚨 SOS Alerts Near You</h3>
                <span id="sv-sos-modal-close" onclick="closeSosModal()">×</span>
            </div>
            <div id="sv-sos-modal-body">
                <div id="sv-sos-empty">Loading...</div>
            </div>
        </div>
    `;

    document.body.appendChild(btn);
    document.body.appendChild(modal);

    // Overlay click → close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeSosModal();
    });

    // Page load এ unread count check করো
    checkUnreadSosCount();
}

function openSosModal() {
    document.getElementById('sv-sos-modal-overlay').classList.add('open');
    loadSosNotifications();
    // Badge clear
    const badge = document.getElementById('sv-sos-badge');
    if (badge) { badge.style.display = 'none'; badge.textContent = '0'; }
    localStorage.setItem('sv-sos-last-seen', Date.now());
}

function closeSosModal() {
    document.getElementById('sv-sos-modal-overlay').classList.remove('open');
}

// ─────────────────────────────────────────────────────────────
// SOS Notifications load করো
// ─────────────────────────────────────────────────────────────
async function loadSosNotifications() {
    const body = document.getElementById('sv-sos-modal-body');
    body.innerHTML = '<div id="sv-sos-empty">Loading...</div>';

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id;

        const res  = await fetch(`/api/sos/my-notifications?user_id=${userId}`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.notifications.length) {
            body.innerHTML = '<div id="sv-sos-empty">📭 No SOS alerts found near you.</div>';
            return;
        }

        body.innerHTML = '';
        data.notifications.forEach(n => {
            const timeAgo = formatTimeAgo(n.sos_time || n.created_at);
            const card    = document.createElement('div');
            card.className = 'sv-sos-card';
            card.innerHTML = `
                <div class="sv-sos-card-top">
                    <span class="sv-sos-victim">👤 ${n.victim_name || 'Unknown'}</span>
                    <span class="sv-sos-time">${timeAgo}</span>
                </div>
                ${n.location_text ? `<div class="sv-sos-loc">📍 ${n.location_text}</div>` : ''}
                ${n.crime_type    ? `<span class="sv-sos-type">${n.crime_type}</span>` : ''}
                <button class="sv-sos-respond-btn"
                    onclick="respondToSos(${n.sos_id}, this)"
                    ${n.status === 'responded' ? 'disabled' : ''}>
                    ${n.status === 'responded' ? '✅ Already Responded' : '🤝 Respond Now'}
                </button>
            `;
            body.appendChild(card);
        });

    } catch(e) {
        body.innerHTML = '<div id="sv-sos-empty">❌ Failed to load. Try again.</div>';
    }
}

async function respondToSos(sosId, btn) {
    btn.disabled    = true;
    btn.textContent = 'Sending...';

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const res  = await fetch('/api/sos/respond', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json' },
            body:        JSON.stringify({
                sos_id:  sosId,
                user_id: svUser.id || svUser.user_id,
            }),
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = '✅ Responded!';
        } else {
            btn.disabled    = false;
            btn.textContent = '🤝 Respond Now';
        }
    } catch(e) {
        btn.disabled    = false;
        btn.textContent = '🤝 Respond Now';
    }
}

// Badge count
function incrementSosBadge() {
    const badge = document.getElementById('sv-sos-badge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    badge.textContent = current + 1;
    badge.style.display = 'flex';
}

async function checkUnreadSosCount() {
    try {
        const svUser  = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId  = svUser.id || svUser.user_id;
        if (!userId) return;

        const res  = await fetch(`/api/sos/my-notifications?user_id=${userId}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;

        const lastSeen = parseInt(localStorage.getItem('sv-sos-last-seen') || '0');
        const unread   = data.notifications.filter(n => {
            const t = new Date(n.created_at).getTime();
            return t > lastSeen && n.status !== 'responded';
        }).length;

        const badge = document.getElementById('sv-sos-badge');
        if (badge && unread > 0) {
            badge.textContent = unread;
            badge.style.display = 'flex';
        }
    } catch(e) { /* silent */ }
}

// ─────────────────────────────────────────────────────────────
// Foreground notification toast
// ─────────────────────────────────────────────────────────────
function handleForegroundNotification(payload) {
    const notif = payload.notification || {};
    const data  = payload.data || {};
    const title = notif.title || data.title || 'SafeVoice';
    const body  = notif.body  || data.body  || '';
    const url   = data.url   || '/dashboard';
    const type  = data.type  || '';

    const colors = {
        sos:              { bg: '#1a0505', border: '#e63946', icon: '🚨', color: '#f87171' },
        evidence_request: { bg: '#140d00', border: '#d97706', icon: '📋', color: '#fbbf24' },
        evidence_expired: { bg: '#1a0a00', border: '#f39c12', icon: '⚠️', color: '#f39c12' },
    };
    const style = colors[type] || { bg: '#0d1526', border: '#4f9eff', icon: '🔔', color: '#4f9eff' };

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 999999;
        background: ${style.bg}; border: 1px solid ${style.border};
        border-radius: 14px; padding: 16px 20px;
        max-width: 360px; width: calc(100vw - 40px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        cursor: pointer; animation: fcmSlideIn .3s ease;
    `;
    toast.innerHTML = `
        <style>
            @keyframes fcmSlideIn {
                from { transform: translateX(120%); opacity: 0; }
                to   { transform: translateX(0);    opacity: 1; }
            }
        </style>
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <div style="font-size:24px;flex-shrink:0;line-height:1;">${style.icon}</div>
            <div style="flex:1;min-width:0;">
                <div style="color:${style.color};font-weight:700;font-size:14px;margin-bottom:4px;">${title}</div>
                <div style="color:#a0b4cc;font-size:12px;line-height:1.5;">${body}</div>
            </div>
            <div style="color:#4a5568;font-size:18px;flex-shrink:0;margin-top:-2px;cursor:pointer;"
                 onclick="this.closest('div[style]').remove()">×</div>
        </div>
        <div style="margin-top:10px;text-align:right;">
            <span style="color:${style.color};font-size:12px;font-weight:600;text-decoration:underline;">View →</span>
        </div>
    `;

    toast.addEventListener('click', () => { toast.remove(); if (url) window.location.href = url; });
    document.body.appendChild(toast);
    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 7000);

    if (type === 'sos') playSosSound();
}

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function formatTimeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)   return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
    return `${Math.floor(diff/86400)}d ago`;
}

function playSosSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const beep = (freq, start, duration) => {
            const osc  = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.4, ctx.currentTime + start);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
            osc.start(ctx.currentTime + start);
            osc.stop(ctx.currentTime + start + duration);
        };
        beep(880, 0.0, 0.15); beep(880, 0.2, 0.15); beep(880, 0.4, 0.15);
        beep(660, 0.7, 0.35); beep(660, 1.1, 0.35); beep(660, 1.5, 0.35);
        beep(880, 1.9, 0.15); beep(880, 2.1, 0.15); beep(880, 2.3, 0.15);
    } catch(e) { /* Audio not supported */ }
}