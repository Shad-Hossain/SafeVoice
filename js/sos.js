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

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${currentLat}&lon=${currentLng}`)
        .then(res => res.json())
        .then(data => {

            currentLocation = data.display_name;

            if (locText) locText.textContent = currentLocation;
            if (activatedLoc) activatedLoc.textContent = currentLocation;
        });

    });
}


// 👀 FAKE SCAN
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
    const statusText = document.getElementById('statusText');
    const statusBar = document.getElementById('statusBar');

    if (btn) btn.classList.add('holding');
    if (statusText) statusText.textContent = 'Hold to activate SOS...';
    if (statusBar) statusBar.className = 'sos-status-bar active-status';

    const circumference = 452;
    holdProgress = 0;

    holdInterval = setInterval(() => {
        holdProgress += circumference / 60;
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
    const statusText = document.getElementById('statusText');
    const statusBar = document.getElementById('statusBar');

    if (btn) btn.classList.remove('holding');
    if (statusText) statusText.textContent = 'Ready to send alert';
    if (statusBar) statusBar.className = 'sos-status-bar';
}


// 🚨 SOS MAIN
function activateSOS() {

    sosActive = true;

    console.log("🚨 SOS FUNCTION TRIGGERED");

    const btn = document.getElementById('sosBtn');

    if (btn) {
        btn.classList.remove('holding');
        btn.classList.add('sent');
        btn.querySelector('span').textContent = 'SENT';
        btn.querySelector('small').textContent = 'Help is coming';
    }

    // 📡 BACKEND CALL
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
    .then(async res => {

        const text = await res.text();
        console.log("RAW RESPONSE:", text);

        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            console.log("JSON ERROR:", e);
            return;
        }

        console.log("SOS RESPONSE:", data);

        if (data.success) {
            currentSOSId = data.sos_id;
            console.log("OPENING MODAL...");
            openEvidenceModal();
        }
    })
    .catch(err => {
        console.log("FETCH ERROR:", err);
    });

    showFakeResponders();
}


// 👥 FAKE UI
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
                    <span>${r.status}</span>
                `;

                responderList.appendChild(card);
            }

            if (alertedCount) alertedCount.textContent = i + 1;

        }, i * 500);
    });

    setTimeout(() => {
        const overlay = document.getElementById('activatedOverlay');
        if (overlay) overlay.classList.add('active');
    }, 1000);
}


// 🧾 MODAL
function openEvidenceModal() {

    const modal = document.getElementById('evidenceModal');

    console.log("MODAL:", modal);

    if (modal) modal.style.display = 'flex';
}


// 📤 EVIDENCE SUBMIT
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
            alert('Evidence uploaded');
            document.getElementById('evidenceModal').style.display = 'none';
        }
    });
}


// ❌ CANCEL
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

    document.getElementById('responderList').innerHTML = '';
    document.getElementById('logList').innerHTML = '';
}