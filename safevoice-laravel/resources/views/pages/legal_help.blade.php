@extends('layouts.app')
@section('title', 'Legal Help — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/legal.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
    <div class="legal-layout">
        <div class="legal-container">

            <div class="legal-header">
                <i class="fas fa-gavel"></i>
                <h1>Legal Help Request</h1>
                <p>Describe your issue and we'll connect you with the right legal support</p>
            </div>

            <div class="legal-info-cards">
                <div class="info-card"><i class="fas fa-clock"></i><h4>Response Time</h4><p>Within 24–48 hours</p></div>
                <div class="info-card"><i class="fas fa-lock"></i><h4>Confidential</h4><p>100% private & secure</p></div>
                <div class="info-card"><i class="fas fa-users"></i><h4>Expert Lawyers</h4><p>Verified legal professionals</p></div>
            </div>

            <div class="legal-form-card">

                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Legal Issue Type</label>
                    <select class="form-select" id="issueType">
                        <option value="">Select issue type...</option>
                        <option value="harassment">Harassment / Abuse</option>
                        <option value="labor">Labor / Workplace Issue</option>
                        <option value="domestic">Domestic Violence</option>
                        <option value="fraud">Fraud / Financial Crime</option>
                        <option value="corruption">Corruption / Bribery</option>
                        <option value="property">Property Dispute</option>
                        <option value="cyber">Cyber Crime</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Describe Your Problem</label>
                    <textarea class="form-textarea" id="issueDesc" placeholder="Explain your legal issue in detail. Include dates, locations, and any relevant information..."></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-wallet"></i> Budget Range</label>
                    <div class="budget-options" id="budgetOptions">
                        <div class="budget-btn" onclick="selectBudget(this, '1000-5000')">
                            <i class="fas fa-money-bill-wave"></i><span>৳ 1,000 – 5,000</span>
                        </div>
                        <div class="budget-btn" onclick="selectBudget(this, '5000-15000')">
                            <i class="fas fa-money-bill-wave"></i><span>৳ 5,000 – 15,000</span>
                        </div>
                        <div class="budget-btn" onclick="selectBudget(this, '15000+')">
                            <i class="fas fa-briefcase"></i><span>৳ 15,000+</span>
                        </div>
                    </div>
                </div>

                {{-- District Preference — mandatory --}}
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Preferred Lawyer District <span style="color:#ef4444">*</span></label>
                    <select class="form-select" id="preferredDistrict">
                        <option value="">Select district...</option>
                    </select>
                </div>

                {{-- Phone — mandatory --}}
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Contact Number <span style="color:#ef4444">*</span></label>
                    <input type="tel" class="form-input" id="contactPhone" placeholder="+880 1700-000000" required />
                </div>

                {{-- Instant or Scheduled --}}
                <div class="form-group">
                    <label><i class="fas fa-bolt"></i> Response Type <span style="color:#ef4444">*</span></label>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <div class="budget-btn" id="typeScheduled" onclick="selectType('scheduled')" style="flex:1;min-width:140px;">
                            <i class="fas fa-calendar-check"></i>
                            <span>Scheduled</span>
                            <small style="display:block;font-size:11px;color:#a0b4cc;margin-top:4px;">Set your own deadline</small>
                        </div>
                        <div class="budget-btn" id="typeInstant" onclick="selectType('instant')" style="flex:1;min-width:140px;">
                            <i class="fas fa-bolt" style="color:#fbbf24"></i>
                            <span>⚡ Instant (2hr)</span>
                            <small style="display:block;font-size:11px;color:#fbbf24;margin-top:4px;">Deadline auto-set to 2 hours</small>
                        </div>
                    </div>
                </div>

                {{-- Deadline — mandatory for Scheduled, hidden for Instant --}}
                <div class="form-group" id="deadlineGroup">
                    <label><i class="fas fa-hourglass-half"></i> Response Deadline <span style="color:#ef4444">*</span>
                        <small style="color:#6b7280;font-weight:400;margin-left:6px;">(min 2 hours from now)</small>
                    </label>
                    <input type="datetime-local" class="form-input" id="deadline" />
                    <p style="font-size:11px;color:#6b7280;margin-top:6px;">
                        <i class="fas fa-info-circle"></i> If no lawyer accepts by this time, you'll be notified to increase your budget.
                    </p>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-paperclip"></i> Supporting Documents <span class="optional-label">(Optional)</span></label>
                    <div class="upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Upload relevant documents</p>
                        <span>PDF, JPG, PNG up to 10MB</span>
                        <input type="file" accept=".pdf, image/*" multiple />
                    </div>
                </div>

                <button class="btn-submit-legal" onclick="submitLegalRequest()">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>

            </div>
        </div>
    </div>

    <div class="modal-overlay" id="successModal">
        <div class="modal-box">
            <div class="success-body">
                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                <h2>Request Submitted!</h2>
                <p>Lawyers in your preferred district will be notified and will respond with their offers shortly.</p>
                <div class="request-id-box">
                    <span>Request ID</span>
                    <h3 id="requestId">LR-2026-0000</h3>
                    <p>Use this ID to track your case</p>
                </div>
                <div id="deadlineInfo" style="background:#fbbf2415;border:1px solid #fbbf2440;border-radius:10px;padding:12px;margin:12px 0;font-size:13px;color:#fbbf24;display:none;">
                    <i class="fas fa-hourglass-half"></i> Deadline: <strong id="deadlineDisplay"></strong>
                </div>
                <div class="success-actions">
                    <a id="trackCaseBtn" href="#" class="btn-go-dash" style="background:#4f9eff20;color:#4f9eff;border:1px solid #4f9eff40;">
                        <i class="fas fa-search"></i> Track My Case
                    </a>
                    <a href="/dashboard" class="btn-go-dash"><i class="fas fa-home"></i> Go to Dashboard</a>
                    <button class="btn-close-modal" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    <?php
        $svUserId    = session('user_id')    ? (int) session('user_id')    : 0;
        $svUserName  = session('user_name')  ? session('user_name')        : '';
        $svUserPhone = session('user_phone') ? session('user_phone')       : '';
    ?>
    <script>
        window.SAFEVOICE_USER = <?php echo json_encode(['id' => $svUserId, 'name' => $svUserName, 'phone' => $svUserPhone]); ?>;
    </script>
    <script src="{{ asset('js/legal.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
@endsection