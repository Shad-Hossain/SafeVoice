// FAKE DATA 
const fakeComplaints = {
    'SV-2026-1001': {
        id: 'SV-2026-1001',
        type: 'Harassment',
        date: 'May 01, 2026',
        location: 'Mirpur, Dhaka',
        anonymous: 'No',
        officer: 'Inspector Karim',
        status: 'resolved',
        statusLabel: 'Resolved',
        currentStep: 5,
        adminMsg: 'Your complaint has been resolved. The accused has been warned and documented.'
    },
    'SV-2026-2002': {
        id: 'SV-2026-2002',
        type: 'Fare Overcharge',
        date: 'May 03, 2026',
        location: 'Motijheel, Dhaka',
        anonymous: 'Yes',
        officer: 'Not Assigned Yet',
        status: 'pending',
        statusLabel: 'Pending',
        currentStep: 1,
        adminMsg: 'Your complaint has been received. An officer will be assigned shortly.'
    },
    'SV-2026-3003': {
        id: 'SV-2026-3003',
        type: 'Corruption',
        date: 'May 05, 2026',
        location: 'Gulshan, Dhaka',
        anonymous: 'No',
        officer: 'Inspector Rahman',
        status: 'review',
        statusLabel: 'Under Review',
        currentStep: 3,
        adminMsg: 'Investigation is ongoing. We have collected initial evidence. Please be patient.'
    }
};

// Anonymous token -> complaint ID mapping
const tokenMap = {
    'TOKEN-ABCD-1234': 'SV-2026-2002',
    'TOKEN-EFGH-5678': 'SV-2026-3003'
};

// Progress steps
const steps = [
    { label: 'Submitted',           desc: 'Complaint received by SafeVoice' },
    { label: 'Under Review',        desc: 'Being reviewed by admin team' },
    { label: 'Officer Assigned',    desc: 'An officer is handling your case' },
    { label: 'Investigation',       desc: 'Active investigation ongoing' },
    { label: 'Resolved',            desc: 'Case has been closed' }
];

// TAB SWITCH
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');

    hideResult();
    hideError();
}

//  TRACK 
function trackComplaint() {
    hideResult();
    hideError();

    const idInput    = document.getElementById('complaintIdInput').value.trim().toUpperCase();
    const tokenInput = document.getElementById('tokenInput').value.trim().toUpperCase();

    let complaint = null;

    if (idInput) {
        complaint = fakeComplaints[idInput] || null;
    } else if (tokenInput) {
        const mappedId = tokenMap[tokenInput];
        complaint = mappedId ? fakeComplaints[mappedId] : null;
    }

    if (!complaint) {
        showError();
        return;
    }

    renderResult(complaint);
}

//  RENDER RESULT 
function renderResult(c) {
    document.getElementById('rId').textContent       = c.id;
    document.getElementById('rType').textContent     = 'Incident Type: ' + c.type;
    document.getElementById('rDate').textContent     = c.date;
    document.getElementById('rLocation').textContent = c.location;
    document.getElementById('rAnon').textContent     = c.anonymous;
    document.getElementById('rOfficer').textContent  = c.officer;
    document.getElementById('adminMsgText').textContent = c.adminMsg;

    // Status badge
    const statusEl = document.getElementById('rStatus');
    statusEl.textContent = c.statusLabel;
    statusEl.className   = 'status ' + c.status;

    // Build tracker steps
    const container = document.getElementById('trackerSteps');
    container.innerHTML = '';

    steps.forEach((s, i) => {
        const stepNum  = i + 1;
        const isDone    = stepNum < c.currentStep;
        const isCurrent = stepNum === c.currentStep;

        const cls = isDone ? 'done' : isCurrent ? 'current' : '';
        const icon = isDone ? '<i class="fas fa-check"></i>' : stepNum;

        container.innerHTML += `
            <div class="tracker-step ${cls}">
                <div class="step-dot">${icon}</div>
                <div class="step-info">
                    <h5>${s.label}</h5>
                    <p>${isCurrent ? s.desc : isDone ? 'Completed' : 'Waiting'}</p>
                </div>
            </div>
        `;
    });

    document.getElementById('resultCard').classList.add('visible');
}

function showError()  { document.getElementById('errorMsg').classList.add('visible'); }
function hideError()  { document.getElementById('errorMsg').classList.remove('visible'); }
function hideResult() { document.getElementById('resultCard').classList.remove('visible'); }

// ENTER KEY SUPPORT 
document.addEventListener('DOMContentLoaded', () => {
    ['complaintIdInput', 'tokenInput'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keypress', e => {
            if (e.key === 'Enter') trackComplaint();
        });
    });
});