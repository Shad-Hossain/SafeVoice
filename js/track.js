// ── Progress steps ────────────────────────────
const steps = [
    { label: 'Submitted',        desc: 'Complaint received by SafeVoice' },
    { label: 'Under Review',     desc: 'Being reviewed by the admin team' },
    { label: 'Officer Assigned', desc: 'An officer is handling your case' },
    { label: 'Investigation',    desc: 'Active investigation ongoing' },
    { label: 'Resolved',         desc: 'Case has been closed' }
];

const statusStepMap = {
    'Submitted':    1,
    'Under Review': 2,
    'Resolved':     5,
    'Rejected':     5
};

// ── Tab switch ────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    hideResult();
    hideError();
}

// ── Track complaint — real API ─────────────────
async function trackComplaint() {
    hideResult();
    hideError();

    const id = document.getElementById('complaintIdInput').value.trim().toUpperCase();
    if (!id) {
        showError('Please enter a Complaint ID.');
        return;
    }

    // Show loading
    const btn = document.querySelector('.btn-search');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
    btn.disabled  = true;

    try {
        const res  = await fetch('../api/get_complaint.php?id=' + encodeURIComponent(id));
        const data = await res.json();

        if (!data.success) {
            showError('No complaint found with ID: ' + id);
            return;
        }

        renderResult(data.complaint);

    } catch (err) {
        showError('Could not connect to server. Make sure XAMPP is running.');
    } finally {
        btn.innerHTML = '<i class="fas fa-search"></i> Track';
        btn.disabled  = false;
    }
}

// ── Render result ─────────────────────────────
function renderResult(c) {
    document.getElementById('rId').textContent       = c.complaint_id;
    document.getElementById('rType').textContent     = 'Incident Type: ' + formatType(c.type);
    document.getElementById('rDate').textContent     = formatDate(c.submitted_at);
    document.getElementById('rLocation').textContent = c.location || '—';
    document.getElementById('rAnon').textContent     = c.is_anonymous == 1 ? 'Yes' : 'No';
    document.getElementById('rOfficer').textContent  = 'Admin Team';
    document.getElementById('adminMsgText').textContent = getStatusMessage(c.status);

    // Status badge
    const statusEl     = document.getElementById('rStatus');
    statusEl.textContent = c.status;
    statusEl.className   = 'status ' + statusClass(c.status);

    // Progress tracker
    const currentStep  = statusStepMap[c.status] || 1;
    const container    = document.getElementById('trackerSteps');
    container.innerHTML = '';

    steps.forEach((s, i) => {
        const stepNum   = i + 1;
        const isDone    = stepNum < currentStep;
        const isCurrent = stepNum === currentStep;
        const cls       = isDone ? 'done' : isCurrent ? 'current' : '';
        const icon      = isDone ? '<i class="fas fa-check"></i>' : stepNum;

        container.innerHTML += `
            <div class="tracker-step ${cls}">
                <div class="step-dot">${icon}</div>
                <div class="step-info">
                    <h5>${s.label}</h5>
                    <p>${isCurrent ? s.desc : isDone ? 'Completed' : 'Waiting'}</p>
                </div>
            </div>`;
    });

    document.getElementById('resultCard').classList.add('visible');
}

// ── Helpers ───────────────────────────────────
function formatType(t) {
    const map = { harassment:'Harassment', fare_overcharge:'Fare Overcharge', crime:'Crime', corruption:'Corruption', abuse:'Abuse', other:'Other' };
    return map[t] || t;
}

function formatDate(d) {
    if (!d) return '—';
    const fixed = d.replace(' ', 'T');
    const date  = new Date(fixed);
    if (isNaN(date)) return d;
    return date.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function statusClass(s) {
    const map = { 'Submitted':'review', 'Under Review':'pending', 'Resolved':'resolved', 'Rejected':'rejected' };
    return map[s] || 'review';
}

function getStatusMessage(s) {
    const map = {
        'Submitted':    'Your complaint has been received. It will be reviewed by our team shortly.',
        'Under Review': 'Your complaint is currently being reviewed by our admin team.',
        'Resolved':     'Your complaint has been resolved. Thank you for reporting.',
        'Rejected':     'Your complaint could not be processed. Please contact support for details.'
    };
    return map[s] || 'Status update pending.';
}

function showError(msg) {
    const el = document.getElementById('errorMsg');
    el.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg || 'No complaint found.'}`;
    el.classList.add('visible');
}
function hideError()  { document.getElementById('errorMsg').classList.remove('visible'); }
function hideResult() { document.getElementById('resultCard').classList.remove('visible'); }

// ── Enter key support ─────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('complaintIdInput');
    if (el) el.addEventListener('keypress', e => { if (e.key === 'Enter') trackComplaint(); });

    // Auto-fill from URL param (e.g. from dashboard View button)
    const params = new URLSearchParams(window.location.search);
    const id     = params.get('id');
    if (id) {
        document.getElementById('complaintIdInput').value = id;
        trackComplaint();
    }
});
