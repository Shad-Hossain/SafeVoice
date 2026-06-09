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

// ── BUDGET SELECTION (single only) ───────────────────────
function selectBudget(el, value) {
    document.querySelectorAll('#budgetOptions .budget-btn').forEach(b => b.classList.remove('selected'));
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

    // ── Payment Agreement Modal ────────────────────────────
    const agreed = await showPaymentAgreementModal();
    if (!agreed) return; // User disagreed — don't submit

    const budgetMap = { '1000-5000': 5000, '5000-15000': 15000, '15000+': null };

    const btn = document.querySelector('.btn-submit-legal');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...'; }

    try {
        const csrfMeta = document.querySelector('meta[name=csrf-token]');
        const csrfToken = csrfMeta ? csrfMeta.content : '';

        // Bug fix: mobile token অথবা web session inject করা user info — দুটোই support
        const token = localStorage.getItem('sv_token') || localStorage.getItem('auth_token') || sessionStorage.getItem('token');

        // Web session থেকে inject করা user info (Blade এ set করা)
        const svUser   = window.SAFEVOICE_USER || {};
        const userId   = svUser.id   || null;
        const userName = svUser.name || null;

        const isInstant = selectedType === 'instant';

        // FormData দিয়ে file upload support করো
        const formData = new FormData();
        formData.append('issue_type',         issueType);
        formData.append('description',        issueDesc);
        formData.append('budget_max',         budgetMap[selectedBudget] ?? '');
        formData.append('user_phone',         contactPhone);
        formData.append('preferred_city',     selectedDistrict);
        formData.append('preferred_district', selectedDistrict);
        formData.append('is_instant',         isInstant ? '1' : '0');
        formData.append('is_urgent',          '0');
        formData.append('deadline',           isInstant
            ? new Date(Date.now() + 2*60*60*1000).toISOString()
            : new Date(deadline).toISOString());
        if (userId)   formData.append('user_id',   userId);
        if (userName) formData.append('user_name', userName);

        // File upload যোগ করো
        const fileInput = document.querySelector('.upload-box input[type="file"]');
        if (fileInput && fileInput.files.length > 0) {
            Array.from(fileInput.files).forEach((file, i) => {
                formData.append(`documents[${i}]`, file);
            });
        }

        const headers = { 'X-CSRF-TOKEN': csrfToken };
        if (token) headers['Authorization'] = 'Bearer ' + token;

        const res  = await fetch('/api/legal-request/submit', {
            method:  'POST',
            headers,
            credentials: 'include',
            body: formData,
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

// ── Payment Agreement Modal ────────────────────────────────
function showPaymentAgreementModal() {
    return new Promise((resolve) => {
        // Create modal if not exists
        let modal = document.getElementById('paymentAgreementModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'paymentAgreementModal';
            modal.style.cssText = `
                position:fixed;inset:0;z-index:9999;
                background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);
                display:flex;align-items:center;justify-content:center;padding:20px;
            `;
            modal.innerHTML = `
                <div style="
                    background:#0d1526;border:1px solid #1e2d4a;border-radius:20px;
                    padding:32px 28px;max-width:480px;width:100%;
                    box-shadow:0 24px 60px rgba(0,0,0,0.6);
                    animation:slideUp .25s ease;
                ">
                    <style>@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}</style>
                    <div style="text-align:center;margin-bottom:24px;">
                        <div style="font-size:40px;margin-bottom:12px;">⚖️</div>
                        <h2 style="font-size:18px;font-weight:800;color:#fff;margin-bottom:8px;">Payment Agreement</h2>
                        <p style="color:#6b7fa3;font-size:13px;line-height:1.5;">
                            Please read and agree before submitting your case
                        </p>
                    </div>

                    <div style="
                        background:#070d1a;border:1px solid #1e3a5f;border-radius:12px;
                        padding:18px 20px;margin-bottom:24px;
                    ">
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <span style="font-size:18px;flex-shrink:0;">💳</span>
                                <div>
                                    <div style="font-weight:700;color:#fff;font-size:13px;margin-bottom:3px;">Payment Obligation</div>
                                    <div style="color:#8899b8;font-size:12px;line-height:1.5;">After your case is resolved by the lawyer, you must pay the agreed fee <strong style="color:#f59e0b;">within 3 days</strong>.</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <span style="font-size:18px;flex-shrink:0;">⏰</span>
                                <div>
                                    <div style="font-weight:700;color:#fff;font-size:13px;margin-bottom:3px;">Deadline Enforcement</div>
                                    <div style="color:#8899b8;font-size:12px;line-height:1.5;">If payment is not made within 3 days of case resolution, the payment option will be <strong style="color:#ef4444;">permanently closed</strong> and your account will be flagged.</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <span style="font-size:18px;flex-shrink:0;">⚠️</span>
                                <div>
                                    <div style="font-weight:700;color:#ef4444;font-size:13px;margin-bottom:3px;">Legal Action</div>
                                    <div style="color:#8899b8;font-size:12px;line-height:1.5;">Failure to pay may result in <strong style="color:#ef4444;">legal action taken against you</strong> under the terms of SafeVoice's service agreement.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;">
                        <button id="payAgreeBtn" style="
                            flex:1;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;
                            border:none;border-radius:10px;padding:13px;font-size:14px;
                            font-weight:700;cursor:pointer;
                        ">
                            ✅ I Agree — Submit Case
                        </button>
                        <button id="payDisagreeBtn" style="
                            background:transparent;color:#6b7fa3;border:1px solid #1e2d4a;
                            border-radius:10px;padding:13px 18px;font-size:14px;
                            font-weight:600;cursor:pointer;
                        ">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        } else {
            modal.style.display = 'flex';
        }

        document.getElementById('payAgreeBtn').onclick = () => {
            modal.style.display = 'none';
            resolve(true);
        };
        document.getElementById('payDisagreeBtn').onclick = () => {
            modal.style.display = 'none';
            resolve(false);
        };
    });
}