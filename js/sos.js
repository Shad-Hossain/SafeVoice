let holdTimer = null;
let holdInterval = null;
let holdProgress = 0;
let sosActive = false;

const HOLD_DURATION = 3000;

const fakeResponders = [
    { name: 'Rakib Hassan', dist: '120m away', status: 'helping' },
    { name: 'Nadia Islam', dist: '180m away', status: 'notified' },
    { name: 'Arif Hossain', dist: '95m away', status: 'helping' },
    { name: 'Tania Begum', dist: '210m away', status: 'notified' },
    { name: 'Jahid Khan', dist: '155m away', status: 'helping' },
];

let currentSOSId = null;
let currentLat = null;
let currentLng = null;
let currentLocation = '';

window.addEventListener('DOMContentLoaded', () => {
    detectSOSLocation();
    startResponderScan();
});


// 🌍 LOCATION
function detectSOSLocation() {

    const locText = document.getElementById('locationText');
    const activatedLoc = document.getElementById('activatedLocation');

    if (!navigator.geolocation) {
        if (locText) locText.textContent = 'Location unavailable';
        return;
    }

    navigator.geolocation.getCurrentPosition(function(pos) {

        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;

        const lat = currentLat;
        const lng = currentLng;

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(res => res.json())
        .then(data => {

            currentLocation = data.display_name;

            if (locText) locText.textContent = currentLocation;
            if (activatedLoc) activatedLoc.textContent = currentLocation;
        });

    });
}


// 👀 FAKE SCAN UI
function startResponderScan() {

    let count = 0;
    const nearbyCount = document.getElementById('nearbyCount');

    const interval = setInterval(() => {

        count++;
        if (nearbyCount) nearbyCount.textContent = count;

        if (count >= fakeResponders.length) {
            clearInterval(interval);
        }

    }, 800);
}


// 🖐 HOLD START
function startHold() {

    if (sosActive) return;

    const btn = document.getElementById('sosBtn');
    const fill = document.getElementById('holdFill');
    const statusText = document.getElementById('statusText');
    const statusBar = document.getElementById('statusBar');

    if (btn) btn.classList.add('holding');
    if (statusText) statusText.textContent = 'Hold to activate SOS...';
    if (statusBar) statusBar.className = 'sos-status-bar active-status';

    const circumference = 452;
    holdProgress = 0;

    holdInterval = setInterval(() => {
        holdProgress += circumference / 60;
        if (fill) {
            fill.style.strokeDashoffset =
                circumference - Math.min(holdProgress, circumference);
        }
    }, HOLD_DURATION / 60);

    holdTimer = setTimeout(() => {
        activateSOS();
    }, HOLD_DURATION);
}


// 🖐 HOLD CANCEL
function cancelHold() {

    if (sosActive) return;

    clearTimeout(holdTimer);
    clearInterval(holdInterval);

    holdProgress = 0;

    const btn = document.getElementById('sosBtn');
    const fill = document.getElementById('holdFill');
    const statusText = document.getElementById('statusText');
    const statusBar = document.getElementById('statusBar');

    if (btn) btn.classList.remove('holding');
    if (fill) fill.style.strokeDashoffset = 452;
    if (statusText) statusText.textContent = 'Ready to send alert';
    if (statusBar) statusBar.className = 'sos-status-bar';
}


// 🚨 SOS ACTIVATE (MAIN CORE)
function activateSOS() {

    sosActive = true;

    // UI update first
    const btn = document.getElementById('sosBtn');

    if (btn) {
        btn.classList.remove('holding');
        btn.classList.add('sent');
        btn.querySelector('span').textContent = 'SENT';
        btn.querySelector('small').textContent = 'Help is coming';
    }

    // 📡 SEND SOS TO BACKEND
    fetch('../api/create_sos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            latitude: currentLat,
            longitude: currentLng,
            location: currentLocation
        })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            currentSOSId = data.sos_id;
            openEvidenceModal();
        }
    });

    // fake UI responders
    showFakeResponders();
}


// 👥 FAKE RESPONDER UI
function showFakeResponders() {

    const responderList = document.getElementById('responderList');
    const scanPlaceholder = document.getElementById('scanPlaceholder');
    const alertLog = document.getElementById('alertLog');
    const logList = document.getElementById('logList');
    const alertedCount = document.getElementById('alertedCount');

    if (scanPlaceholder) scanPlaceholder.style.display = 'none';
    if (alertLog) alertLog.style.display = 'block';

    fakeResponders.forEach((r, i) => {

        setTimeout(() => {

            if (responderList) {

                const card = document.createElement('div');
                card.className = 'responder-card-item';

                card.innerHTML = `
                    <div class="resp-avatar"><i class="fas fa-user"></i></div>
                    <div class="resp-info">
                        <h5>${r.name}</h5>
                        <p>${r.dist}</p>
                    </div>
                    <span class="resp-status ${r.status}">
                        ${r.status === 'helping' ? 'Responding' : 'Notified'}
                    </span>
                `;

                responderList.appendChild(card);
            }

            if (logList) {
                const log = document.createElement('div');
                log.className = 'log-item';
                log.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <div>${r.name} notified</div>
                `;
                logList.appendChild(log);
            }

            if (alertedCount) alertedCount.textContent = i + 1;

        }, i * 500);
    });

    setTimeout(() => {
        const overlay = document.getElementById('activatedOverlay');
        if (overlay) overlay.classList.add('active');
    }, 1000);
}


// 🧾 OPEN MODAL
function openEvidenceModal() {

    const modal = document.getElementById('evidenceModal');
    if (modal) modal.style.display = 'flex';
}


// 📤 SUBMIT EVIDENCE
function submitEvidence() {

    const crimeType = document.getElementById('crimeType').value;
    const desc = document.getElementById('crimeDesc').value;
    const file = document.getElementById('evidenceFile').files[0];

    const formData = new FormData();

    formData.append('sos_id', currentSOSId);
    formData.append('crime_type', crimeType);
    formData.append('description', desc);

    if (file) formData.append('evidence', file);

    fetch('../api/upload_sos_evidence.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            alert('Evidence uploaded successfully');
            document.getElementById('evidenceModal').style.display = 'none';
        }
    });
}


// ❌ CANCEL SOS
function cancelSOS() {

    sosActive = false;

    const overlay = document.getElementById('activatedOverlay');
    if (overlay) overlay.classList.remove('active');

    const btn = document.getElementById('sosBtn');

    if (btn) {
        btn.classList.remove('sent');
        btn.querySelector('span').textContent = 'SOS';
        btn.querySelector('small').textContent = 'Hold to activate';
    }

    const fill = document.getElementById('holdFill');
    if (fill) fill.style.strokeDashoffset = 452;

    document.getElementById('responderList').innerHTML = '';
    document.getElementById('logList').innerHTML = '';

    const alertLog = document.getElementById('alertLog');
    if (alertLog) alertLog.style.display = 'none';

    const alertedCount = document.getElementById('alertedCount');
    if (alertedCount) alertedCount.textContent = 0;

    const nearbyCount = document.getElementById('nearbyCount');
    if (nearbyCount) nearbyCount.textContent = 0;
}