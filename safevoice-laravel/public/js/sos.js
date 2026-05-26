// ================================================================
// SafeVoice — SOS Core  (Laravel edition)
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
// sos.js content block এর ভেতরে load হয় — DOM ততক্ষণে ready।
// DOMContentLoaded already fired হয়ে গেছে, তাই callback কখনো
// run হতো না। readyState check করে সরাসরি call করি।
function initSOS() {
    detectSOSLocation();
    startResponderScan();
    pollForIncomingAlerts();
    bindSOSButton();
}

if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', initSOS);
} else {
    initSOS();
}


// ── SOS BUTTON binding ───────────────────────────────────────────
function bindSOSButton() {
    const btn = document.getElementById('sosBtn');
    if (!btn) return;
    btn.addEventListener('mousedown',  () => startHold());
    btn.addEventListener('mouseup',    () => cancelHold());
    btn.addEventListener('mouseleave', () => cancelHold());
    btn.addEventListener('touchstart',  e => { e.preventDefault(); startHold();  }, { passive: false });
    btn.addEventListener('touchend',    e => { e.preventDefault(); cancelHold(); }, { passive: false });
    btn.addEventListener('touchcancel', e => { e.preventDefault(); cancelHold(); }, { passive: false });
}


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


// ── RESPONDER SCAN UI ─────────────────────────────────────────────
function startResponderScan() {
    const nearbyCount = document.getElementById('nearbyCount');

    function fetchRealCount() {
        if (!currentLat || !currentLng) {
            setTimeout(fetchRealCount, 1000);
            return;
        }
        const serverUser = window.SV_SERVER_USER || null;
        const svUser     = serverUser || JSON.parse(localStorage.getItem('sv_user') || '{}');
        const uid        = svUser.id || 0;
        const params     = new URLSearchParams({ lat: currentLat, lng: currentLng });
        if (uid) params.append('user_id', uid);

        fetch('/api/sos/nearby-count?' + params.toString(), { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (nearbyCount) nearbyCount.textContent = data.count || 0;
            })
            .catch(() => {
                if (nearbyCount) nearbyCount.textContent = '0';
            });
    }

    fetchRealCount();
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


// ── ACTIVATE SOS ─────────────────────────────────────────────────
async function activateSOS() {
    // Server-side session check (সবচেয়ে reliable — stale localStorage এর সমস্যা নেই)
    // window.SV_SERVER_USER → sos.blade.php এ PHP session থেকে inject হয়
   const serverUser = window.SV_SERVER_USER || null;
    const svUserRaw  = localStorage.getItem('sv_user');
    const svUser     = serverUser || (svUserRaw ? JSON.parse(svUserRaw) : {});
    const userId     = svUser.id || 0;

    if (!userId) {
        // Login ছাড়া — phone number দিতে বলব
        showAnonymousSosModal();
        return;
    }

    await _doActivateSOS(userId, null, null);
}

// ── ANONYMOUS SOS MODAL ──────────────────────────────────────────
function showAnonymousSosModal() {
    cancelHold(); // Hold animation reset
    const modal = document.getElementById('anonymousSosModal');
    if (modal) modal.style.display = 'flex';
}

function closeAnonymousSosModal() {
    const modal = document.getElementById('anonymousSosModal');
    if (modal) modal.style.display = 'none';
}

async function proceedAnonymousSos() {
    const phoneInput = document.getElementById('anonPhone');
    const nameInput  = document.getElementById('anonName');
    const phone = phoneInput ? phoneInput.value.trim() : '';
    const name  = nameInput  ? nameInput.value.trim()  : 'Anonymous';

    if (!phone || phone.length < 10) {
        const errEl = document.getElementById('anonSosErr');
        if (errEl) { errEl.textContent = 'অনুগ্রহ করে সঠিক phone number দাও।'; errEl.style.display = 'block'; }
        return;
    }

    closeAnonymousSosModal();
    await _doActivateSOS(0, phone, name);
}

async function _doActivateSOS(userId, contactPhone, contactName) {
    sosActive = true;
    const btn  = document.getElementById('sosBtn');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (btn) {
        btn.classList.remove('holding');
        btn.classList.add('sending');
        btn.querySelector('span').textContent  = 'SENDING...';
        btn.querySelector('small').textContent = 'Broadcasting alert';
    }
    updateStatusBar('Sending SOS alert...', true);

    try {
        // STEP 1: Create SOS
        const createRes  = await fetch('/api/sos/create', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                latitude:      currentLat,
                longitude:     currentLng,
                location:      currentLocation,
                user_id:       userId,
                contact_phone: contactPhone,
                contact_name:  contactName,
            }),
        });
        if (!createRes.ok) {
            showError('Server error (' + createRes.status + '). Please try again.');
            resetSOS(); return;
        }
        const createData = await createRes.json();
        if (!createData.success) { showError('Failed to create SOS: ' + (createData.message||'')); resetSOS(); return; }
        currentSOSId = createData.sos_id;

        // STEP 2: Notify nearby users
        const notifRes  = await fetch('/api/sos/notify', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ sos_id: currentSOSId, latitude: currentLat, longitude: currentLng, location: currentLocation, user_id: userId }),
        });
        const notifData     = await notifRes.json();
        const notifiedCount = notifData.notified_count || 0;

        // STEP 3: Update button
        if (btn) {
            btn.classList.remove('sending');
            btn.classList.add('sent');
            btn.querySelector('span').textContent  = 'SENT';
            btn.querySelector('small').textContent = 'Help is coming';
        }
        updateStatusBar('Alert sent to ' + notifiedCount + ' people nearby!', false);
       showRealResponders(notifiedCount, notifData);
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


// ── FAKE RESPONDERS ───────────────────────────────────────────────
// ── REAL RESPONDERS (SOS send এর পরে) ───────────────────────────
function showRealResponders(notifiedCount, notifData) {
    const responderList   = document.getElementById('responderList');
    const scanPlaceholder = document.getElementById('scanPlaceholder');
    const alertLog        = document.getElementById('alertLog');
    const logList         = document.getElementById('logList');
    const alertedCount    = document.getElementById('alertedCount');

    if (scanPlaceholder) scanPlaceholder.style.display = 'none';
    if (alertLog)        alertLog.style.display = 'block';
    if (alertedCount)    alertedCount.textContent = notifiedCount;

    if (notifiedCount === 0) {
        if (responderList) {
            const card = document.createElement('div');
            card.className = 'responder-card-item';
            card.innerHTML = `<div class="resp-avatar"><i class="fas fa-search"></i></div>
                <div class="resp-info"><h5>Scanning area...</h5><p>No responders found nearby yet</p></div>
                <span class="resp-status notified">Waiting</span>`;
            responderList.appendChild(card);
        }
        if (logList) {
            const log = document.createElement('div');
            log.className = 'log-item';
            log.innerHTML = `<i class="fas fa-info-circle"></i><div>Alert broadcast sent. Waiting for responders.</div>`;
            logList.appendChild(log);
        }
        setTimeout(() => {
            const overlay = document.getElementById('activatedOverlay');
            if (overlay) overlay.classList.add('active');
        }, 500);
        return;
    }

    const toShow = Math.min(notifiedCount, 5);
    for (let i = 0; i < toShow; i++) {
        setTimeout(() => {
            if (responderList) {
                const card = document.createElement('div');
                card.className = 'responder-card-item';
                card.innerHTML = `<div class="resp-avatar"><i class="fas fa-user-shield"></i></div>
                    <div class="resp-info"><h5>Nearby Responder</h5><p>Notified — awaiting response</p></div>
                    <span class="resp-status notified">Notified</span>`;
                responderList.appendChild(card);
            }
            if (logList) {
                const log = document.createElement('div');
                log.className = 'log-item';
                log.innerHTML = `<i class="fas fa-check-circle"></i><div>Responder #${i + 1} notified</div>`;
                logList.appendChild(log);
            }
            if (alertedCount) alertedCount.textContent = i + 1;
        }, i * 400);
    }

    setTimeout(() => {
        const overlay = document.getElementById('activatedOverlay');
        if (overlay) overlay.classList.add('active');
    }, 1000);
}

// ── VICTIM: EVIDENCE MODAL (SOS create এর পর) ────────────────────
function openEvidenceModal() {
    const modal = document.getElementById('evidenceModal');
    if (!modal) return;
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('visible'), 10);

    // Login ছাড়া থাকলে phone field দেখাব
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    const isLoggedIn = !!(svUser.id || svUser.user_id);
    const phoneField = document.getElementById('anonPhoneField');
    if (phoneField) {
        phoneField.style.display = isLoggedIn ? 'none' : 'block';
    }
    // Anonymous modal থেকে phone নেওয়া হয়ে থাকলে pre-fill করি
    const anonPhoneInput = document.getElementById('anonPhone');
    const evidencePhoneInput = document.getElementById('evidenceContactPhone');
    if (!isLoggedIn && anonPhoneInput && evidencePhoneInput && anonPhoneInput.value) {
        evidencePhoneInput.value = anonPhoneInput.value;
    }
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
        vid.src = URL.createObjectURL(file); vid.controls = true;
        vid.style.cssText = 'max-width:100%;border-radius:8px;margin-top:8px;';
        preview.appendChild(vid);
    } else {
        const p = document.createElement('p');
        p.style.cssText = 'color:#a0b4cc;font-size:13px;margin-top:8px;';
        p.textContent   = `Attached: ${file.name} (${(file.size/1024).toFixed(1)} KB)`;
        preview.appendChild(p);
    }
}

// VICTIM এর evidence submit (crime type + description + file)
async function submitEvidence() {
    const crimeType = document.getElementById('crimeType').value;
    const desc      = document.getElementById('crimeDesc').value.trim();
    const fileInput = document.getElementById('evidenceFile');
    const submitBtn = document.getElementById('submitEvidenceBtn');
    if (!crimeType) { showModalMsg('Please select a crime type', 'error'); return; }

    const formData = new FormData();
    formData.append('sos_id',      currentSOSId);
    formData.append('crime_type',  crimeType);
    formData.append('description', desc);

    // Anonymous user এর phone number যোগ করি (DB তে update হবে)
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    if (!svUser.id && !svUser.user_id) {
        const phone = document.getElementById('evidenceContactPhone')?.value?.trim() || '';
        const name  = document.getElementById('anonName')?.value?.trim() || '';
        if (phone) formData.append('contact_phone', phone);
        if (name)  formData.append('contact_name', name);
    }

    if (fileInput && fileInput.files[0]) formData.append('evidence[0]', fileInput.files[0]);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (csrf) formData.append('_token', csrf);
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...'; }
    try {
        const res  = await fetch('/api/upload_sos_evidence', { method: 'POST', credentials: 'include', body: formData });
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
    if (el) { el.textContent = msg; el.className = 'modal-msg ' + type; el.style.display = 'block'; }
}


// ── INCOMING ALERTS (Responder Side) ─────────────────────────────
let pollingInterval   = null;
let lastSeenSosId     = null;
let isFirstPoll       = true;   // প্রথম poll এ শুধু baseline set করব, alert দেখাব না

function pollForIncomingAlerts() {
    checkIncomingAlerts();
    pollingInterval = setInterval(checkIncomingAlerts, 8000); // 8 sec polling
}

async function checkIncomingAlerts() {
    try {
        // Server-side session check (reliable) + localStorage fallback
       const serverUser = window.SV_SERVER_USER || null;
        const svUser     = serverUser || JSON.parse(localStorage.getItem('sv_user') || '{}');
        const uid        = svUser.id || 0;
        const params = uid ? '?user_id=' + uid : '';
        const res    = await fetch('/api/sos/my-notifications' + params, { credentials: 'include' });
        const data   = await res.json();
        if (data.success && data.notifications && data.notifications.length > 0) {
            const newest = data.notifications[0];
            if (isFirstPoll) {
                // প্রথম poll: শুধু current state মনে রাখব, কোনো alert দেখাব না
                lastSeenSosId = newest.sos_id;
                isFirstPoll   = false;
                return;
            }
            if (newest.sos_id !== lastSeenSosId) {
                lastSeenSosId = newest.sos_id;
                showIncomingAlert(newest);
            }
        } else if (isFirstPoll) {
            isFirstPoll = false; // কোনো notification নেই, পরবর্তী পোল থেকে নতুন আসলে দেখাবে
        }
    } catch (e) { /* silent */ }
}

function showIncomingAlert(notif) {
    const panel = document.getElementById('incomingAlertPanel');
    if (!panel || panel.classList.contains('visible')) return;
    document.getElementById('incomingVictimName').textContent = notif.victim_name   || 'Unknown';
    document.getElementById('incomingLocation').textContent   = notif.location_text || 'Location unknown';
    document.getElementById('incomingCrimeType').textContent  = notif.crime_type    || 'Not specified yet';
    document.getElementById('incomingTime').textContent       = formatTime(notif.sos_time);
    panel.dataset.sosId = notif.sos_id;
    panel.classList.add('visible');
    playAlertSound();
}

function viewSOSDetails() {
    const panel = document.getElementById('incomingAlertPanel');
    const sosId = panel?.dataset.sosId;
    if (!sosId) return;

    // 1. Record respond in DB
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    fetch('/api/sos/respond', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body:    JSON.stringify({ sos_id: parseInt(sosId) }),
    });

    // 2. Dismiss the incoming panel immediately
    dismissIncomingAlert();

    // 3. Fetch victim coords then open Google Maps navigation (my location → victim)
    fetch(`/api/sos/alerts?sos_id=${sosId}`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.sos) {
                const lat = data.sos.latitude;
                const lng = data.sos.longitude;
                if (lat && lng) {
                    // Google Maps: directions from current location to victim
                    window.open(
                        `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`,
                        '_blank'
                    );
                }
            }
        })
        .catch(() => {});
    // No modal — navigation IS the response
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
        const res  = await fetch(`/api/sos/alerts?sos_id=${sosId}`, { credentials: 'include' });
        const data = await res.json();
        if (data.success && data.sos) renderSOSDetails(data.sos, data.evidence || [], sosId);
    } catch (e) {
        document.getElementById('sosDetailsContent').innerHTML =
            '<p style="color:#e63946;text-align:center;">Could not load details.</p>';
    }
}

function renderSOSDetails(sos, evidence, sosId) {
    const el = document.getElementById('sosDetailsContent');
    if (!el) return;
    const evidenceHtml = evidence && evidence.length > 0
        ? evidence.map(e => {
            if (e.file_type && e.file_type.startsWith('image/'))
                return `<img src="/${e.file_path}" style="max-width:100%;border-radius:8px;margin-top:8px;" />`;
            if (e.file_type && e.file_type.startsWith('video/'))
                return `<video src="/${e.file_path}" controls style="max-width:100%;border-radius:8px;margin-top:8px;"></video>`;
            return `<a href="/${e.file_path}" target="_blank" style="color:#4fc3f7;">View Evidence File</a>`;
        }).join('')
        : '<p style="color:#666;font-size:13px;margin-top:4px;">No evidence uploaded yet</p>';

    el.innerHTML = `
        <div class="sos-detail-row"><i class="fas fa-user-circle"></i>
            <div><label>Victim Name</label><strong>${sos.victim_name || 'Anonymous'}</strong></div></div>
        <div class="sos-detail-row"><i class="fas fa-map-marker-alt"></i>
            <div><label>Location</label><strong>${sos.location_text || 'Not available'}</strong></div></div>
        <div class="sos-detail-row"><i class="fas fa-exclamation-triangle"></i>
            <div><label>Crime Type</label><strong class="crime-badge">${sos.crime_type || 'Not specified yet'}</strong></div></div>
        <div class="sos-detail-row"><i class="fas fa-align-left"></i>
            <div><label>Description</label><p>${sos.description || 'No description provided'}</p></div></div>
        <div class="sos-detail-row"><i class="fas fa-clock"></i>
            <div><label>Alert Time</label><strong>${formatTime(sos.created_at)}</strong></div></div>
        <div class="sos-detail-evidence"><label><i class="fas fa-paperclip"></i> Evidence</label><div>${evidenceHtml}</div></div>
        <div class="sos-detail-actions">
            <button onclick="navigateToVictim('${sos.latitude}','${sos.longitude}')" class="btn-navigate">
                <i class="fas fa-directions"></i> Navigate to Spot</button>
            ${sos.victim_phone ? `<a href="tel:${sos.victim_phone}" class="btn-call-victim"><i class="fas fa-phone"></i> Call Victim</a>` : ''}
            <a href="tel:999" class="btn-call-police"><i class="fas fa-shield-alt"></i> Call Police</a>
        </div>
        <div style="margin-top:12px;">
            <button onclick="closeSOSDetailsModal()" class="btn-dismiss-modal">
                <i class="fas fa-times"></i> Dismiss
            </button>
        </div>

        <!-- ── RESPONDER EVIDENCE UPLOAD ─────────────────────── -->
        <div class="responder-evidence-section" id="responderEvidenceSection-${sosId}">
            <div class="rev-header">
                <i class="fas fa-camera"></i>
                <div>
                    <h4>Upload Your Evidence</h4>
                    <p>Photo বা video তুলে submit করো — admin verify করলে তোমার rank বাড়বে</p>
                </div>
            </div>
            <div class="rev-upload-area" onclick="document.getElementById('responderEvidenceFile-${sosId}').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Tap to choose photo or video</span>
                <small>JPG, PNG, MP4, MOV — max 50MB</small>
            </div>
            <input type="file" id="responderEvidenceFile-${sosId}" style="display:none;"
                   accept="image/*,video/*"
                   onchange="previewResponderFile(this, '${sosId}')">
            <div id="responderEvidencePreview-${sosId}" style="margin-top:8px;"></div>
            <div id="responderEvidenceMsg-${sosId}" style="display:none; margin-top:8px; padding:10px 14px; border-radius:8px; font-size:13px;"></div>
            <button class="btn-submit-responder-evidence"
                    id="submitResponderEvidenceBtn-${sosId}"
                    onclick="submitResponderEvidence('${sosId}')">
                <i class="fas fa-paper-plane"></i> Submit Evidence for Verification
            </button>
        </div>`;
}

function closeSOSDetailsModal() {
    const modal = document.getElementById('sosDetailsModal');
    if (modal) modal.style.display = 'none';
}


// ── RESPONDER EVIDENCE UPLOAD ─────────────────────────────────────
function previewResponderFile(input, sosId) {
    const preview = document.getElementById(`responderEvidencePreview-${sosId}`);
    const file    = input.files[0];
    if (!file || !preview) return;
    preview.innerHTML = '';
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src   = URL.createObjectURL(file);
        img.style.cssText = 'max-width:100%;border-radius:8px;';
        preview.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const vid = document.createElement('video');
        vid.src = URL.createObjectURL(file); vid.controls = true;
        vid.style.cssText = 'max-width:100%;border-radius:8px;';
        preview.appendChild(vid);
    } else {
        preview.innerHTML = `<p style="color:#a0b4cc;font-size:13px;">${file.name} (${(file.size/1024/1024).toFixed(1)} MB)</p>`;
    }
}

async function submitResponderEvidence(sosId) {
    const fileInput = document.getElementById(`responderEvidenceFile-${sosId}`);
    const btn       = document.getElementById(`submitResponderEvidenceBtn-${sosId}`);
    const msgEl     = document.getElementById(`responderEvidenceMsg-${sosId}`);

    if (!fileInput || !fileInput.files[0]) {
        showResponderEvidenceMsg(sosId, 'Please choose a photo or video first.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('sos_id',   sosId);
    formData.append('evidence', fileInput.files[0]);

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (csrf) formData.append('_token', csrf);

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...'; }

    try {
        const res  = await fetch('/api/sos/upload-evidence', {
            method: 'POST',
            credentials: 'include',
            body: formData,
        });
        const data = await res.json();

        if (data.success) {
            showResponderEvidenceMsg(sosId,
                '✅ Evidence submitted! Admin will verify soon. Your ranking will update after approval.',
                'success');
            // Upload area আর button লুকিয়ে দেব
            const section = document.getElementById(`responderEvidenceSection-${sosId}`);
            if (section) {
                const uploadArea = section.querySelector('.rev-upload-area');
                if (uploadArea) uploadArea.style.display = 'none';
                if (btn) btn.style.display = 'none';
            }
        } else {
            showResponderEvidenceMsg(sosId, '❌ ' + (data.message || 'Upload failed. Try again.'), 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence for Verification'; }
        }
    } catch (err) {
        showResponderEvidenceMsg(sosId, '❌ Network error. Check your connection.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence for Verification'; }
    }
}

function showResponderEvidenceMsg(sosId, msg, type) {
    const el = document.getElementById(`responderEvidenceMsg-${sosId}`);
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = type === 'success' ? '#1a3a2a' : '#3a1a1a';
    el.style.color      = type === 'success' ? '#4caf50' : '#e53e3e';
    el.style.border     = `1px solid ${type === 'success' ? '#4caf5040' : '#e53e3e40'}`;
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

function cancelSOS() {
    // DB te status = cancelled set koro
    if (currentSOSId) {
        const csrf   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        fetch('/api/sos/cancel', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body:        JSON.stringify({ sos_id: currentSOSId, user_id: svUser.id || svUser.user_id }),
        }).catch(() => {});
    }
    sosActive    = false;
    currentSOSId = null;
    const overlay = document.getElementById('activatedOverlay');
    if (overlay) overlay.classList.remove('active');
    resetSOS();
    ['responderList', 'logList'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
    const alertLog = document.getElementById('alertLog');
    if (alertLog) alertLog.style.display = 'none';
    ['alertedCount', 'nearbyCount'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = 0;
    });
}

// ── NAVIGATION HELPER ─────────────────────────────────────────────
// Google Maps directions: browser/device current location → victim
function navigateToVictim(lat, lng) {
    if (!lat || !lng) return;
    window.open(
        `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`,
        '_blank'
    );
}