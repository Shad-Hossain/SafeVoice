@extends('layouts.app')
@section('title', 'Sos — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/sos.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
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
<script src="{{ asset('js/theme.js') }}"></script>
@endsection