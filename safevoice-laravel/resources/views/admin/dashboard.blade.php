@extends('layouts.admin')
@section('title', 'Admin Dashboard — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
            align-items: center;
        }

        .filter-bar select,
        .filter-bar input {
            background: #0f1a2e;
            border: 1px solid #1e2d4a;
            color: #e5e7eb;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            cursor: pointer;
        }

        body.light-mode .filter-bar select,
        body.light-mode .filter-bar input {
            background: #f8fafc;
            border-color: #d1dce8;
            color: #0f172a;
        }

        .filter-bar select:focus,
        .filter-bar input:focus { border-color: #4f9eff; }

        .filter-bar .btn-refresh {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background .2s;
        }
        .filter-bar .btn-refresh:hover { background: #1d4ed8; }

        .status-select {
            background: transparent;
            border: 1px solid #1e2d4a;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all .2s;
        }

        body.light-mode .status-select {
            border-color: #d1dce8;
            color: #0f172a;
            background: #fff;
        }

        .status-select.s-submitted       { color: #93c5fd; border-color: #2563eb40; background: #2563eb15; }
        .status-select.s-under-review    { color: #fbbf24; border-color: #f59e0b40; background: #f59e0b15; }
        .status-select.s-officer-assigned{ color: #c084fc; border-color: #a855f740; background: #a855f715; }
        .status-select.s-investigation   { color: #fb923c; border-color: #f9731640; background: #f9731615; }
        .status-select.s-resolved        { color: #34d399; border-color: #10b98140; background: #10b98115; }
        .status-select.s-rejected        { color: #f87171; border-color: #ef444440; background: #ef444415; }

        .table-state {
            text-align: center;
            padding: 50px 20px;
            color: #a0b4cc;
            font-size: 15px;
        }
        .table-state i { font-size: 36px; margin-bottom: 12px; display: block; color: #1e2d4a; }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #2ecc71;
            color: #fff;
            padding: 14px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideUp .3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .toast.error { background: #e63946; }
        @keyframes slideUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .view-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7);
            display: none; align-items: center; justify-content: center;
            z-index: 9999; padding: 20px;
        }
        .view-modal-overlay.active { display: flex; }
        .view-modal {
            background: #111c33;
            border: 1px solid #1e2d4a;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        }
        body.light-mode .view-modal { background: #fff; border-color: #d1dce8; }
        .view-modal h3 { color: #4f9eff; font-size: 18px; margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #1e2d4a; font-size: 14px; }
        body.light-mode .detail-row { border-bottom-color: #e2e8f0; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #a0b4cc; font-weight: 600; min-width: 130px; }
        .detail-value { color: #fff; text-align: right; flex: 1; }
        body.light-mode .detail-value { color: #0f172a; }
        .desc-box { background: #0a0f1e; border: 1px solid #1e2d4a; border-radius: 10px; padding: 15px; margin-top: 15px; font-size: 13px; color: #a0b4cc; line-height: 1.7; }
        body.light-mode .desc-box { background: #f8fafc; border-color: #d1dce8; color: #475569; }
        .modal-close-btn { background: transparent; border: 1px solid #1e2d4a; color: #a0b4cc; border-radius: 8px; padding: 10px 20px; font-size: 13px; cursor: pointer; margin-top: 20px; transition: all .2s; }
        .modal-close-btn:hover { border-color: #4f9eff; color: #4f9eff; }

        /* PI Notification modal */
        .pi-modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,0.75);display:none;align-items:center;justify-content:center;z-index:99999;padding:20px; }
        .pi-modal-overlay.active { display:flex; }
        .pi-modal { background:#111c33;border:1px solid #a855f740;border-radius:20px;padding:30px;max-width:480px;width:100%; }
        .pi-modal-icon { width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#a855f720,#c084fc20);border:2px solid #a855f750;display:flex;align-items:center;justify-content:center;margin:0 auto 18px; }
        .pi-modal-icon i { font-size:28px;color:#c084fc; }
        .pi-modal h3 { text-align:center;color:#fff;font-size:18px;margin-bottom:8px; }
        .pi-modal p  { text-align:center;color:#a0b4cc;font-size:14px;line-height:1.6;margin-bottom:22px; }
        .pi-fee-box { background:#a855f710;border:1px solid #a855f740;border-radius:12px;padding:16px 20px;text-align:center;margin-bottom:22px; }
        .pi-fee-box .fee-label { font-size:12px;color:#a0b4cc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px; }
        .pi-fee-box .fee-amount { font-size:28px;font-weight:800;color:#c084fc; }
        .pi-fee-box .fee-note { font-size:12px;color:#a0b4cc;margin-top:4px; }
        .pi-modal-btns { display:flex;gap:12px; }
        .pi-modal-btns .btn-send { flex:1;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s; }
        .pi-modal-btns .btn-send:hover { opacity:.85; }
        .pi-modal-btns .btn-cancel { background:transparent;border:1px solid #1e2d4a;color:#a0b4cc;border-radius:10px;padding:13px 20px;font-size:14px;cursor:pointer;transition:all .2s; }
        .pi-modal-btns .btn-cancel:hover { border-color:#4f9eff;color:#4f9eff; }

        /* Request Evidence button in complaint modal */
        .btn-request-evidence { background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .2s;margin-top:20px;margin-right:10px; }
        .btn-request-evidence:hover { opacity:.85; }

        /* Evidence Request Modal */
        .er-modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,0.8);display:none;align-items:center;justify-content:center;z-index:999999;padding:20px; }
        .er-modal-overlay.active { display:flex; }
        .er-modal { background:#111c33;border:1px solid #d9770640;border-radius:20px;padding:30px;max-width:480px;width:100%; }
        .er-modal-icon { width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#d9770620,#f59e0b20);border:2px solid #d9770650;display:flex;align-items:center;justify-content:center;margin:0 auto 16px; }
        .er-modal-icon i { font-size:26px;color:#f59e0b; }
        .er-modal h3 { text-align:center;color:#fff;font-size:17px;margin-bottom:6px; }
        .er-modal-cid { text-align:center;color:#f59e0b;font-weight:700;font-size:14px;margin-bottom:16px; }
        .er-note-label { color:#a0b4cc;font-size:13px;font-weight:600;margin-bottom:6px;display:block; }
        .er-note-textarea { width:100%;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;color:#fff;font-size:13px;padding:12px;resize:vertical;min-height:90px;font-family:inherit;box-sizing:border-box; }
        .er-note-textarea:focus { outline:none;border-color:#d97706; }
        .er-modal-info { background:#d9770610;border:1px solid #d9770630;border-radius:10px;padding:12px 14px;font-size:12px;color:#d97706;margin:14px 0;line-height:1.6; }
        .er-modal-btns { display:flex;gap:12px;margin-top:20px; }
        .er-modal-btns .btn-send-req { flex:1;background:linear-gradient(135deg,#b45309,#d97706);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s; }
        .er-modal-btns .btn-send-req:hover { opacity:.85; }
        .er-modal-btns .btn-cancel-req { background:transparent;border:1px solid #1e2d4a;color:#a0b4cc;border-radius:10px;padding:13px 20px;font-size:14px;cursor:pointer;transition:all .2s; }
        .er-modal-btns .btn-cancel-req:hover { border-color:#d97706;color:#d97706; }

        /* Expired Evidence Alert Banner */
        .expired-evidence-banner { background:linear-gradient(135deg,#7f1d1d20,#991b1b20);border:1px solid #ef444440;border-radius:14px;padding:16px 20px;margin-bottom:20px;display:none; }
        .expired-evidence-banner h4 { color:#f87171;font-size:14px;font-weight:700;margin:0 0 10px 0; }
        .expired-ev-item { background:#0a0f1e;border:1px solid #ef444430;border-radius:8px;padding:10px 14px;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;gap:12px; }
        .expired-ev-item .ev-cid { color:#f87171;font-weight:700;font-size:13px; }
        .expired-ev-item .ev-meta { color:#a0b4cc;font-size:12px;margin-top:2px; }
        .expired-ev-item .btn-send-pi-notif { background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .2s; }
        .expired-ev-item .btn-send-pi-notif:hover { opacity:.85; }
    </style>
@endsection

@section('content')
<script>
    if (localStorage.getItem('isAdminLoggedIn') !== 'true') window.location.href = '/admin/login';
</script>

<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li id="nav-dashboard"><a href="#" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a></li>
            <li id="nav-complaints"><a href="#" onclick="showSection('complaints')"><i class="fas fa-file-alt"></i> Complaints</a></li>
            <li id="nav-users"><a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> Users</a></li>
            <li id="nav-payments"><a href="#" onclick="showSection('payments')"><i class="fas fa-credit-card"></i> Payments</a></li>
            <li id="nav-sos"><a href="#" onclick="showSection('sos')"><i class="fas fa-exclamation-triangle"></i> SOS Alerts</a></li>
            <li id="nav-sos-evidence"><a href="#" onclick="showSection('sos-evidence')"><i class="fas fa-camera"></i> SOS Evidence <span id="evidencePendingBadge" style="display:none;background:#e63946;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:6px;"></span></a></li>
            <li id="nav-pending-accounts">
                <a href="#" onclick="showSection('pending-accounts')">
                    <i class="fas fa-user-clock"></i>
                    Pending Accounts
                    <span id="pendingAccountsBadge"
                          style="display:none;background:#e63946;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:6px;"></span>
                </a>
            </li>
        </ul>
        <div style="padding:14px 16px;border-top:1px solid #1e2d4a;margin-top:20px">
            <a href="{{ route('super-admin.login') }}" style="display:flex;align-items:center;gap:8px;color:#fbbf24;font-size:12px;font-weight:600;text-decoration:none;background:#fbbf2410;border:1px solid #fbbf2430;border-radius:8px;padding:9px 12px">
                <i class="fas fa-crown"></i> Super Admin Portal
            </a>
        </div>
    </aside>

    <main class="main-content" id="mainContent">

        <div id="view-dashboard">
            <!-- Expired Evidence Alert Banner -->
            <div class="expired-evidence-banner" id="expiredEvidenceBanner">
                <h4><i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>Evidence Submission Failed — Action Required</h4>
                <p style="color:#a0b4cc;font-size:12px;margin:0 0 12px 0;">The following users did not submit evidence within 7 days. You can notify them via PI.</p>
                <div id="expiredEvidenceList"></div>
            </div>
            <div class="welcome-bar">
                <h1>Welcome Admin 👋</h1>
                <p>Real-time SafeVoice complaint management</p>
            </div>
            <div class="summary-cards">
                <div class="summary-card" style="cursor:pointer" onclick="showSection('complaints')">
                    <div class="card-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="card-info"><h3 id="stat-total">—</h3><p>Total Complaints</p></div>
                </div>
                <div class="summary-card" style="cursor:pointer" onclick="showSection('complaints','Submitted')">
                    <div class="card-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="card-info"><h3 id="stat-submitted">—</h3><p>Submitted</p></div>
                </div>
                <div class="summary-card" style="cursor:pointer" onclick="showSection('complaints','Under Review')">
                    <div class="card-icon blue"><i class="fas fa-search"></i></div>
                    <div class="card-info"><h3 id="stat-review">—</h3><p>Under Review</p></div>
                </div>
                <div class="summary-card" style="cursor:pointer" onclick="showSection('complaints','Resolved')">
                    <div class="card-icon green"><i class="fas fa-check"></i></div>
                    <div class="card-info"><h3 id="stat-resolved">—</h3><p>Resolved</p></div>
                </div>
            </div>
            <div class="section-title" style="font-size:18px;font-weight:700;margin-bottom:15px;">
                Recent Complaints
                <span id="dashboard-loading" style="font-size:13px;color:var(--text-secondary);font-weight:400;margin-left:10px;"></span>
            </div>
            <div class="complaints-table">
                <table>
                    <thead>
                        <tr><th>Complaint ID</th><th>Type</th><th>Date</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="recent-tbody">
                        <tr><td colspan="5" class="table-state"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view-complaints" style="display:none">
            <div class="welcome-bar" style="margin-bottom:20px">
                <h1><i class="fas fa-file-alt" style="font-size:22px;margin-right:10px"></i>All Complaints</h1>
                <p>View, filter, and update complaint statuses</p>
            </div>
            <div class="filter-bar">
                <select id="filterStatus" onchange="loadComplaints()">
                    <option value="">All Statuses</option>
                    <option value="Submitted">Submitted</option>
                    <option value="Under Review">Under Review</option>
                    <option value="PI Notification Sent">PI Notification Sent</option>
                    <option value="PI Payment Pending">PI Payment Pending</option>
                    <option value="Private Investigator Assigned">PI Assigned</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <select id="filterType" onchange="loadComplaints()">
                    <option value="">All Types</option>
                    <option value="harassment">Harassment</option>
                    <option value="fare_overcharge">Fare Overcharge</option>
                    <option value="crime">Crime</option>
                    <option value="corruption">Corruption</option>
                    <option value="abuse">Abuse</option>
                    <option value="other">Other</option>
                </select>
                <button class="btn-refresh" onclick="loadComplaints()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span id="complaints-count" style="color:var(--text-secondary);font-size:13px;margin-left:auto"></span>
            </div>
            <div class="complaints-table">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Submitted</th>
                            <th>Anonymous</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="complaints-tbody">
                        <tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i>Loading complaints...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view-users" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-users" style="font-size:22px;margin-right:10px"></i>User Management</h1>
                <p>Ban, suspend, or manage user accounts</p>
            </div>

            <!-- User ID Search -->
            <div style="margin-bottom:20px;padding:16px 20px;border-radius:14px;border:1px solid #1e3a5f;background:linear-gradient(135deg,#0a1628,#0d1f3c);">
                <p style="margin:0 0 10px 0;font-size:13px;color:#4f9eff;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">
                    <i class="fas fa-search" style="margin-right:6px;"></i> Search User by ID
                </p>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <input type="number" id="userIdSearchInput" placeholder="Enter User ID (e.g. 5)"
                        style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:10px 14px;color:#fff;font-size:14px;width:220px;outline:none;"
                        onkeydown="if(event.key==='Enter') searchUserById()" />
                    <button onclick="searchUserById()"
                        style="background:#4f9eff;color:#000;border:none;border-radius:10px;padding:10px 20px;font-weight:700;font-size:14px;cursor:pointer;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button onclick="clearUserSearch()"
                        style="background:#1e2d4a;color:#a0b4cc;border:1px solid #1e2d4a;border-radius:10px;padding:10px 16px;font-size:13px;cursor:pointer;">
                        Clear
                    </button>
                </div>
                <!-- Search Result -->
                <div id="userSearchResult" style="display:none;margin-top:16px;"></div>
            </div>

            <div class="complaints-table">
                <table>
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Email</th><th>Joined</th><th>Complaints</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="users-tbody">
                        <tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAYMENTS VIEW -->
        <div id="view-payments" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-credit-card" style="font-size:22px;margin-right:10px"></i>Payments</h1>
                <p>PI service payments submitted by users</p>
            </div>
            <div class="complaints-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case ID</th>
                            <th>Method</th>
                            <th>TXN ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="payments-tbody">
                        <tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="view-sos" style="display:none">
            <div class="welcome-bar">
                <h1>SOS Alerts</h1>
                <p>Real-time emergency SOS signals</p>
            </div>
            <div style="display:flex;gap:10px;margin-bottom:14px;align-items:center;">
                <button class="btn-refresh" onclick="loadSosAlerts()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span id="sosAlertsCount" style="color:var(--text-secondary);font-size:13px;margin-left:auto"></span>
            </div>
            <div class="complaints-table">
                <table>
                    <thead><tr><th>Alert ID</th><th>User</th><th>Location</th><th>Crime Type</th><th>Time</th><th>Status</th><th>Details</th></tr></thead>
                    <tbody id="sos-alerts-tbody">
                        <tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SOS EVIDENCE VERIFICATION SECTION -->
        <div id="view-sos-evidence" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-camera" style="font-size:22px;margin-right:10px;color:#4f9eff"></i>SOS Evidence Verification</h1>
                <p>Review and approve responders' evidence — approved evidence increases their leaderboard rank</p>
            </div>
            <div class="filter-bar">
                <select id="evidenceFilterStatus" onchange="loadSosEvidence()">
                    <option value="">All</option>
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button class="btn-refresh" onclick="loadSosEvidence()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span id="evidenceCount" style="color:var(--text-secondary);font-size:13px;margin-left:auto"></span>
            </div>
            <div id="evidenceCardGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
                <div class="table-state" style="grid-column:1/-1">
                    <i class="fas fa-spinner fa-spin"></i> Loading evidence...
                </div>
            </div>
        </div>

        <!-- PENDING ACCOUNTS SECTION -->
        <div id="view-pending-accounts" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-user-clock" style="font-size:22px;margin-right:10px;color:#fbbf24"></i>Pending Account Requests</h1>
                <p>Users who registered with a birth certificate — review and approve or reject</p>
            </div>
            <div class="filter-bar">
                <button class="btn-refresh" onclick="loadPendingAccounts()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span id="pendingAccountsCount" style="color:var(--text-secondary);font-size:13px;margin-left:auto"></span>
            </div>
            <div class="complaints-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Birth Cert No.</th>
                            <th>Registered</th>
                            <th>Document</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pending-accounts-tbody">
                        <tr><td colspan="8" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Birth Certificate Image Modal -->
<div id="bcDocModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#0d1526;border-radius:16px;border:1px solid #1e2d4a;max-width:680px;width:90%;padding:24px;position:relative;">
        <button onclick="closeBcDocModal()"
                style="position:absolute;top:14px;right:16px;background:none;border:none;color:#a0b4cc;font-size:20px;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <h3 style="color:#fff;margin:0 0 16px;font-size:16px;">
            <i class="fas fa-certificate" style="color:#fbbf24;margin-right:8px;"></i>
            Birth Certificate Document
        </h3>
        <div id="bcDocContent" style="text-align:center;"></div>
        <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end;" id="bcDocActions"></div>
    </div>
</div>

<!-- Complaint Detail Modal -->
<div class="view-modal-overlay" id="viewModal">
    <div class="view-modal">
        <h3><i class="fas fa-file-alt"></i> Complaint Details</h3>
        <div id="modalContent"></div>
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:20px;">
            <button class="btn-request-evidence" id="btnRequestEvidence" onclick="openEvidenceRequestModal()">
                <i class="fas fa-file-upload"></i> Request Evidence
            </button>
            <button class="modal-close-btn" style="margin-top:0;" onclick="document.getElementById('viewModal').classList.remove('active')">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- PI Notification Modal -->
<div class="pi-modal-overlay" id="piNotifyModal">
    <div class="pi-modal">
        <div class="pi-modal-icon"><i class="fas fa-user-secret"></i></div>
        <h3>Send Private Investigator Notification</h3>
        <p>You've marked this complaint as <strong style="color:#fbbf24">Private Investigator Assigned</strong>. The user will receive a notification asking them to accept or decline the PI service.</p>
        <div class="pi-fee-box">
            <div class="fee-label">Service Fee (Paid by User)</div>
            <div class="fee-amount">৳1,000</div>
            <div class="fee-note">One-time fee · bKash / Nagad / Rocket / Bank</div>
        </div>
        <p style="font-size:12px;color:#a0b4cc;text-align:center;margin-bottom:18px">
            <i class="fas fa-shield-alt" style="color:#a855f7"></i>
            The system will auto-assign the PI with lowest workload. PI identity stays hidden from normal admin.
        </p>
        <div class="pi-modal-btns">
            <button class="btn-cancel" onclick="cancelPINotify()">Cancel</button>
            <button class="btn-send" onclick="confirmSendPINotify()"><i class="fas fa-paper-plane"></i> Send Notification to User</button>
        </div>
    </div>
</div>

<!-- SOS Evidence Verify Modal -->
<div class="sv-ev-modal-overlay" id="sosEvidenceVerifyModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.8);display:none;align-items:center;justify-content:center;z-index:99999;padding:20px;">
    <div style="background:#111c33;border:1px solid #1e2d4a;border-radius:20px;padding:28px;max-width:520px;width:100%;max-height:85vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="color:#4f9eff;font-size:17px;margin:0;"><i class="fas fa-camera" style="margin-right:8px;"></i>Verify Evidence</h3>
            <button onclick="closeSosEvidenceModal()" style="background:transparent;border:none;color:#a0b4cc;font-size:18px;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="evModalResponderInfo" style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;margin-bottom:16px;font-size:13px;"></div>
        <div id="evModalPreview" style="margin-bottom:16px;text-align:center;"></div>
        <div style="margin-bottom:16px;">
            <label style="color:#a0b4cc;font-size:12px;font-weight:600;display:block;margin-bottom:6px;">
                <i class="fas fa-pen" style="color:#4f9eff;margin-right:4px;"></i>Admin Note <span style="color:#4a5568;font-weight:400">(optional — visible to user)</span>
            </label>
            <textarea id="evModalNote" rows="3" placeholder="e.g. Clear evidence confirmed. / Photo not clear enough..."
                style="width:100%;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;color:#fff;font-size:13px;padding:12px;resize:vertical;font-family:inherit;box-sizing:border-box;outline:none;"></textarea>
        </div>
        <div style="display:flex;gap:10px;">
            <button id="evModalRejectBtn" onclick="verifyEvidence('reject')"
                style="flex:1;padding:13px;border-radius:10px;border:1px solid #e6394640;background:#e6394615;color:#e63946;font-weight:700;font-size:14px;cursor:pointer;transition:all .2s;">
                <i class="fas fa-times-circle"></i> Reject
            </button>
            <button id="evModalApproveBtn" onclick="verifyEvidence('approve')"
                style="flex:2;padding:13px;border-radius:10px;border:none;background:linear-gradient(135deg,#1a6a3a,#2ecc71);color:#fff;font-weight:700;font-size:14px;cursor:pointer;transition:all .2s;">
                <i class="fas fa-check-circle"></i> Approve & Update Rank
            </button>
        </div>
    </div>
</div>

<!-- Victim Evidence Modal -->
<div id="victimEvidenceModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.85);display:none;align-items:center;justify-content:center;z-index:99999;padding:20px;">
    <div style="background:#111c33;border:1px solid #1e2d4a;border-radius:20px;padding:28px;max-width:560px;width:100%;max-height:88vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="color:#16a34a;font-size:17px;margin:0;"><i class="fas fa-user-shield" style="margin-right:8px;"></i>Victim Details & Evidence</h3>
            <button onclick="closeVictimEvidenceModal()" style="background:transparent;border:none;color:#a0b4cc;font-size:20px;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="victimEvidenceContent">
            <div style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i></div>
        </div>
    </div>
</div>

<!-- SOS Alert Detail Modal -->
<div id="sosAlertDetailModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.85);display:none;align-items:center;justify-content:center;z-index:99999;padding:20px;">
    <div style="background:#111c33;border:1px solid #1e2d4a;border-radius:20px;padding:28px;max-width:560px;width:100%;max-height:88vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="color:#e63946;font-size:17px;margin:0;"><i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>SOS Alert Details</h3>
            <button onclick="closeSosAlertDetail()" style="background:transparent;border:none;color:#a0b4cc;font-size:20px;cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="sosAlertDetailContent">
            <div style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i></div>
        </div>
    </div>
</div>

<!-- Evidence Request Modal -->
<div class="er-modal-overlay" id="erRequestModal">
    <div class="er-modal">
        <div class="er-modal-icon"><i class="fas fa-file-upload"></i></div>
        <h3>Request Evidence</h3>
        <div class="er-modal-cid" id="erModalComplaintId"></div>

        <!-- Mode Selector -->
        <div style="display:flex;gap:10px;margin:14px 0;">
            <div id="erMode7Btn" onclick="selectErMode(7)"
                 style="flex:1;cursor:pointer;border-radius:10px;padding:11px 12px;border:2px solid #4f9eff;background:#0a0f1e;transition:all .2s;">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:3px;">
                    <i class="fas fa-clock" style="color:#4f9eff;font-size:13px;"></i>
                    <span style="color:#4f9eff;font-weight:700;font-size:12px;">7-Day Request</span>
                </div>
                <p style="margin:0;color:#a0b4cc;font-size:10px;line-height:1.4;">Normal evidence request. User has 7 days to upload supporting files.</p>
            </div>
            <div id="erMode30Btn" onclick="selectErMode(30)"
                 style="flex:1;cursor:pointer;border-radius:10px;padding:11px 12px;border:2px solid #1e2d4a;background:#0a0f1e;transition:all .2s;">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:3px;">
                    <i class="fas fa-exclamation-triangle" style="color:#e63946;font-size:13px;"></i>
                    <span style="color:#e63946;font-weight:700;font-size:12px;">30-Day Notice</span>
                </div>
                <p style="margin:0;color:#a0b4cc;font-size:10px;line-height:1.4;">Complaint appears fake. User must prove innocence within 30 days.</p>
            </div>
        </div>
        <input type="hidden" id="erSelectedDays" value="7">

        <label class="er-note-label"><i class="fas fa-pen" style="color:#d97706;margin-right:6px;"></i>Message to User <span style="color:#4a5568;font-weight:400">(optional)</span></label>
        <textarea class="er-note-textarea" id="erAdminNoteInput" placeholder="e.g. Please upload a clearer photo of the incident location or any witness statement..."></textarea>
        <div class="er-modal-info" id="erModeInfo">
            <i class="fas fa-info-circle"></i>
            User will receive a notification to submit more evidence.
            They can upload files instantly or skip for <strong>7 days</strong>.
            If they fail to submit within 7 days, you will be automatically notified.
        </div>
        <div class="er-modal-btns">
            <button class="btn-cancel-req" onclick="closeEvidenceRequestModal()">Cancel</button>
            <button class="btn-send-req" id="erSendBtn" onclick="sendEvidenceRequest()">
                <i class="fas fa-paper-plane"></i> Send 7-Day Request
            </button>
        </div>
    </div>
</div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>

// ── Section Navigation ────────────────────────────────────────
function showSection(section, preFilter) {
    ['dashboard','complaints','users','payments','sos','sos-evidence','pending-accounts'].forEach(s => {
        document.getElementById('view-' + s).style.display = 'none';
        document.getElementById('nav-' + s)?.classList.remove('active');
    });
    document.getElementById('view-' + section).style.display = 'block';
    document.getElementById('nav-' + section)?.classList.add('active');

    if (section === 'dashboard')        loadDashboard();
    if (section === 'complaints') {
        if (preFilter) document.getElementById('filterStatus').value = preFilter;
        loadComplaints();
    }
    if (section === 'users')            loadUsers();
    if (section === 'payments')         loadPayments();
    if (section === 'sos')              loadSosAlerts();
    if (section === 'sos-evidence')     loadSosEvidence();
    if (section === 'pending-accounts') loadPendingAccounts();
}

// ── Payments ─────────────────────────────────────────────────
async function loadPayments() {
    const tbody = document.getElementById('payments-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    try {
        const res  = await fetch('/api/admin/payments');
        const data = await res.json();
        if (!data.success) throw new Error();
        const list = data.payments || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-inbox"></i> No payments yet.</td></tr>';
            return;
        }
        const methodLabel = { bkash:'bKash', nagad:'Nagad', rocket:'Rocket', bank:'Bank Transfer' };
        tbody.innerHTML = list.map((p, i) => `
            <tr>
                <td>${i+1}</td>
                <td><strong style="color:#4f9eff">${p.complaint_id}</strong></td>
                <td><span style="font-weight:600;color:#a0b4cc">${methodLabel[p.payment_method] || p.payment_method}</span></td>
                <td><code style="color:#fbbf24;font-size:13px;letter-spacing:1px">${p.txn_id}</code></td>
                <td style="color:#2ecc71;font-weight:700">৳${parseFloat(p.amount).toLocaleString()}</td>
                <td>${p.status === 'confirmed'
                    ? '<span style="background:#2ecc7115;color:#2ecc71;border:1px solid #2ecc7140;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;">✅ Confirmed</span>'
                    : '<span style="background:#fbbf2415;color:#fbbf24;border:1px solid #fbbf2440;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;">⏳ Pending</span>'
                }</td>
                <td style="color:var(--text-secondary);font-size:12px">${formatDate(p.initiated_at)}</td>
            </tr>`).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="7" class="table-state">Could not load payments.</td></tr>';
    }
}

// ── Users ─────────────────────────────────────────────────────
async function loadUsers() {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    try {
        const res  = await fetch('/api/manage_user');
        const data = await res.json();
        if (!data.success) throw new Error();
        tbody.innerHTML = data.users.map((u, i) => `
            <tr>
                <td>${i+1}</td>
                <td><strong>${u.name}</strong></td>
                <td style="color:var(--text-secondary);font-size:13px">${u.email}</td>
                <td style="color:var(--text-secondary);font-size:13px">${formatDate(u.joined_at)}</td>
                <td style="text-align:center">${u.complaints_count}</td>
                <td>${userStatusBadge(u.status)}</td>
                <td>
                    <select class="status-select" onchange="updateUserStatus(${u.id}, this)" style="font-size:12px;padding:4px 8px">
                        <option ${u.status==='Active'    ? 'selected':''} value="Active">✅ Active</option>
                        <option ${u.status==='Probation' ? 'selected':''} value="Probation">⚠️ Probation</option>
                        <option ${u.status==='Suspended' ? 'selected':''} value="Suspended">🚫 Suspended</option>
                    </select>
                </td>
            </tr>`).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="7" class="table-state">Could not load users.</td></tr>';
    }
}

async function searchUserById() {
    const input = document.getElementById('userIdSearchInput');
    const result = document.getElementById('userSearchResult');
    const uid = (input.value || '').trim();
    if (!uid || isNaN(uid)) {
        result.style.display = 'block';
        result.innerHTML = '<p style="color:#f39c12;font-size:13px;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Please enter a valid User ID number.</p>';
        return;
    }
    result.style.display = 'block';
    result.innerHTML = '<p style="color:#a0b4cc;font-size:13px;"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Searching...</p>';
    try {
        const res  = await fetch(`/api/admin/user/${encodeURIComponent(uid)}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) {
            result.innerHTML = `<p style="color:#e63946;font-size:13px;"><i class="fas fa-times-circle" style="margin-right:6px;"></i>${data.message || 'User not found.'}</p>`;
            return;
        }
        const u  = data.user;
        const cs = data.complaints || [];
        const joinedDate = u.joined_at ? new Date(u.joined_at).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '—';
        const statusColors = { Active:'#2ecc71', Suspended:'#f39c12', Banned:'#e63946', Probation:'#f39c12', Pending:'#a0b4cc' };
        const sc = statusColors[u.status] || '#fff';
        const complaintsHtml = cs.length === 0
            ? '<p style="color:#6b7280;font-size:12px;margin:8px 0 0 0;">No complaints filed.</p>'
            : `<div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px;">` +
              cs.map(c => `
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:8px;padding:8px 12px;font-size:12px;">
                    <span style="color:#4f9eff;font-weight:700;">${c.complaint_id}</span>
                    <span style="color:#6b7280;margin:0 6px;">•</span>
                    <span style="color:#a0b4cc;">${c.type || '—'}</span>
                    <span style="color:#6b7280;margin:0 6px;">•</span>
                    <span style="color:${statusColors[c.status]||'#fff'};font-weight:600;">${c.status}</span>
                </div>`).join('') + '</div>';
        result.innerHTML = `
            <div style="border-radius:12px;border:1px solid #1e3a5f;background:#060d1a;overflow:hidden;">
                <div style="padding:14px 16px;background:linear-gradient(90deg,#0d1f3c,#060d1a);display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:#1e2d4a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-user" style="color:#4f9eff;font-size:20px;"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="color:#fff;font-weight:700;font-size:16px;">${u.name}</div>
                        <div style="color:#4f9eff;font-size:13px;">${u.email}</div>
                    </div>
                    <span style="background:${sc}22;color:${sc};border:1px solid ${sc}55;border-radius:8px;padding:4px 12px;font-size:12px;font-weight:700;">${u.status}</span>
                </div>
                <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;">
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">User ID</div>
                        <div style="color:#fff;font-family:monospace;font-size:14px;font-weight:700;">#${u.id}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Phone</div>
                        <div style="color:#a0b4cc;font-size:13px;">${u.phone || '—'}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">ID Type</div>
                        <div style="color:#a0b4cc;font-size:13px;text-transform:capitalize;">${(u.id_type||'—').replace('_',' ')}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">ID Number</div>
                        <div style="color:#a0b4cc;font-size:13px;">${u.id_number || '—'}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Joined</div>
                        <div style="color:#a0b4cc;font-size:13px;">${joinedDate}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Complaints Filed</div>
                        <div style="color:#fff;font-size:14px;font-weight:700;">${u.complaints_count}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Location</div>
                        <div style="color:#a0b4cc;font-size:13px;">${u.location || '—'}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">SOS Helped</div>
                        <div style="color:#a0b4cc;font-size:13px;">${u.sos_helped_count || 0} times</div>
                    </div>
                </div>
                <div style="padding:0 16px 14px 16px;">
                    <div style="color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Complaints</div>
                    ${complaintsHtml}
                </div>
            </div>`;
    } catch(e) {
        result.innerHTML = '<p style="color:#e63946;font-size:13px;"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>Network error. Please try again.</p>';
    }
}

function clearUserSearch() {
    document.getElementById('userIdSearchInput').value = '';
    const r = document.getElementById('userSearchResult');
    r.style.display = 'none';
    r.innerHTML = '';
}

async function updateUserStatus(id, selectEl) {
    const status = selectEl.value;
    try {
        const res  = await fetch('/api/admin/users/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        });
        const data = await res.json();
        if (data.success) {
            if (data.auto_banned) {
                showToast('<i class="fas fa-shield-alt"></i> System has automatically taken action on a user.');
                loadUsers();
            } else {
                showToast('<i class="fas fa-check-circle"></i> ' + data.message);
            }
        } else {
            throw new Error(data.message || 'Failed');
        }
    } catch(e) {
        showToast('<i class="fas fa-times-circle"></i> ' + (e.message || 'Failed to update'), true);
        loadUsers();
    }
}

function userStatusBadge(s) {
    const map = {
        'Active':    '<span class="status resolved">Active</span>',
        'Probation': '<span class="status review">Probation</span>',
        'Suspended': '<span class="status pending">Suspended</span>',
        'Banned':    '<span class="status" style="background:#ef444415;color:#f87171">Banned</span>'
    };
    return map[s] || `<span class="status">${s}</span>`;
}

// ── Dashboard ─────────────────────────────────────────────────
async function loadDashboard() {
    document.getElementById('dashboard-loading').textContent = 'Loading...';
    try {
        const res  = await fetch('/api/complaints');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        const stats = data.stats || {};
        document.getElementById('stat-total').textContent     = data.total || 0;
        document.getElementById('stat-submitted').textContent = stats['Submitted']    || 0;
        document.getElementById('stat-review').textContent    = stats['Under Review'] || 0;
        document.getElementById('stat-resolved').textContent  = stats['Resolved']     || 0;
        const recent = (data.complaints || []).slice(0, 5);
        const tbody  = document.getElementById('recent-tbody');
        if (recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="table-state"><i class="fas fa-inbox"></i>No complaints yet</td></tr>';
        } else {
            tbody.innerHTML = recent.map(c => `
                <tr>
                    <td><strong style="color:#4f9eff">${c.complaint_id}</strong></td>
                    <td>${formatType(c.type)}</td>
                    <td>${formatDate(c.submitted_at)}</td>
                    <td>${statusBadge(c.status)}</td>
                    <td><button class="btn-view" onclick="viewComplaint(${JSON.stringify(c).replace(/"/g,'&quot;')})">View</button></td>
                </tr>`).join('');
        }
        document.getElementById('dashboard-loading').textContent = '';
    } catch(err) {
        document.getElementById('dashboard-loading').textContent = '⚠️ Could not load — is XAMPP running?';
        document.getElementById('recent-tbody').innerHTML =
            '<tr><td colspan="5" class="table-state"><i class="fas fa-exclamation-triangle" style="color:#e63946"></i>Cannot connect to database.</td></tr>';
    }
}

// ── Complaints ────────────────────────────────────────────────
async function loadComplaints() {
    const status = document.getElementById('filterStatus').value;
    const type   = document.getElementById('filterType').value;
    const tbody  = document.getElementById('complaints-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';
    let url = '/api/complaints?';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    if (type)   url += 'type='   + encodeURIComponent(type);
    try {
        const res  = await fetch(url);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        const list = data.complaints || [];
        document.getElementById('complaints-count').textContent = list.length + ' complaint(s) found';
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-inbox"></i>No complaints found</td></tr>';
            return;
        }
        tbody.innerHTML = list.map(c => `
            <tr id="row-${c.complaint_id}">
                <td><strong style="color:#4f9eff">${c.complaint_id}</strong></td>
                <td>${formatType(c.type)}</td>
                <td style="font-size:12px;color:var(--text-secondary)">${c.location || '—'}</td>
                <td style="font-size:12px;color:var(--text-secondary)">${formatDate(c.submitted_at)}</td>
                <td style="text-align:center">${c.is_anonymous ? '<i class="fas fa-user-secret" style="color:#4f9eff" title="Anonymous"></i>' : '<i class="fas fa-user" style="color:var(--text-secondary)"></i>'}</td>
                <td>
                    <select class="status-select ${statusClass(c.status)}" onchange="updateStatus('${c.complaint_id}', this)">
                        <option ${c.status==='Submitted'                       ? 'selected':''}>Submitted</option>
                        <option ${c.status==='Under Review'                    ? 'selected':''}>Under Review</option>
                        <option ${c.status==='PI Notification Sent'           ? 'selected':''}>PI Notification Sent</option>
                        <option ${c.status==='PI Payment Pending'             ? 'selected':''}>PI Payment Pending</option>
                        <option ${c.status==='Private Investigator Assigned'  ? 'selected':''}>Private Investigator Assigned</option>
                        <option ${c.status==='Resolved'                       ? 'selected':''}>Resolved</option>
                        <option ${c.status==='Rejected'                       ? 'selected':''}>Rejected</option>
                    </select>
                </td>
                <td><button class="btn-view" onclick="viewComplaint(${JSON.stringify(c).replace(/"/g,'&quot;')})">
                    <i class="fas fa-eye"></i> View
                </button></td>
            </tr>`).join('');
    } catch(err) {
        tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-exclamation-triangle" style="color:#e63946"></i>Cannot connect to database.</td></tr>';
        document.getElementById('complaints-count').textContent = '';
    }
}

// ── PI Notification ───────────────────────────────────────────
let piPendingComplaintId = null;
let piPendingSelectEl    = null;
let piPendingOldStatus   = null;

async function updateStatus(complaint_id, selectEl) {
    const newStatus = selectEl.value;
    const oldStatus = selectEl.dataset.prev || selectEl.value;
    selectEl.dataset.prev = newStatus;
    selectEl.className = 'status-select ' + statusClass(newStatus);

    if (newStatus === 'Private Investigator Assigned') {
        piPendingComplaintId = complaint_id;
        piPendingSelectEl    = selectEl;
        piPendingOldStatus   = oldStatus;
        selectEl.value = oldStatus;
        selectEl.className = 'status-select ' + statusClass(oldStatus);
        document.getElementById('piNotifyModal').classList.add('active');
        return;
    }

    try {
        const res  = await fetch('/api/complaints/update-status', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ complaint_id, status: newStatus })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);
        showToast('<i class="fas fa-check-circle"></i> Status updated to ' + newStatus);
    } catch(err) {
        showToast('<i class="fas fa-times-circle"></i> Failed: ' + err.message, true);
        selectEl.value = oldStatus;
        selectEl.className = 'status-select ' + statusClass(oldStatus);
    }
}

function cancelPINotify() {
    document.getElementById('piNotifyModal').classList.remove('active');
    piPendingComplaintId = piPendingSelectEl = piPendingOldStatus = null;
}

async function confirmSendPINotify() {
    document.getElementById('piNotifyModal').classList.remove('active');

    const pending = JSON.parse(localStorage.getItem('sv-pi-notifications') || '[]');
    const updated = pending.filter(n => n.complaint_id !== piPendingComplaintId);
    updated.push({ complaint_id: piPendingComplaintId, timestamp: Date.now(), status: 'pending_payment' });
    localStorage.setItem('sv-pi-notifications', JSON.stringify(updated));

    try {
        await fetch('/api/pi_notification', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ complaint_id: piPendingComplaintId })
        });
    } catch(e) { /* backend optional */ }

    if (piPendingSelectEl) {
        piPendingSelectEl.value = 'PI Notification Sent';
        piPendingSelectEl.className = 'status-select s-under-review';
        piPendingSelectEl.dataset.prev = 'PI Notification Sent';
    }

    showToast('<i class="fas fa-paper-plane"></i> PI notification sent to user for ' + piPendingComplaintId);
    piPendingComplaintId = piPendingSelectEl = piPendingOldStatus = null;
    loadComplaints();
}

// ── Evidence Request ──────────────────────────────────────────
let currentViewComplaint = null;

function selectErMode(days) {
    document.getElementById('erSelectedDays').value = days;
    const btn7   = document.getElementById('erMode7Btn');
    const btn30  = document.getElementById('erMode30Btn');
    const info   = document.getElementById('erModeInfo');
    const sendBtn = document.getElementById('erSendBtn');
    if (days === 7) {
        btn7.style.border  = '2px solid #4f9eff'; btn7.style.background  = '#060c18';
        btn30.style.border = '2px solid #1e2d4a'; btn30.style.background = '#0a0f1e';
        info.innerHTML = '<i class="fas fa-info-circle"></i> User will receive a notification to submit more evidence. They can upload files instantly or skip for <strong>7 days</strong>. If they fail to submit within 7 days, you will be automatically notified.';
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send 7-Day Request';
        sendBtn.style.background = '';
    } else {
        btn30.style.border = '2px solid #e63946'; btn30.style.background = '#1a060a';
        btn7.style.border  = '2px solid #1e2d4a'; btn7.style.background  = '#0a0f1e';
        info.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e63946;"></i> <strong style="color:#e63946;">Fake complaint notice.</strong> User will receive an official 30-day notice to submit evidence proving their complaint is valid. If they fail to respond, further action will be taken against them.';
        sendBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Send 30-Day Notice';
        sendBtn.style.background = 'linear-gradient(135deg,#991b1b,#e63946)';
    }
}

function openEvidenceRequestModal() {
    if (!currentViewComplaint) return;
    document.getElementById('erModalComplaintId').textContent = currentViewComplaint.complaint_id;
    document.getElementById('erAdminNoteInput').value = '';
    selectErMode(7); // reset to 7-day default
    const btn = document.getElementById('erSendBtn');
    btn.disabled = false;
    document.getElementById('erRequestModal').classList.add('active');
}

function closeEvidenceRequestModal() {
    document.getElementById('erRequestModal').classList.remove('active');
}

async function sendEvidenceRequest() {
    const complaintId = currentViewComplaint?.complaint_id;
    if (!complaintId) return;
    const note = document.getElementById('erAdminNoteInput').value.trim();
    const days = parseInt(document.getElementById('erSelectedDays').value) || 7;
    const btn  = document.getElementById('erSendBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    try {
        const res  = await fetch('/api/evidence-request/create', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ complaint_id: complaintId, admin_note: note, days: days }),
        });
        const data = await res.json();
        closeEvidenceRequestModal();
        if (data.success) {
            showToast('<i class="fas fa-check-circle"></i> ' + days + '-day evidence request sent for ' + complaintId);
        } else {
            showToast('<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Failed to send request'), true);
        }
    } catch(e) {
        closeEvidenceRequestModal();
        showToast('<i class="fas fa-exclamation-circle"></i> Network error. Try again.', true);
    }
}

// ── Expired Evidence Notifications ───────────────────────────
async function loadExpiredEvidenceAlerts() {
    try {
        await fetch('/api/evidence-request/check-expired', { method: 'POST', credentials: 'include' });
        const res  = await fetch('/api/evidence-request/expired-list', { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.expired || data.expired.length === 0) return;
        const banner = document.getElementById('expiredEvidenceBanner');
        const list   = document.getElementById('expiredEvidenceList');
        if (!banner || !list) return;
        list.innerHTML = data.expired.map(r => {
            const deadline = r.deadline ? new Date(r.deadline).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '—';
            return `<div class="expired-ev-item">
                <div>
                    <div class="ev-cid"><i class="fas fa-folder-open" style="margin-right:6px;"></i>${r.complaint_id}</div>
                    <div class="ev-meta">Deadline was: ${deadline} · Evidence not submitted</div>
                </div>
                <button class="btn-send-pi-notif" onclick="triggerPIFromExpired('${r.complaint_id}')">
                    <i class="fas fa-user-secret"></i> Notify PI
                </button>
            </div>`;
        }).join('');
        banner.style.display = 'block';
    } catch(e) { /* silent */ }
}

function triggerPIFromExpired(complaintId) {
    piPendingComplaintId = complaintId;
    document.getElementById('piNotifyModal').classList.add('active');
}

// ── Complaint Detail Modal ────────────────────────────────────
function viewComplaint(c) {
    if (typeof c === 'string') c = JSON.parse(c);
    currentViewComplaint = c;
    document.getElementById('modalContent').innerHTML = `
        <div class="detail-row"><span class="detail-label">Complaint ID</span><span class="detail-value" style="color:#4f9eff;font-weight:700">${c.complaint_id}</span></div>
        <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${formatType(c.type)}</span></div>
        <div class="detail-row"><span class="detail-label">Incident Date</span><span class="detail-value">${c.incident_date ? formatDate(c.incident_date) : '—'}</span></div>
        <div class="detail-row"><span class="detail-label">Location</span><span class="detail-value">${c.location || '—'}</span></div>
        <div class="detail-row"><span class="detail-label">Anonymous</span><span class="detail-value">${c.is_anonymous ? 'Yes' : 'No'}</span></div>
        ${!c.is_anonymous ? `<div class="detail-row"><span class="detail-label">Submitted By</span><div id="sbCardDash" style="background:#060c18;border:1px solid #1e2d4a;border-radius:10px;padding:8px 12px;margin-top:4px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;"><span id="sbUidDash" style="font-family:monospace;color:#4f9eff;font-weight:700;font-size:13px;"><i class="fas fa-spinner fa-spin" style="font-size:11px;"></i> Loading...</span></div></div>` : ""}
        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">${statusBadge(c.status)}</span></div>
        <div class="detail-row"><span class="detail-label">Submitted At</span><span class="detail-value">${formatDate(c.submitted_at)}</span></div>
        <div style="display:flex;gap:10px;margin:14px 0;flex-wrap:wrap;">
            <div style="flex:1;min-width:160px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px 14px;">
                <div style="font-size:11px;color:#a0b4cc;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">⚖️ Legal Action</div>
                <div style="font-size:13px;">${c.legal_consent === 'yes' ? '<span style="color:#4f9eff;font-weight:700;">✅ Yes — Wants to proceed legally</span>' : c.legal_consent === 'no' ? '<span style="color:#e63946;font-weight:700;">❌ No — Does not want legal action</span>' : '<span style="color:#6b7280;">— Not answered</span>'}</div>
            </div>
            <div style="flex:1;min-width:160px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px 14px;">
                <div style="font-size:11px;color:#a0b4cc;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">📢 Publish Consent</div>
                <div style="font-size:13px;">${c.publish_consent === 'yes' ? '<span style="color:#2ecc71;font-weight:700;">✅ Yes — Agreed to publish</span>' : c.publish_consent === 'no' ? '<span style="color:#e63946;font-weight:700;">❌ No — Does not want case published</span>' : '<span style="color:#6b7280;">— Not answered</span>'}</div>
            </div>
        </div>
        <div class="detail-label" style="margin-top:15px;display:block">Description</div>
        <div class="desc-box">${c.description || '—'}</div>
        <div class="detail-label" style="margin-top:18px;display:block"><i class="fas fa-paperclip" style="color:#4f9eff"></i> Evidence Files</div>
        <div id="adminDashboardEvidenceList" style="margin-top:8px;"><p style="color:#4a5568;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading evidence...</p></div>`;
    document.getElementById('viewModal').classList.add('active');
    loadAdminDashboardEvidence(c.complaint_id);
    if (!c.is_anonymous) fetchSubmittedBy(c.complaint_id);
}

async function fetchSubmittedBy(complaint_id) {
    const card = document.getElementById('sbCardDash');
    const uid  = document.getElementById('sbUidDash');
    if (!card || !uid) return;
    try {
        const res  = await fetch(`/api/complaints/${encodeURIComponent(complaint_id)}`, { credentials: 'include' });
        const data = await res.json();
        let sb = data.submitted_by;
        let userId = sb?.user_id || data.complaint?.user_id || data.complaint?.anonymous_user_id;
        if (!userId) { card.innerHTML = '<span style="color:#4a5568;font-size:12px;">Not available</span>'; return; }
        const sc = {Active:'#2ecc71',Suspended:'#e63946',Banned:'#e63946',Probation:'#f39c12'}[sb?.status] || '#a0b4cc';
        card.innerHTML = `
            <span style="font-family:monospace;color:#4f9eff;font-weight:700;font-size:13px;">User ID: #${userId}</span>
            ${sb?.name  ? `<span style="display:flex;align-items:center;gap:5px;"><i class="fas fa-user" style="color:#a0b4cc;font-size:11px;"></i><span style="color:#e2e8f0;font-size:13px;font-weight:600;">${sb.name}</span></span>` : ''}
            ${sb?.email ? `<span style="display:flex;align-items:center;gap:5px;"><i class="fas fa-envelope" style="color:#a0b4cc;font-size:11px;"></i><span style="color:#a0b4cc;font-size:12px;">${sb.email}</span></span>` : ''}
            ${sb?.phone ? `<span style="display:flex;align-items:center;gap:5px;"><i class="fas fa-phone" style="color:#a0b4cc;font-size:11px;"></i><span style="color:#a0b4cc;font-size:12px;">${sb.phone}</span></span>` : ''}
            ${sb?.status ? `<span style="background:${sc}22;color:${sc};border:1px solid ${sc}55;border-radius:6px;padding:2px 9px;font-size:11px;font-weight:700;">${sb.status}</span>` : ''}
        `;
    } catch(e) {
        const uid = document.getElementById('sbUidDash');
        if (uid) uid.textContent = 'Could not load';
    }
}

async function loadAdminDashboardEvidence(complaint_id) {
    const box = document.getElementById('adminDashboardEvidenceList');
    if (!box) return;
    try {
        const res  = await fetch(`/api/get_complaints_evidence?complaint_id=${encodeURIComponent(complaint_id)}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.files || data.files.length === 0) {
            box.innerHTML = '<p style="color:#4a5568;font-size:13px;"><i class="fas fa-folder-open"></i> No evidence files uploaded yet.</p>';
            return;
        }
        function esc2(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        box.innerHTML = data.files.map(f => {
            const isPdf = f.file_name.toLowerCase().endsWith('.pdf');
            const icon  = isPdf ? 'fa-file-pdf' : 'fa-file-image';
            const url   = `/${f.file_path}`;
            const date  = new Date(f.uploaded_at).toLocaleString('en-GB');
            return `<div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;margin-bottom:8px;">
                <i class="fas ${icon}" style="color:#4f9eff;font-size:22px;flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc2(f.file_name)}</div>
                    <div style="color:#4a5568;font-size:11px;margin-top:2px;">Uploaded: ${date}</div>
                </div>
                <a href="${url}" target="_blank" style="background:#1e2d4a;color:#4f9eff;border:1px solid #4f9eff;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="${url}" download style="background:#1e2d4a;color:#2ecc71;border:1px solid #2ecc71;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
                    <i class="fas fa-download"></i>
                </a>
            </div>`;
        }).join('');
    } catch (e) {
        box.innerHTML = '<p style="color:#e63946;font-size:13px;"><i class="fas fa-exclamation-circle"></i> Could not load evidence.</p>';
    }
}

// ── Helpers ───────────────────────────────────────────────────
function formatType(t) {
    const map = { harassment:'Harassment', fare_overcharge:'Fare Overcharge', crime:'Crime', corruption:'Corruption', abuse:'Abuse', other:'Other' };
    return map[t] || t;
}

function formatDate(d) {
    if (!d) return '—';
    const fixed = d.replace(' ', 'T');
    const date  = new Date(fixed);
    if (isNaN(date)) return d;
    return date.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function statusBadge(s) {
    const map = {
        'Submitted':                        '<span class="status review">Submitted</span>',
        'Under Review':                     '<span class="status pending">Under Review</span>',
        'Private Investigator Assigned':    '<span class="status" style="background:#a855f715;color:#c084fc;border:1px solid #a855f740;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600">Private Investigator Assigned</span>',
        'Investigation':                    '<span class="status" style="background:#f9731615;color:#fb923c;border:1px solid #f9731640;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600">Investigation</span>',
        'Resolved':                         '<span class="status resolved">Resolved</span>',
        'Rejected':                         '<span class="status" style="background:#ef444415;color:#f87171">Rejected</span>'
    };
    return map[s] || `<span class="status">${s}</span>`;
}

function statusClass(s) {
    const map = {
        'Submitted':                       's-submitted',
        'Under Review':                    's-under-review',
        'Private Investigator Assigned':   's-officer-assigned',
        'PI Notification Sent':            's-under-review',
        'PI Payment Confirmed':            's-under-review',
        'Resolved':                        's-resolved',
        'Rejected':                        's-rejected'
    };
    return map[s] || '';
}

// ── SOS Evidence ──────────────────────────────────────────────
let currentEvidenceRecord = null;
const evidenceRecordsMap  = {};

async function loadSosAlerts() {
    const tbody   = document.getElementById('sos-alerts-tbody');
    const countEl = document.getElementById('sosAlertsCount');
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    try {
        const res  = await fetch('/api/sos/alerts', { credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        const list = data.alerts || [];
        if (countEl) countEl.textContent = list.length + ' alert(s)';
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="table-state"><i class="fas fa-inbox"></i> No SOS alerts yet.</td></tr>';
            return;
        }
        const fmtTime = (t) => {
            if (!t) return '—';
            const d = new Date(t);
            return d.toLocaleDateString('en-BD', { month: 'short', day: '2-digit' }) + ', ' +
                   d.toLocaleTimeString('en-BD', { hour: '2-digit', minute: '2-digit' });
        };
        tbody.innerHTML = list.map(a => {
            const sClass = a.status === 'active' ? 'review' : 'resolved';
            const sLabel = a.status === 'active' ? 'Active' : (a.status || 'Unknown');
            const user   = a.user ? a.user.name : 'Anonymous';
            return `<tr>
                <td><strong style="color:#e63946">SOS-${String(a.id).padStart(3,'0')}</strong></td>
                <td>${user}</td>
                <td>${a.location_text || '—'}</td>
                <td>${a.crime_type || 'Not specified'}</td>
                <td>${fmtTime(a.created_at)}</td>
                <td><span class="status ${sClass}">${sLabel}</span></td>
                <td>
                    <button onclick="openSosAlertDetail(${a.id})"
                        style="padding:6px 14px;border-radius:8px;border:none;background:linear-gradient(135deg,#1a3a6a,#2563eb);color:#fff;font-size:12px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="table-state" style="color:#e63946"><i class="fas fa-exclamation-triangle"></i> Could not load SOS alerts. ' + e.message + '</td></tr>';
    }
}

async function openSosAlertDetail(sosId) {
    const modal   = document.getElementById('sosAlertDetailModal');
    const content = document.getElementById('sosAlertDetailContent');
    modal.style.display = 'flex';
    content.innerHTML   = '<div style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p style="margin-top:12px;">Loading...</p></div>';
    try {
        const res  = await fetch(`/api/sos/alerts?sos_id=${sosId}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.sos) throw new Error(data.message || 'Not found');
        const s        = data.sos;
        const evidence = data.evidence || [];
        const fmtT     = (t) => t ? new Date(t).toLocaleString('en-BD') : '—';
        let evHtml = '';
        if (evidence.length > 0) {
            evHtml = evidence.map(e => {
                const isImg = e.file_type && e.file_type.startsWith('image');
                if (isImg) {
                    return `<div style="margin-bottom:10px;">
                        <img src="/${e.file_path}" style="max-width:100%;border-radius:10px;max-height:280px;object-fit:contain;" />
                        <div style="margin-top:6px;"><a href="/${e.file_path}" target="_blank" style="color:#4f9eff;font-size:12px;"><i class="fas fa-external-link-alt"></i> Open full size</a></div>
                    </div>`;
                } else {
                    return `<video src="/${e.file_path}" controls style="max-width:100%;border-radius:10px;margin-bottom:10px;"></video>`;
                }
            }).join('');
        } else {
            evHtml = '<p style="color:#4a5568;font-size:13px;font-style:italic;">No evidence uploaded yet.</p>';
        }
        content.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Victim</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:15px;">${s.victim_name || 'Anonymous'}</p>
                    ${s.victim_phone ? `<p style="margin:4px 0 0;"><a href="tel:${s.victim_phone}" style="color:#4f9eff;font-size:13px;"><i class="fas fa-phone"></i> ${s.victim_phone}</a></p>` : ''}
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Crime Type</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:15px;color:#e63946;">${s.crime_type || 'Not specified'}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;grid-column:1/-1;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Location</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;">${s.location_text || '—'}</p>
                    ${s.latitude && s.longitude ? `<a href="https://maps.google.com?q=${s.latitude},${s.longitude}" target="_blank" style="color:#4f9eff;font-size:12px;margin-top:4px;display:inline-block;"><i class="fas fa-map-marker-alt"></i> Open in Maps</a>` : ''}
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;grid-column:1/-1;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Description</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;line-height:1.6;">${s.description || 'No description provided.'}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Alert Time</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;">${fmtT(s.created_at)}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                    <span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Status</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:13px;color:${s.status==='active'?'#fbbf24':'#2ecc71'};">${s.status || '—'}</p>
                </div>
            </div>
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                <p style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 10px;"><i class="fas fa-paperclip" style="margin-right:4px;"></i>Submitted Evidence</p>
                ${evHtml}
            </div>`;
    } catch(e) {
        content.innerHTML = `<div style="text-align:center;padding:30px;color:#e63946;"><i class="fas fa-exclamation-triangle" style="font-size:24px;"></i><p style="margin-top:12px;">${e.message}</p></div>`;
    }
}

function closeSosAlertDetail() {
    document.getElementById('sosAlertDetailModal').style.display = 'none';
}

async function loadSosEvidenceBadge() {
    try {
        const res  = await fetch('/api/admin/sos-evidence-pending', { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;
        const count = (data.pending || []).filter(r => r.evidence_status === 'pending').length;
        const badge = document.getElementById('evidencePendingBadge');
        if (badge) { badge.textContent = count; badge.style.display = count > 0 ? 'inline' : 'none'; }
    } catch(e) {}
}

async function loadSosEvidence() {
    const grid    = document.getElementById('evidenceCardGrid');
    const countEl = document.getElementById('evidenceCount');
    const status  = document.getElementById('evidenceFilterStatus')?.value ?? '';
    grid.innerHTML = '<div class="table-state" style="grid-column:1/-1"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    try {
        const res  = await fetch('/api/admin/sos-evidence-pending', { credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        let list = data.pending || [];
        if (status) list = list.filter(r => r.evidence_status === status);
        const pendingCount = (data.pending || []).filter(r => r.evidence_status === 'pending').length;
        const badge = document.getElementById('evidencePendingBadge');
        if (badge) { badge.textContent = pendingCount; badge.style.display = pendingCount > 0 ? 'inline' : 'none'; }
        if (countEl) countEl.textContent = list.length + ' record(s)';
        if (list.length === 0) {
            grid.innerHTML = `<div class="table-state" style="grid-column:1/-1">
                <i class="fas fa-check-circle" style="color:#2ecc71"></i>
                <p style="margin-top:10px;">No evidence records for this filter.</p>
            </div>`;
            return;
        }
        grid.innerHTML = list.map(r => buildEvidenceCard(r)).join('');
    } catch(e) {
        grid.innerHTML = `<div class="table-state" style="grid-column:1/-1">
            <i class="fas fa-exclamation-triangle" style="color:#e63946"></i>
            <p style="margin-top:10px;">Could not load evidence. Check server connection.</p>
        </div>`;
    }
}

function buildEvidenceCard(r) {
    const statusColor = { pending:'#fbbf24', approved:'#2ecc71', rejected:'#e63946', none:'#4a5568' }[r.evidence_status] || '#fff';
    const statusLabel = { pending:'⏳ Pending Review', approved:'✅ Approved', rejected:'❌ Rejected', none:'No Evidence' }[r.evidence_status] || r.evidence_status;
    let previewHtml = '';
    if (r.evidence_path) {
        if (r.file_type === 'image') {
            previewHtml = `<img src="/${r.evidence_path}" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:12px;" />`;
        } else {
            previewHtml = `<div style="background:#0a0f1e;border-radius:10px;padding:20px;text-align:center;margin-bottom:12px;border:1px solid #1e2d4a;">
                <i class="fas fa-video" style="font-size:36px;color:#4f9eff;"></i>
                <p style="color:#a0b4cc;font-size:12px;margin-top:8px;">Video Evidence</p>
            </div>`;
        }
    } else {
        previewHtml = `<div style="background:#0a0f1e;border-radius:10px;padding:20px;text-align:center;margin-bottom:12px;border:1px dashed #1e2d4a;">
            <i class="fas fa-image" style="font-size:36px;color:#2a3a5a;"></i>
            <p style="color:#4a5568;font-size:12px;margin-top:8px;">No file uploaded</p>
        </div>`;
    }
    const sosInfo     = r.sos   ? `${r.sos.crime_type || 'SOS'} — ${r.sos.location_text || ''}` : 'SOS #' + r.sos_id;
    const userName    = r.responder ? r.responder.name : 'Unknown';
    const userRank    = r.responder ? r.responder.sos_helped_verified_count : 0;
    const submittedAt = r.evidence_submitted_at ? formatDate(r.evidence_submitted_at) : '—';
    const victimName  = r.sos && r.sos.user ? r.sos.user.name  : 'Anonymous';
    const victimPhone = r.sos && r.sos.user ? r.sos.user.phone : null;
    const showVerifyBtn = r.evidence_status === 'pending' && r.evidence_path;
    evidenceRecordsMap[r.id] = r;
    return `<div style="background:#111c33;border:1px solid #1e2d4a;border-radius:16px;padding:18px;display:flex;flex-direction:column;">
        ${previewHtml}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <span style="font-size:11px;color:${statusColor};font-weight:700;background:${statusColor}18;border:1px solid ${statusColor}30;border-radius:20px;padding:3px 10px;">${statusLabel}</span>
            <span style="font-size:11px;color:#4a5568;">${submittedAt}</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#1e2d4a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-shield-alt" style="color:#4f9eff;font-size:14px;"></i>
            </div>
            <div>
                <p style="margin:0;font-size:10px;color:#6a7fa0;text-transform:uppercase;letter-spacing:.4px;">Responder</p>
                <p style="margin:0;font-weight:700;font-size:14px;">${userName}</p>
                <p style="margin:0;color:#6a7fa0;font-size:12px;">${userRank} verified responses</p>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:10px;">
            <div style="width:30px;height:30px;border-radius:50%;background:#2a1a1a;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-user" style="color:#e63946;font-size:12px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="margin:0;font-size:10px;color:#6a7fa0;text-transform:uppercase;letter-spacing:.4px;">Victim (SOS requester)</p>
                <p style="margin:0;font-weight:700;font-size:13px;color:#fff;">${victimName}</p>
                ${victimPhone ? `<a href="tel:${victimPhone}" style="color:#4f9eff;font-size:11px;"><i class="fas fa-phone" style="margin-right:3px;"></i>${victimPhone}</a>` : ''}
            </div>
            <button onclick="openVictimEvidenceModal(${r.sos_id})"
                style="padding:6px 10px;border-radius:8px;border:none;background:linear-gradient(135deg,#1a3a2a,#16a34a);color:#fff;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                <i class="fas fa-eye"></i> View
            </button>
        </div>
        <p style="font-size:12px;color:#a0b4cc;margin:0 0 14px;line-height:1.5;">
            <i class="fas fa-exclamation-triangle" style="color:#e63946;margin-right:4px;"></i>${sosInfo}
        </p>
        ${r.admin_note ? `<p style="font-size:12px;color:#6a7fa0;margin:0 0 12px;font-style:italic;">Note: ${r.admin_note}</p>` : ''}
        ${showVerifyBtn ? `<button onclick="openSosEvidenceModalById(${r.id})"
            style="padding:11px;border-radius:10px;border:none;background:linear-gradient(135deg,#1a3a6a,#2563eb);color:#fff;font-size:13px;font-weight:700;cursor:pointer;margin-top:auto;">
            <i class="fas fa-search"></i> Review Evidence
        </button>` : ''}
    </div>`;
}

function openSosEvidenceModalById(id) {
    const record = evidenceRecordsMap[id];
    if (!record) return;
    openSosEvidenceModal(record);
}

function closeSosEvidenceModal() {
    document.getElementById('sosEvidenceVerifyModal').style.display = 'none';
    currentEvidenceRecord = null;
}

function openSosEvidenceModal(record) {
    currentEvidenceRecord = record;
    document.getElementById('evModalNote').value = '';
    const info = document.getElementById('evModalResponderInfo');
    const user = record.responder || {};
    const sos  = record.sos || {};
    info.innerHTML = `
        <div style="display:flex;gap:14px;flex-wrap:wrap;">
            <div><span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Responder</span>
                <p style="margin:3px 0 0;font-weight:700;color:#fff;">${user.name || '—'}</p></div>
            <div><span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Current Rank Count</span>
                <p style="margin:3px 0 0;font-weight:700;color:#2ecc71;">${user.sos_helped_verified_count || 0} verified</p></div>
            <div><span style="color:#6a7fa0;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">SOS Incident</span>
                <p style="margin:3px 0 0;color:#a0b4cc;font-size:12px;">${sos.crime_type || '—'} · ${sos.location_text || '—'}</p></div>
        </div>`;
    const preview = document.getElementById('evModalPreview');
    if (record.file_type === 'image') {
        preview.innerHTML = `<img src="/${record.evidence_path}" style="max-width:100%;max-height:260px;border-radius:12px;object-fit:contain;" />
            <p style="margin:8px 0 0;font-size:12px;color:#6a7fa0;">
                <a href="/${record.evidence_path}" target="_blank" style="color:#4f9eff;">Open full size <i class="fas fa-external-link-alt"></i></a>
            </p>`;
    } else {
        preview.innerHTML = `<video src="/${record.evidence_path}" controls style="max-width:100%;border-radius:12px;"></video>`;
    }
    document.getElementById('sosEvidenceVerifyModal').style.display = 'flex';
}

// ── Victim Evidence Modal ─────────────────────────────────────
async function openVictimEvidenceModal(sosId) {
    const modal   = document.getElementById('victimEvidenceModal');
    const content = document.getElementById('victimEvidenceContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p style="margin-top:12px;">Loading victim evidence...</p></div>';
    try {
        const res  = await fetch(`/api/sos/victim-evidence?sos_id=${sosId}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        const s        = data.sos;
        const evidence = data.evidence || [];
        const fmtT     = (t) => t ? new Date(t).toLocaleString('en-BD') : '—';
        let evHtml = '';
        if (evidence.length > 0) {
            evHtml = evidence.map(e => {
                const isImg = e.file_type && (e.file_type === 'image' || e.file_type.startsWith('image/'));
                if (isImg) {
                    return `<div style="margin-bottom:12px;">
                        <img src="/${e.file_path}" style="max-width:100%;border-radius:10px;max-height:260px;object-fit:contain;border:1px solid #1e2d4a;" />
                        <div style="margin-top:6px;"><a href="/${e.file_path}" target="_blank" style="color:#4f9eff;font-size:12px;"><i class="fas fa-external-link-alt"></i> Open full size</a></div>
                    </div>`;
                } else {
                    return `<video src="/${e.file_path}" controls style="max-width:100%;border-radius:10px;margin-bottom:10px;border:1px solid #1e2d4a;"></video>`;
                }
            }).join('');
        } else {
            evHtml = `<div style="text-align:center;padding:20px;color:#4a5568;border:1px dashed #1e2d4a;border-radius:10px;">
                <i class="fas fa-image" style="font-size:28px;margin-bottom:8px;display:block;"></i>
                <p style="margin:0;font-size:13px;">No evidence uploaded by victim yet.</p>
            </div>`;
        }
        content.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Victim</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:14px;">${s.victim_name || 'Anonymous'}</p>
                    ${s.victim_phone ? `<a href="tel:${s.victim_phone}" style="color:#4f9eff;font-size:12px;"><i class="fas fa-phone"></i> ${s.victim_phone}</a>` : ''}
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Crime Type</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:14px;color:#e63946;">${s.crime_type || 'Not specified'}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;grid-column:1/-1;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Location</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;">${s.location_text || '—'}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;grid-column:1/-1;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Description</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;line-height:1.6;">${s.description || 'No description.'}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">SOS Time</span>
                    <p style="margin:4px 0 0;font-size:13px;color:#a0b4cc;">${fmtT(s.created_at)}</p>
                </div>
                <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:12px;">
                    <span style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;">Status</span>
                    <p style="margin:4px 0 0;font-weight:700;font-size:13px;color:${s.status==='active'?'#fbbf24':'#2ecc71'};">${s.status || '—'}</p>
                </div>
            </div>
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:14px;">
                <p style="color:#6a7fa0;font-size:10px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px;"><i class="fas fa-paperclip" style="margin-right:4px;"></i>Victim Submitted Evidence</p>
                ${evHtml}
            </div>`;
    } catch(e) {
        content.innerHTML = `<div style="text-align:center;padding:30px;color:#e63946;"><i class="fas fa-exclamation-triangle" style="font-size:24px;"></i><p style="margin-top:12px;">${e.message}</p></div>`;
    }
}

function closeVictimEvidenceModal() {
    document.getElementById('victimEvidenceModal').style.display = 'none';
}

async function verifyEvidence(action) {
    if (!currentEvidenceRecord) return;
    const note       = document.getElementById('evModalNote').value.trim();
    const approveBtn = document.getElementById('evModalApproveBtn');
    const rejectBtn  = document.getElementById('evModalRejectBtn');
    approveBtn.disabled = rejectBtn.disabled = true;
    approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    try {
        const res  = await fetch('/api/admin/sos-evidence-verify', {
            method:  'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ responder_id: currentEvidenceRecord.id, action, note }),
        });
        const data = await res.json();
        if (data.success) {
            closeSosEvidenceModal();
            showToast(`${action === 'approve' ? '✅' : '❌'} ${data.message}`);
            loadSosEvidence();
        } else {
            throw new Error(data.message || 'Failed');
        }
    } catch(e) {
        showToast('<i class="fas fa-exclamation-circle"></i> Error: ' + e.message, true);
        approveBtn.disabled = rejectBtn.disabled = false;
        approveBtn.innerHTML = '<i class="fas fa-check-circle"></i> Approve & Update Rank';
    }
}

// ── Pending Accounts ──────────────────────────────────────────
let currentPendingUserId = null;

async function loadPendingAccounts() {
    const tbody = document.getElementById('pending-accounts-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="table-state"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    try {
        const res  = await fetch('/api/admin/pending-accounts');
        const data = await res.json();
        if (!data.success) throw new Error();
        const list  = data.users || [];
        const badge = document.getElementById('pendingAccountsBadge');
        if (list.length > 0) {
            badge.style.display = 'inline';
            badge.textContent   = list.length;
        } else {
            badge.style.display = 'none';
        }
        document.getElementById('pendingAccountsCount').textContent =
            list.length ? `${list.length} pending request${list.length > 1 ? 's' : ''}` : 'No pending requests';
        if (!list.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="table-state">
                <i class="fas fa-check-circle" style="color:#2ecc71"></i><br>No pending accounts — all clear!
            </td></tr>`;
            return;
        }
        tbody.innerHTML = list.map((u, i) => `
            <tr>
                <td>${i + 1}</td>
                <td><strong style="color:#fff">${escHtml(u.name)}</strong></td>
                <td style="color:#a0b4cc;font-size:13px">${escHtml(u.email)}</td>
                <td style="color:#a0b4cc;font-size:13px">${escHtml(u.phone || '—')}</td>
                <td><code style="color:#fbbf24;font-size:13px">${escHtml(u.id_number)}</code></td>
                <td style="color:#a0b4cc;font-size:12px">${u.joined_at ? new Date(u.joined_at).toLocaleDateString('en-BD') : '—'}</td>
                <td>
                    ${u.id_document_path
                        ? `<button onclick="viewBcDoc(${u.id}, '${escHtml(u.id_document_path)}', '${escHtml(u.name)}')"
                                   style="background:#2563eb15;border:1px solid #2563eb40;color:#4f9eff;border-radius:8px;padding:5px 12px;font-size:12px;cursor:pointer;">
                               <i class="fas fa-eye"></i> View
                           </button>`
                        : '<span style="color:#4a5568;font-size:12px;">No file</span>'
                    }
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button onclick="approveAccount(${u.id}, '${escHtml(u.name)}')"
                                style="background:#2ecc7115;border:1px solid #2ecc7140;color:#2ecc71;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="rejectAccount(${u.id}, '${escHtml(u.name)}')"
                                style="background:#e6394615;border:1px solid #e6394640;color:#e63946;border-radius:8px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" class="table-state">Could not load pending accounts.</td></tr>';
    }
}

function viewBcDoc(userId, docPath, userName) {
    currentPendingUserId = userId;
    const modal   = document.getElementById('bcDocModal');
    const content = document.getElementById('bcDocContent');
    const actions = document.getElementById('bcDocActions');
    const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(docPath);
    const isPdf   = /\.pdf$/i.test(docPath);
    const fullUrl = '/' + docPath;
    if (isImage) {
        content.innerHTML = `<img src="${fullUrl}" alt="Birth Certificate"
            style="max-width:100%;max-height:70vh;border-radius:10px;border:1px solid #1e2d4a;"
            onerror="this.outerHTML='<p style=color:#e63946>Image could not be loaded.</p>'"
        />`;
    } else if (isPdf) {
        content.innerHTML = `<embed src="${fullUrl}" type="application/pdf"
            style="width:100%;height:500px;border-radius:10px;" />`;
    } else {
        content.innerHTML = `<p style="color:#a0b4cc">
            <a href="${fullUrl}" target="_blank" style="color:#4f9eff;">
                <i class="fas fa-download"></i> Download Document
            </a>
        </p>`;
    }
    actions.innerHTML = `
        <button onclick="closeBcDocModal()"
                style="background:none;border:1px solid #1e2d4a;color:#a0b4cc;border-radius:8px;padding:8px 20px;cursor:pointer;">
            Close
        </button>
        <button onclick="approveAccount(${userId}, '${escHtml(userName)}'); closeBcDocModal();"
                style="background:#2ecc71;border:none;color:#fff;border-radius:8px;padding:8px 20px;font-weight:700;cursor:pointer;">
            <i class="fas fa-check"></i> Approve
        </button>
        <button onclick="rejectAccount(${userId}, '${escHtml(userName)}'); closeBcDocModal();"
                style="background:#e63946;border:none;color:#fff;border-radius:8px;padding:8px 20px;font-weight:700;cursor:pointer;">
            <i class="fas fa-times"></i> Reject
        </button>
    `;
    modal.style.display = 'flex';
}

function closeBcDocModal() {
    document.getElementById('bcDocModal').style.display = 'none';
    document.getElementById('bcDocContent').innerHTML = '';
    currentPendingUserId = null;
}

async function approveAccount(userId, userName) {
    if (!confirm(`Approve account for "${userName}"?\n\nThey will be able to login immediately.`)) return;
    try {
        const res  = await fetch('/api/admin/approve-account', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body:    JSON.stringify({ id: userId }),
        });
        const data = await res.json();
        if (data.success) {
            showToast(`✅ ${userName}'s account approved!`);
            loadPendingAccounts();
        } else {
            showToast('Approval failed: ' + (data.message || 'Unknown error'), true);
        }
    } catch (e) {
        showToast('Network error. Please try again.', true);
    }
}

async function rejectAccount(userId, userName) {
    if (!confirm(`Reject and delete account request for "${userName}"?\n\nThis cannot be undone.`)) return;
    try {
        const res  = await fetch('/api/admin/reject-account', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body:    JSON.stringify({ id: userId }),
        });
        const data = await res.json();
        if (data.success) {
            showToast(`🗑️ ${userName}'s request rejected.`);
            loadPendingAccounts();
        } else {
            showToast('Rejection failed: ' + (data.message || 'Unknown error'), true);
        }
    } catch (e) {
        showToast('Network error. Please try again.', true);
    }
}

async function checkPendingBadgeOnLoad() {
    try {
        const res  = await fetch('/api/admin/pending-accounts');
        const data = await res.json();
        if (data.success && data.count > 0) {
            const badge = document.getElementById('pendingAccountsBadge');
            badge.style.display = 'inline';
            badge.textContent   = data.count;
        }
    } catch(e) {}
}

// ── Misc ──────────────────────────────────────────────────────
function logout() {
    localStorage.removeItem('isAdminLoggedIn');
    window.location.href = '/admin/login';
}

function showToast(msg, isError) {
    const t = document.createElement('div');
    t.className = 'toast' + (isError ? ' error' : '');
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    showSection('dashboard');
    document.getElementById('nav-dashboard').classList.add('active');
    loadExpiredEvidenceAlerts();
    loadSosEvidenceBadge();
    checkPendingBadgeOnLoad();
    document.getElementById('erRequestModal').addEventListener('click', function(e) {
        if (e.target === this) closeEvidenceRequestModal();
    });
});

</script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection