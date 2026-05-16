@extends('layouts.app')
@section('title', 'Legal Help — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/legal.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
<!-- NAVBAR -->
    

    <div class="legal-layout">
        <div class="legal-container">

            <!-- HEADER -->
            <div class="legal-header">
                <i class="fas fa-gavel"></i>
                <h1>Legal Help Request</h1>
                <p>Describe your issue and we'll connect you with the right legal support</p>
            </div>

            <!-- INFO CARDS -->
            <div class="legal-info-cards">
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h4>Response Time</h4>
                    <p>Within 24–48 hours</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-lock"></i>
                    <h4>Confidential</h4>
                    <p>100% private & secure</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-users"></i>
                    <h4>Expert Lawyers</h4>
                    <p>Verified legal professionals</p>
                </div>
            </div>

            <!-- FORM CARD -->
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
                        <div class="budget-btn" onclick="selectBudget(this, 'free')">
                            <i class="fas fa-hand-holding-heart"></i>
                            <span>Free / Pro Bono</span>
                        </div>
                        <div class="budget-btn" onclick="selectBudget(this, '1000-5000')">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>৳ 1,000 – 5,000</span>
                        </div>
                        <div class="budget-btn" onclick="selectBudget(this, '5000-15000')">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>৳ 5,000 – 15,000</span>
                        </div>
                        <div class="budget-btn" onclick="selectBudget(this, '15000+')">
                            <i class="fas fa-briefcase"></i>
                            <span>৳ 15,000+</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Preferred Contact <span class="optional-label">(Optional)</span></label>
                    <input type="tel" class="form-input" id="contactPhone" placeholder="+880 1700-000000" />
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Preferred Consultation Time <span class="optional-label">(Optional)</span></label>
                    <input type="datetime-local" class="form-input" id="consultTime" />
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

    <!-- SUCCESS MODAL -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-box">
            <div class="success-body">
                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                <h2>Request Submitted!</h2>
                <p>Your legal help request has been received. Our team will review and connect you with a lawyer soon.</p>

                <div class="request-id-box">
                    <span>Request ID</span>
                    <h3 id="requestId">LR-2026-0000</h3>
                    <p>Save this ID to track your request</p>
                </div>

                <div class="success-actions">
                    <a href="/dashboard" class="btn-go-dash">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                    <button class="btn-close-modal" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/legal.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
@endsection

@section('scripts')
<script src="{{ asset('js/legal.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
@endsection
