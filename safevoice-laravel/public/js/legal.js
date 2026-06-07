let selectedBudget  = '';
let selectedDistrict= '';

// ── DISTRICT LIST ──────────────────────────────────────────
const BD_DISTRICTS = [
    'Dhaka','Chittagong','Rajshahi','Khulna','Barisal','Sylhet','Rangpur','Mymensingh',
    'Comilla','Narayanganj','Gazipur','Narsingdi','Munshiganj','Manikganj','Tangail',
    'Faridpur','Gopalganj','Madaripur','Shariatpur','Rajbari','Kishoreganj','Netrokona',
    'Jamalpur','Sherpur','Bogura','Joypurhat','Chapai Nawabganj','Naogaon','Natore',
    'Sirajganj','Pabna','Jessore','Satkhira','Magura','Jhenaidah','Narail','Kushtia',
    'Chuadanga','Meherpur','Bagerhat','Barguna','Bhola','Jhalokathi','Patuakhali',
    'Pirojpur','Cox\'s Bazar','Bandarban','Rangamati','Khagrachhari','Feni','Lakshmipur',
    'Noakhali','Chandpur','Brahmanbaria','Habiganj','Moulvibazar','Sunamganj',
    'Gaibandha','Kurigram','Lalmonirhat','Nilphamari','Panchagarh','Thakurgaon','Dinajpur'
];

// ── INIT ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Populate district dropdown
    const distSelect = document.getElementById('preferredDistrict');
    if (distSelect) {
        BD_DISTRICTS.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d; opt.textContent = d;
            distSelect.appendChild(opt);
        });
        distSelect.addEventListener('change', e => selectedDistrict = e.target.value);
    }

    // Date min = now + 5 hours
    const consultTime = document.getElementById('consultTime');
    if (consultTime) {
        const pad = n => String(n).padStart(2,'0');
        const min = new Date(Date.now() + 5*60*60*1000);
        consultTime.min = `${min.getFullYear()}-${pad(min.getMonth()+1)}-${pad(min.getDate())}T${pad(min.getHours())}:${pad(min.getMinutes())}`;
        consultTime.value = '';
    }

    // Modal close on overlay
    document.getElementById('successModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
});

// ── BUDGET SELECTION ──────────────────────────────────────
function selectBudget(el, value) {
    document.querySelectorAll('.budget-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    selectedBudget = value;
}

// ── SUBMIT ────────────────────────────────────────────────
async function submitLegalRequest() {
    const issueType    = document.getElementById('issueType').value;
    const issueDesc    = document.getElementById('issueDesc').value.trim();
    const contactPhone = document.getElementById('contactPhone').value.trim();
    const consultTime  = document.getElementById('consultTime').value;

    if (!issueType)           { alert('Please select a legal issue type.'); return; }
    if (issueDesc.length < 20){ alert('Please describe your issue in at least 20 characters.'); return; }
    if (!selectedBudget)      { alert('Please select a budget range.'); return; }
    if (!contactPhone)        { alert('Contact number is required.'); return; }
    if (contactPhone.replace(/\D/g,'').length < 10) { alert('Please enter a valid phone number.'); return; }
    if (!selectedDistrict)    { alert('Please select your preferred district for lawyer.'); return; }

    // Date validation
    if (consultTime) {
        const selected = new Date(consultTime);
        const minTime  = new Date(Date.now() + 5*60*60*1000);
        if (selected < minTime) {
            alert('Consultation time must be at least 5 hours from now.');
            return;
        }
    }

    const budgetMap = { '1000-5000': 5000, '5000-15000': 15000, '15000+': null };

    const btn = document.querySelector('.btn-submit-legal');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...'; }

    try {
        const csrfMeta = document.querySelector('meta[name=csrf-token]');
        const reqHeaders = { 'Content-Type': 'application/json' };
        if (csrfMeta) reqHeaders['X-CSRF-TOKEN'] = csrfMeta.content;
        const token = localStorage.getItem('auth_token') || localStorage.getItem('token') || sessionStorage.getItem('token');
        if (token) reqHeaders['Authorization'] = 'Bearer ' + token;

        const res  = await fetch('/api/legal-request/submit', {
            method:  'POST',
            headers: reqHeaders,
            body: JSON.stringify({
                issue_type:      issueType,
                description:     issueDesc,
                budget_max:      budgetMap[selectedBudget] ?? null,
                user_phone:      contactPhone,
                preferred_city:  selectedDistrict,
                consultation_time: consultTime || null,
                is_urgent:       false,
            }),
        });

        const data = await res.json();

        if (data.success) {
            document.getElementById('requestId').textContent = data.request_id || 'LR-' + Date.now();
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