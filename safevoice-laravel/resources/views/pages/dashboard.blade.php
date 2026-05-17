@extends('layouts.app')
@section('title', 'Dashboard — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
<div class="dashboard-layout">
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li id="nav-overview" class="active"><a href="#" onclick="showSection('overview')"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="/complaint"><i class="fas fa-file-alt"></i> New Complaint</a></li>
            <li id="nav-mycomplaints"><a href="#" onclick="showSection('mycomplaints')"><i class="fas fa-list"></i> My Complaints</a></li>
            <li><a href="/track"><i class="fas fa-search"></i> Track Complaint</a></li>
            <li><a href="/legal"><i class="fas fa-gavel"></i> Legal Help</a></li>
            <li><a href="/leaderboard"><i class="fas fa-trophy"></i> Leaderboard</a></li>
            <li><a href="/sos"><i class="fas fa-exclamation-triangle" style="color:#e63946"></i> Emergency SOS</a></li>
            <li class="sidebar-divider"></li>
            <li><a href="#" onclick="openSettings()"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#" onclick="doLogout()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">

        <!-- ── OVERVIEW ── -->
        <div id="view-overview">
            <div class="welcome-bar">
                <div>
                    <h1>Welcome back, <span id="welcomeName">User</span>! 👋</h1>
                    <p>Here's what's happening with your complaints</p>
                </div>
                <a href="/complaint" class="btn-new-complaint"><i class="fas fa-plus"></i> New Complaint</a>
            </div>

            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="card-info"><h3 id="stat-total">—</h3><p>Total Complaints</p></div>
                </div>
                <div class="summary-card">
                    <div class="card-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="card-info"><h3 id="stat-resolved">—</h3><p>Resolved</p></div>
                </div>
                <div class="summary-card">
                    <div class="card-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="card-info"><h3 id="stat-pending">—</h3><p>Under Review</p></div>
                </div>
                <div class="summary-card">
                    <div class="card-icon blue"><i class="fas fa-paper-plane"></i></div>
                    <div class="card-info"><h3 id="stat-submitted">—</h3><p>Submitted</p></div>
                </div>
            </div>

            <div class="quick-actions" style="margin-bottom:30px">
                <a href="/complaint" class="action-card"><i class="fas fa-file-alt"></i><span>New Complaint</span></a>
                <a href="/sos" class="action-card sos"><i class="fas fa-exclamation-triangle"></i><span>Emergency SOS</span></a>
                <a href="/track" class="action-card"><i class="fas fa-search"></i><span>Track Complaint</span></a>
                <a href="/legal" class="action-card"><i class="fas fa-gavel"></i><span>Legal Help</span></a>
            </div>

            <div class="section-title">Recent Complaints</div>
            <div class="complaints-table">
                <table>
                    <thead>
                        <tr><th>Complaint ID</th><th>Type</th><th>Date</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="recent-tbody">
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary)"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── MY COMPLAINTS ── -->
        <div id="view-mycomplaints" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-list" style="font-size:22px;margin-right:10px"></i>My Complaints</h1>
                <a href="/complaint" class="btn-new-complaint"><i class="fas fa-plus"></i> New Complaint</a>
            </div>

            <div class="complaints-table">
                <table>
                    <thead>
                        <tr><th>Complaint ID</th><th>Type</th><th>Location</th><th>Date</th><th>Anonymous</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody id="all-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-secondary)"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Settings Modal -->
<div class="modal-overlay" id="settingsModal" style="display:none;">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-header">
            <h3><i class="fas fa-cog"></i> Settings</h3>
            <i class="fas fa-times modal-close" onclick="closeSettings()"></i>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:20px;">
                <h4 style="color:#4f9eff;margin-bottom:12px;">Profile</h4>
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="color:var(--text-secondary);font-size:13px;display:block;margin-bottom:6px;">Full Name</label>
                    <input type="text" id="settingName" style="width:100%;background:var(--input-bg,#0a0f1e);border:1px solid var(--border-color,#1e2d4a);border-radius:8px;padding:10px 14px;color:var(--text-primary,#fff);font-size:14px;outline:none;">
                </div>
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="color:var(--text-secondary);font-size:13px;display:block;margin-bottom:6px;">Email</label>
                    <input type="email" id="settingEmail" style="width:100%;background:var(--input-bg,#0a0f1e);border:1px solid var(--border-color,#1e2d4a);border-radius:8px;padding:10px 14px;color:var(--text-primary,#fff);font-size:14px;outline:none;">
                </div>
                <div class="form-group">
                    <label style="color:var(--text-secondary);font-size:13px;display:block;margin-bottom:6px;">Phone</label>
                    <input type="tel" id="settingPhone" style="width:100%;background:var(--input-bg,#0a0f1e);border:1px solid var(--border-color,#1e2d4a);border-radius:8px;padding:10px 14px;color:var(--text-primary,#fff);font-size:14px;outline:none;">
                </div>
            </div>
            <div>
                <h4 style="color:#4f9eff;margin-bottom:12px;">Notifications</h4>
                <label style="display:flex;align-items:center;gap:10px;color:var(--text-secondary);font-size:14px;margin-bottom:10px;cursor:pointer;">
                    <input type="checkbox" id="notifEmail" checked style="accent-color:#4f9eff;width:16px;height:16px;"> Email notifications
                </label>
                <label style="display:flex;align-items:center;gap:10px;color:var(--text-secondary);font-size:14px;cursor:pointer;">
                    <input type="checkbox" id="notifSos" checked style="accent-color:#4f9eff;width:16px;height:16px;"> SOS alerts
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-decline" onclick="closeSettings()">Cancel</button>
            <button class="btn-accept" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
// ── Auth Guard — server session verify ────────────────────────
// পেজ লুকিয়ে রাখো যতক্ষণ verify না হয় - habijabi access ঠেকাতে
document.documentElement.style.visibility = 'hidden';

(function() {
    const token  = localStorage.getItem('sv_token');
    const svUser = localStorage.getItem('sv_user');
    if (!token || !svUser) {
        window.location.href = '/login';
        return;
    }
    const user = JSON.parse(svUser);
    var navEl = document.getElementById('navUsername');
    if (navEl) navEl.textContent = user.name || 'User';
    var welcomeEl = document.getElementById('welcomeName');
    if (welcomeEl) welcomeEl.textContent = user.name || 'User';
    document.documentElement.style.visibility = 'visible';
})();
// ── Load user profile from localStorage ────────
function loadProfile() {
    // sv_user = data from login API; sv-profile = locally saved edits
    const svUser   = JSON.parse(localStorage.getItem('sv_user')   || '{}');
    const svEdit   = JSON.parse(localStorage.getItem('sv-profile') || '{}');
    const name     = svEdit.name  || svUser.name  || 'User';
    const email    = svEdit.email || svUser.email || '';
    const phone    = svEdit.phone || svUser.phone || '';

    const welN = document.getElementById('welcomeName'); if (welN) welN.textContent = name;
    const navU = document.getElementById('navUsername'); if (navU) navU.textContent = name;
    document.getElementById('settingName').value  = name;
    document.getElementById('settingEmail').value = email;
    document.getElementById('settingPhone').value = phone;
}

// ── Section navigation ─────────────────────────
function showSection(section) {
    document.getElementById('view-overview').style.display     = section === 'overview'     ? 'block' : 'none';
    document.getElementById('view-mycomplaints').style.display = section === 'mycomplaints' ? 'block' : 'none';
    document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
    const navEl = document.getElementById('nav-' + section);
    if (navEl) navEl.classList.add('active');
    if (section === 'mycomplaints') loadAllComplaints();
}

// ── Load complaints from DB ────────────────────
async function loadComplaints() {
    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id || '';
        const url    = userId ? `/api/my-complaints?user_id=${userId}` : '/api/my-complaints';
        const res    = await fetch(url, { credentials: 'include' });
        const data   = await res.json();
        if (data.redirect) { doLogout(); return; }
        if (!data.success) throw new Error(data.message || 'Failed');

        const complaints = data.complaints || [];

        // Stats
        const stats = { Total: complaints.length, Resolved: 0, 'Under Review': 0, Submitted: 0 };
        complaints.forEach(c => { if (stats[c.status] !== undefined) stats[c.status]++; });
        document.getElementById('stat-total').textContent     = stats.Total;
        document.getElementById('stat-resolved').textContent  = stats['Resolved'];
        document.getElementById('stat-pending').textContent   = stats['Under Review'];
        document.getElementById('stat-submitted').textContent = stats['Submitted'];

        const tbody = document.getElementById('recent-tbody');
        const recent = complaints.slice(0, 5);
        if (!recent.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary)">No complaints yet. <a href="/complaint" style="color:#4f9eff">Submit one!</a></td></tr>';
            return;
        }
        tbody.innerHTML = recent.map(c => `
            <tr>
                <td><strong style="color:#4f9eff">${c.complaint_id}</strong></td>
                <td>${formatType(c.type)}</td>
                <td style="color:var(--text-secondary);font-size:13px">${formatDate(c.submitted_at)}</td>
                <td>${statusBadge(c.status)}</td>
                <td style="white-space:nowrap;">
                    <a href="/track?id=${c.complaint_id}" class="btn-view"><i class="fas fa-eye"></i> View</a>
                    &nbsp;
                    <button class="btn-view" style="background:#1a3a2a;border-color:#2ecc71;color:#2ecc71;" onclick="openEvidenceModal('${c.complaint_id}')"><i class="fas fa-paperclip"></i> Evidence</button>
                    ${(c.status === 'PI Payment Pending' || c.status === 'PI Notification Sent') && (!c.payment_deadline || new Date(c.payment_deadline) > new Date()) ? `&nbsp;<button class="btn-view" style="background:#2d1a4a;border-color:#a855f7;color:#c084fc;" onclick="openPaymentForComplaint('${c.complaint_id}')"><i class="fas fa-credit-card"></i> Pay for PI</button>` : ''}
                </td>
            </tr>`).join('');
    } catch(e) {
        console.error('loadComplaints error:', e);
        document.getElementById('recent-tbody').innerHTML =
            '<tr><td colspan="5" style="text-align:center;padding:30px;color:#e63946"><i class="fas fa-exclamation-circle"></i> Could not load complaints. <a href="#" onclick="loadComplaints()" style="color:#4f9eff">Retry</a></td></tr>';
        // Reset stats to 0 on error
        ['stat-total','stat-resolved','stat-pending','stat-submitted'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '0';
        });
    }
}

async function loadAllComplaints() {
    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id || '';
        const url    = userId ? `/api/my-complaints?user_id=${userId}` : '/api/my-complaints';
        const res    = await fetch(url, { credentials: 'include' });
        const data = await res.json();
        if (data.redirect) { doLogout(); return; }
        if (!data.success) throw new Error();
        const tbody = document.getElementById('all-tbody');

        if (!data.complaints || !data.complaints.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-secondary)">No complaints yet. <a href="/complaint" style="color:#4f9eff">Submit one!</a></td></tr>';
            return;
        }
        tbody.innerHTML = data.complaints.map(c => `
            <tr>
                <td><strong style="color:#4f9eff">${c.complaint_id}</strong></td>
                <td>${formatType(c.type)}</td>
                <td style="font-size:12px;color:var(--text-secondary)">${c.location || '—'}</td>
                <td style="font-size:12px;color:var(--text-secondary)">${formatDate(c.submitted_at)}</td>
                <td style="text-align:center">${c.is_anonymous == 1 ? '<i class="fas fa-user-secret" style="color:#4f9eff" title="Anonymous"></i>' : '<i class="fas fa-user" style="color:var(--text-secondary)"></i>'}</td>
                <td>${statusBadge(c.status)}</td>
                <td style="white-space:nowrap;">
                    <a href="/track?id=${c.complaint_id}" class="btn-view"><i class="fas fa-eye"></i> Track</a>
                    &nbsp;
                    <button class="btn-view" style="background:#1a3a2a;border-color:#2ecc71;color:#2ecc71;" onclick="openEvidenceModal('${c.complaint_id}')"><i class="fas fa-paperclip"></i> Evidence</button>
                    ${(c.status === 'PI Payment Pending' || c.status === 'PI Notification Sent') && (!c.payment_deadline || new Date(c.payment_deadline) > new Date()) ? `&nbsp;<button class="btn-view" style="background:#2d1a4a;border-color:#a855f7;color:#c084fc;" onclick="openPaymentForComplaint('${c.complaint_id}')"><i class="fas fa-credit-card"></i> Pay for PI</button>` : ''}
                </td>
            </tr>`).join('');
    } catch(e) {
        console.error('loadAllComplaints error:', e);
        document.getElementById('all-tbody').innerHTML =
            '<tr><td colspan="7" style="text-align:center;padding:30px;color:#e63946"><i class="fas fa-exclamation-circle"></i> Could not load. <a href="#" onclick="loadAllComplaints()" style="color:#4f9eff">Retry</a></td></tr>';
    }
}

// ── Settings ───────────────────────────────────
function openSettings() {
    loadProfile();
    document.getElementById('settingsModal').style.display = 'flex';
}
function closeSettings() {
    document.getElementById('settingsModal').style.display = 'none';
}
function saveSettings() {
    const profile = {
        name:  document.getElementById('settingName').value.trim()  || 'User',
        email: document.getElementById('settingEmail').value.trim(),
        phone: document.getElementById('settingPhone').value.trim()
    };
    localStorage.setItem('sv-profile', JSON.stringify(profile));
    // Also update sv_user so the name persists across sessions
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    svUser.name  = profile.name;
    svUser.email = profile.email;
    svUser.phone = profile.phone;
    localStorage.setItem('sv_user', JSON.stringify(svUser));
    closeSettings();
    loadProfile();
    showToast('<i class="fas fa-check-circle"></i> Settings saved!');
}

function showToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:30px;right:30px;background:#2ecc71;color:#fff;padding:14px 22px;border-radius:10px;font-size:14px;font-weight:600;z-index:99999;display:flex;align-items:center;gap:10px;box-shadow:0 4px 20px rgba(0,0,0,.3)';
    t.innerHTML = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// ── Helpers ────────────────────────────────────
