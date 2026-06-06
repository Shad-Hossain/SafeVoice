@extends('layouts.app')
@section('title', 'Lawyer Registration — SafeVoice')
@section('styles')
<style>
:root {
    --bg-primary:   #070d1a;
    --bg-card:      #0d1526;
    --bg-input:     #0a1020;
    --border:       #1e2d4a;
    --accent:       #4f9eff;
    --accent-dark:  #1a3a6e;
    --text-main:    #e8f0fe;
    --text-muted:   #6b7fa3;
    --success:      #22c55e;
    --warning:      #f59e0b;
    --error:        #ef4444;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg-primary); color: var(--text-main); font-family: 'Segoe UI', sans-serif; }

.reg-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 100px 20px 60px;
}
.reg-card {
    width: 100%;
    max-width: 680px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 40px 44px;
}
.reg-header {
    text-align: center;
    margin-bottom: 36px;
}
.reg-header .icon {
    width: 68px; height: 68px;
    background: linear-gradient(135deg, var(--accent-dark), #0a1e40);
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    border: 1px solid var(--border);
}
.reg-header h1 { font-size: 24px; font-weight: 700; }
.reg-header p  { color: var(--text-muted); font-size: 14px; margin-top: 6px; }

.form-section {
    margin-bottom: 28px;
}
.section-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--accent);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 7px;
}
.form-group label span { color: var(--error); }
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text-main);
    font-size: 14px;
    padding: 11px 14px;
    outline: none;
    transition: border-color .2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--accent); }
.form-group input[readonly] {
    background: #060b15;
    color: var(--accent);
    cursor: not-allowed;
}
.form-group textarea { min-height: 90px; resize: vertical; }
.form-group select option { background: #0d1526; }

/* Bar Council upload area */
.upload-zone {
    border: 2px dashed var(--border);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    position: relative;
}
.upload-zone:hover { border-color: var(--accent); background: rgba(79,158,255,.04); }
.upload-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.upload-zone .uz-icon { font-size: 28px; margin-bottom: 8px; }
.upload-zone p { color: var(--text-muted); font-size: 13px; }
.upload-zone p strong { color: var(--text-main); }
.upload-preview {
    display: none;
    align-items: center;
    gap: 12px;
    background: rgba(79,158,255,.07);
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 10px;
}
.upload-preview img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; }
.upload-preview .fname { font-size: 13px; font-weight: 600; }
.upload-preview .fsize { font-size: 11px; color: var(--text-muted); }

/* OCR result box */
.ocr-result {
    display: none;
    background: rgba(34,197,94,.08);
    border: 1px solid rgba(34,197,94,.25);
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 12px;
}
.ocr-result .ocr-label { font-size: 11px; color: var(--success); font-weight: 600; margin-bottom: 8px; }
.ocr-result .ocr-field { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
.ocr-result .ocr-field span:first-child { color: var(--text-muted); }
.ocr-result .ocr-field span:last-child  { font-weight: 600; color: var(--text-main); }

/* Specializations */
.spec-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}
.spec-chip {
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--border);
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
}
.spec-chip:hover   { border-color: var(--accent); color: var(--accent); }
.spec-chip.selected{ background: var(--accent); border-color: var(--accent); color: #fff; }

.submit-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--accent), #2563eb);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .2s;
    margin-top: 8px;
}
.submit-btn:hover    { opacity: .9; }
.submit-btn:disabled { opacity: .5; cursor: not-allowed; }

.login-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: var(--text-muted);
}
.login-link a { color: var(--accent); text-decoration: none; }

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    display: none;
}
.alert-success { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
.alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }

/* spinner */
.spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle; margin-right: 8px; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 600px) { .reg-card { padding: 28px 20px; } .form-row { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="reg-wrapper">
<div class="reg-card">

    <div class="reg-header">
        <div class="icon">⚖️</div>
        <h1>Lawyer Registration</h1>
        <p>Join SafeVoice as a verified legal professional</p>
    </div>

    <div class="alert alert-success" id="successAlert"></div>
    <div class="alert alert-error"   id="errorAlert"></div>

    {{-- ── SECTION 1: Bar Council ID Card ─────────────────────────── --}}
    <div class="form-section">
        <div class="section-label">📋 Bar Council ID Card</div>

        <div class="form-group">
            <label>Upload Bar Council Card <span>*</span></label>
            <div class="upload-zone" id="barUploadZone">
                <input type="file" id="barCouncilFile" accept="image/jpeg,image/png,application/pdf">
                <div class="uz-icon">🪪</div>
                <p><strong>Click or drag</strong> your Bar Council card here</p>
                <p>JPG, PNG or PDF — max 5MB</p>
            </div>
            <div class="upload-preview" id="barPreview">
                <img id="barPreviewImg" src="" alt="">
                <div>
                    <div class="fname" id="barFileName"></div>
                    <div class="fsize" id="barFileSize"></div>
                </div>
            </div>
        </div>

        {{-- OCR loading state --}}
        <div id="ocrLoading" style="display:none; text-align:center; padding:14px; color:var(--text-muted); font-size:13px;">
            <span class="spinner"></span> Extracting info from card...
        </div>

        <div class="ocr-result" id="ocrResult">
            <div class="ocr-label">✅ Auto-extracted from card (cannot be edited)</div>
            <div class="ocr-field"><span>Bar Council ID</span><span id="ocrId">—</span></div>
            <div class="ocr-field"><span>Name on Card</span><span id="ocrName">—</span></div>
        </div>

        <div class="form-row" style="margin-top:14px;">
            <div class="form-group">
                <label>Bar Council ID <span>*</span></label>
                <input type="text" id="barCouncilId" placeholder="Auto-filled from card" readonly>
            </div>
            <div class="form-group">
                <label>Full Name (from card) <span>*</span></label>
                <input type="text" id="cardName" placeholder="Auto-filled from card" readonly>
            </div>
        </div>
        <p style="font-size:11px; color:var(--text-muted); margin-top:-8px;">
            ⚠️ These fields are auto-extracted and cannot be changed after submission.
        </p>
    </div>

    {{-- ── SECTION 2: Personal Info ────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-label">👤 Personal Information</div>
        <div class="form-row">
            <div class="form-group">
                <label>Email Address <span>*</span></label>
                <input type="email" id="email" placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label>Phone Number <span>*</span></label>
                <input type="tel" id="phone" placeholder="01XXXXXXXXX">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <input type="password" id="password" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <input type="password" id="confirmPassword" placeholder="Repeat password">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>City / District</label>
                <input type="text" id="city" placeholder="Dhaka, Chittagong...">
            </div>
            <div class="form-group">
                <label>Years of Experience</label>
                <input type="number" id="experience" placeholder="0" min="0" max="60">
            </div>
        </div>
        <div class="form-group">
            <label>Office Address</label>
            <input type="text" id="address" placeholder="Full address">
        </div>
        <div class="form-group">
            <label>Minimum Consultation Fee (৳)</label>
            <input type="number" id="minFee" placeholder="500" min="0" value="500">
        </div>
    </div>

    {{-- ── SECTION 3: Specializations ─────────────────────────────── --}}
    <div class="form-section">
        <div class="section-label">⚖️ Specializations</div>
        <div class="spec-grid" id="specGrid">
            <div class="spec-chip" data-value="harassment">Harassment / Abuse</div>
            <div class="spec-chip" data-value="criminal">Criminal Law</div>
            <div class="spec-chip" data-value="family">Family Law</div>
            <div class="spec-chip" data-value="domestic">Domestic Violence</div>
            <div class="spec-chip" data-value="labor">Labor / Employment</div>
            <div class="spec-chip" data-value="fraud">Fraud / Financial</div>
            <div class="spec-chip" data-value="property">Property Dispute</div>
            <div class="spec-chip" data-value="cyber">Cyber Crime</div>
            <div class="spec-chip" data-value="corruption">Corruption</div>
            <div class="spec-chip" data-value="other">Other</div>
        </div>
    </div>

    {{-- ── SECTION 4: Bio ──────────────────────────────────────────── --}}
    <div class="form-section">
        <div class="section-label">📝 Professional Bio</div>
        <div class="form-group" style="margin-bottom:0">
            <label>Brief Description</label>
            <textarea id="bio" placeholder="Describe your experience, notable cases, areas of expertise..."></textarea>
        </div>
    </div>

    <button class="submit-btn" id="submitBtn" onclick="submitRegistration()">
        Register as Lawyer
    </button>

    <div class="login-link">
        Already registered? <a href="/lawyer/login">Login here</a>
    </div>

</div>
</div>
@endsection

@section('scripts')
<script>
const selectedSpecs = new Set();

// ── Spec chips ─────────────────────────────────────────────
document.querySelectorAll('.spec-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        const val = chip.dataset.value;
        if (selectedSpecs.has(val)) {
            selectedSpecs.delete(val);
            chip.classList.remove('selected');
        } else {
            selectedSpecs.add(val);
            chip.classList.add('selected');
        }
    });
});

// ── Bar council file upload + OCR ─────────────────────────
document.getElementById('barCouncilFile').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Preview
    const preview = document.getElementById('barPreview');
    const previewImg = document.getElementById('barPreviewImg');
    preview.style.display = 'flex';
    document.getElementById('barFileName').textContent = file.name;
    document.getElementById('barFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';

    if (file.type.startsWith('image/')) {
        previewImg.src = URL.createObjectURL(file);
        previewImg.style.display = 'block';
    } else {
        previewImg.style.display = 'none';
    }

    // OCR call
    document.getElementById('ocrLoading').style.display = 'block';
    document.getElementById('ocrResult').style.display = 'none';

    try {
        const formData = new FormData();
        formData.append('bar_council_photo', file);

        const res = await fetch('/api/lawyer/ocr-extract', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: formData,
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('barCouncilId').value = data.bar_council_id || '';
            document.getElementById('cardName').value     = data.name || '';
            document.getElementById('ocrId').textContent  = data.bar_council_id || 'Not detected';
            document.getElementById('ocrName').textContent= data.name || 'Not detected';
            document.getElementById('ocrResult').style.display = 'block';
        } else {
            showError('Could not extract info from card. Please fill manually if needed.');
        }
    } catch(err) {
        // OCR failed — let user type manually for now
        document.getElementById('barCouncilId').removeAttribute('readonly');
        document.getElementById('cardName').removeAttribute('readonly');
        showError('OCR unavailable. Please fill in the Bar Council ID and Name manually.');
    } finally {
        document.getElementById('ocrLoading').style.display = 'none';
    }
});

// ── Submit ─────────────────────────────────────────────────
async function submitRegistration() {
    const btn = document.getElementById('submitBtn');

    // Validation
    const barId    = document.getElementById('barCouncilId').value.trim();
    const fullName = document.getElementById('cardName').value.trim();
    const email    = document.getElementById('email').value.trim();
    const phone    = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('confirmPassword').value;

    if (!barId || !fullName) return showError('Please upload your Bar Council card first.');
    if (!email)    return showError('Email is required.');
    if (!phone)    return showError('Phone is required.');
    if (!password) return showError('Password is required.');
    if (password.length < 8) return showError('Password must be at least 8 characters.');
    if (password !== confirm)  return showError('Passwords do not match.');

    const fileInput = document.getElementById('barCouncilFile');
    if (!fileInput.files[0]) return showError('Please upload your Bar Council card image.');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Submitting...';
    hideAlerts();

    const fd = new FormData();
    fd.append('full_name',        fullName);
    fd.append('email',            email);
    fd.append('phone',            phone);
    fd.append('password',         password);
    fd.append('bar_council_id',   barId);
    fd.append('bar_council_photo',fileInput.files[0]);
    fd.append('city',             document.getElementById('city').value.trim());
    fd.append('experience_years', document.getElementById('experience').value || 0);
    fd.append('min_fee',          document.getElementById('minFee').value || 500);
    fd.append('address',          document.getElementById('address').value.trim());
    fd.append('bio',              document.getElementById('bio').value.trim());
    [...selectedSpecs].forEach(s => fd.append('specializations[]', s));

    try {
        const res  = await fetch('/api/lawyer/register', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: fd,
        });
        const data = await res.json();

        if (data.success) {
            showSuccess(`✅ Registration submitted! Your lawyer code is <strong>${data.lawyer_code}</strong>. Admin will review within 24-48 hours. <a href="/lawyer/login" style="color:#86efac">Login here</a>`);
            btn.innerHTML = '✅ Submitted';
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('<br>')
                : (data.message || 'Registration failed.');
            showError(msg);
            btn.disabled = false;
            btn.innerHTML = 'Register as Lawyer';
        }
    } catch(err) {
        showError('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Register as Lawyer';
    }
}

function showError(msg)   { const el = document.getElementById('errorAlert');   el.innerHTML = msg; el.style.display = 'block'; document.getElementById('successAlert').style.display='none'; el.scrollIntoView({behavior:'smooth',block:'center'}); }
function showSuccess(msg) { const el = document.getElementById('successAlert'); el.innerHTML = msg; el.style.display = 'block'; document.getElementById('errorAlert').style.display='none';   el.scrollIntoView({behavior:'smooth',block:'center'}); }
function hideAlerts()     { document.getElementById('errorAlert').style.display='none'; document.getElementById('successAlert').style.display='none'; }
</script>
@endsection
