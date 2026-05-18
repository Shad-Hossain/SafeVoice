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
        </ul>
        <div style="padding:14px 16px;border-top:1px solid #1e2d4a;margin-top:20px">
            <a href="{{ route('super-admin.login') }}" style="display:flex;align-items:center;gap:8px;color:#fbbf24;font-size:12px;font-weight:600;text-decoration:none;background:#fbbf2410;border:1px solid #fbbf2430;border-radius:8px;padding:9px 12px">
                <i class="fas fa-crown"></i> Super Admin Portal
            </a>
        </div>
    </aside>

    <main class="main-content" id="mainContent">

        <div id="view-dashboard">
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
            <div class="welcome-bar"><h1>SOS Alerts</h1><p>Emergency SOS signals</p></div>
            <div class="complaints-table">
                <table>
                    <thead><tr><th>Alert ID</th><th>User</th><th>Location</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td><strong style="color:#e63946">SOS-001</strong></td><td>Tania Begum</td><td>Mirpur, Dhaka</td><td>May 07, 09:14 AM</td><td><span class="status resolved">Resolved</span></td></tr>
                        <tr><td><strong style="color:#e63946">SOS-002</strong></td><td>Anonymous</td><td>Gulshan, Dhaka</td><td>May 08, 11:30 PM</td><td><span class="status review">Active</span></td></tr>
                        <tr><td><strong style="color:#e63946">SOS-003</strong></td><td>Arif Hossain</td><td>Uttara, Dhaka</td><td>May 09, 01:22 AM</td><td><span class="status resolved">Resolved</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div class="view-modal-overlay" id="viewModal">
    <div class="view-modal">
        <h3><i class="fas fa-file-alt"></i> Complaint Details</h3>
        <div id="modalContent"></div>
        <button class="modal-close-btn" onclick="document.getElementById('viewModal').classList.remove('active')">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
</div>


<!-- PI Notification Modal (Admin sends to user) -->
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

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
function showSection(section, preFilter) {
    ['dashboard','complaints','users','payments','sos'].forEach(s => {
        document.getElementById('view-' + s).style.display = 'none';
        document.getElementById('nav-' + s)?.classList.remove('active');
    });
    document.getElementById('view-' + section).style.display = 'block';
    document.getElementById('nav-' + section)?.classList.add('active');
    if (section === 'dashboard')  loadDashboard();
    if (section === 'complaints') {
        if (preFilter) document.getElementById('filterStatus').value = preFilter;
        loadComplaints();
    }
    if (section === 'users')    loadUsers();
    if (section === 'payments') loadPayments();
}