function formatType(t) {
    const map = { harassment:'Harassment', fare_overcharge:'Fare Overcharge', crime:'Crime', corruption:'Corruption', abuse:'Abuse', other:'Other' };
    return map[t] || t;
}
function formatDate(d) {
    if (!d) return '—';
    const fixed = d.replace(' ', 'T');
    const date  = new Date(fixed);
    if (isNaN(date)) return d;
    return date.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
}
function statusBadge(s) {
    const map = { 'Submitted':'<span class="status review">Submitted</span>', 'Under Review':'<span class="status pending">Under Review</span>', 'Resolved':'<span class="status resolved">Resolved</span>', 'Rejected':'<span class="status" style="background:#ef444415;color:#f87171">Rejected</span>', 'PI Notification Sent':'<span class="status" style="background:#fbbf2415;color:#fbbf24;border:1px solid #fbbf2440;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap;">PI Review Pending</span>', 'PI Payment Pending':'<span class="status" style="background:#e2146c15;color:#e2146c;border:1px solid #e2146c40;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap;">💳 Payment Pending</span>', 'Private Investigator Assigned':'<span class="status" style="background:#a855f715;color:#c084fc;border:1px solid #a855f740;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap;">PI Assigned</span>' };
    return map[s] || `<span class="status">${s}</span>`;
}

// ── Init ───────────────────────────────────────

// ── Logout ─────────────────────────────────────
async function doLogout() {
    try {
        await fetch('/api/logout', {
            method: 'POST',
            credentials: 'include',
            cache: 'no-store'
        });
    } catch(e) {
        console.error(e);
    }

    localStorage.removeItem('sv_user');
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userName');

    sessionStorage.clear();

    // Force full reload
    window.location.replace('/login');
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfile();
    loadComplaints();
    checkPINotifications();
});

// ── "Pay Now" button — decline করার পরেও deadline এর মধ্যে pay করা যাবে ──
function openPaymentForComplaint(complaintId) {
    currentPIComplaintId = complaintId;
    document.getElementById('bkashComplaintRef').textContent = complaintId;
    document.getElementById('bkashModal').style.display = 'flex';
}

// ── PI Notification System ─────────────────────
let currentPIComplaintId = null;

async function checkPINotifications() {
    // Dismissed list — user যেগুলো decline করেছে সেগুলো আর দেখাব না
    const dismissed = JSON.parse(localStorage.getItem('sv-pi-dismissed') || '[]');

    // ১. localStorage check (same device / demo mode)
    const lsNotifs  = JSON.parse(localStorage.getItem('sv-pi-notifications') || '[]');
    const lsPending = lsNotifs.find(n =>
        n.status === 'pending_payment' && !dismissed.includes(n.complaint_id)
    );
    if (lsPending) {
        currentPIComplaintId = lsPending.complaint_id;
        document.getElementById('piNotifyComplaintId').textContent = lsPending.complaint_id;
        document.getElementById('piUserModal').style.display = 'flex';
        return;
    }

    // ২. DB poll — works across devices / after logout-login
    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id || '';
        const url    = userId ? `/api/my-complaints?user_id=${userId}` : '/api/my-complaints';
        const res    = await fetch(url, { credentials: 'include' });
        const data   = await res.json();
        const complaints = data.complaints || [];

        for (const c of complaints) {
            // Status 'PI Notification Sent' + deadline এখনো পার হয়নি + dismissed নয়
            if (
                c.status === 'PI Notification Sent' &&
                !dismissed.includes(c.complaint_id) &&
                (!c.payment_deadline || new Date(c.payment_deadline) > new Date())
            ) {
                currentPIComplaintId = c.complaint_id;
                document.getElementById('piNotifyComplaintId').textContent = c.complaint_id;
                // Deadline বাকি কত দিন দেখাও
                if (c.payment_deadline) {
                    const days = Math.ceil((new Date(c.payment_deadline) - new Date()) / 86400000);
                    const deadlineEl = document.getElementById('piDeadlineNote');
                    if (deadlineEl) deadlineEl.textContent = `Deadline: ${days} day(s) remaining`;
                }
                document.getElementById('piUserModal').style.display = 'flex';
                return;
            }
        }
    } catch(e) { /* backend offline — demo mode */ }
}

async function rejectPINotify() {
    const cid = currentPIComplaintId;

    // ১. DB-তে dismissed হিসেবে mark করো
    let deadline = null;
    try {
        const res = await fetch('/api/pi/reject-payment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:    JSON.stringify({ complaint_id: cid })
        });
        const data = await res.json();
        deadline = data.deadline || null;
    } catch(e) { /* offline */ }

    // ২. localStorage notification queue থেকে সরাও
    const notifications = JSON.parse(localStorage.getItem('sv-pi-notifications') || '[]');
    localStorage.setItem('sv-pi-notifications',
        JSON.stringify(notifications.filter(n => n.complaint_id !== cid)));

    // ৩. Dismissed list এ রাখো
    const dismissed = JSON.parse(localStorage.getItem('sv-pi-dismissed') || '[]');
    if (!dismissed.includes(cid)) dismissed.push(cid);
    localStorage.setItem('sv-pi-dismissed', JSON.stringify(dismissed));

    document.getElementById('piUserModal').style.display = 'none';

    // Deadline info সহ toast দেখাও
    let deadlineMsg = '';
    if (deadline) {
        const d = new Date(deadline);
        const days = Math.ceil((d - new Date()) / 86400000);
        if (days > 0) deadlineMsg = ` You have <strong>${days} day(s)</strong> to pay before the deadline.`;
    }
    showToast('<i class="fas fa-times-circle"></i> PI request declined.' + deadlineMsg);
    currentPIComplaintId = null;

    // Complaint list reload — "Pay for PI" button এখন দেখা যাবে
    await loadComplaints();
    if (document.getElementById('view-mycomplaints').style.display !== 'none') {
        await loadAllComplaints();
    }
}

function acceptPINotify() {
    document.getElementById('piUserModal').style.display = 'none';
    document.getElementById('bkashComplaintRef').textContent = currentPIComplaintId;
    document.getElementById('bkashModal').style.display = 'flex';
}

function closeBkashModal() {
    document.getElementById('bkashModal').style.display = 'none';
    // Re-show PI modal
    document.getElementById('piUserModal').style.display = 'flex';
}

let selectedPaymentMethod = 'bkash';
function selectBkashMethod(m) {
    selectedPaymentMethod = m;
    document.querySelectorAll('#bkashModal span[onclick]').forEach(el => {
        el.style.opacity = el.getAttribute('onclick').includes(m) ? '1' : '0.5';
    });
}

async function processBkashPayment() {
    const number = document.getElementById('bkashNumber').value.trim();
    const pin    = document.getElementById('bkashPin').value.trim();
    if (number.length < 11) { alert('Please enter a valid 11-digit mobile number.'); return; }
    if (pin.length < 4)     { alert('Please enter your PIN.'); return; }

    const txnId  = 'TXN' + Date.now().toString().slice(-8).toUpperCase();
    const method = selectedPaymentMethod || 'bkash';
    const btn    = document.querySelector('#bkashModal button[onclick="processBkashPayment()"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled  = true;

    try {
        // ✅ সঠিক route: /api/pi/payment — auto-confirm, auto-assign PI, email both
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const res  = await fetch('/api/pi/payment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:    JSON.stringify({
                complaint_id:   currentPIComplaintId,
                txn_id:         txnId,
                payment_method: method,
                sender_number:  number,
                user_id:        svUser.id || svUser.user_id || ''
            })
        });
        const data = await res.json();
        if (!data.success) {
            btn.innerHTML = '<i class="fas fa-lock"></i> Pay ৳1,000';
            btn.disabled  = false;
            alert(data.message || 'Payment failed. Please try again.');
            return;
        }
    } catch(e) {
        btn.innerHTML = '<i class="fas fa-lock"></i> Pay ৳1,000';
        btn.disabled  = false;
        alert('Could not connect. Please check your connection and try again.');
        return;
    }

    // Clear notification from queue (localStorage + dismissed list)
    const notifs = JSON.parse(localStorage.getItem('sv-pi-notifications') || '[]');
    localStorage.setItem('sv-pi-notifications',
        JSON.stringify(notifs.filter(n => n.complaint_id !== currentPIComplaintId)));

    // Dismissed list থেকেও সরাও (payment হয়ে গেছে, আর দরকার নেই)
    const dismissed = JSON.parse(localStorage.getItem('sv-pi-dismissed') || '[]');
    localStorage.setItem('sv-pi-dismissed',
        JSON.stringify(dismissed.filter(id => id !== currentPIComplaintId)));

    document.getElementById('txnId').textContent                 = txnId;
    document.getElementById('bkashModal').style.display          = 'none';
    document.getElementById('paymentSuccessModal').style.display = 'flex';

    btn.innerHTML = '<i class="fas fa-lock"></i> Pay ৳1,000';
    btn.disabled  = false;
    currentPIComplaintId = null;
}

function closePaymentSuccess() {
    document.getElementById('paymentSuccessModal').style.display = 'none';
    loadComplaints();
}
</script>

<!-- PI Notification Modal for User -->
<div id="piUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:99999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#111c33;border:1px solid #a855f740;border-radius:20px;padding:32px;max-width:460px;width:100%;">
        <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#a855f720,#c084fc20);border:2px solid #a855f750;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <i class="fas fa-user-secret" style="font-size:28px;color:#c084fc;"></i>
        </div>
        <h2 style="text-align:center;color:#fff;font-size:18px;margin-bottom:10px;">Private Investigator Required</h2>
        <p style="text-align:center;color:#a0b4cc;font-size:14px;line-height:1.6;margin-bottom:6px;">
            After reviewing your complaint <strong id="piNotifyComplaintId" style="color:#4f9eff;"></strong>, our admin team has determined that a <strong style="color:#c084fc;">Private Investigator</strong> is needed for further action.
        </p>
        <p style="text-align:center;color:#a0b4cc;font-size:13px;margin-bottom:8px;">Would you like to proceed? A one-time service fee applies.</p>
        <p id="piDeadlineNote" style="text-align:center;color:#fbbf24;font-size:12px;margin-bottom:20px;font-weight:600;"></p>
        <div style="background:#a855f710;border:1px solid #a855f740;border-radius:12px;padding:16px 20px;text-align:center;margin-bottom:22px;">
            <div style="font-size:12px;color:#a0b4cc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">Service Fee</div>
            <div style="font-size:30px;font-weight:800;color:#c084fc;">৳1,000</div>
            <div style="font-size:12px;color:#a0b4cc;margin-top:4px;">One-time payment via bKash</div>
        </div>
        <div style="display:flex;gap:12px;">
            <button onclick="rejectPINotify()" style="flex:1;background:transparent;border:1px solid #ef444440;color:#f87171;border-radius:10px;padding:13px;font-size:14px;font-weight:600;cursor:pointer;">
                <i class="fas fa-times"></i> Decline
            </button>
            <button onclick="acceptPINotify()" style="flex:2;background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-check"></i> Accept &amp; Pay ৳1,000
            </button>
        </div>
    </div>
</div>

<!-- bKash Payment Modal -->
<div id="bkashModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:999999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#111c33;border:1px solid #e2146c40;border-radius:20px;padding:32px;max-width:420px;width:100%;">
        <!-- bKash header -->
        <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:22px;">
            <div style="width:48px;height:48px;border-radius:12px;background:#e2146c;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-mobile-alt" style="color:#fff;font-size:22px;"></i>
            </div>
            <div>
                <div style="color:#e2146c;font-size:20px;font-weight:800;letter-spacing:1px;">bKash</div>
                <div style="color:#a0b4cc;font-size:12px;">Secure Mobile Payment</div>
            </div>
        </div>

        <div style="background:#e2146c10;border:1px solid #e2146c30;border-radius:12px;padding:14px 18px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                <span style="color:#a0b4cc;font-size:13px;">Complaint ID</span>
                <span id="bkashComplaintRef" style="color:#4f9eff;font-weight:700;font-size:13px;"></span>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#a0b4cc;font-size:13px;">Amount</span>
                <span style="color:#e2146c;font-weight:800;font-size:16px;">৳1,000</span>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="color:#a0b4cc;font-size:13px;display:block;margin-bottom:6px;">bKash Account Number</label>
            <input type="tel" id="bkashNumber" placeholder="01XXXXXXXXX" maxlength="11"
                style="width:100%;background:#0a0f1e;border:1px solid #e2146c40;border-radius:8px;padding:11px 14px;color:#fff;font-size:15px;outline:none;letter-spacing:1px;"
                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>
        <div style="margin-bottom:20px;">
            <label style="color:#a0b4cc;font-size:13px;display:block;margin-bottom:6px;">bKash PIN</label>
            <input type="password" id="bkashPin" placeholder="••••••" maxlength="6"
                style="width:100%;background:#0a0f1e;border:1px solid #e2146c40;border-radius:8px;padding:11px 14px;color:#fff;font-size:18px;letter-spacing:4px;outline:none;"
                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
        </div>

        <!-- Payment method pills -->
        <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
            <span style="background:#e2146c15;border:1px solid #e2146c40;color:#e2146c;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;cursor:pointer;" onclick="selectBkashMethod('bkash')">bKash</span>
            <span style="background:#0a3a7915;border:1px solid #0a3a7940;color:#4f9eff;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;cursor:pointer;" onclick="selectBkashMethod('nagad')">Nagad</span>
            <span style="background:#1a6f4a15;border:1px solid #1a6f4a40;color:#2ecc71;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;cursor:pointer;" onclick="selectBkashMethod('rocket')">Rocket</span>
        </div>

        <div style="display:flex;gap:10px;">
            <button onclick="closeBkashModal()" style="background:transparent;border:1px solid #1e2d4a;color:#a0b4cc;border-radius:10px;padding:12px 18px;font-size:14px;cursor:pointer;">Cancel</button>
            <button onclick="processBkashPayment()" style="flex:1;background:linear-gradient(135deg,#b5006a,#e2146c);color:#fff;border:none;border-radius:10px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-lock"></i> Pay ৳1,000
            </button>
        </div>
        <p style="text-align:center;color:#a0b4cc;font-size:11px;margin-top:14px;"><i class="fas fa-shield-alt"></i> Secured by SSL encryption</p>
    </div>
</div>

<!-- Payment Success Modal -->
<div id="paymentSuccessModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#111c33;border:1px solid #2ecc7140;border-radius:20px;padding:36px;max-width:400px;width:100%;text-align:center;">
        <div style="width:72px;height:72px;border-radius:50%;background:#2ecc7120;border:2px solid #2ecc7150;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <i class="fas fa-check-circle" style="font-size:32px;color:#2ecc71;"></i>
        </div>
        <h2 style="color:#fff;font-size:20px;margin-bottom:8px;">Payment Successful!</h2>
        <p style="color:#a0b4cc;font-size:14px;margin-bottom:20px;">Your payment of <strong style="color:#2ecc71;">৳1,000</strong> has been received. A Private Investigator will be assigned to your case shortly.</p>
        <div style="background:#2ecc7110;border:1px solid #2ecc7130;border-radius:10px;padding:14px;margin-bottom:22px;">
            <div style="font-size:12px;color:#a0b4cc;margin-bottom:4px;">Transaction ID</div>
            <div id="txnId" style="font-size:15px;font-weight:700;color:#2ecc71;letter-spacing:1px;"></div>
        </div>
        <button onclick="closePaymentSuccess()" style="background:linear-gradient(135deg,#1a6f4a,#2ecc71);color:#fff;border:none;border-radius:10px;padding:13px 40px;font-size:15px;font-weight:700;cursor:pointer;">Done</button>
    </div>
</div>
<!-- EVIDENCE MODAL -->
<div id="evidenceModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#0d1526;border:1px solid #1e2d4a;border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;">
        <div style="padding:20px 24px;border-bottom:1px solid #1e2d4a;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#0d1526;z-index:1;">
            <div>
                <h3 style="margin:0;color:#fff;font-size:17px;"><i class="fas fa-paperclip" style="color:#4f9eff;margin-right:8px;"></i>Evidence Files</h3>
                <p id="evModalId" style="margin:4px 0 0;color:#4a5568;font-size:12px;"></p>
            </div>
            <i class="fas fa-times" onclick="closeEvidenceModal()" style="color:#a0b4cc;font-size:20px;cursor:pointer;padding:4px;"></i>
        </div>

        <!-- Existing evidence list -->
        <div style="padding:20px 24px;">
            <p style="font-size:13px;font-weight:700;color:#a0b4cc;margin:0 0 12px;text-transform:uppercase;letter-spacing:.5px;">Uploaded Evidence</p>
            <div id="evExistingList">
                <p style="color:#4a5568;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
        </div>

        <!-- Upload more -->
        <div style="padding:0 24px 24px;">
            <p style="font-size:13px;font-weight:700;color:#a0b4cc;margin:0 0 12px;text-transform:uppercase;letter-spacing:.5px;">Add More Evidence</p>
            <div id="evUploadBox" style="background:#0a0f1e;border:2px dashed #1e2d4a;border-radius:12px;padding:24px;text-align:center;cursor:pointer;transition:border .2s;" onmouseover="this.style.borderColor='#4f9eff'" onmouseout="this.style.borderColor='#1e2d4a'">
                <i class="fas fa-cloud-upload-alt" style="font-size:30px;color:#4f9eff;margin-bottom:8px;display:block;"></i>
                <p style="color:#fff;font-size:14px;margin:0 0 4px;">Click to select files</p>
                <span style="color:#4a5568;font-size:12px;">JPG, PNG, PDF — max 10MB each</span>
            </div>
            <input type="file" id="evFileInput" accept="image/jpeg,image/png,image/gif,image/webp,.pdf" multiple style="position:absolute;left:-9999px;" />
            <div id="evFileList" style="margin-top:10px;display:none;">
                <ul id="evFileNames" style="list-style:none;padding:0;margin:0;font-size:13px;color:#a0b4cc;"></ul>
            </div>
            <button id="evUploadBtn" onclick="uploadMoreEvidence()" style="margin-top:16px;width:100%;background:linear-gradient(135deg,#1a6f4a,#2ecc71);color:#fff;border:none;border-radius:10px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fas fa-upload"></i> Upload Evidence
            </button>
            <div id="evUploadMsg" style="margin-top:10px;font-size:13px;text-align:center;display:none;"></div>
        </div>
    </div>
</div>

<script>
let evComplaintId = '';

function openEvidenceModal(complaint_id) {
    evComplaintId = complaint_id;
    document.getElementById('evModalId').textContent = complaint_id;
    document.getElementById('evidenceModal').style.display = 'flex';
    document.getElementById('evFileList').style.display = 'none';
    document.getElementById('evFileNames').innerHTML = '';
    document.getElementById('evUploadMsg').style.display = 'none';
    document.getElementById('evFileInput').value = '';
    document.getElementById('evUploadBox').querySelector('p').textContent = 'Click to select files';
    loadExistingEvidence(complaint_id);
}

function closeEvidenceModal() {
    document.getElementById('evidenceModal').style.display = 'none';
    evComplaintId = '';
}

document.getElementById('evidenceModal').addEventListener('click', function(e) {
    if (e.target === this) closeEvidenceModal();
});

// Wire upload box
document.addEventListener('DOMContentLoaded', function() {
    const box   = document.getElementById('evUploadBox');
    const input = document.getElementById('evFileInput');
    if (box && input) {
        box.addEventListener('click', function() { input.click(); });
        input.addEventListener('change', function() {
            const files = this.files;
            const list  = document.getElementById('evFileList');
            const names = document.getElementById('evFileNames');
            if (!files.length) { list.style.display='none'; return; }
            names.innerHTML = '';
            Array.from(files).forEach(f => {
                const li = document.createElement('li');
                li.style.cssText = 'padding:4px 0;display:flex;align-items:center;gap:8px;';
                const icon = f.name.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file-image';
                li.innerHTML = `<i class="fas ${icon}" style="color:#4f9eff;width:16px;"></i> ${escDash(f.name)} <span style="color:#4a5568;font-size:12px;">(${(f.size/1024/1024).toFixed(2)} MB)</span>`;
                names.appendChild(li);
            });
            list.style.display = 'block';
            box.querySelector('p').textContent = files.length + ' file(s) selected';
            box.style.borderColor = '#4f9eff';
        });
    }
});

async function loadExistingEvidence(complaint_id) {
    const box = document.getElementById('evExistingList');
    box.innerHTML = '<p style="color:#4a5568;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    try {
       const res  = await fetch(`/api/get_complaints_evidence?complaint_id=${encodeURIComponent(complaint_id)}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.files || !data.files.length) {
            box.innerHTML = '<p style="color:#4a5568;font-size:13px;"><i class="fas fa-folder-open"></i> No evidence uploaded yet.</p>';
            return;
        }
        box.innerHTML = data.files.map(f => {
            const isPdf = f.file_name.toLowerCase().endsWith('.pdf');
            const icon  = isPdf ? 'fa-file-pdf' : 'fa-file-image';
            const url   = `/${f.file_path}`;
            const date  = new Date(f.uploaded_at).toLocaleString('en-GB');
            return `<div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;margin-bottom:8px;">
                <i class="fas ${icon}" style="color:#4f9eff;font-size:20px;flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escDash(f.file_name)}</div>
                    <div style="color:#4a5568;font-size:11px;">${date}</div>
                </div>
                <a href="${url}" target="_blank" style="color:#4f9eff;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;background:#1e2d4a;border:1px solid #4f9eff;padding:5px 12px;border-radius:8px;">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>`;
        }).join('');
    } catch(e) {
        box.innerHTML = '<p style="color:#e63946;font-size:13px;">Could not load evidence.</p>';
    }
}

async function uploadMoreEvidence() {
    const input = document.getElementById('evFileInput');
    const btn   = document.getElementById('evUploadBtn');
    const msg   = document.getElementById('evUploadMsg');

    if (!input.files.length) { 
        msg.style.display = 'block';
        msg.style.color   = '#f39c12';
        msg.textContent   = 'Please select at least one file first.';
        return;
    }

    btn.disabled     = true;
    btn.innerHTML    = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    msg.style.display = 'none';

    const formData = new FormData();
    formData.append('complaint_id', evComplaintId);
    for (let i = 0; i < input.files.length; i++) {
        formData.append('evidence[]', input.files[i]);
    }

    try {
        const res  = await fetch('/api/upload_complaint_evidence', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        const data = await res.json();

        msg.style.display = 'block';
        if (data.success) {
            msg.style.color = '#2ecc71';
            msg.innerHTML   = `<i class="fas fa-check-circle"></i> ${data.message}`;
            input.value     = '';
            document.getElementById('evFileList').style.display = 'none';
            document.getElementById('evUploadBox').querySelector('p').textContent = 'Click to select files';
            document.getElementById('evUploadBox').style.borderColor = '#1e2d4a';
            // Reload evidence list
            setTimeout(() => loadExistingEvidence(evComplaintId), 500);
        } else {
            msg.style.color = '#e63946';
            msg.innerHTML   = `<i class="fas fa-exclamation-circle"></i> Upload failed: ${data.message || 'Unknown error'}`;
        }
    } catch(e) {
        msg.style.display = 'block';
        msg.style.color   = '#e63946';
        msg.innerHTML     = '<i class="fas fa-exclamation-circle"></i> Upload failed. Check your connection.';
    }

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-upload"></i> Upload Evidence';
}

function escDash(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection