@extends('layouts.app')
@section('title', 'Sos — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/sos.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
/* SOS page সবসময় dark mode এ থাকবে */
body { background: #070d1a !important; color: #fff !important; }
body.light-mode { background: #070d1a !important; color: #fff !important; }
.theme-toggle { display: none !important; }
</style>
@endsection

@section('content')
<div class="sos-layout">
    <!-- LEFT: SOS Button Panel -->
    <div class="sos-panel">
        <div class="sos-status-bar" id="statusBar">
            <span class="status-dot"></span>
            <span id="statusText">Ready to send alert</span>
        </div>
        <div class="sos-heading">
            <h1>Emergency <span>SOS</span></h1>
            <p>Press and hold the button to instantly alert nearby responders</p>
        </div>
        <div class="sos-btn-wrapper" id="sosBtnWrapper">
            <div class="sos-ring sos-ring-1"></div>
            <div class="sos-ring sos-ring-2"></div>
            <div class="sos-ring sos-ring-3"></div>
            <button class="sos-btn" id="sosBtn">
                <i class="fas fa-exclamation-triangle"></i>
                <span>SOS</span>
                <small>Hold to activate</small>
            </button>
            <svg class="hold-progress" id="holdProgress" viewBox="0 0 160 160">
                <circle class="hold-track" cx="80" cy="80" r="72"/>
                <circle class="hold-fill" cx="80" cy="80" r="72" id="holdFill"/>
            </svg>
        </div>
        <div class="sos-location-bar" id="locationBar">
            <i class="fas fa-map-marker-alt"></i>
            <span id="locationText">Detecting your location...</span>
        </div>
        <div class="sos-quick-actions">
            <a href="tel:999" class="quick-action-btn police">
                <i class="fas fa-shield-alt"></i><span>Police</span><small>999</small>
            </a>
            <a href="tel:199" class="quick-action-btn fire">
                <i class="fas fa-fire-extinguisher"></i><span>Fire Service</span><small>199</small>
            </a>
            <a href="tel:16430" class="quick-action-btn ambulance">
                <i class="fas fa-ambulance"></i><span>Ambulance</span><small>16430</small>
            </a>
        </div>
    </div>

    <!-- RIGHT: Responders Panel -->
    <div class="responders-panel">
        <div class="nearby-header">
            <div class="nearby-count-box">
                <span id="nearbyCount">0</span><p>Nearby Responders</p>
            </div>
            <div class="scan-indicator" id="scanIndicator">
                <div class="scan-dot"></div><span>Scanning area...</span>
            </div>
        </div>
        <div class="responder-list" id="responderList">
            <div class="scanning-placeholder" id="scanPlaceholder">
                <div class="radar-anim">
                    <div class="radar-ring"></div><div class="radar-ring"></div><div class="radar-ring"></div>
                    <i class="fas fa-wifi"></i>
                </div>
                <p>Scanning for nearby responders...</p>
                <small>Active users within 200m will appear here</small>
            </div>
        </div>
        <div class="alert-log" id="alertLog" style="display:none;">
            <h4><i class="fas fa-bell"></i> Alert Log</h4>
            <div class="log-list" id="logList"></div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     ACTIVATED OVERLAY
════════════════════════════════════════════════════════════ -->
<div class="sos-activated-overlay" id="activatedOverlay">
    <div class="activated-box">
        <div class="activated-icon">
            <div class="pulse-ring"></div><div class="pulse-ring delay1"></div>
            <i class="fas fa-broadcast-tower"></i>
        </div>
        <h2>SOS Alert Sent!</h2>
        <p>Your emergency alert has been broadcast to <strong id="alertedCount">0</strong> nearby responders.</p>
        <div class="activated-location">
            <i class="fas fa-map-marker-alt"></i>
            <span id="activatedLocation">Detecting...</span>
        </div>
        <div class="activated-actions">
            <button class="btn-cancel-sos" onclick="cancelSOS()">
                <i class="fas fa-times"></i> Cancel Alert
            </button>
            <a href="tel:999" class="btn-call-police">
                <i class="fas fa-phone"></i> Call Police
            </a>
        </div>
        <p class="activated-note">Stay calm. Help is on the way. Keep your phone with you.</p>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     EVIDENCE MODAL — Victim adds crime type + evidence
     Opens AFTER notifications are sent
════════════════════════════════════════════════════════════ -->
<div id="evidenceModal" style="display:none;" class="sv-modal-overlay">
    <div class="sv-modal evidence-modal">

        <div class="sv-modal-header">
            <div class="sv-modal-icon sent">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <h3>Alert Sent!</h3>
                <p>Add details to help responders</p>
            </div>
            <button class="sv-modal-close" onclick="closeEvidenceModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sv-modal-badge">
            <i class="fas fa-bell"></i>
            Notifications sent — now add details so responders know what's happening
        </div>

        <!-- Crime Type -->
        <div class="sv-form-group">
            <label><i class="fas fa-exclamation-triangle"></i> Crime Type <span class="required">*</span></label>
            <div class="crime-type-grid">
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Harassment" onchange="setCrimeType(this)">
                    <span><i class="fas fa-user-slash"></i> Harassment</span>
                </label>
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Violence" onchange="setCrimeType(this)">
                    <span><i class="fas fa-fist-raised"></i> Violence</span>
                </label>
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Robbery" onchange="setCrimeType(this)">
                    <span><i class="fas fa-mask"></i> Robbery</span>
                </label>
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Kidnapping" onchange="setCrimeType(this)">
                    <span><i class="fas fa-handcuffs"></i> Kidnapping</span>
                </label>
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Stalking" onchange="setCrimeType(this)">
                    <span><i class="fas fa-eye"></i> Stalking</span>
                </label>
                <label class="crime-chip">
                    <input type="radio" name="crimeTypeRadio" value="Other" onchange="setCrimeType(this)">
                    <span><i class="fas fa-ellipsis-h"></i> Other</span>
                </label>
            </div>
            <input type="hidden" id="crimeType" value="">
        </div>

        <!-- Description -->
        <div class="sv-form-group">
            <label><i class="fas fa-align-left"></i> Describe the situation</label>
            <textarea id="crimeDesc" placeholder="What is happening? Where are you exactly? Describe the attacker..." rows="3"></textarea>
        </div>

        <!-- Evidence Upload -->
        <div class="sv-form-group">
            <label><i class="fas fa-paperclip"></i> Upload Evidence <span class="optional">(optional)</span></label>
            <div class="file-upload-area" onclick="document.getElementById('evidenceFile').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Tap to upload photo, video or audio</p>
                <small>JPG, PNG, MP4, MP3 supported</small>
            </div>
            <input type="file" id="evidenceFile" style="display:none;"
                   accept="image/*,video/*,audio/*"
                   onchange="handleFileSelect(this)">
            <div id="filePreview"></div>
        </div>

        <!-- Message -->
        <div id="modalMsg" class="modal-msg" style="display:none;"></div>

        <!-- Actions -->
        <div class="sv-modal-actions">
            <button class="btn-skip" onclick="closeEvidenceModal()">
                Skip for now
            </button>
            <button class="btn-submit-evidence" id="submitEvidenceBtn" onclick="submitEvidence()">
                <i class="fas fa-paper-plane"></i> Submit Evidence
            </button>
        </div>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     INCOMING ALERT PANEL — Shown to responders who get notified
════════════════════════════════════════════════════════════ -->
<div id="incomingAlertPanel" class="incoming-alert-panel">
    <div class="incoming-alert-pulse"></div>
    <div class="incoming-alert-header">
        <div class="incoming-alert-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <strong>SOS ALERT NEARBY</strong>
            <small>Someone needs help!</small>
        </div>
        <button class="incoming-dismiss" onclick="dismissIncomingAlert()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="incoming-alert-body">
        <div class="incoming-detail">
            <i class="fas fa-user"></i>
            <span id="incomingVictimName">Unknown</span>
        </div>
        <div class="incoming-detail">
            <i class="fas fa-map-marker-alt"></i>
            <span id="incomingLocation">Loading...</span>
        </div>
        <div class="incoming-detail">
            <i class="fas fa-exclamation-circle"></i>
            <span id="incomingCrimeType">Not specified</span>
        </div>
        <div class="incoming-detail">
            <i class="fas fa-clock"></i>
            <span id="incomingTime">Just now</span>
        </div>
    </div>
    <div class="incoming-alert-actions">
        <button class="btn-respond" onclick="viewSOSDetails()">
            <i class="fas fa-running"></i> Respond & View Details
        </button>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════
     SOS DETAILS MODAL — Full details for responders
════════════════════════════════════════════════════════════ -->
<div id="sosDetailsModal" style="display:none;" class="sv-modal-overlay">
    <div class="sv-modal details-modal">
        <div class="sv-modal-header">
            <div class="sv-modal-icon emergency">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3>Emergency Details</h3>
                <p>Victim information &amp; evidence</p>
            </div>
            <button class="sv-modal-close" onclick="closeSOSDetailsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="sosDetailsContent" class="sos-details-content">
            <p style="color:#a0b4cc;text-align:center;padding:20px;">Loading...</p>
        </div>
    </div>
</div>


<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/sos.js') }}"></script>

<!-- Anonymous SOS Modal — Login ছাড়া SOS দিলে phone number চাইবে -->
<div id="anonymousSosModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:999999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#0d1117;border:1px solid #e6394666;border-radius:20px;padding:28px;max-width:420px;width:100%;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:60px;height:60px;border-radius:50%;background:#e6394622;border:2px solid #e6394666;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <i class="fas fa-exclamation-triangle" style="font-size:26px;color:#e63946;"></i>
            </div>
            <h2 style="color:#fff;font-size:18px;margin-bottom:6px;">Emergency SOS</h2>
            <p style="color:#a0b4cc;font-size:13px;line-height:1.6;">তুমি login করা নেই। Responders তোমার সাথে contact করতে পারবে না।<br>
            নিচে তোমার <strong style="color:#e63946;">phone number</strong> দাও যাতে কেউ তোমাকে call করতে পারে।</p>
        </div>
        <div style="margin-bottom:14px;">
            <label style="color:#a0b4cc;font-size:13px;display:block;margin-bottom:6px;">তোমার নাম (optional)</label>
            <input type="text" id="anonName" placeholder="তোমার নাম..." maxlength="60"
                style="width:100%;background:#0a0f1e;border:1px solid #e6394640;border-radius:8px;padding:10px 14px;color:#fff;font-size:14px;outline:none;box-sizing:border-box;">
        </div>
        <div style="margin-bottom:16px;">
            <label style="color:#a0b4cc;font-size:13px;display:block;margin-bottom:6px;">Phone Number <span style="color:#e63946;">*</span></label>
            <input type="tel" id="anonPhone" placeholder="01XXXXXXXXX" maxlength="13"
                style="width:100%;background:#0a0f1e;border:1px solid #e6394640;border-radius:8px;padding:10px 14px;color:#fff;font-size:15px;outline:none;box-sizing:border-box;letter-spacing:1px;"
                oninput="this.value=this.value.replace(/[^0-9+]/g,'')">
        </div>
        <div id="anonSosErr" style="display:none;color:#e63946;font-size:12px;margin-bottom:12px;padding:8px;background:#e6394622;border-radius:8px;"></div>
        <div style="display:flex;gap:10px;">
            <button onclick="closeAnonymousSosModal()"
                style="flex:1;background:transparent;border:1px solid #1e2d4a;color:#a0b4cc;border-radius:10px;padding:12px;font-size:14px;cursor:pointer;">
                Cancel
            </button>
            <button onclick="proceedAnonymousSos()"
                style="flex:2;background:#e63946;border:none;color:#fff;border-radius:10px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-broadcast-tower"></i> Send SOS Alert
            </button>
        </div>
        <p style="text-align:center;color:#4a5568;font-size:11px;margin-top:14px;">
            <a href="/login" style="color:#4f9eff;">Login করলে</a> location automatically track হয় এবং বেশি সুরক্ষা পাবে।
        </p>
    </div>
</div>

<script>
// Crime type radio helper
function setCrimeType(radio) {
    document.getElementById('crimeType').value = radio.value;
    // Update modal msg visibility
    const el = document.getElementById('modalMsg');
    if (el) el.style.display = 'none';
}

// Override showModalMsg to also show the element
const _origShowModalMsg = showModalMsg;
function showModalMsg(msg, type) {
    const el = document.getElementById('modalMsg');
    if (el) {
        el.textContent = msg;
        el.className   = 'modal-msg ' + type;
        el.style.display = 'block';
    }
}
</script>
@endsection

@section('scripts')
{{-- SOS page এ theme toggle নেই, সবসময় dark mode --}}
<script src="{{ asset('js/fcm.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const svUser = localStorage.getItem('sv_user');
        if (svUser) setTimeout(() => initFCM(), 2000);
    });
</script>
@endsection