let selectedBudget   = '';
let selectedDistrict = '';
let selectedType     = ''; // 'instant' | 'scheduled'

// ── DISTRICT LIST ──────────────────────────────────────────
const BD_DISTRICTS = [
    'Dhaka','Chittagong','Rajshahi','Khulna','Barisal','Sylhet','Rangpur','Mymensingh',
    'Comilla','Narayanganj','Gazipur','Narsingdi','Munshiganj','Manikganj','Tangail',
    'Faridpur','Gopalganj','Madaripur','Shariatpur','Rajbari','Kishoreganj','Netrokona',
    'Jamalpur','Sherpur','Bogura','Joypurhat','Chapai Nawabganj','Naogaon','Natore',
    'Sirajganj','Pabna','Jessore','Satkhira','Magura','Jhenaidah','Narail','Kushtia',
    'Chuadanga','Meherpur','Bagerhat','Barguna','Bhola','Jhalokathi','Patuakhali',
    'Pirojpur',"Cox's Bazar",'Bandarban','Rangamati','Khagrachhari','Feni','Lakshmipur',
    'Noakhali','Chandpur','Brahmanbaria','Habiganj','Moulvibazar','Sunamganj',
    'Gaibandha','Kurigram','Lalmonirhat','Nilphamari','Panchagarh','Thakurgaon','Dinajpur'
];

// ── INIT ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // District dropdown
    const distSelect = document.getElementById('preferredDistrict');
    if (distSelect) {
        BD_DISTRICTS.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d; opt.textContent = d;
            distSelect.appendChild(opt);
        });
        distSelect.addEventListener('change', e => selectedDistrict = e.target.value);
    }

    // Deadline min = now + 2 hours
    setDeadlineMin();

    document.getElementById('successModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});

function setDeadlineMin() {
    const dl = document.getElementById('deadline');
    if (!dl) return;
    const pad = n => String(n).padStart(2,'0');
    const min = new Date(Date.now() + 2*60*60*1000);
    dl.min = `${min.getFullYear()}-${pad(min.getMonth()+1)}-${pad(min.getDate())}T${pad(min.getHours())}:${pad(min.getMinutes())}`;
    dl.value = '';
}

// ── TYPE SELECTION (Instant / Scheduled) ───────────────────
function selectType(type) {
    selectedType = type;
    document.getElementById('typeInstant').classList.toggle('selected', type === 'instant');
    document.getElementById('typeScheduled').classList.toggle('selected', type === 'scheduled');

    const dg = document.getElementById('deadlineGroup');
    if (type === 'instant') {
        dg.style.display = 'none';
        document.getElementById('deadline').value = '';
    } else {
        dg.style.display = 'block';
        setDeadlineMin();
    }
}

// ── BUDGET SELECTION ──────────────────────────────────────
function selectBudget(el, value) {
    document.querySelectorAll('.budget-btn[data-budget]').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    selectedBudget = value;
}

// ── SUBMIT ────────────────────────────────────────────────
async function submitLegalRequest() {
    const issueType    = document.getElementById('issueType').value;
    const issueDesc    = document.getElementById('issueDesc').value.trim();
    const contactPhone = document.getElementById('contactPhone').value.trim();
    const deadline     = document.getElementById('deadline').value;

    if (!issueType)            { alert('Please select a legal issue type.'); return; }
    if (issueDesc.length < 20) { alert('Please describe your issue in at least 20 characters.'); return; }
    if (!selectedBudget)       { alert('Please select a budget range.'); return; }
    if (!contactPhone)         { alert('Contact number is required.'); return; }
    if (contactPhone.replace(/\D/g,'').length < 10) { alert('Please enter a valid phone number.'); return; }
    if (!selectedDistrict)     { alert('Please select your preferred district.'); return; }
    if (!selectedType)         { alert('Please select Instant or Scheduled response type.'); return; }

    if (selectedType === 'scheduled') {
        if (!deadline) { alert('Please set a response deadline.'); return; }
        const selected = new Date(deadline);
        const minTime  = new Date(Date.now() + 2*60*60*1000);
        if (selected < minTime) { alert('Deadline must be at least 2 hours from now.'); return; }
    }

    const budgetMap = { '1000-5000': 5000, '5000-15000': 15000, '15000+': null };

    const btn = document.querySelector('.btn-submit-legal');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...'; }

    try {
        const csrfMeta = document.querySelector('meta[name=csrf-token]');
        const headers  = { 'Content-Type': 'application/json' };
        if (csrfMeta) headers['X-CSRF-TOKEN'] = csrfMeta.content;
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('token');
        if (token) headers['Authorization'] = 'Bearer ' + token;

        const isInstant = selectedType === 'instant';

        const res  = await fetch('/api/legal-request/submit', {
            method:  'POST',
            headers,
            body: JSON.stringify({
                issue_type:     issueType,
                description:    issueDesc,
                budget_max:     budgetMap[selectedBudget] ?? null,
                user_phone:     contactPhone,
                preferred_city: selectedDistrict,
                is_instant:     isInstant,
                is_urgent:      false,
                // Instant হলে dummy future deadline পাঠাই — backend override করবে
                deadline:       isInstant
                    ? new Date(Date.now() + 3*60*60*1000).toISOString()
                    : new Date(deadline).toISOString(),
            }),
        });

        const data = await res.json();

        if (data.success) {
            const reqId = data.request_id || 'LR-' + Date.now();
            document.getElementById('requestId').textContent = reqId;

            // Deadline display
            if (data.deadline) {
                const dl = new Date(data.deadline);
                document.getElementById('deadlineDisplay').textContent =
                    dl.toLocaleDateString('en-BD', { day:'numeric', month:'short', year:'numeric' }) +
                    ' ' + dl.toLocaleTimeString('en-BD', { hour:'2-digit', minute:'2-digit' });
                document.getElementById('deadlineInfo').style.display = 'block';
            }

            // Track button
            document.getElementById('trackCaseBtn').href = '/legal/track?id=' + reqId;

            document.getElementById('successModal').classList.add('active');
        } else {
            alert(data.message || 'Submission failed. Please try again.');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request'; }
        }
    } catch(err) {
        alert('Network error. Please try again.');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request'; }
    }
}

function closeModal() {
    document.getElementById('successModal').classList.remove('active');
}