// ── PAYMENTS ─────────────────────────────────────────────────
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
                        <option ${u.status==='Banned'    ? 'selected':''} value="Banned">❌ Banned</option>
                    </select>
                </td>
            </tr>`).join('');
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="7" class="table-state">Could not load users.</td></tr>';
    }
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
        if (data.success) showToast('<i class="fas fa-check-circle"></i> User status → ' + status);
        else throw new Error();
    } catch(e) {
        showToast('<i class="fas fa-times-circle"></i> Failed to update', true);
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
                        <option ${c.status==='Submitted'        ? 'selected':''}>Submitted</option>
                        <option ${c.status==='Under Review'     ? 'selected':''}>Under Review</option>
                       <option ${c.status==='PI Notification Sent'          ? 'selected':''}>PI Notification Sent</option>
<option ${c.status==='PI Payment Pending'            ? 'selected':''}>PI Payment Pending</option>
<option ${c.status==='Private Investigator Assigned'  ? 'selected':''}>Private Investigator Assigned</option>
                        <option ${c.status==='Resolved'         ? 'selected':''}>Resolved</option>
                        <option ${c.status==='Rejected'         ? 'selected':''}>Rejected</option>
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

// PI notify state
let piPendingComplaintId = null;
let piPendingSelectEl    = null;
let piPendingOldStatus   = null;

async function updateStatus(complaint_id, selectEl) {
    const newStatus = selectEl.value;
    const oldStatus = selectEl.dataset.prev || selectEl.value;
    selectEl.dataset.prev = newStatus;
    selectEl.className = 'status-select ' + statusClass(newStatus);

    // Intercept PI assignment — show notification modal first
    if (newStatus === 'Private Investigator Assigned') {
        piPendingComplaintId = complaint_id;
        piPendingSelectEl    = selectEl;
        piPendingOldStatus   = oldStatus;
        // Revert select visually until user confirms
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

    // 1. Store in localStorage so user dashboard sees it immediately
    const pending = JSON.parse(localStorage.getItem('sv-pi-notifications') || '[]');
    // Remove any old notification for same complaint
    const updated = pending.filter(n => n.complaint_id !== piPendingComplaintId);
    updated.push({ complaint_id: piPendingComplaintId, timestamp: Date.now(), status: 'pending_payment' });
    localStorage.setItem('sv-pi-notifications', JSON.stringify(updated));

    // 2. Also update DB via API
    try {
        await fetch('/api/pi_notification', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ complaint_id: piPendingComplaintId })
        });
    } catch(e) { /* backend optional */ }

    // 3. Update status dropdown visually
    if (piPendingSelectEl) {
        piPendingSelectEl.value = 'PI Notification Sent';
        piPendingSelectEl.className = 'status-select s-under-review';
        piPendingSelectEl.dataset.prev = 'PI Notification Sent';
    }

    showToast('<i class="fas fa-paper-plane"></i> PI notification sent to user for ' + piPendingComplaintId);
    piPendingComplaintId = piPendingSelectEl = piPendingOldStatus = null;
    loadComplaints();
}

function viewComplaint(c) {
    if (typeof c === 'string') c = JSON.parse(c);
    document.getElementById('modalContent').innerHTML = `
        <div class="detail-row"><span class="detail-label">Complaint ID</span><span class="detail-value" style="color:#4f9eff;font-weight:700">${c.complaint_id}</span></div>
        <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${formatType(c.type)}</span></div>
        <div class="detail-row"><span class="detail-label">Incident Date</span><span class="detail-value">${c.incident_date ? formatDate(c.incident_date) : '—'}</span></div>
        <div class="detail-row"><span class="detail-label">Location</span><span class="detail-value">${c.location || '—'}</span></div>
        <div class="detail-row"><span class="detail-label">Anonymous</span><span class="detail-value">${c.is_anonymous ? 'Yes' : 'No'}</span></div>
        <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value">${statusBadge(c.status)}</span></div>
        <div class="detail-row"><span class="detail-label">Submitted At</span><span class="detail-value">${formatDate(c.submitted_at)}</span></div>
        <div class="detail-label" style="margin-top:15px;display:block">Description</div>
        <div class="desc-box">${c.description || '—'}</div>
        <div class="detail-label" style="margin-top:18px;display:block"><i class="fas fa-paperclip" style="color:#4f9eff"></i> Evidence Files</div>
        <div id="adminDashboardEvidenceList" style="margin-top:8px;"><p style="color:#4a5568;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading evidence...</p></div>`;
    document.getElementById('viewModal').classList.add('active');
    loadAdminDashboardEvidence(c.complaint_id);
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
            const url   = `/storage/${f.file_path}`;
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
        'Submitted':        '<span class="status review">Submitted</span>',
        'Under Review':     '<span class="status pending">Under Review</span>',
        'Private Investigator Assigned': '<span class="status" style="background:#a855f715;color:#c084fc;border:1px solid #a855f740;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600">Private Investigator Assigned</span>',
        'Investigation':    '<span class="status" style="background:#f9731615;color:#fb923c;border:1px solid #f9731640;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600">Investigation</span>',
        'Resolved':         '<span class="status resolved">Resolved</span>',
        'Rejected':         '<span class="status" style="background:#ef444415;color:#f87171">Rejected</span>'
    };
    return map[s] || `<span class="status">${s}</span>`;
}

function statusClass(s) {
    const map = {
        'Submitted':        's-submitted',
        'Under Review':     's-under-review',
        'Private Investigator Assigned': 's-officer-assigned',
        'PI Notification Sent':         's-under-review',
        'PI Payment Confirmed':         's-under-review',
        'Resolved':         's-resolved',
        'Rejected':         's-rejected'
    };
    return map[s] || '';
}

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

document.addEventListener('DOMContentLoaded', function() {
    showSection('dashboard');
    document.getElementById('nav-dashboard').classList.add('active');
});
</script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection