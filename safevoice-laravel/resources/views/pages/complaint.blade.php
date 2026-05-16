@extends('layouts.app')
@section('title', 'Complaint — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/complaint.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident — SafeVoice</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/complaint.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ai-panel {
            background: var(--accent-glow, #4f9eff15);
            border: 1px solid var(--accent, #4f9eff);
            border-radius: 14px;
            padding: 20px 22px;
            margin-bottom: 22px;
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .ai-panel.visible { display: block; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }

        .ai-panel-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .ai-panel-header i { color:var(--accent,#4f9eff); font-size:18px; }
        .ai-panel-header h4 { font-size:14px; font-weight:700; color:var(--accent,#4f9eff); margin:0; }
        .ai-badge { font-size:10px; background:var(--accent,#4f9eff); color:#fff; padding:2px 8px; border-radius:20px; font-weight:700; letter-spacing:.5px; }

        .ai-content-text { font-size:13px; color:var(--text-secondary,#a0b4cc); line-height:1.7; }
        .ai-loading { display:flex; align-items:center; gap:10px; color:var(--text-secondary,#a0b4cc); font-size:13px; }
        .ai-dots span { display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--accent,#4f9eff); margin:0 2px; animation:bounce 1.2s infinite; }
        .ai-dots span:nth-child(2){animation-delay:.2s} .ai-dots span:nth-child(3){animation-delay:.4s}
        @keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-8px)}}

        .severity-tag { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; margin-top:10px; }
        .severity-high   { background:#e6394615; color:#e63946; border:1px solid #e6394630; }
        .severity-medium { background:#f39c1215; color:#f39c12; border:1px solid #f39c1230; }
        .severity-low    { background:#2ecc7115; color:#2ecc71; border:1px solid #2ecc7130; }

        .btn-submit.loading { opacity:.7; pointer-events:none; }
        .btn-submit.loading i { animation:spin 1s linear infinite; }
        @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

        .copy-id-btn { background:var(--accent-glow,#4f9eff20); border:1px solid var(--accent,#4f9eff); color:var(--accent,#4f9eff); border-radius:6px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; margin-top:10px; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
        .copy-id-btn:hover { background:var(--accent,#4f9eff); color:#fff; }

        .ai-summary-box { background:var(--accent-glow,#4f9eff10); border:1px solid var(--accent,#4f9eff); border-radius:10px; padding:15px 18px; margin:15px 0; text-align:left; font-size:13px; color:var(--text-secondary,#a0b4cc); line-height:1.6; }
        .ai-summary-box .ai-label { font-size:11px; font-weight:700; color:var(--accent,#4f9eff); text-transform:uppercase; letter-spacing:.8px; margin-bottom:6px; }

        /* Anonymous notice box */
        .anon-notice {
            display:none;
            background:#4f9eff10;
            border:1px solid #4f9eff40;
            border-radius:10px;
            padding:12px 16px;
            margin-top:12px;
            font-size:13px;
            color:#a0b4cc;
            line-height:1.6;
        }
        .anon-notice.visible { display:block; }
        .anon-notice i { color:#4f9eff; margin-right:6px; }
    </style>
</head>
<body>

<div class="complaint-layout">
    <div class="complaint-container">

        <div class="complaint-header">
            <h1><i class="fas fa-file-alt"></i> Report an Incident</h1>
            <p>Your identity is protected. Report safely and anonymously.</p>
        </div>

        <div class="progress-bar">
            <div class="progress-step active" id="step1"><div class="step-circle">1</div><span>Incident Info</span></div>
            <div class="progress-line"></div>
            <div class="progress-step" id="step2"><div class="step-circle">2</div><span>Details</span></div>
            <div class="progress-line"></div>
            <div class="progress-step" id="step3"><div class="step-circle">3</div><span>Submit</span></div>
        </div>

        <div class="complaint-form">

            <!-- STEP 1 -->
            <div class="form-step active" id="formStep1">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Incident Type</label>
                    <select class="form-select" id="incidentType">
                        <option value="">Select incident type...</option>
                        <option value="harassment">Harassment</option>
                        <option value="fare_overcharge">Fare Overcharge</option>
                        <option value="crime">Crime</option>
                        <option value="corruption">Corruption</option>
                        <option value="abuse">Abuse</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Date &amp; Time of Incident</label>
                    <input type="datetime-local" class="form-input" id="incidentDate" />
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <div class="location-input">
                        <input type="text" class="form-input" id="incidentLocation" placeholder="Auto-detect or enter manually" />
                        <button class="btn-locate" title="Detect my location" onclick="detectLocation()"><i class="fas fa-crosshairs"></i></button>
                        <button class="btn-locate btn-map-pick" title="Pick on map" onclick="openMapPicker()" style="margin-left:6px;background:linear-gradient(135deg,#1a6f4a,#2ecc71);"><i class="fas fa-map"></i></button>
                    </div>
                </div>

                <!-- Map Picker Modal -->
                <div id="mapPickerModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:99999;align-items:center;justify-content:center;">
                    <div style="background:#111c33;border:1px solid #1e2d4a;border-radius:20px;width:min(700px,95vw);max-height:90vh;overflow:hidden;display:flex;flex-direction:column;">
                        <div style="padding:18px 22px;border-bottom:1px solid #1e2d4a;display:flex;align-items:center;justify-content:space-between;">
                            <h3 style="margin:0;color:#4f9eff;font-size:16px;"><i class="fas fa-map-marker-alt"></i> Pick Location on Map</h3>
                            <button onclick="closeMapPicker()" style="background:transparent;border:none;color:#a0b4cc;font-size:20px;cursor:pointer;">&times;</button>
                        </div>
                        <div style="padding:12px 22px;background:#0a0f1e;display:flex;gap:8px;align-items:center;">
                            <input type="text" id="mapSearchInput" placeholder="Search a place..." onkeydown="if(event.key==='Enter')searchMapPlace()" style="flex:1;background:#111c33;border:1px solid #1e2d4a;color:#fff;padding:9px 14px;border-radius:8px;font-size:14px;outline:none;">
                            <button onclick="searchMapPlace()" style="background:#2563eb;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fas fa-search"></i></button>
                        </div>
                        <div id="mapContainer" style="flex:1;min-height:380px;position:relative;">
                            <div id="leafletMap" style="width:100%;height:380px;"></div>
                        </div>
                        <div style="padding:12px 22px;background:#0a0f1e;border-top:1px solid #1e2d4a;display:flex;align-items:center;gap:12px;">
                            <i class="fas fa-map-pin" style="color:#e63946;"></i>
                            <span id="mapSelectedAddr" style="color:#a0b4cc;font-size:13px;flex:1;">Click anywhere on the map to select a location</span>
                            <button onclick="confirmMapLocation()" style="background:#2563eb;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:14px;font-weight:600;cursor:pointer;"><i class="fas fa-check"></i> Confirm</button>
                        </div>
                    </div>
                </div>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

                <div class="anonymous-toggle">
                    <div class="toggle-info">
                        <i class="fas fa-user-secret"></i>
                        <div><h4>Anonymous Report</h4><p>Your name and identity will be completely hidden</p></div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="anonymousToggle" onchange="onAnonToggle()" /><span class="slider"></span>
                    </label>
                </div>

                <!-- Anonymous notice — toggle on হলে দেখাবে -->
                <div class="anon-notice" id="anonNotice">
                    <i class="fas fa-shield-alt"></i>
                    <strong style="color:#4f9eff">Anonymous mode is ON.</strong><br>
                    Your name, phone, and email will <strong>not</strong> be saved anywhere.
                    Admin will only see the incident details — not who you are.
                    If you later accept PI service, the PI will contact you directly via this platform.
                    <strong style="color:#fbbf24;display:block;margin-top:6px;">
                        ⚠️ Save your Complaint ID after submission — it's your only way to track this case.
                    </strong>
                </div>

                <button class="btn-next" onclick="nextStep(2)">Next <i class="fas fa-arrow-right"></i></button>
            </div>

            <!-- STEP 2 -->
            <div class="form-step" id="formStep2">
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea class="form-textarea" id="description" placeholder="Describe the incident in detail..." oninput="onDescriptionInput()"></textarea>
                </div>

                <!-- AI Analysis Panel -->
                <div class="ai-panel" id="aiPanel">
                    <div class="ai-panel-header">
                        <i class="fas fa-robot"></i>
                        <h4>AI Analysis</h4>
                        <span class="ai-badge">BETA</span>
                    </div>
                    <div id="aiContent">
                        <div class="ai-loading"><div class="ai-dots"><span></span><span></span><span></span></div>Analyzing your report...</div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-paperclip"></i> Evidence <span style="color:var(--text-secondary);font-weight:400">(Optional)</span></label>
                    <div class="upload-box" id="uploadBox" style="cursor:pointer;">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Upload photos or PDF</p>
                        <span>JPG, PNG, PDF up to 10MB each</span>
                    </div>
                    <input type="file" id="evidenceFiles" accept="image/jpeg,image/png,image/gif,image/webp,.pdf" multiple style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;opacity:0;" />
                    <div id="fileList" style="margin-top:10px;display:none;">
                        <p style="font-size:13px;color:var(--text-secondary,#a0b4cc);margin-bottom:6px;"><i class="fas fa-paperclip"></i> Selected files:</p>
                        <ul id="fileNames" style="list-style:none;padding:0;margin:0;font-size:13px;color:#a0b4cc;"></ul>
                    </div>
                </div>

                <div class="btn-row">
                    <button class="btn-back" onclick="nextStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn-next" onclick="nextStep(3)" style="flex:1">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 3 -->
            <div class="form-step" id="formStep3">
                <div class="review-box">
                    <h3><i class="fas fa-clipboard-check"></i> Review Your Report</h3>
                    <div class="review-item"><span class="review-label">Incident Type</span><span class="review-value" id="reviewType">—</span></div>
                    <div class="review-item"><span class="review-label">Date &amp; Time</span><span class="review-value" id="reviewDate">—</span></div>
                    <div class="review-item"><span class="review-label">Location</span><span class="review-value" id="reviewLocation">—</span></div>
                    <div class="review-item"><span class="review-label">Anonymous</span><span class="review-value" id="reviewAnon">No</span></div>
                    <div class="review-item"><span class="review-label">Description</span><span class="review-value" id="reviewDesc">—</span></div>
                </div>

                <!-- Anonymous reminder step 3 e -->
                <div id="step3AnonWarning" style="display:none;background:#fbbf2410;border:1px solid #fbbf2440;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#fbbf24;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Anonymous submission.</strong> After submitting, copy your Complaint ID — it's the only way to track this case.
                </div>

                <div class="ai-summary-box" id="reviewAiSummary" style="display:none">
                    <div class="ai-label"><i class="fas fa-robot"></i> &nbsp;AI Assessment</div>
                    <div id="reviewAiText">—</div>
                </div>

                <div class="btn-row">
                    <button class="btn-back" onclick="nextStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
                    <button class="btn-submit" id="submitBtn" onclick="submitComplaint()"><i class="fas fa-paper-plane"></i> Submit Report</button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box success-modal">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h2>Report Submitted!</h2>
        <p>Your complaint has been successfully submitted and logged.</p>
        <div class="complaint-id-box">
            <span>Complaint ID</span>
            <h3 id="complaintId">SV-2026-0000</h3>
            <p>Save this ID to track your complaint</p>
            <button class="copy-id-btn" onclick="copyId()"><i class="fas fa-copy"></i> Copy ID</button>
        </div>

        <!-- Anonymous specific message -->
        <div id="anonSuccessMsg" style="display:none;background:#4f9eff10;border:1px solid #4f9eff40;border-radius:10px;padding:14px 18px;margin:14px 0;text-align:left;font-size:13px;color:#a0b4cc;line-height:1.7;">
            <div style="font-size:11px;font-weight:700;color:#4f9eff;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">
                <i class="fas fa-user-secret"></i> &nbsp;Anonymous Submission
            </div>
            Your identity is fully protected. Admin will review your case without knowing who you are.<br>
            If admin assigns a PI and you choose to pay — the PI will contact you through this platform.<br>
            <strong style="color:#fbbf24;">Your Complaint ID is your only tracking key. It has been saved to your browser.</strong>
        </div>

        <div class="ai-summary-box" id="modalAiBox" style="display:none">
            <div class="ai-label"><i class="fas fa-robot"></i> &nbsp;What happens next</div>
            <div id="modalAiText">—</div>
        </div>

        <!-- Additional Evidence Upload -->
        <div id="additionalEvidenceBox" style="margin:18px 0 10px;padding:16px;background:#0a0f1e;border:1px dashed #1e2d4a;border-radius:12px;text-align:left;">
            <p style="color:#4f9eff;font-size:13px;font-weight:600;margin:0 0 10px;"><i class="fas fa-paperclip"></i> &nbsp;Add More Evidence (Optional)</p>
            <input type="file" id="extraEvidenceFiles" accept="image/jpeg,image/png,image/gif,image/webp,.pdf" multiple style="display:none;" />
            <div id="extraUploadBox" onclick="document.getElementById('extraEvidenceFiles').click()" style="border:1px dashed #2d3a55;border-radius:8px;padding:12px;text-align:center;cursor:pointer;color:#4a5568;font-size:12px;">
                <i class="fas fa-cloud-upload-alt" style="font-size:20px;color:#4f9eff;display:block;margin-bottom:6px;"></i>
                Click to select images or PDF files (max 10MB each)
            </div>
            <div id="extraFileNames" style="margin-top:8px;font-size:12px;color:#a0aec0;"></div>
            <button id="extraUploadBtn" onclick="uploadExtraEvidence()" style="display:none;margin-top:10px;width:100%;background:#4f9eff;color:#fff;border:none;border-radius:8px;padding:9px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-upload"></i> Upload Evidence
            </button>
            <div id="extraUploadStatus" style="margin-top:8px;font-size:12px;"></div>
        </div>

        <div class="modal-footer" style="border:none;justify-content:center;gap:15px">
            <a href="/dashboard" class="btn-accept">Go to Dashboard</a>
            <a href="/track" class="btn-decline">Track Complaint</a>
        </div>
    </div>
</div>

<script src="{{ asset('js/theme.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script>
let aiAnalysis = '';
let aiTimer = null;

// ── Anonymous toggle ─────────────────────────────────────────
function onAnonToggle() {
    const isAnon = document.getElementById('anonymousToggle').checked;
    document.getElementById('anonNotice').classList.toggle('visible', isAnon);
}

function nextStep(step) {
    if (step === 2) {
        if (!document.getElementById('incidentType').value) { alert('Please select an incident type.'); return; }
        if (!document.getElementById('incidentDate').value) { alert('Please select the date and time.'); return; }
    }
    if (step === 3) {
        if (document.getElementById('description').value.trim().length < 20) {
            alert('Please describe the incident in at least 20 characters.'); return;
        }
    }

    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.progress-step').forEach(s => s.classList.remove('active'));
    document.getElementById('formStep' + step).classList.add('active');
    for (let i = 1; i <= step; i++) document.getElementById('step' + i).classList.add('active');

    if (step === 3) {
        const type = document.getElementById('incidentType').value;
        const date = document.getElementById('incidentDate').value;
        const loc  = document.getElementById('incidentLocation').value;
        const desc = document.getElementById('description').value.trim();
        const anon = document.getElementById('anonymousToggle').checked;

        document.getElementById('reviewType').textContent     = type || '—';
        document.getElementById('reviewDate').textContent     = date ? new Date(date).toLocaleString() : '—';
        document.getElementById('reviewLocation').textContent = loc  || '—';
        document.getElementById('reviewAnon').innerHTML       = anon
            ? '<span style="color:#4f9eff;font-weight:700"><i class="fas fa-user-secret"></i> Yes — Identity Hidden</span>'
            : 'No';
        document.getElementById('reviewDesc').textContent     = desc.length > 120 ? desc.substring(0,120)+'…' : desc;

        // Anonymous warning step 3 e
        document.getElementById('step3AnonWarning').style.display = anon ? 'block' : 'none';

        if (aiAnalysis) {
            document.getElementById('reviewAiText').textContent = aiAnalysis;
            document.getElementById('reviewAiSummary').style.display = 'block';
        }
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function detectLocation() {
    const input = document.getElementById('incidentLocation');
    input.placeholder = 'Detecting...';
    if (!navigator.geolocation) { input.placeholder = 'Not supported'; return; }
    navigator.geolocation.getCurrentPosition(function(pos) {
        fetch('https://nominatim.openstreetmap.org/reverse?lat=' + pos.coords.latitude + '&lon=' + pos.coords.longitude + '&format=json')
            .then(r => r.json())
            .then(d => { input.value = d.display_name; input.placeholder = 'Auto-detect or enter manually'; })
            .catch(() => { input.value = pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4); });
    }, function() { input.placeholder = 'Could not detect — enter manually'; });
}

function onDescriptionInput() {
    const desc = document.getElementById('description').value.trim();
    clearTimeout(aiTimer);
    if (desc.length < 30) {
        document.getElementById('aiPanel').classList.remove('visible');
        aiAnalysis = '';
        return;
    }
    document.getElementById('aiPanel').classList.add('visible');
    document.getElementById('aiContent').innerHTML = '<div class="ai-loading"><div class="ai-dots"><span></span><span></span><span></span></div>Analyzing your report...</div>';
    aiTimer = setTimeout(() => runAiAnalysis(desc), 1500);
}

async function runAiAnalysis(description) {
    const type     = document.getElementById('incidentType').value || 'unspecified';
    const location = document.getElementById('incidentLocation').value || 'unspecified';
    const prompt   = 'You are a complaint analyst for SafeVoice, a citizen reporting platform in Bangladesh.\n\nA user submitted:\n- Type: ' + type + '\n- Location: ' + location + '\n- Description: "' + description + '"\n\nIn 2-3 concise sentences: state the Severity (High/Medium/Low) with a brief reason, then give one practical piece of advice (evidence to gather or immediate next step). Be supportive and professional. Start with the severity.';
    try {
        const res  = await fetch('https://api.anthropic.com/v1/messages', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ model:'claude-sonnet-4-20250514', max_tokens:200, messages:[{role:'user',content:prompt}] })
        });
        const data = await res.json();
        const text = (data.content || []).map(b => b.text||'').join('').trim();
        if (!text) throw new Error();
        aiAnalysis = text;
        const lower = text.toLowerCase();
        let tag = '';
        if (lower.includes('high'))   tag = '<span class="severity-tag severity-high"><i class="fas fa-exclamation-circle"></i> High Severity</span>';
        else if (lower.includes('medium')||lower.includes('moderate')) tag = '<span class="severity-tag severity-medium"><i class="fas fa-exclamation-triangle"></i> Medium Severity</span>';
        else if (lower.includes('low')) tag = '<span class="severity-tag severity-low"><i class="fas fa-info-circle"></i> Low Severity</span>';
        document.getElementById('aiContent').innerHTML = '<div class="ai-content-text">' + esc(text) + '</div>' + tag;
    } catch(e) {
        document.getElementById('aiContent').innerHTML = '<div class="ai-content-text" style="color:var(--text-muted,#4a5568)"><i class="fas fa-info-circle"></i> AI analysis unavailable — your report will still be submitted normally.</div>';
    }
}

// ── SUBMIT ───────────────────────────────────────────────────
async function submitComplaint() {
    const btn      = document.getElementById('submitBtn');
    const isAnon   = document.getElementById('anonymousToggle').checked;
    const svUser   = JSON.parse(localStorage.getItem('sv_user') || '{}');

    btn.classList.add('loading');
    btn.innerHTML = '<i class="fas fa-spinner"></i> Submitting...';

    const payload = {
        type:          document.getElementById('incidentType').value,
        incident_date: document.getElementById('incidentDate').value,
        location:      document.getElementById('incidentLocation').value,
        description:   document.getElementById('description').value.trim(),
        is_anonymous:  isAnon,
        user_id:       svUser.id || null
    };

    let complaint_id    = '';
    let anonymous_token = '';

    try {
        const res  = await fetch('/api/submit_complaint', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (!data.success) throw new Error(data.message || 'Submission failed');

        complaint_id    = data.complaint_id;
        anonymous_token = data.anonymous_token || '';
        window.lastComplaintId = complaint_id;

        // ── Anonymous token localStorage e save koro ──────────
        if (isAnon && anonymous_token) {
            const anonList = JSON.parse(localStorage.getItem('sv_anon_complaints') || '[]');
            // Same complaint_id thakle replace koro
            const filtered = anonList.filter(c => c.complaint_id !== complaint_id);
            filtered.push({
                complaint_id:    complaint_id,
                token:           anonymous_token,
                submitted_at:    new Date().toISOString(),
                type:            payload.type,
                location:        payload.location,
            });
            localStorage.setItem('sv_anon_complaints', JSON.stringify(filtered));
        }

        // ── Normal (non-anonymous) complaints o save koro ──────
        if (!isAnon) {
            const myComplaints = JSON.parse(localStorage.getItem('sv_my_complaints') || '[]');
            myComplaints.unshift({
                complaint_id: complaint_id,
                submitted_at: new Date().toISOString(),
                type:         payload.type,
                status:       'Submitted',
            });
            localStorage.setItem('sv_my_complaints', JSON.stringify(myComplaints.slice(0, 50)));
        }

    } catch (err) {
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Report';
        alert('Error submitting complaint: ' + err.message + '\n\nMake sure XAMPP is running and the database is set up.');
        return;
    }

    // ── Evidence upload ──────────────────────────────────────
    const fileInput = document.getElementById('evidenceFiles');
    if (fileInput && fileInput.files.length > 0) {
        btn.innerHTML = '<i class="fas fa-spinner"></i> Uploading evidence...';
        const formData = new FormData();
        formData.append('complaint_id', complaint_id);
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('evidence[]', fileInput.files[i]);
        }
        try {
            await fetch('/api/upload_complaint_evidence', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
        } catch(e) {
            console.warn('Evidence upload failed:', e);
        }
    }

    // ── Success modal show ───────────────────────────────────
    document.getElementById('complaintId').textContent = complaint_id;

    // Anonymous specific UI
    if (isAnon) {
        document.getElementById('anonSuccessMsg').style.display = 'block';
        document.getElementById('modalAiText').textContent =
            'Your anonymous complaint is saved. Admin will review it without knowing your identity. ' +
            'If a PI is assigned and you accept, the PI will contact you through the platform using your Complaint ID.';
    } else {
        document.getElementById('anonSuccessMsg').style.display = 'none';
        document.getElementById('modalAiText').textContent =
            'Your complaint has been saved and will be reviewed by the admin team within 24–48 hours.';
    }
    document.getElementById('modalAiBox').style.display = 'block';

    btn.classList.remove('loading');
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Report';
    document.getElementById('successModal').classList.add('active');
}

function copyId() {
    const id = document.getElementById('complaintId').textContent;
    navigator.clipboard.writeText(id).then(() => {
        const btn = document.querySelector('.copy-id-btn');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy ID'; }, 2000);
    });
}

function showSelectedFiles(files) {
    const list  = document.getElementById('fileList');
    const names = document.getElementById('fileNames');
    const box   = document.getElementById('uploadBox');
    if (!files || !files.length) { list.style.display = 'none'; return; }
    names.innerHTML = '';
    Array.from(files).forEach(f => {
        const li = document.createElement('li');
        li.style.cssText = 'padding:4px 0;display:flex;align-items:center;gap:8px;';
        const icon = f.type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file-image';
        li.innerHTML = `<i class="fas ${icon}" style="color:#4f9eff;width:16px;"></i> ${esc(f.name)} <span style="color:#4a5568;font-size:12px;">(${(f.size/1024/1024).toFixed(2)} MB)</span>`;
        names.appendChild(li);
    });
    list.style.display = 'block';
    if (box) {
        const p = box.querySelector('p');
        if (p) p.textContent = files.length + ' file(s) selected — click to change';
        box.style.borderColor = '#4f9eff';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var box   = document.getElementById('uploadBox');
    var input = document.getElementById('evidenceFiles');
    if (box && input) {
        box.addEventListener('click', function() { input.click(); });
        input.addEventListener('change', function() { showSelectedFiles(this.files); });
    }
});

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Map Picker ───────────────────────────────────────────────
let mapInstance = null;
let mapMarker   = null;
let mapSelectedLatLng = null;

function openMapPicker() {
    const modal = document.getElementById('mapPickerModal');
    modal.style.display = 'flex';
    setTimeout(() => {
        if (!mapInstance) {
            mapInstance = L.map('leafletMap').setView([23.8103, 90.4125], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapInstance);
            mapInstance.on('click', function(e) {
                placeMapMarker(e.latlng.lat, e.latlng.lng);
            });
        }
        mapInstance.invalidateSize();
        const existing = document.getElementById('incidentLocation').value;
        if (existing && !mapMarker) {
            fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(existing) + '&format=json&limit=1')
                .then(r => r.json()).then(d => {
                    if (d.length) { mapInstance.setView([d[0].lat, d[0].lon], 15); placeMapMarker(d[0].lat, d[0].lon, d[0].display_name); }
                }).catch(() => {});
        }
    }, 100);
}

function closeMapPicker() {
    document.getElementById('mapPickerModal').style.display = 'none';
}

function placeMapMarker(lat, lng, label) {
    mapSelectedLatLng = { lat, lng };
    if (mapMarker) mapInstance.removeLayer(mapMarker);
    mapMarker = L.marker([lat, lng]).addTo(mapInstance);
    if (label) {
        document.getElementById('mapSelectedAddr').textContent = label;
    } else {
        document.getElementById('mapSelectedAddr').textContent = 'Fetching address...';
        fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
            .then(r => r.json())
            .then(d => {
                document.getElementById('mapSelectedAddr').textContent = d.display_name || (lat.toFixed(5) + ', ' + lng.toFixed(5));
                mapSelectedLatLng.address = d.display_name;
            })
            .catch(() => { document.getElementById('mapSelectedAddr').textContent = lat.toFixed(5) + ', ' + lng.toFixed(5); });
    }
}

function searchMapPlace() {
    const q = document.getElementById('mapSearchInput').value.trim();
    if (!q) return;
    fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&limit=1')
        .then(r => r.json()).then(d => {
            if (!d.length) { alert('Place not found. Try a different search.'); return; }
            mapInstance.setView([d[0].lat, d[0].lon], 15);
            placeMapMarker(parseFloat(d[0].lat), parseFloat(d[0].lon), d[0].display_name);
        }).catch(() => alert('Search failed. Check your connection.'));
}

function confirmMapLocation() {
    if (!mapSelectedLatLng) { alert('Please click on the map to select a location first.'); return; }
    const addr = mapSelectedLatLng.address || document.getElementById('mapSelectedAddr').textContent;
    document.getElementById('incidentLocation').value = addr;
    closeMapPicker();
}

// ── Extra evidence upload ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var extraInput = document.getElementById('extraEvidenceFiles');
    if (extraInput) {
        extraInput.addEventListener('change', function() {
            var names = document.getElementById('extraFileNames');
            var btn   = document.getElementById('extraUploadBtn');
            if (this.files.length > 0) {
                names.innerHTML = Array.from(this.files).map(f =>
                    '<div style="padding:3px 0;color:#cbd5e0;"><i class="fas fa-file" style="color:#4f9eff;margin-right:6px;"></i>' + esc(f.name) + '</div>'
                ).join('');
                btn.style.display = 'block';
            } else {
                names.innerHTML = '';
                btn.style.display = 'none';
            }
        });
    }
});

async function uploadExtraEvidence() {
    var complaint_id = window.lastComplaintId || document.getElementById('complaintId').textContent.trim();
    var input  = document.getElementById('extraEvidenceFiles');
    var status = document.getElementById('extraUploadStatus');
    var btn    = document.getElementById('extraUploadBtn');

    if (!input.files.length || !complaint_id) {
        status.innerHTML = '<span style="color:#e63946;"><i class="fas fa-exclamation-circle"></i> Error: Complaint ID পাওয়া যাচ্ছে না।</span>';
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    status.innerHTML = '';

    var formData = new FormData();
    formData.append('complaint_id', complaint_id);
    for (var i = 0; i < input.files.length; i++) {
        formData.append('evidence[]', input.files[i]);
    }

    try {
        var res  = await fetch('/api/upload_complaint_evidence', {
            method: 'POST', credentials: 'include', body: formData
        });
        var data = await res.json();
        if (data.success) {
            status.innerHTML = '<span style="color:#2ecc71;"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
            input.value = '';
            document.getElementById('extraFileNames').innerHTML = '';
            btn.style.display = 'none';
        } else {
            status.innerHTML = '<span style="color:#e63946;"><i class="fas fa-exclamation-circle"></i> Upload failed: ' + (data.message || 'Unknown error') + '</span>';
        }
    } catch(e) {
        status.innerHTML = '<span style="color:#e63946;"><i class="fas fa-exclamation-circle"></i> Upload error: ' + e.message + '</span>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Upload Evidence';
}
</script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection