
let holdTimer = null;
let holdInterval = null;
let holdProgress = 0;
let sosActive = false;
const HOLD_DURATION = 3000; // 3 seconds hold

const fakeResponders = [
    { name: 'Rakib Hassan',  dist: '120m away', status: 'helping' },
    { name: 'Nadia Islam',   dist: '180m away', status: 'notified' },
    { name: 'Arif Hossain',  dist: '95m away',  status: 'helping' },
    { name: 'Tania Begum',   dist: '210m away', status: 'notified' },
    { name: 'Jahid Khan',    dist: '155m away', status: 'helping' },
];

// Detect location on load
window.addEventListener('DOMContentLoaded', () => {
    detectSOSLocation();
    startResponderScan();
});

function detectSOSLocation() {
    const locText = document.getElementById('locationText');
    const activatedLoc = document.getElementById('activatedLocation');
    if (!navigator.geolocation) {
        if (locText) locText.textContent = 'Location unavailable';
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function (pos) {
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
                .then(r => r.json())
                .then(data => {
                    const loc = data.display_name || `${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`;
                    if (locText) locText.textContent = loc;
                    if (activatedLoc) activatedLoc.textContent = loc;
                })
                .catch(() => {
                    const loc = `${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`;
                    if (locText) locText.textContent = loc;
                    if (activatedLoc) activatedLoc.textContent = loc;
                });
        },
        function () {
            if (locText) locText.textContent = 'Mirpur, Dhaka (estimated)';
            if (activatedLoc) activatedLoc.textContent = 'Mirpur, Dhaka (estimated)';
        }
    );
}

function startResponderScan() {
    let count = 0;
    const nearbyCount = document.getElementById('nearbyCount');
    const scanInterval = setInterval(() => {
        count++;
        if (nearbyCount) nearbyCount.textContent = count;
        if (count >= fakeResponders.length) clearInterval(scanInterval);
    }, 800);
}

// HOLD TO ACTIVATE
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
    const steps = 60;
    const stepTime = HOLD_DURATION / steps;

    holdInterval = setInterval(() => {
        holdProgress += (circumference / steps);
        if (fill) fill.style.strokeDashoffset = circumference - Math.min(holdProgress, circumference);
    }, stepTime);

    holdTimer = setTimeout(() => {
        activateSOS();
    }, HOLD_DURATION);
}

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

function activateSOS() {
    sosActive = true;
    clearInterval(holdInterval);
    const btn = document.getElementById('sosBtn');
    if (btn) {
        btn.classList.remove('holding');
        btn.classList.add('sent');
        btn.querySelector('span').textContent = 'SENT';
        btn.querySelector('small').textContent = 'Help is coming';
    }

    // Show responders
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
                    <span class="resp-status ${r.status}">${r.status === 'helping' ? 'Responding' : 'Notified'}</span>
                `;
                responderList.appendChild(card);
            }
            if (logList) {
                const log = document.createElement('div');
                log.className = 'log-item';
                log.innerHTML = `<i class="fas fa-check-circle"></i><div><div>${r.name} notified</div><div class="log-time">just now</div></div>`;
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
    const statusText = document.getElementById('statusText');
    const statusBar = document.getElementById('statusBar');
    if (statusText) statusText.textContent = 'Alert cancelled';
    if (statusBar) statusBar.className = 'sos-status-bar';

    const fill = document.getElementById('holdFill');
    if (fill) fill.style.strokeDashoffset = 452;

    // Clear responders
    const responderList = document.getElementById('responderList');
    if (responderList) {
        const items = responderList.querySelectorAll('.responder-card-item');
        items.forEach(i => i.remove());
        const placeholder = document.getElementById('scanPlaceholder');
        if (placeholder) placeholder.style.display = 'flex';
    }
    const logList = document.getElementById('logList');
    if (logList) logList.innerHTML = '';
    const alertLog = document.getElementById('alertLog');
    if (alertLog) alertLog.style.display = 'none';
    const alertedCount = document.getElementById('alertedCount');
    if (alertedCount) alertedCount.textContent = 0;
    const nearbyCount = document.getElementById('nearbyCount');
    if (nearbyCount) nearbyCount.textContent = 0;
}
