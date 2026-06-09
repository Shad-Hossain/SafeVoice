@extends('layouts.admin')
@section('title', 'Lawyer Management — SafeVoice Admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
<style>
/* ── Page Layout ──────────────────────────────────────────── */
.page-wrapper {
    max-width: 1300px;
    margin: 30px auto;
    padding: 0 20px;
}
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #e5e7eb;
    display: flex;
    align-items: center;
    gap: 10px;
}
body.light-mode .page-header h1 { color: #0f172a; }

/* ── Stats Row ────────────────────────────────────────────── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.stat-card {
    background: #0f1a2e;
    border: 1px solid #1e2d4a;
    border-radius: 12px;
    padding: 16px 18px;
    text-align: center;
}
body.light-mode .stat-card {
    background: #f1f5fb;
    border-color: #d1dce8;
}
.stat-card .num {
    font-size: 26px;
    font-weight: 800;
    color: #4f9eff;
    line-height: 1;
}
.stat-card .label {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* ── Filter Bar ───────────────────────────────────────────── */
.filter-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    align-items: center;
}
.filter-bar select,
.filter-bar input[type="text"] {
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
body.light-mode .filter-bar input[type="text"] {
    background: #f8fafc;
    border-color: #d1dce8;
    color: #0f172a;
}
.filter-bar select:focus,
.filter-bar input[type="text"]:focus { border-color: #4f9eff; }
.filter-bar input[type="text"] { min-width: 200px; }
.btn-refresh {
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
.btn-refresh:hover { background: #1d4ed8; }

/* ── Table ────────────────────────────────────────────────── */
.table-card {
    background: #0a1120;
    border: 1px solid #1e2d4a;
    border-radius: 14px;
    overflow: hidden;
}
body.light-mode .table-card {
    background: #fff;
    border-color: #dce7f3;
}
.table-card table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.table-card th {
    background: #0f1a2e;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .5px;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #1e2d4a;
    white-space: nowrap;
}
body.light-mode .table-card th {
    background: #f1f5fb;
    color: #64748b;
    border-color: #dce7f3;
}
.table-card td {
    padding: 12px 16px;
    border-bottom: 1px solid #111d31;
    color: #d1d5db;
    vertical-align: middle;
}
body.light-mode .table-card td {
    color: #374151;
    border-color: #e8f0fa;
}
.table-card tr:last-child td { border-bottom: none; }
.table-card tr:hover td { background: rgba(79,158,255,.04); }

/* ── Lawyer Info Cell ─────────────────────────────────────── */
.lawyer-info { display: flex; align-items: center; gap: 10px; }
.lawyer-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    background: #1e2d4a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #4f9eff;
    flex-shrink: 0;
    overflow: hidden;
}
.lawyer-avatar img { width: 100%; height: 100%; object-fit: cover; }
.lawyer-meta .name { font-weight: 600; color: #e5e7eb; font-size: 13px; }
.lawyer-meta .code { font-size: 11px; color: #64748b; margin-top: 2px; }
body.light-mode .lawyer-meta .name { color: #0f172a; }

/* ── Status Badges ────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.badge-active    { background: rgba(34,197,94,.15);  color: #22c55e; }
.badge-pending   { background: rgba(251,191,36,.15); color: #fbbf24; }
.badge-suspended { background: rgba(249,115,22,.15); color: #f97316; }
.badge-banned    { background: rgba(239,68,68,.15);  color: #ef4444; }

/* ── Action Buttons ───────────────────────────────────────── */
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-action {
    padding: 5px 11px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: opacity .2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-action:hover { opacity: .82; }
.btn-approve  { background: #16a34a; color: #fff; }
.btn-reject   { background: #dc2626; color: #fff; }
.btn-suspend  { background: #d97706; color: #fff; }
.btn-unsuspend{ background: #0ea5e9; color: #fff; }
.btn-ban      { background: #991b1b; color: #fff; }
.btn-view     { background: #334155; color: #cbd5e1; }

/* ── Empty / Loading ──────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}
.empty-state i { font-size: 40px; margin-bottom: 12px; display: block; }
.loading-row td { text-align: center; padding: 40px; color: #64748b; }

/* ── Modal ────────────────────────────────────────────────── */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal {
    background: #0f1a2e;
    border: 1px solid #1e2d4a;
    border-radius: 16px;
    padding: 28px;
    width: 480px;
    max-width: 95vw;
    position: relative;
}
body.light-mode .modal {
    background: #fff;
    border-color: #dce7f3;
}
.modal h3 { font-size: 17px; font-weight: 700; color: #e5e7eb; margin-bottom: 6px; }
body.light-mode .modal h3 { color: #0f172a; }
.modal p  { font-size: 13px; color: #94a3b8; margin-bottom: 18px; }
.modal label { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 6px; font-weight: 600; }
.modal textarea {
    width: 100%;
    background: #0a1120;
    border: 1px solid #1e2d4a;
    border-radius: 8px;
    color: #e5e7eb;
    padding: 10px 12px;
    font-size: 13px;
    resize: vertical;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
}
body.light-mode .modal textarea {
    background: #f8fafc;
    border-color: #d1dce8;
    color: #0f172a;
}
.modal textarea:focus { border-color: #4f9eff; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
.btn-cancel {
    background: #1e2d4a;
    color: #94a3b8;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.btn-confirm {
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    color: #fff;
}
.btn-confirm.danger  { background: #dc2626; }
.btn-confirm.warning { background: #d97706; }
.btn-confirm.success { background: #16a34a; }

/* ── Detail Panel ─────────────────────────────────────────── */
.detail-panel {
    display: none;
    position: fixed;
    right: 0; top: 0; bottom: 0;
    width: 380px;
    background: #0a1120;
    border-left: 1px solid #1e2d4a;
    z-index: 900;
    overflow-y: auto;
    padding: 24px;
    box-shadow: -4px 0 24px rgba(0,0,0,.4);
}
body.light-mode .detail-panel {
    background: #fff;
    border-color: #dce7f3;
}
.detail-panel.open { display: block; }
.detail-close {
    position: absolute;
    top: 16px; right: 16px;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 20px;
    cursor: pointer;
}
.detail-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    object-fit: cover;
    background: #1e2d4a;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; color: #4f9eff;
    margin: 0 auto 14px;
    overflow: hidden;
}
.detail-avatar img { width: 100%; height: 100%; object-fit: cover; }
.detail-name { text-align: center; font-size: 17px; font-weight: 700; color: #e5e7eb; }
.detail-code { text-align: center; font-size: 12px; color: #64748b; margin-top: 3px; }
body.light-mode .detail-name { color: #0f172a; }
.detail-section { margin-top: 20px; }
.detail-section h4 { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; border-bottom: 1px solid #1e2d4a; padding-bottom: 6px; }
.detail-row { display: flex; justify-content: space-between; font-size: 13px; padding: 5px 0; }
.detail-row .key { color: #94a3b8; }
.detail-row .val { color: #d1d5db; font-weight: 500; text-align: right; max-width: 60%; }
body.light-mode .detail-row .val { color: #374151; }
.detail-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 20px; }
.detail-actions .btn-action { width: 100%; justify-content: center; padding: 9px; font-size: 13px; }

/* ── Toast ────────────────────────────────────────────────── */
.toast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: #1e2d4a;
    color: #e5e7eb;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    z-index: 2000;
    display: none;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,.3);
    max-width: 320px;
}
.toast.show { display: flex; }
.toast.success { border-left: 4px solid #22c55e; }
.toast.error   { border-left: 4px solid #ef4444; }
.toast.warning { border-left: 4px solid #f97316; }
</style>
@endsection

@section('content')
<div class="page-wrapper">

    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-user-tie"></i> Lawyer Management</h1>
        <a href="{{ route('admin.dashboard') }}" style="font-size:13px;color:#4f9eff;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card"><div class="num" id="statTotal">—</div><div class="label">Total Lawyers</div></div>
        <div class="stat-card"><div class="num" id="statActive" style="color:#22c55e">—</div><div class="label">Active</div></div>
        <div class="stat-card"><div class="num" id="statPending" style="color:#fbbf24">—</div><div class="label">Pending</div></div>
        <div class="stat-card"><div class="num" id="statSuspended" style="color:#f97316">—</div><div class="label">Suspended</div></div>
        <div class="stat-card"><div class="num" id="statBanned" style="color:#ef4444">—</div><div class="label">Banned</div></div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="🔍  Search name, email, Bar Council ID…" oninput="applyFilters()">
        <select id="statusFilter" onchange="applyFilters()">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Active">Active</option>
            <option value="Suspended">Suspended</option>
            <option value="Banned">Banned</option>
        </select>
        <select id="divisionFilter" onchange="applyFilters()">
            <option value="">All Divisions</option>
        </select>
        <button class="btn-refresh" onclick="loadLawyers()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>

    <!-- Table -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Lawyer</th>
                    <th>Bar Council ID</th>
                    <th>Location</th>
                    <th>Specializations</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="lawyersTable">
                <tr class="loading-row"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading lawyers…</td></tr>
            </tbody>
        </table>
    </div>

    <div id="emptyState" class="empty-state" style="display:none">
        <i class="fas fa-user-slash"></i>
        No lawyers found matching your filters.
    </div>

    <!-- ── Lawyer Notifications Panel (Disputes + Commission) ── -->
    <div style="margin-top:32px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
                🔔 Lawyer Notifications
                <span id="notifBadge" style="display:none;background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">0</span>
            </h2>
            <div style="display:flex;gap:8px;">
                <button onclick="markNotifsRead()" style="font-size:12px;background:none;border:1px solid #4f9eff40;color:#4f9eff;padding:6px 12px;border-radius:6px;cursor:pointer;">✓ Mark all read</button>
                <button onclick="loadAdminNotifications()" style="font-size:12px;background:none;border:1px solid #4f9eff40;color:#4f9eff;padding:6px 12px;border-radius:6px;cursor:pointer;">↻ Refresh</button>
            </div>
        </div>
        <div id="notifList" class="table-card" style="padding:0;">
            <div style="padding:24px;text-align:center;color:#6b7fa3;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>

    <!-- ── Commission Payments Panel ── -->
    <div style="margin-top:32px;margin-bottom:32px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h2 style="font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
                💰 Commission Payments
                <span id="commBadge" style="display:none;background:#f59e0b;color:#000;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;">0 pending</span>
            </h2>
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="commFilter" onchange="loadCommissionPayments()" style="font-size:12px;background:#0d1520;border:1px solid #1e2d4a;color:#e2e8f0;padding:6px 10px;border-radius:6px;">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="all">All</option>
                </select>
                <button onclick="loadCommissionPayments()" style="font-size:12px;background:none;border:1px solid #4f9eff40;color:#4f9eff;padding:6px 12px;border-radius:6px;cursor:pointer;">↻ Refresh</button>
            </div>
        </div>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Ref Code</th>
                        <th>Lawyer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Transaction Ref</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="commTable">
                    <tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7fa3;"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /page-wrapper -->

<!-- ── Reject Commission Modal ──────────────────────────────── -->
<div id="rejectCommModal" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;">
    <div style="background:#0d1520;border:1px solid #4f9eff30;border-radius:14px;padding:24px;width:90%;max-width:420px;">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;">❌ Reject Commission Payment</h3>
        <input type="hidden" id="rejectRefCode">
        <div style="margin-bottom:12px;">
            <label style="font-size:12px;color:#6b7fa3;display:block;margin-bottom:6px;">Reason for rejection</label>
            <textarea id="rejectNote" rows="3" placeholder="e.g. Transaction ID not found, incorrect amount..." style="width:100%;background:#0a1422;border:1px solid #1e2d4a;color:#e2e8f0;border-radius:8px;padding:10px;font-size:13px;resize:none;box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="document.getElementById('rejectCommModal').style.display='none'" style="background:none;border:1px solid #4f9eff40;color:#6b7fa3;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;">Cancel</button>
            <button onclick="confirmRejectComm()" style="background:#ef4444;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">Reject</button>
        </div>
    </div>
</div>

<!-- ── Action Confirm Modal ──────────────────────────────────── -->
<div class="modal-overlay" id="actionModal">
    <div class="modal">
        <h3 id="modalTitle">Confirm Action</h3>
        <p id="modalDesc">Are you sure?</p>
        <label id="reasonLabel" for="reasonInput">Reason <span id="reasonRequired" style="color:#ef4444">*</span></label>
        <textarea id="reasonInput" rows="3" placeholder="Enter reason…"></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-confirm" id="modalConfirmBtn" onclick="executeAction()">Confirm</button>
        </div>
    </div>
</div>

<!-- ── Lawyer Detail Panel ────────────────────────────────────── -->
<div class="detail-panel" id="detailPanel">
    <button class="detail-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>

    <div class="detail-avatar" id="detailAvatar"><i class="fas fa-user-tie"></i></div>
    <div class="detail-name" id="detailName">—</div>
    <div class="detail-code" id="detailCode">—</div>

    <div id="detailStatusBadge" style="text-align:center;margin-top:8px"></div>

    <div class="detail-section">
        <h4>Contact</h4>
        <div class="detail-row"><span class="key">Email</span><span class="val" id="dEmail">—</span></div>
        <div class="detail-row"><span class="key">Phone</span><span class="val" id="dPhone">—</span></div>
    </div>

    <div class="detail-section">
        <h4>Location</h4>
        <div class="detail-row"><span class="key">Division</span><span class="val" id="dDivision">—</span></div>
        <div class="detail-row"><span class="key">City</span><span class="val" id="dCity">—</span></div>
        <div class="detail-row"><span class="key">Serving Areas</span><span class="val" id="dAreas">—</span></div>
    </div>

    <div class="detail-section">
        <h4>Professional</h4>
        <div class="detail-row"><span class="key">Bar Council ID</span><span class="val" id="dBarId">—</span></div>
        <div class="detail-row"><span class="key">Experience</span><span class="val" id="dExp">—</span></div>
        <div class="detail-row"><span class="key">Min Fee</span><span class="val" id="dFee">—</span></div>
        <div class="detail-row"><span class="key">Rating</span><span class="val" id="dRating">—</span></div>
        <div class="detail-row"><span class="key">Total Cases</span><span class="val" id="dCases">—</span></div>
    </div>

    <div class="detail-section">
        <h4>Specializations</h4>
        <div id="dSpecs" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px"></div>
    </div>

    <div class="detail-actions" id="detailActionBtns"></div>
</div>

<!-- ── Toast ──────────────────────────────────────────────────── -->
<div class="toast" id="toast"></div>

@endsection

@section('scripts')
<script>
// ── State ──────────────────────────────────────────────────────
let allLawyers  = [];
let pendingAction = null;   // { lawyerId, action, lawyerName }
const API = '/api/admin/legal/lawyers';

// ── Boot ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadLawyers();
    populateDivisions();
    loadAdminNotifications();
    loadCommissionPayments();
});

// ── Load all lawyers ───────────────────────────────────────────
async function loadLawyers() {
    document.getElementById('lawyersTable').innerHTML =
        '<tr class="loading-row"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading…</td></tr>';
    document.getElementById('emptyState').style.display = 'none';

    try {
        const res  = await fetch(API);
        const data = await res.json();
        if (!data.success) throw new Error('API error');
        allLawyers = data.lawyers;
        updateStats(allLawyers);
        applyFilters();
    } catch (e) {
        document.getElementById('lawyersTable').innerHTML =
            '<tr class="loading-row"><td colspan="7" style="color:#ef4444">Failed to load lawyers. Check your connection.</td></tr>';
    }
}

// ── Stats ──────────────────────────────────────────────────────
function updateStats(lawyers) {
    const count = s => lawyers.filter(l => l.status === s).length;
    document.getElementById('statTotal').textContent     = lawyers.length;
    document.getElementById('statActive').textContent    = count('Active');
    document.getElementById('statPending').textContent   = count('Pending');
    document.getElementById('statSuspended').textContent = count('Suspended');
    document.getElementById('statBanned').textContent    = count('Banned');
}

// ── Filters ────────────────────────────────────────────────────
function applyFilters() {
    const search   = document.getElementById('searchInput').value.toLowerCase().trim();
    const status   = document.getElementById('statusFilter').value;
    const division = document.getElementById('divisionFilter').value;

    let filtered = allLawyers.filter(l => {
        if (status   && l.status   !== status)   return false;
        if (division && l.division !== division) return false;
        if (search) {
            const hay = [l.full_name, l.email, l.lawyer_code, l.bar_council_id, l.city]
                          .join(' ').toLowerCase();
            if (!hay.includes(search)) return false;
        }
        return true;
    });

    renderTable(filtered);
}

function populateDivisions() {
    const divs = ['Dhaka','Chittagong','Rajshahi','Khulna','Barisal','Sylhet','Rangpur','Mymensingh'];
    const sel  = document.getElementById('divisionFilter');
    divs.forEach(d => {
        const o = document.createElement('option');
        o.value = d; o.textContent = d;
        sel.appendChild(o);
    });
}

// ── Render Table ───────────────────────────────────────────────
function renderTable(lawyers) {
    const tbody = document.getElementById('lawyersTable');
    const empty = document.getElementById('emptyState');

    if (!lawyers.length) {
        tbody.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';

    tbody.innerHTML = lawyers.map(l => {
        const avatarHtml = l.profile_photo
            ? `<div class="lawyer-avatar"><img src="${l.profile_photo}" alt="${l.full_name}"></div>`
            : `<div class="lawyer-avatar"><i class="fas fa-user-tie"></i></div>`;

        const specs = (l.specializations || []).slice(0, 2).map(s =>
            `<span style="background:#1e2d4a;color:#94a3b8;padding:2px 7px;border-radius:10px;font-size:11px">${s}</span>`
        ).join(' ');

        const moreSpecs = (l.specializations || []).length > 2
            ? `<span style="font-size:11px;color:#64748b">+${l.specializations.length - 2}</span>`
            : '';

        return `
        <tr>
            <td>
                <div class="lawyer-info">
                    ${avatarHtml}
                    <div class="lawyer-meta">
                        <div class="name">${l.full_name}</div>
                        <div class="code">${l.lawyer_code} · ${l.city || '—'}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:12px;color:#94a3b8;font-family:monospace">${l.bar_council_id || '—'}</td>
            <td style="font-size:12px">${l.division || '—'}</td>
            <td><div style="display:flex;gap:4px;flex-wrap:wrap">${specs}${moreSpecs}</div></td>
            <td>${statusBadge(l.status)}</td>
            <td>${l.rating ? `<span style="color:#fbbf24">★ ${parseFloat(l.rating).toFixed(1)}</span>` : '<span style="color:#64748b">—</span>'}</td>
            <td><div class="actions">${buildActions(l)}</div></td>
        </tr>`;
    }).join('');
}

function statusBadge(status) {
    const map = {
        Active: 'badge-active', Pending: 'badge-pending',
        Suspended: 'badge-suspended', Banned: 'badge-banned'
    };
    return `<span class="badge ${map[status] || ''}">${status}</span>`;
}

function buildActions(l) {
    const btns = [];

    btns.push(`<button class="btn-action btn-view" onclick="showDetail(${l.id})"><i class="fas fa-eye"></i></button>`);

    if (l.status === 'Pending') {
        btns.push(`<button class="btn-action btn-approve" onclick="openModal(${l.id},'approve','${escQ(l.full_name)}')">
            <i class="fas fa-check"></i> Approve</button>`);
        btns.push(`<button class="btn-action btn-reject" onclick="openModal(${l.id},'reject','${escQ(l.full_name)}')">
            <i class="fas fa-times"></i> Reject</button>`);
    }

    if (l.status === 'Active') {
        btns.push(`<button class="btn-action btn-suspend" onclick="openModal(${l.id},'suspend','${escQ(l.full_name)}')">
            <i class="fas fa-pause"></i> Suspend</button>`);
        btns.push(`<button class="btn-action btn-ban" onclick="openModal(${l.id},'ban','${escQ(l.full_name)}')">
            <i class="fas fa-ban"></i> Ban</button>`);
    }

    if (l.status === 'Suspended') {
        btns.push(`<button class="btn-action btn-unsuspend" onclick="openModal(${l.id},'unsuspend','${escQ(l.full_name)}')">
            <i class="fas fa-play"></i> Unsuspend</button>`);
        btns.push(`<button class="btn-action btn-ban" onclick="openModal(${l.id},'ban','${escQ(l.full_name)}')">
            <i class="fas fa-ban"></i> Ban</button>`);
    }

    return btns.join('');
}

function escQ(str) { return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

// ── Modal ──────────────────────────────────────────────────────
const ACTION_CONFIG = {
    approve:   { title: 'Approve Lawyer',         desc: (n) => `Approve ${n}? They will be able to receive case notifications.`,          confirmClass: 'success', confirmText: 'Approve',    required: false },
    reject:    { title: 'Reject Application',      desc: (n) => `Reject ${n}'s application? This will permanently ban their account.`,      confirmClass: 'danger',  confirmText: 'Reject',     required: false },
    suspend:   { title: 'Suspend Lawyer',          desc: (n) => `Suspend ${n}? They will lose access to new cases until unsuspended.`,      confirmClass: 'warning', confirmText: 'Suspend',    required: false },
    unsuspend: { title: 'Unsuspend Lawyer',        desc: (n) => `Unsuspend ${n}? They will regain access to cases.`,                        confirmClass: 'success', confirmText: 'Unsuspend',  required: false },
    ban:       { title: '⚠️ Permanently Ban',      desc: (n) => `Permanently ban ${n}? This will revoke all tokens and disable account.`,   confirmClass: 'danger',  confirmText: 'Permanently Ban', required: true  },
};

function openModal(lawyerId, action, lawyerName) {
    const cfg = ACTION_CONFIG[action];
    pendingAction = { lawyerId, action, lawyerName };

    document.getElementById('modalTitle').textContent       = cfg.title;
    document.getElementById('modalDesc').textContent        = cfg.desc(lawyerName);
    document.getElementById('reasonInput').value            = '';
    document.getElementById('reasonRequired').style.display = cfg.required ? 'inline' : 'none';
    document.getElementById('reasonInput').placeholder      = cfg.required ? 'Enter reason (required)…' : 'Enter reason (optional)…';

    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.textContent  = cfg.confirmText;
    confirmBtn.className    = `btn-confirm ${cfg.confirmClass}`;

    document.getElementById('actionModal').classList.add('open');
}

function closeModal() {
    document.getElementById('actionModal').classList.remove('open');
    pendingAction = null;
}

// ── Execute Action ─────────────────────────────────────────────
async function executeAction() {
    if (!pendingAction) return;
    const { lawyerId, action, lawyerName } = pendingAction;
    const cfg    = ACTION_CONFIG[action];
    const reason = document.getElementById('reasonInput').value.trim();

    if (cfg.required && !reason) {
        showToast('Reason is required for this action.', 'error');
        return;
    }

    const confirmBtn = document.getElementById('modalConfirmBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

    try {
        let endpoint, body;

        if (action === 'approve' || action === 'reject') {
            endpoint = `/api/admin/legal/lawyers/${lawyerId}/verify`;
            body = { action: action === 'approve' ? 'approve' : 'reject', reason };
        } else if (action === 'suspend' || action === 'unsuspend') {
            endpoint = `/api/admin/legal/lawyers/${lawyerId}/toggle-suspend`;
            body = { reason };
        } else if (action === 'ban') {
            endpoint = `/api/admin/legal/lawyers/${lawyerId}/ban`;
            body = { reason: reason || 'Admin action' };
        }

        const res  = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (data.success) {
            closeModal();
            closeDetail();
            showToast(data.message || 'Action completed.', 'success');
            await loadLawyers();
        } else {
            showToast(data.message || 'Action failed.', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.textContent = cfg.confirmText;
    }
}

// ── Detail Panel ───────────────────────────────────────────────
async function showDetail(lawyerId) {
    const panel = document.getElementById('detailPanel');
    panel.classList.add('open');

    // First show from local cache
    const local = allLawyers.find(l => l.id === lawyerId);
    if (local) populateDetail(local);

    // Then fetch full detail
    try {
        const res  = await fetch(`/api/admin/legal/lawyers/${lawyerId}`);
        const data = await res.json();
        if (data.success) populateDetail(data.lawyer);
    } catch (e) {}
}

function populateDetail(l) {
    const el = id => document.getElementById(id);

    // Avatar
    const avatarEl = el('detailAvatar');
    avatarEl.innerHTML = l.profile_photo
        ? `<img src="${l.profile_photo}" alt="${l.full_name}">`
        : `<i class="fas fa-user-tie"></i>`;

    el('detailName').textContent = l.full_name;
    el('detailCode').textContent = `${l.lawyer_code} · ${l.experience_years || 0} yrs exp`;
    el('detailStatusBadge').innerHTML = statusBadge(l.status);

    el('dEmail').textContent    = l.email    || '—';
    el('dPhone').textContent    = l.phone    || '—';
    el('dDivision').textContent = l.division || '—';
    el('dCity').textContent     = l.city     || '—';
    el('dAreas').textContent    = (l.serving_areas || []).join(', ') || '—';
    el('dBarId').textContent    = l.bar_council_id  || '—';
    el('dExp').textContent      = l.experience_years ? `${l.experience_years} years` : '—';
    el('dFee').textContent      = l.min_fee ? `৳${Number(l.min_fee).toLocaleString()}` : '—';
    el('dRating').textContent   = l.rating  ? `★ ${parseFloat(l.rating).toFixed(1)} (${l.rating_count || 0} reviews)` : '—';
    el('dCases').textContent    = l.total_cases != null ? `${l.total_cases} (${l.completed_cases || 0} completed)` : '—';

    const specsEl = el('dSpecs');
    specsEl.innerHTML = (l.specializations || []).map(s =>
        `<span style="background:#1e2d4a;color:#94a3b8;padding:3px 10px;border-radius:10px;font-size:12px">${s}</span>`
    ).join('') || '<span style="color:#64748b;font-size:12px">No specializations listed</span>';

    // Action buttons in detail panel
    const actBtns = el('detailActionBtns');
    actBtns.innerHTML = '';

    if (l.status === 'Pending') {
        actBtns.innerHTML += `<button class="btn-action btn-approve" onclick="openModal(${l.id},'approve','${escQ(l.full_name)}')"><i class="fas fa-check"></i> Approve Application</button>`;
        actBtns.innerHTML += `<button class="btn-action btn-reject"  onclick="openModal(${l.id},'reject','${escQ(l.full_name)}')" ><i class="fas fa-times"></i> Reject Application</button>`;
    }
    if (l.status === 'Active') {
        actBtns.innerHTML += `<button class="btn-action btn-suspend" onclick="openModal(${l.id},'suspend','${escQ(l.full_name)}')"><i class="fas fa-pause"></i> Suspend Lawyer</button>`;
        actBtns.innerHTML += `<button class="btn-action btn-ban" onclick="openModal(${l.id},'ban','${escQ(l.full_name)}')"><i class="fas fa-ban"></i> Permanently Ban</button>`;
    }
    if (l.status === 'Suspended') {
        actBtns.innerHTML += `<button class="btn-action btn-unsuspend" onclick="openModal(${l.id},'unsuspend','${escQ(l.full_name)}')"><i class="fas fa-play"></i> Unsuspend</button>`;
        actBtns.innerHTML += `<button class="btn-action btn-ban" onclick="openModal(${l.id},'ban','${escQ(l.full_name)}')"><i class="fas fa-ban"></i> Permanently Ban</button>`;
    }
    if (l.status === 'Banned') {
        actBtns.innerHTML += `<div style="text-align:center;color:#ef4444;font-size:13px;padding:10px"><i class="fas fa-ban"></i> This lawyer is permanently banned.</div>`;
    }

    // Bar council photo link
    if (l.bar_council_photo) {
        actBtns.innerHTML += `<a href="${l.bar_council_photo}" target="_blank" class="btn-action btn-view" style="text-decoration:none;justify-content:center"><i class="fas fa-id-card"></i> View Bar Council Photo</a>`;
    }
}

function closeDetail() {
    document.getElementById('detailPanel').classList.remove('open');
}

// ── Toast ──────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const icons = { success: '✅', error: '❌', warning: '⚠️' };
    const toast = document.getElementById('toast');
    toast.innerHTML  = `${icons[type] || ''} ${msg}`;
    toast.className  = `toast show ${type}`;
    setTimeout(() => toast.classList.remove('show'), 4000);
}

// Close modal on overlay click
document.getElementById('actionModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// ══════════════════════════════════════════════════════════════
// ADMIN NOTIFICATIONS — lawyer disputes + commission payments
// ══════════════════════════════════════════════════════════════
async function loadAdminNotifications() {
    try {
        const res  = await fetch('/api/admin/lawyer-notifications', { credentials: 'include', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
        const data = await res.json();
        if (!data.success) return;

        const badge = document.getElementById('notifBadge');
        if (data.unread_count > 0) {
            badge.style.display = 'inline-block';
            badge.textContent   = data.unread_count;
        } else {
            badge.style.display = 'none';
        }

        const list = document.getElementById('notifList');
        if (!data.notifications || data.notifications.length === 0) {
            list.innerHTML = '<div style="padding:24px;text-align:center;color:#6b7fa3;font-size:13px;">No notifications yet.</div>';
            return;
        }

        const typeColor = { commission_payment: '#f59e0b', lawyer_admin_contact: '#ef4444' };
        const typeLabel = { commission_payment: '💰 Commission', lawyer_admin_contact: '🚨 Dispute' };

        list.innerHTML = data.notifications.map(n => `
            <div style="padding:14px 20px;border-bottom:1px solid #1e2d4a;display:flex;align-items:flex-start;gap:12px;${!n.is_read ? 'background:rgba(79,158,255,0.04);' : ''}">
                <span style="font-size:20px;margin-top:2px;">${n.icon || '🔔'}</span>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:700;color:#e2e8f0;">${n.title}</span>
                        <span style="font-size:10px;padding:2px 8px;border-radius:10px;background:${typeColor[n.type]||'#4f9eff'}20;color:${typeColor[n.type]||'#4f9eff'};font-weight:600;">${typeLabel[n.type] || n.type}</span>
                        ${!n.is_read ? '<span style="font-size:10px;padding:2px 6px;border-radius:10px;background:#4f9eff20;color:#4f9eff;">New</span>' : ''}
                    </div>
                    <div style="font-size:12px;color:#8899b8;line-height:1.5;">${n.message}</div>
                    <div style="font-size:11px;color:#4b5563;margin-top:4px;">${timeAgo(n.created_at)}</div>
                </div>
            </div>`).join('');
    } catch(e) { console.error('Notifications error:', e); }
}

async function markNotifsRead() {
    await fetch('/api/admin/lawyer-notifications/mark-read', { method: 'POST', credentials: 'include', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
    loadAdminNotifications();
}

// ══════════════════════════════════════════════════════════════
// COMMISSION PAYMENTS
// ══════════════════════════════════════════════════════════════
async function loadCommissionPayments() {
    const status = document.getElementById('commFilter')?.value || 'pending';
    const tbody  = document.getElementById('commTable');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7fa3;"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';

    try {
        const res  = await fetch(`/api/admin/commission/all?status=${status}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;

        const payments = data.payments || [];

        // Update badge (pending count)
        if (status === 'pending') {
            const badge = document.getElementById('commBadge');
            if (payments.length > 0) {
                badge.style.display = 'inline-block';
                badge.textContent   = payments.length + ' pending';
            } else {
                badge.style.display = 'none';
            }
        }

        if (payments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7fa3;">No payments found.</td></tr>';
            return;
        }

        const statusBadge = (s) => {
            const cfg = { pending: '#f59e0b', approved: '#22c55e', rejected: '#ef4444' };
            return `<span style="background:${cfg[s]||'#6b7fa3'}20;color:${cfg[s]||'#6b7fa3'};padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
        };

        tbody.innerHTML = payments.map(p => `
            <tr>
                <td style="font-family:monospace;color:#4f9eff;font-size:12px;">${p.ref_code}</td>
                <td>
                    <div style="font-weight:600;font-size:13px;">${p.full_name}</div>
                    <div style="font-size:11px;color:#6b7fa3;">${p.lawyer_code}</div>
                </td>
                <td style="font-weight:700;color:#22c55e;">৳${Number(p.amount).toLocaleString('en-BD',{minimumFractionDigits:2})}</td>
                <td style="text-transform:uppercase;font-size:12px;">${p.method}</td>
                <td style="font-family:monospace;font-size:12px;color:#e2e8f0;">${p.transaction_ref}</td>
                <td style="font-size:12px;color:#6b7fa3;">${timeAgo(p.submitted_at)}</td>
                <td>${statusBadge(p.status)}${p.admin_note ? `<div style="font-size:11px;color:#6b7fa3;margin-top:4px;">${p.admin_note}</div>` : ''}</td>
                <td>
                    ${p.status === 'pending' ? `
                    <div style="display:flex;gap:6px;">
                        <button onclick="approveCommission('${p.ref_code}')" style="background:#22c55e;color:#fff;border:none;padding:5px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">✓ Approve</button>
                        <button onclick="openRejectComm('${p.ref_code}')" style="background:#ef4444;color:#fff;border:none;padding:5px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">✗ Reject</button>
                    </div>` : '—'}
                </td>
            </tr>`).join('');
    } catch(e) { console.error('Commission load error:', e); }
}

async function approveCommission(refCode) {
    if (!confirm(`Approve commission payment ${refCode}?\n\nThis will adjust the lawyer's balance.`)) return;
    try {
        const res  = await fetch(`/api/admin/commission/${refCode}/approve`, { method: 'POST', credentials: 'include', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
        const data = await res.json();
        if (data.success) {
            showToast('✅ ' + data.message);
            loadCommissionPayments();
        } else {
            showToast('❌ ' + (data.message || 'Failed'), 'error');
        }
    } catch(e) { showToast('Network error', 'error'); }
}

function openRejectComm(refCode) {
    document.getElementById('rejectRefCode').value = refCode;
    document.getElementById('rejectNote').value    = '';
    document.getElementById('rejectCommModal').style.display = 'flex';
}

async function confirmRejectComm() {
    const refCode = document.getElementById('rejectRefCode').value;
    const note    = document.getElementById('rejectNote').value.trim();
    if (!note) { alert('Please enter a rejection reason.'); return; }

    try {
        const res  = await fetch(`/api/admin/commission/${refCode}/reject`, {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
            body: JSON.stringify({ note }),
        });
        const data = await res.json();
        document.getElementById('rejectCommModal').style.display = 'none';
        if (data.success) {
            showToast('❌ Payment rejected.');
            loadCommissionPayments();
        } else {
            showToast('Error: ' + (data.message || 'Failed'), 'error');
        }
    } catch(e) { showToast('Network error', 'error'); }
}

function timeAgo(dateStr) {
    if (!dateStr) return '—';
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}
</script>
@endsection