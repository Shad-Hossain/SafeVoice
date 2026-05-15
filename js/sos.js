// ================================================================
// SafeVoice — SOS Core
// FLOW: Hold → Create SOS → Send Notification → Evidence Modal
// ================================================================

let holdTimer    = null;
let holdInterval = null;
let holdProgress = 0;
let sosActive    = false;

const HOLD_DURATION = 3000;

let currentSOSId    = null;
let currentLat      = null;
let currentLng      = null;
let currentLocation = '';

// ── INIT ─────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    detectSOSLocation();
    startResponderScan();
    pollForIncomingAlerts();
});


// ── LOCATION ─────────────────────────────────────────────────────
function detectSOSLocation() {
    const locText      = document.getElementById('locationText');
    const activatedLoc = document.getElementById('activatedLocation');

    if (!navigator.geolocation) {
        if (locText) locText.textContent = 'Location unavailable';
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${currentLat}&lon=${currentLng}`)
            .then(r => r.json())
            .then(data => {
                currentLocation = data.display_name;
                if (locText)      locText.textContent      = currentLocation;
                if (activatedLoc) activatedLoc.textContent = currentLocation;
            })
            .catch(() => {
                currentLocation = `${currentLat.toFixed(4)}, ${currentLng.toFixed(4)}`;
                if (locText)      locText.textContent      = currentLocation;
                if (activatedLoc) activatedLoc.textContent = currentLocation;
            });
    }, () => {
        if (locText) locText.textContent = 'Location access denied';
    });
}


// ── RESPONDER SCAN UI (visual only) ──────────────────────────────
function startResponderScan() {
    let count = 0;
    const nearbyCount = document.getElementById('nearbyCount');
    const interval = setInterval(() => {
        count++;
        if (nearbyCount) nearbyCount.textContent = count;
        if (count >= 5) clearInterval(interval);
    }, 600);
}


// ── HOLD START ───────────────────────────────────────────────────
function startHold() {
    if (sosActive) return;

    const btn        = document.getElementById('sosBtn');
    const fill       = document.getElementById('holdFill');
    const statusText = document.getElementById('statusText');
    const statusBar  = document.getElementById('statusBar');

    if (btn) btn.classList.add('holding');
    if (statusText) statusText.textContent = 'Hold to activate SOS...';
    if (statusBar)  statusBar.className    = 'sos-status-bar active-status';

    const circumference = 452;
    holdProgress = 0;

    holdInterval = setInterval(() => {
        holdProgress += circumference / 60;
        if (fill) fill.style.strokeDashoffset = circumference - Math.min(holdProgress, circumference);
    }, HOLD_DURATION / 60);

    holdTimer = setTimeout(() => { activateSOS(); }, HOLD_DURATION);
}


// ── HOLD CANCEL ──────────────────────────────────────────────────
function cancelHold() {
    if (sosActive) return;

    clearTimeout(holdTimer);
    clearInterval(holdInterval);
    holdProgress = 0;

    const btn        = document.getElementById('sosBtn');
    const fill       = document.getElementById('holdFill');
    const statusText = document.getElementById('statusText');
    const statusBar  = document.getElementById('statusBar');

    if (btn)        btn.classList.remove('holding');
    if (fill)       fill.style.strokeDashoffset = 452;
    if (statusText) statusText.textContent = 'Ready to send alert';
    if (statusBar)  statusBar.className    = 'sos-status-bar';
}


// ── ACTIVATE SOS — MAIN FLOW ─────────────────────────────────────
async function activateSOS() {
    sosActive = true;

    const btn = document.getElementById('sosBtn');
    if (btn) {
        btn.classList.remove('holding');
        btn.classList.add('sending');
        btn.querySelector('span').textContent  = 'SENDING...';
        btn.querySelector('small').textContent = 'Broadcasting alert';
    }
    updateStatusBar('Sending SOS alert...', true);

    try {
        // STEP 1: Create SOS record
        const createRes  = await fetch('../api/create_sos.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ latitude: currentLat, longitude: currentLng, location: currentLocation })
        });
        const createData = await createRes.json();

        if (!createData.success) { showError('Failed to create SOS.'); resetSOS(); return; }
        currentSOSId = createData.sos_id;

        // STEP 2: Notify nearby users FIRST
        const notifRes  = await fetch('../api/send_sos_notification.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ sos_id: currentSOSId, latitude: currentLat, longitude: currentLng, location: currentLocation })
        });
        const notifData = await notifRes.json();
        const notifiedCount = notifData.notified_count || 0;

        // STEP 3: Update button to SENT
        if (btn) {
            btn.classList.remove('sending');
            btn.classList.add('sent');
            btn.querySelector('span').textContent  = 'SENT';
            btn.querySelector('small').textContent = 'Help is coming';
        }
        updateStatusBar('Alert sent to ' + notifiedCount + ' people nearby!', false);

        // Show responder UI + overlay
        showFakeResponders(notifiedCount);

        // STEP 4: After 1.5s, open evidence modal for victim to add details
        setTimeout(() => { openEvidenceModal(); }, 1500);

    } catch (err) {
        console.error('SOS Error:', err);
        showError('Network error. Check your connection.');
        resetSOS();
    }
}


// ── STATUS BAR ───────────────────────────────────────────────────
function updateStatusBar(message, isActive) {
    const statusText = document.getElementById('statusText');
    const statusBar  = document.getElementById('statusBar');
    if (statusText) statusText.textContent = message;
    if (statusBar)  statusBar.className = isActive ? 'sos-status-bar active-status' : 'sos-status-bar sent-status';
}


// ── FAKE RESPONDER UI ─────────────────────────────────────────────
const fakeResponders = [
    { name: 'Rakib Hassan', dist: '120m away', status: 'helping'  },
    { name: 'Nadia Islam',  dist: '180m away', status: 'notified' },
    { name: 'Arif Hossain', dist: '95m away',  status: 'helping'  },
    { name: 'Tania Begum',  dist: '210m away', status: 'notified' },
    { name: 'Jahid Khan',   dist: '155m away', status: 'helping'  },
];

function showFakeResponders(notifiedCount) {
    const responderList   = document.getElementById('responderList');
    const scanPlaceholder = document.getElementById('scanPlaceholder');
    const alertLog        = document.getElementById('alertLog');
    const logList         = document.getElementById('logList');
    const alertedCount    = document.getElementById('alertedCount');

    if (scanPlaceholder) scanPlaceholder.style.display = 'none';
    if (alertLog)        alertLog.style.display = 'block';

    fakeResponders.forEach((r, i) => {
        setTimeout(() => {
            if (responderList) {
                const card = document.createElement('div');
                card.className = 'responder-card-item';
                card.innerHTML = `
                    <div class="resp-avatar"><i class="fas fa-user"></i></div>
                    <div class="resp-info"><h5>${r.name}</h5><p>${r.dist}</p></div>
                    <span class="resp-status ${r.status}">${r.status === 'helping' ? 'Responding' : 'Notified'}</span>
                `;
                responderList.appendChild(card);
            }
            if (logList) {
                const log = document.createElement('div');
                log.className = 'log-item';
                log.innerHTML = `<i class="fas fa-check-circle"></i><div>${r.name} notified</div>`;
                logList.appendChild(log);
            }
            if (alertedCount) alertedCount.textContent = Math.max(notifiedCount, i + 1);
        }, i * 500);
    });

    setTimeout(() => {
        const overlay = document.getElementById('activatedOverlay');
        if (overlay) overlay.classList.add('active');
    }, 1000);
}


// ── EVIDENCE MODAL ────────────────────────────────────────────────
function openEvidenceModal() {
    const modal = document.getElementById('evidenceModal');
    if (!modal) return;
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('visible'), 10);
}

function closeEvidenceModal() {
    const modal = document.getElementById('evidenceModal');
    if (!modal) return;
    modal.classList.remove('visible');
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function handleFileSelect(input) {
    const preview = document.getElementById('filePreview');
    const file    = input.files[0];
    if (!file || !preview) return;
    preview.innerHTML = '';

    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src   = URL.createObjectURL(file);
        img.style.cssText = 'max-width:100%;border-radius:8px;margin-top:8px;';
        preview.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const vid = document.createElement('video');
        vid.src      = URL.createObjectURL(file);
        vid.controls = true;
        vid.style.cssText = 'max-width:100%;border-radius:8px;margin-top:8px;';
        preview.appendChild(vid);
    } else {
        const p = document.createElement('p');
        p.style.cssText = 'color:#a0b4cc;font-size:13px;margin-top:8px;';
        p.textContent   = `Attached: ${file.name} (${(file.size/1024).toFixed(1)} KB)`;
        preview.appendChild(p);
    }
}


// ── SUBMIT EVIDENCE ───────────────────────────────────────────────
async function submitEvidence() {
    const crimeType = document.getElementById('crimeType').value;
    const desc      = document.getElementById('crimeDesc').value.trim();
    const fileInput = document.getElementById('evidenceFile');
    const submitBtn = document.getElementById('submitEvidenceBtn');
    const msgEl     = document.getElementById('modalMsg');

    if (!crimeType) { showModalMsg('Please select a crime type', 'error'); return; }

    const formData = new FormData();
    formData.append('sos_id',      currentSOSId);
    formData.append('crime_type',  crimeType);
    formData.append('description', desc);
    if (fileInput && fileInput.files[0]) formData.append('evidence', fileInput.files[0]);

    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...'; }

    try {
        const res  = await fetch('../api/upload_sos_evidence.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showModalMsg('Evidence submitted! Responders can now see your full details.', 'success');
            setTimeout(() => closeEvidenceModal(), 2500);
        } else {
            showModalMsg('Upload failed. You can skip and add later.', 'error');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence'; }
        }
    } catch (err) {
        showModalMsg('Network error. Evidence not uploaded.', 'error');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence'; }
    }
}

function showModalMsg(msg, type) {
    const el = document.getElementById('modalMsg');
    if (el) { el.textContent = msg; el.className = 'modal-msg ' + type; }
}


// ── INCOMING SOS NOTIFICATIONS (Responder Side) ───────────────────
let pollingInterval   = null;
let lastSeenSosId     = null;

function pollForIncomingAlerts() {
    checkIncomingAlerts();
    pollingInterval = setInterval(checkIncomingAlerts, 10000);
}

async function checkIncomingAlerts() {
    try {
        const res  = await fetch('../api/get_my_sos_notifications.php');
        const data = await res.json();
        if (data.success && data.count > 0) {
            const newest = data.notifications[0];
            if (newest.sos_id !== lastSeenSosId) {
                lastSeenSosId = newest.sos_id;
                showIncomingAlert(newest);
            }
        }
    } catch (e) { /* silent */ }
}

function showIncomingAlert(notif) {
    const panel = document.getElementById('incomingAlertPanel');
    if (!panel) return;
    if (panel.classList.contains('visible')) return;

    document.getElementById('incomingVictimName').textContent = notif.victim_name  || 'Unknown';
    document.getElementById('incomingLocation').textContent   = notif.location_text || 'Location unknown';
    document.getElementById('incomingCrimeType').textContent  = notif.crime_type   || 'Not specified yet';
    document.getElementById('incomingTime').textContent       = formatTime(notif.sos_time);

    panel.dataset.sosId = notif.sos_id;
    panel.classList.add('visible');
    playAlertSound();
}

function viewSOSDetails() {
    const panel = document.getElementById('incomingAlertPanel');
    const sosId = panel?.dataset.sosId;
    if (!sosId) return;

    fetch('../api/respond_to_sos.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ sos_id: parseInt(sosId), action: 'respond' })
    });

    openSOSDetailsModal(sosId);
    dismissIncomingAlert();
}

function dismissIncomingAlert() {
    const panel = document.getElementById('incomingAlertPanel');
    if (panel) panel.classList.remove('visible');
}

async function openSOSDetailsModal(sosId) {
    const modal = document.getElementById('sosDetailsModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.getElementById('sosDetailsContent').innerHTML =
        '<p style="color:#a0b4cc;text-align:center;padding:20px;">Loading victim details...</p>';

    try {
        const res  = await fetch(`../api/get_sos_alert.php?sos_id=${sosId}`);
        const data = await res.json();
        if (data.success) renderSOSDetails(data.sos, data.evidence);
    } catch (e) {
        document.getElementById('sosDetailsContent').innerHTML =
            '<p style="color:#e63946;text-align:center;">Could not load details.</p>';
    }
}

function renderSOSDetails(sos, evidence) {
    const el = document.getElementById('sosDetailsContent');
    if (!el) return;

    const evidenceHtml = evidence && evidence.length > 0
        ? evidence.map(e => {
            if (e.file_type && e.file_type.startsWith('image/'))
                return `<img src="../${e.file_path}" style="max-width:100%;border-radius:8px;margin-top:8px;" />`;
            if (e.file_type && e.file_type.startsWith('video/'))
                return `<video src="../${e.file_path}" controls style="max-width:100%;border-radius:8px;margin-top:8px;"></video>`;
            return `<a href="../${e.file_path}" target="_blank" style="color:#4fc3f7;">View Evidence File</a>`;
        }).join('')
        : '<p style="color:#666;font-size:13px;margin-top:4px;">No evidence uploaded yet</p>';

    el.innerHTML = `
        <div class="sos-detail-row">
            <i class="fas fa-user-circle"></i>
            <div><label>Victim Name</label><strong>${sos.victim_name || 'Anonymous'}</strong></div>
        </div>
        <div class="sos-detail-row">
            <i class="fas fa-map-marker-alt"></i>
            <div><label>Location</label><strong>${sos.location_text || 'Not available'}</strong></div>
        </div>
        <div class="sos-detail-row">
            <i class="fas fa-exclamation-triangle"></i>
            <div><label>Crime Type</label><strong class="crime-badge">${sos.crime_type || 'Not specified yet'}</strong></div>
        </div>
        <div class="sos-detail-row">
            <i class="fas fa-align-left"></i>
            <div><label>Description</label><p>${sos.description || 'No description provided'}</p></div>
        </div>
        <div class="sos-detail-row">
            <i class="fas fa-clock"></i>
            <div><label>Alert Time</label><strong>${formatTime(sos.created_at)}</strong></div>
        </div>
        <div class="sos-detail-evidence">
            <label><i class="fas fa-paperclip"></i> Evidence</label>
            <div>${evidenceHtml}</div>
        </div>
        <div class="sos-detail-actions">
            <a href="https://maps.google.com?q=${sos.latitude},${sos.longitude}" target="_blank" class="btn-navigate">
                <i class="fas fa-directions"></i> Navigate to Location
            </a>
            ${sos.victim_phone ? `<a href="tel:${sos.victim_phone}" class="btn-call-victim"><i class="fas fa-phone"></i> Call Victim</a>` : ''}
            <a href="tel:999" class="btn-call-police"><i class="fas fa-shield-alt"></i> Call Police</a>
        </div>
    `;
}

function closeSOSDetailsModal() {
    const modal = document.getElementById('sosDetailsModal');
    if (modal) modal.style.display = 'none';
}


// ── UTILS ─────────────────────────────────────────────────────────
function formatTime(str) {
    if (!str) return 'Unknown';
    return new Date(str).toLocaleTimeString('en-BD', { hour: '2-digit', minute: '2-digit' });
}

function playAlertSound() {
    try {
        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.setValueAtTime(660, ctx.currentTime + 0.15);
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.3);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start(); osc.stop(ctx.currentTime + 0.5);
    } catch (e) {}
}

function showError(msg) {
    const statusText = document.getElementById('statusText');
    if (statusText) { statusText.textContent = msg; statusText.style.color = '#e63946'; }
}

function resetSOS() {
    sosActive = false;
    const btn = document.getElementById('sosBtn');
    if (btn) {
        btn.classList.remove('holding', 'sending', 'sent');
        btn.querySelector('span').textContent  = 'SOS';
        btn.querySelector('small').textContent = 'Hold to activate';
    }
    const fill = document.getElementById('holdFill');
    if (fill) fill.style.strokeDashoffset = 452;
    updateStatusBar('Ready to send alert', false);
}


// ── CANCEL SOS ────────────────────────────────────────────────────
function cancelSOS() {
    sosActive = false;
    currentSOSId = null;

    const overlay = document.getElementById('activatedOverlay');
    if (overlay) overlay.classList.remove('active');

    resetSOS();

    ['responderList','logList'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
    const alertLog = document.getElementById('alertLog');
    if (alertLog) alertLog.style.display = 'none';
    ['alertedCount','nearbyCount'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = 0;
    });
}