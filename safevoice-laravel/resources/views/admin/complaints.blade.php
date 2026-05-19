@extends('layouts.admin')
@section('title', 'Manage Complaints — SafeVoice Admin')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin-complaints.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">

@endsection

@section('content')
<script>
    if (localStorage.getItem('isAdminLoggedIn') !== 'true') {
        window.location.href = '/admin/login';
    }
</script>

<!-- NAVBAR -->


<div class="dashboard-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="/admin/dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="active"><a href="admin-complaints.html"><i class="fas fa-file-alt"></i> Complaints</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#"><i class="fas fa-exclamation-triangle"></i> SOS</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOP BAR -->
        <div class="ac-topbar">
            <div>
                <h1 class="ac-title"><i class="fas fa-file-alt"></i> Complaint Management</h1>
                <p class="ac-subtitle">Review, assign, and update all complaints</p>
            </div>
            <div class="ac-stats-mini">
                <div class="mini-stat">
                    <span id="totalCount">0</span>
                    <p>Total</p>
                </div>
                <div class="mini-stat orange">
                    <span id="pendingCount">0</span>
                    <p>Pending</p>
                </div>
                <div class="mini-stat blue">
                    <span id="reviewCount">0</span>
                    <p>Review</p>
                </div>
                <div class="mini-stat green">
                    <span id="resolvedCount">0</span>
                    <p>Resolved</p>
                </div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="filter-bar">
            <div class="filter-left">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by ID, type, location..." oninput="applyFilters()" />
                </div>
            </div>
            <div class="filter-right">
                <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                    <option value="">All Statuses</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Private Investigator Assigned">PI Assigned</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <select class="filter-select" id="typeFilter" onchange="applyFilters()">
                    <option value="">All Types</option>
                    <option value="Harassment">Harassment</option>
                    <option value="Fare Overcharge">Fare Overcharge</option>
                    <option value="Crime">Crime</option>
                    <option value="Corruption">Corruption</option>
                    <option value="Other">Other</option>
                </select>
                <button class="btn-reset-filter" onclick="resetFilters()">
                    <i class="fas fa-times"></i> Reset
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="complaints-table ac-table">
            <table>
                <thead>
                    <tr>
                        <th>Complaint ID</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="complaintsBody">
                    <!-- injected by JS -->
                </tbody>
            </table>
            <div class="empty-state" id="emptyState" style="display:none;">
                <i class="fas fa-inbox"></i>
                <p>No complaints found</p>
            </div>
        </div>

    </main>
</div>

<!-- DETAIL MODAL -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-box detail-modal">

        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> Complaint Detail</h3>
            <i class="fas fa-times modal-close" onclick="closeModal()"></i>
        </div>

        <div class="modal-body">

            <!-- Info grid -->
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Complaint ID</span>
                    <span class="detail-value" id="dId">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Type</span>
                    <span class="detail-value" id="dType">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date</span>
                    <span class="detail-value" id="dDate">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Location</span>
                    <span class="detail-value" id="dLocation">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Reporter</span>
                    <span class="detail-value" id="dReporter">—</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Anonymous</span>
                    <span class="detail-value" id="dAnon">—</span>
                </div>
            </div>

            <!-- Description -->
            <div class="detail-desc-box">
                <span class="detail-label">Description</span>
                <p id="dDesc">—</p>
            </div>

            <!-- Evidence Section -->
            <div class="detail-desc-box" style="margin-top:16px;">
                <span class="detail-label"><i class="fas fa-paperclip" style="color:#4f9eff;margin-right:6px;"></i> Evidence Files</span>
                <div id="evidenceList" style="margin-top:12px;">
                    <p style="color:#4a5568;font-size:13px;">Loading...</p>
                </div>
            </div>

            <!-- Evidence Request Status -->
            <div id="evReqStatusBox" style="display:none;margin-top:12px;padding:10px 14px;border-radius:10px;border:1px solid #1e2d4a;background:#0a0f1e;">
                <p style="margin:0;font-size:12px;color:#a0b4cc;font-weight:600;text-transform:uppercase;letter-spacing:.5px;"><i class="fas fa-bell" style="color:#f39c12;margin-right:6px;"></i> Evidence Request Status</p>
                <div id="evReqStatusContent" style="margin-top:8px;font-size:13px;color:#a0b4cc;"></div>
            </div>

            <!-- Admin Controls -->
            <div class="admin-controls">
                <div class="control-group">
                    <label><i class="fas fa-sync-alt"></i> Update Status</label>
                    <select class="filter-select" id="statusUpdate">
    <option value="Submitted">Submitted</option>
    <option value="Under Review">Under Review</option>
    <option value="PI Notification Sent">PI Notification Sent</option>
    <option value="PI Payment Confirmed">PI Payment Confirmed</option>
    <option value="Private Investigator Assigned">PI Assigned</option>
    <option value="Resolved">Resolved</option>
    <option value="Rejected">Rejected</option>
</select>
                </div>
                <div class="control-group">
                    <label><i class="fas fa-user-tie"></i> Assign Officer</label>
                    <select class="filter-select" id="officerAssign">
                        <option value="Not Assigned">Not Assigned</option>
                        <option value="Inspector Karim">Inspector Karim</option>
                        <option value="Inspector Rahman">Inspector Rahman</option>
                        <option value="Inspector Sadia">Inspector Sadia</option>
                        <option value="Inspector Faruk">Inspector Faruk</option>
                    </select>
                </div>
            </div>

            <!-- Message to victim -->
            <div class="control-group" style="margin-top: 16px;">
                <label><i class="fas fa-comment-dots"></i> Message to Reporter</label>
                <textarea class="msg-textarea" id="adminMsgInput" placeholder="Send an update or note to the reporter..."></textarea>
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn-decline" onclick="closeModal()">
                <i class="fas fa-times"></i> Close
            </button>
            <button class="btn-req-evidence" id="btnReqEvidence" onclick="openRequestEvidenceModal()" title="Ask user to submit more evidence">
                <i class="fas fa-file-upload"></i> Request Evidence
            </button>
            <button class="btn-accept" onclick="saveChanges()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>

    </div>
</div>

<!-- REQUEST EVIDENCE MODAL -->
<div class="modal-overlay" id="reqEvidenceModal">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-upload" style="color:#f39c12;margin-right:8px;"></i> Request More Evidence</h3>
            <i class="fas fa-times modal-close" onclick="closeReqEvidenceModal()"></i>
        </div>
        <div class="modal-body">
            <p style="color:#a0b4cc;font-size:13px;margin-bottom:16px;">
                User will receive a notification asking them to submit additional evidence for complaint
                <strong id="reqEvComplaintId" style="color:#4f9eff;"></strong>.
                They will have <strong style="color:#f39c12;">7 days</strong> to respond.
            </p>
            <div class="control-group">
                <label><i class="fas fa-comment-alt"></i> Note to User <span style="color:#4a5568;font-size:11px;">(optional)</span></label>
                <textarea class="msg-textarea" id="reqEvNote" placeholder="e.g. Please upload clearer photos of the incident, or a receipt showing the correct fare amount..." style="height:100px;"></textarea>
            </div>
            <div id="reqEvStatus" style="margin-top:12px;font-size:13px;display:none;padding:10px 14px;border-radius:8px;"></div>
            <!-- Existing request info -->
            <div id="reqEvExisting" style="display:none;margin-top:12px;background:#0a0f1e;border:1px solid #f39c1240;border-radius:10px;padding:12px 14px;">
                <p style="margin:0;font-size:12px;color:#f39c12;font-weight:700;"><i class="fas fa-exclamation-triangle"></i> Active Request Exists</p>
                <p id="reqEvExistingInfo" style="margin:6px 0 0;font-size:12px;color:#a0b4cc;"></p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-decline" onclick="closeReqEvidenceModal()">Cancel</button>
            <button class="btn-accept" id="reqEvSendBtn" onclick="sendEvidenceRequest()" style="background:linear-gradient(135deg,#b45309,#f59e0b);">
                <i class="fas fa-paper-plane"></i> Send Request
            </button>
        </div>
    </div>
</div>

<!-- EXPIRED EVIDENCE BANNER (top of page) -->
<div id="expiredEvidenceBanner" style="display:none;background:#1a0a00;border:1px solid #f39c12;border-radius:12px;padding:14px 20px;margin-bottom:20px;position:relative;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <i class="fas fa-exclamation-triangle" style="color:#f39c12;font-size:20px;flex-shrink:0;"></i>
        <div style="flex:1;">
            <p style="margin:0;font-weight:700;color:#f39c12;font-size:14px;">Evidence Submission Deadline Missed</p>
            <p id="expiredBannerText" style="margin:4px 0 0;color:#a0b4cc;font-size:13px;"></p>
        </div>
        <button onclick="document.getElementById('expiredEvidenceBanner').style.display='none'" style="background:none;border:none;color:#4a5568;cursor:pointer;font-size:18px;flex-shrink:0;"><i class="fas fa-times"></i></button>
    </div>
</div>

<!-- SAVE SUCCESS TOAST -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Changes saved successfully!
</div>

<script>
    function logout() {
        localStorage.removeItem('isAdminLoggedIn');
        window.location.href = '/admin/login';
    }
</script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/admin-complaints.js') }}"></script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection