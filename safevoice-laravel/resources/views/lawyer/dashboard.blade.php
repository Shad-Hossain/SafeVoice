@extends('layouts.app')
@section('title', 'Lawyer Dashboard — SafeVoice')
@section('styles')
<style>
:root {
    --bg-primary: #070d1a; --bg-card: #0d1526; --bg-input: #0a1020;
    --border: #1e2d4a; --accent: #4f9eff; --accent-dark: #1a3a6e;
    --text-main: #e8f0fe; --text-muted: #6b7fa3;
    --success: #22c55e; --warning: #f59e0b; --error: #ef4444;
    --sidebar-w: 260px;
}
* { box-sizing:border-box; margin:0; padding:0; }
body { background:var(--bg-primary); color:var(--text-main); font-family:'Segoe UI',sans-serif; min-height:100vh; }

/* ── Layout ─────────────────────────────────────────────── */
.dash-layout { display:flex; min-height:100vh; padding-top:70px; }

/* ── Sidebar ────────────────────────────────────────────── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--bg-card);
    border-right: 1px solid var(--border);
    position: fixed; top:70px; left:0; bottom:0;
    display:flex; flex-direction:column;
    overflow-y: auto; z-index:90;
}
.sidebar-profile {
    padding: 24px 20px;
    border-bottom: 1px solid var(--border);
    text-align: center;
}
.sidebar-avatar {
    width:64px; height:64px; border-radius:50%;
    background: linear-gradient(135deg,var(--accent-dark),#0a1e40);
    display:flex; align-items:center; justify-content:center;
    font-size:26px; margin:0 auto 12px;
    border:2px solid var(--border);
    overflow:hidden;
}
.sidebar-avatar img { width:100%; height:100%; object-fit:cover; }
.sidebar-name { font-size:15px; font-weight:700; }
.sidebar-code { font-size:11px; color:var(--accent); font-family:monospace; margin-top:2px; }
.sidebar-city { font-size:12px; color:var(--text-muted); margin-top:4px; }

.avail-toggle {
    display:flex; align-items:center; gap:8px;
    margin:12px auto 0;
    padding:7px 14px;
    border-radius:20px;
    border:1px solid var(--border);
    font-size:12px; cursor:pointer;
    transition:all .2s; width:fit-content;
}
.avail-toggle.on  { border-color:var(--success); color:var(--success); background:rgba(34,197,94,.08); }
.avail-toggle.off { border-color:var(--text-muted); color:var(--text-muted); }
.avail-dot { width:8px; height:8px; border-radius:50%; }
.avail-toggle.on  .avail-dot { background:var(--success); }
.avail-toggle.off .avail-dot { background:var(--text-muted); }

.sidebar-nav { flex:1; padding:16px 12px; }
.nav-item {
    display:flex; align-items:center; gap:12px;
    padding:11px 14px; border-radius:10px;
    font-size:14px; cursor:pointer;
    transition:all .15s; margin-bottom:4px;
    color:var(--text-muted);
}
.nav-item:hover { background:rgba(79,158,255,.08); color:var(--text-main); }
.nav-item.active { background:rgba(79,158,255,.12); color:var(--accent); }
.nav-item .badge {
    margin-left:auto; background:var(--error);
    border-radius:10px; padding:1px 7px;
    font-size:11px; color:#fff; font-weight:700;
}

.sidebar-footer { padding:16px; border-top:1px solid var(--border); }
.logout-btn {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:10px;
    font-size:13px; cursor:pointer; color:var(--text-muted);
    transition:all .15s;
}
.logout-btn:hover { background:rgba(239,68,68,.08); color:var(--error); }

/* ── Main content ────────────────────────────────────────── */
.main { margin-left:var(--sidebar-w); flex:1; padding:28px 32px; }

.page { display:none; }
.page.active { display:block; }

.page-title { font-size:22px; font-weight:700; margin-bottom:6px; }
.page-sub   { color:var(--text-muted); font-size:14px; margin-bottom:28px; }

/* ── Stat cards ─────────────────────────────────────────── */
.stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat-card {
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:14px; padding:20px;
    transition:border-color .2s;
}
.stat-card:hover { border-color: rgba(79,158,255,.3); }
.stat-card .label { font-size:12px; color:var(--text-muted); margin-bottom:8px; }
.stat-card .value { font-size:28px; font-weight:800; color:var(--accent); }
.stat-card .sub   { font-size:11px; color:var(--text-muted); margin-top:4px; }

/* ── Cards / Panels ─────────────────────────────────────── */
.panel { background:var(--bg-card); border:1px solid var(--border); border-radius:16px; margin-bottom:24px; overflow:hidden; }
.panel-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 22px; border-bottom:1px solid var(--border);
}
.panel-header h3 { font-size:16px; font-weight:700; }
.panel-body { padding:18px 22px; }

/* ── Request cards ──────────────────────────────────────── */
.request-card {
    display:block !important; width:100% !important;
    background:var(--bg-input); border:1px solid var(--border);
    border-radius:12px; padding:18px 20px; margin-bottom:12px;
    transition:border-color .2s;
}
.request-card:hover { border-color:rgba(79,158,255,.3); }
.request-card.urgent { border-left:3px solid var(--error); }
.req-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px; }
.req-type { font-size:15px; font-weight:700; }
.req-id   { font-size:11px; color:var(--text-muted); font-family:monospace; }
.req-badge {
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;
    white-space:nowrap;
}
.badge-open     { background:rgba(34,197,94,.12); color:var(--success); }
.badge-urgent   { background:rgba(239,68,68,.12);  color:var(--error); }
.badge-bidding  { background:rgba(245,158,11,.12); color:var(--warning); }
.badge-accepted { background:rgba(79,158,255,.12); color:var(--accent); }
.req-desc { font-size:13px; color:var(--text-muted); line-height:1.6; margin-bottom:12px; }
.req-meta { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:14px; }
.req-meta span { font-size:12px; color:var(--text-muted); }
.req-meta span i { margin-right:5px; color:var(--accent); }
.req-actions { display:flex; gap:10px; align-items:center; }

/* ── Bid form ───────────────────────────────────────────── */
.request-card { display:block !important; width:100% !important; }
.req-actions { display:flex !important; gap:10px; align-items:center; }
.bid-form {
    display:none;
    width:100%;
    box-sizing:border-box;
    background:rgba(79,158,255,.05);
    border:1px solid rgba(79,158,255,.2);
    border-radius:12px; padding:18px; margin-top:12px;
}
.bid-form.show { display:block; }
.bid-form h4   { font-size:14px; font-weight:700; margin-bottom:14px; color:var(--accent); }
.bid-row       { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
.bid-form input,
.bid-form textarea {
    width:100%; background:var(--bg-input); border:1px solid var(--border);
    border-radius:8px; color:var(--text-main); font-size:13px; padding:10px 12px;
    outline:none;
}
.bid-form input:focus,
.bid-form textarea:focus { border-color:var(--accent); }
.bid-form textarea { min-height:70px; resize:vertical; }

/* ── Notification items ─────────────────────────────────── */
.notif-item {
    display:flex; gap:14px; padding:14px 0;
    border-bottom:1px solid var(--border);
}
.notif-item:last-child { border-bottom:none; }
.notif-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.notif-icon.new_request  { background:rgba(34,197,94,.1); }
.notif-icon.bid_accepted { background:rgba(79,158,255,.1); }
.notif-icon.bid_rejected { background:rgba(239,68,68,.1); }
.notif-title  { font-size:14px; font-weight:600; margin-bottom:4px; }
.notif-body   { font-size:12px; color:var(--text-muted); line-height:1.5; }
.notif-time   { font-size:11px; color:var(--text-muted); margin-top:4px; }
.notif-unread .notif-title { color:var(--accent); }

/* ── Buttons ────────────────────────────────────────────── */
.btn { padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; display:inline-flex; align-items:center; gap:6px; }
.btn-primary { background:var(--accent); color:#fff; display:inline-flex !important; padding:8px 18px !important; font-size:13px !important; }
.btn-primary:hover { opacity:.85; background:var(--accent) !important; }
.btn-outline { background:transparent; border:1px solid var(--border); color:var(--text-muted); }
.btn-outline:hover { border-color:var(--accent); color:var(--accent); }
.btn-sm { padding:6px 14px; font-size:12px; }
.btn-success { background:var(--success); color:#fff; }
.btn-success:hover { opacity:.85; }

/* ── Empty state ────────────────────────────────────────── */
.empty { text-align:center; padding:40px 20px; color:var(--text-muted); }
.empty .e-icon { font-size:40px; margin-bottom:12px; }
.empty p { font-size:14px; }

/* ── Loading ────────────────────────────────────────────── */
.loading { text-align:center; padding:32px; color:var(--text-muted); font-size:14px; }
.spinner { display:inline-block; width:20px; height:20px; border:2px solid rgba(79,158,255,.2); border-top-color:var(--accent); border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:8px; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Rating stars ───────────────────────────────────────── */
.stars { color:#f59e0b; font-size:13px; }

/* ── Toast ──────────────────────────────────────────────── */
.toast {
    position:fixed; bottom:28px; right:28px; z-index:9999;
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:12px; padding:14px 20px;
    font-size:14px; max-width:340px;
    transform:translateY(80px); opacity:0;
    transition:all .3s; pointer-events:none;
}
.toast.show { transform:translateY(0); opacity:1; }
.toast.success { border-color:rgba(34,197,94,.4); color:#86efac; }
.toast.error   { border-color:rgba(239,68,68,.4);  color:#fca5a5; }

@media (max-width:768px) {
    .sidebar { transform:translateX(-100%); transition:transform .3s; }
    .sidebar.open { transform:translateX(0); }
    .main { margin-left:0; padding:20px 16px; }
    .stats-row { grid-template-columns:1fr 1fr; }
}
</style>
@endsection

@section('content')
<div class="dash-layout">

    <!-- ── SIDEBAR ────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-profile">
            <div class="sidebar-avatar" id="sidebarAvatar">⚖️</div>
            <div class="sidebar-name" id="sidebarName">Loading...</div>
            <div class="sidebar-code" id="sidebarCode"></div>
            <div class="sidebar-city" id="sidebarCity"></div>
            <div class="avail-toggle" id="availToggle" onclick="toggleAvailability()">
                <div class="avail-dot"></div>
                <span id="availText">Available</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item active" onclick="switchPage('overview')" id="nav-overview">
                <span>📊</span> Overview
            </div>
            <div class="nav-item" onclick="switchPage('requests')" id="nav-requests">
                <span>📋</span> Open Requests
                <span class="badge" id="openBadge" style="display:none">0</span>
            </div>
            <div class="nav-item" onclick="switchPage('my-bids')" id="nav-my-bids">
                <span>💬</span> My Bids
            </div>
            <div class="nav-item" onclick="switchPage('cases')" id="nav-cases">
                <span>✅</span> Active Cases
            </div>
            <div class="nav-item" onclick="switchPage('notifications')" id="nav-notifications">
                <span>🔔</span> Notifications
                <span class="badge" id="notifBadge" style="display:none">0</span>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="logout-btn" onclick="doLogout()">
                <span>🚪</span> Logout
            </div>
        </div>
    </aside>

    <!-- ── MAIN ───────────────────────────────────────────── -->
    <main class="main">

        <!-- OVERVIEW PAGE -->
        <div class="page active" id="page-overview">
            <div class="page-title">Dashboard</div>
            <div class="page-sub" id="welcomeMsg">Good day, Lawyer!</div>

            <div class="stats-row" id="statsRow">
                <div class="stat-card"><div class="label">Open Requests</div><div class="value" id="statOpen">—</div><div class="sub">Waiting for bids</div></div>
                <div class="stat-card"><div class="label">My Active Bids</div><div class="value" id="statBids">—</div><div class="sub">Pending response</div></div>
                <div class="stat-card"><div class="label">Accepted Cases</div><div class="value" id="statAccepted">—</div><div class="sub">From my bids</div></div>
                <div class="stat-card"><div class="label">Rating</div><div class="value" id="statRating">—</div><div class="sub stars">★★★★★</div></div>
            </div>

            <!-- Recent open requests -->
            <div class="panel">
                <div class="panel-header">
                    <h3>🆕 New Legal Requests</h3>
                    <button class="btn btn-outline btn-sm" onclick="switchPage('requests')">View All</button>
                </div>
                <div class="panel-body" id="recentRequests">
                    <div class="loading"><span class="spinner"></span>Loading...</div>
                </div>
            </div>
        </div>

        <!-- OPEN REQUESTS PAGE -->
        <div class="page" id="page-requests">
            <div class="page-title">Open Legal Requests</div>
            <div class="page-sub">Submit your bid — clients will see your offer and choose</div>
            <div id="requestsList">
                <div class="loading"><span class="spinner"></span>Loading requests...</div>
            </div>
        </div>

        <!-- MY BIDS PAGE -->
        <div class="page" id="page-my-bids">
            <div class="page-title">My Bids</div>
            <div class="page-sub">Track the status of your submitted bids</div>
            <div id="myBidsList">
                <div class="loading"><span class="spinner"></span>Loading...</div>
            </div>
        </div>

        <!-- ACTIVE CASES PAGE -->
        <div class="page" id="page-cases">
            <div class="page-title">Active Cases</div>
            <div class="page-sub">Cases where your bid was accepted</div>
            <div id="casesList">
                <div class="loading"><span class="spinner"></span>Loading...</div>
            </div>
        </div>

        <!-- NOTIFICATIONS PAGE -->
        <div class="page" id="page-notifications">
            <div class="page-title">Notifications</div>
            <div class="page-sub">Your latest updates</div>
            <div class="panel">
                <div class="panel-body" id="notifList">
                    <div class="loading"><span class="spinner"></span>Loading...</div>
                </div>
            </div>
        </div>

    </main>
</div>

<div class="toast" id="toast"></div>
@endsection

@section('scripts')
<script>
const API = '/api/lawyer';
let lawyerData = null;
let dashData   = null;

const token = () => localStorage.getItem('lawyer_token') || '';
const headers = () => ({
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
    ...(token() ? { 'Authorization': 'Bearer ' + token() } : {}),
});

// ── Boot ───────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
    const stored = localStorage.getItem('lawyer_data');
    if (stored) lawyerData = JSON.parse(stored);

    // Session check
    try {
        const res  = await fetch(`${API}/check-session`, { headers: headers() });
        const data = await res.json();
        if (!data.loggedIn) {
            window.location.href = '/lawyer/login';
            return;
        }
        lawyerData = data.lawyer;
        localStorage.setItem('lawyer_data', JSON.stringify(lawyerData));
    } catch(e) {
        if (!lawyerData) { window.location.href = '/lawyer/login'; return; }
    }

    renderSidebar();
    loadDashboard();
    pollNotifications();
});

function renderSidebar() {
    if (!lawyerData) return;
    const nameEl   = document.getElementById('sidebarName');
    const codeEl   = document.getElementById('sidebarCode');
    const cityEl   = document.getElementById('sidebarCity');
    const avatarEl = document.getElementById('sidebarAvatar');

    nameEl.textContent = lawyerData.full_name;
    codeEl.textContent = lawyerData.lawyer_code;
    cityEl.textContent = lawyerData.city || '';

    if (lawyerData.profile_photo) {
        avatarEl.innerHTML = `<img src="/${lawyerData.profile_photo}" alt="">`;
    }

    document.getElementById('welcomeMsg').textContent =
        `Welcome back, ${lawyerData.full_name.split(' ')[0]}! Here's your overview.`;

    updateAvailToggle(lawyerData.is_available);
    updateNotifBadge(lawyerData.unread_notifications || 0);
}

// ── Dashboard data ─────────────────────────────────────────
async function loadDashboard() {
    try {
        const res  = await fetch(`${API}/dashboard`, { headers: headers() });
        const data = await res.json();
        if (!data.success) { window.location.href = '/lawyer/login'; return; }

        dashData   = data;
        lawyerData = { ...lawyerData, ...data.lawyer };

        // Stats
        document.getElementById('statOpen').textContent     = data.open_requests?.length || 0;
        document.getElementById('statBids').textContent     = data.stats?.pending_bids   || 0;
        document.getElementById('statAccepted').textContent = data.stats?.accepted_bids  || 0;
        document.getElementById('statRating').textContent   = parseFloat(data.stats?.rating || 0).toFixed(1);

        const badge = document.getElementById('openBadge');
        if ((data.open_requests?.length || 0) > 0) {
            badge.textContent     = data.open_requests.length;
            badge.style.display   = 'inline';
        }

        renderRecentRequests(data.open_requests || []);
    } catch(e) {
        console.error(e);
    }
}

// ── Render helpers ─────────────────────────────────────────
function renderRecentRequests(reqs) {
    const el = document.getElementById('recentRequests');
    if (!reqs.length) {
        el.innerHTML = `<div class="empty"><div class="e-icon">📭</div><p>No open requests right now. Check back soon!</p></div>`;
        return;
    }
    el.innerHTML = reqs.slice(0,5).map(r => requestCard(r, true, 'ov')).join('');
}

function requestCard(r, compact = false, prefix = '') {
    const urgent    = r.is_urgent;
    const typeCap   = (r.issue_type || '').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
    const budgetTxt = r.budget_max ? `৳${Number(r.budget_max).toLocaleString()}` : 'No budget set';
    const ago       = timeAgo(r.created_at);
    const rid       = (prefix ? prefix + '-' : '') + r.request_id;

    // consultation date min = now + 5 hours
    const minDate = new Date(Date.now() + 5*60*60*1000);
    const pad     = n => String(n).padStart(2,'0');
    const minDT   = `${minDate.getFullYear()}-${pad(minDate.getMonth()+1)}-${pad(minDate.getDate())}T${pad(minDate.getHours())}:${pad(minDate.getMinutes())}`;

    return `
    <div class="request-card ${urgent?'urgent':''}" id="rc-${rid}">
        <div class="req-top">
            <div>
                <div class="req-type">${typeCap}</div>
                <div class="req-id">${rid}</div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                ${urgent ? '<span class="req-badge badge-urgent">🚨 URGENT</span>' : ''}
                <span class="req-badge badge-open">Open</span>
            </div>
        </div>
        <div class="req-desc">${r.description}</div>
        <div class="req-meta">
            ${r.location ? `<span><i class="fas fa-map-marker-alt"></i>${r.location}</span>` : ''}
            <span><i class="fas fa-wallet"></i>Budget: ${budgetTxt}</span>
            <span><i class="fas fa-users"></i>${r.bid_count || 0} bid(s) so far</span>
            <span><i class="fas fa-clock"></i>${ago}</span>
        </div>
        <div class="req-actions">
            <button class="btn btn-primary btn-sm" onclick="toggleBidForm('${rid}')">
                💬 Place Bid
            </button>
            ${compact ? `<button class="btn btn-outline btn-sm" onclick="switchPage('requests')">View All →</button>` : ''}
        </div>
        <div class="bid-form" id="bf-${rid}">
            <h4>📝 Your Bid for: ${typeCap}</h4>
            <div class="bid-row">
                <div>
                    <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Your Fee (৳) *</label>
                    <input type="number" id="fee-${rid}" placeholder="e.g. 2000" min="100">
                </div>
                <div>
                    <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Consultation Date & Time *</label>
                    <input type="datetime-local" id="consult-${rid}" min="${minDT}">
                </div>
            </div>
            <div style="margin-bottom:10px">
                <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Office Address</label>
                <input type="text" id="office-${rid}" placeholder="Your office address where client will visit">
            </div>
            <div style="margin-bottom:10px">
                <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Cover Note (optional)</label>
                <textarea id="note-${rid}" placeholder="Briefly explain why you're the right lawyer for this..."></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-success btn-sm" onclick="submitBid('${rid}')">✅ Submit Bid</button>
                <button class="btn btn-outline btn-sm" onclick="toggleBidForm('${rid}')">Cancel</button>
            </div>
        </div>
    </div>`;
}

// ── Page switching ─────────────────────────────────────────
function switchPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    document.getElementById('nav-' + name).classList.add('active');

    if (name === 'requests')      loadOpenRequests();
    if (name === 'my-bids')       loadMyBids();
    if (name === 'cases')         loadCases();
    if (name === 'notifications') loadNotifications();
}

// ── Open Requests ──────────────────────────────────────────
async function loadOpenRequests() {
    const el = document.getElementById('requestsList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/requests`, { headers: headers() });
        const data = await res.json();
        const reqs = data.data || data.requests?.data || [];
        el.innerHTML = reqs.length
            ? reqs.map(r => requestCard(r)).join('')
            : `<div class="empty"><div class="e-icon">✅</div><p>No open requests right now.</p></div>`;
    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading requests.</p></div>`; }
}

// ── My Bids ────────────────────────────────────────────────
async function loadMyBids() {
    const el = document.getElementById('myBidsList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/dashboard`, { headers: headers() });
        const data = await res.json();
        const bids = data.active_bids || [];

        el.innerHTML = bids.length ? bids.map(b => `
            <div class="request-card">
                <div class="req-top">
                    <div>
                        <div class="req-type">${capitalize(b.legal_request?.issue_type || 'Case')}</div>
                        <div class="req-id">${b.legal_request?.request_id || ''}</div>
                    </div>
                    <span class="req-badge ${b.status==='pending'?'badge-open':'badge-bidding'}">
                        ${b.status === 'pending' ? '⏳ Awaiting Response' : '👁 Seen by Client'}
                    </span>
                </div>
                <div class="req-meta">
                    <span><i class="fas fa-wallet"></i>Your Bid: <strong style="color:var(--accent)">৳${Number(b.proposed_fee).toLocaleString()}</strong></span>
                    ${b.estimated_days ? `<span><i class="fas fa-calendar"></i>${b.estimated_days} days</span>` : ''}
                    <span><i class="fas fa-clock"></i>${timeAgo(b.bid_at)}</span>
                </div>
                ${b.cover_note ? `<div class="req-desc">"${b.cover_note}"</div>` : ''}
            </div>`).join('')
        : `<div class="empty"><div class="e-icon">💬</div><p>You haven't placed any bids yet. Go to Open Requests to start bidding!</p></div>`;
    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading bids.</p></div>`; }
}

// ── Cases ──────────────────────────────────────────────────
async function loadCases() {
    const el = document.getElementById('casesList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/dashboard`, { headers: headers() });
        const data = await res.json();
        const cases = data.accepted_cases || [];

        el.innerHTML = cases.length ? cases.map(c => `
            <div class="request-card">
                <div class="req-top">
                    <div>
                        <div class="req-type">${capitalize(c.legal_request?.issue_type || 'Case')}</div>
                        <div class="req-id">${c.legal_request?.request_id || ''}</div>
                    </div>
                    <span class="req-badge badge-accepted">✅ Accepted</span>
                </div>
                <div class="req-meta">
                    ${c.legal_request?.user_name  ? `<span><i class="fas fa-user"></i>Client: ${c.legal_request.user_name}</span>` : ''}
                    ${c.legal_request?.user_phone ? `<span><i class="fas fa-phone"></i>${c.legal_request.user_phone}</span>` : ''}
                    <span><i class="fas fa-wallet"></i>Fee: <strong style="color:var(--success)">৳${Number(c.proposed_fee).toLocaleString()}</strong></span>
                    <span><i class="fas fa-calendar"></i>${timeAgo(c.bid_at)}</span>
                </div>
            </div>`).join('')
        : `<div class="empty"><div class="e-icon">📁</div><p>No accepted cases yet. Keep bidding!</p></div>`;
    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading cases.</p></div>`; }
}

// ── Notifications ──────────────────────────────────────────
async function loadNotifications() {
    const el = document.getElementById('notifList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/notifications`, { headers: headers() });
        const data = await res.json();
        const notifs = data.notifications || [];

        updateNotifBadge(0);
        document.getElementById('notifBadge').style.display = 'none';

        el.innerHTML = notifs.length ? notifs.map(n => {
            const icons = { new_request:'⚖️', bid_accepted:'🎉', bid_rejected:'❌' };
            const extra = n.data ? (typeof n.data==='string' ? JSON.parse(n.data) : n.data) : {};
            return `
            <div class="notif-item ${n.is_read ? '' : 'notif-unread'}">
                <div class="notif-icon ${n.type}">${icons[n.type] || '🔔'}</div>
                <div>
                    <div class="notif-title">${n.title}</div>
                    <div class="notif-body">${n.body || ''}</div>
                    ${extra.request_id ? `<div class="notif-body" style="margin-top:4px;color:var(--accent);font-size:11px;">Request: ${extra.request_id}</div>` : ''}
                    <div class="notif-time">${timeAgo(n.created_at)}</div>
                </div>
            </div>`;
        }).join('')
        : `<div class="empty"><div class="e-icon">🔕</div><p>No notifications yet.</p></div>`;
    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading notifications.</p></div>`; }
}

// ── Bid form toggle ────────────────────────────────────────
function toggleBidForm(requestId) {
    const form = document.getElementById('bf-' + requestId);
    if (!form) return;
    const isOpen = form.classList.contains('show');
    document.querySelectorAll('.bid-form').forEach(f => f.classList.remove('show'));
    if (!isOpen) {
        form.classList.add('show');
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

async function submitBid(requestId) {
    const fee     = document.getElementById('fee-'     + requestId)?.value;
    const consult = document.getElementById('consult-' + requestId)?.value;
    const office  = document.getElementById('office-'  + requestId)?.value;
    const note    = document.getElementById('note-'    + requestId)?.value;

    // Strip prefix (e.g. 'ov-LR-xxx' → 'LR-xxx')
    const actualRequestId = requestId.replace(/^ov-/, '');

    if (!fee || parseFloat(fee) < 100) { showToast('Enter a valid fee (min ৳100)', 'error'); return; }

    // Consultation date validation — must be 5 hours from now
    if (consult) {
        const selected = new Date(consult);
        const minTime  = new Date(Date.now() + 5*60*60*1000);
        if (selected < minTime) {
            showToast('Consultation time must be at least 5 hours from now.', 'error');
            return;
        }
    }

    try {
        const res  = await fetch(`${API}/bid`, {
            method: 'POST',
            headers: headers(),
            body: JSON.stringify({
                request_id:        actualRequestId,
                proposed_fee:      parseFloat(fee),
                cover_note:        note    || null,
                office_address:    office  || null,
                consultation_date: consult || null,
            }),
        });
        const data = await res.json();

        if (data.success) {
            showToast('✅ Bid submitted! Client will be notified.', 'success');
            document.getElementById('bf-' + requestId).classList.remove('show');
            document.getElementById('rc-' + requestId)?.remove();
            loadDashboard();
        } else {
            showToast(data.message || 'Failed to submit bid.', 'error');
        }
    } catch(e) { showToast('Network error.', 'error'); }
}

// ── Availability toggle ────────────────────────────────────
async function toggleAvailability() {
    try {
        const res  = await fetch(`${API}/toggle-availability`, { method:'POST', headers: headers() });
        const data = await res.json();
        if (data.success) {
            updateAvailToggle(data.is_available);
            showToast(data.message, data.is_available ? 'success' : 'error');
        }
    } catch(e) { showToast('Error toggling availability.', 'error'); }
}

function updateAvailToggle(isAvail) {
    const el = document.getElementById('availToggle');
    el.className = 'avail-toggle ' + (isAvail ? 'on' : 'off');
    document.getElementById('availText').textContent = isAvail ? 'Available' : 'Unavailable';
}

// ── Poll notifications ─────────────────────────────────────
async function pollNotifications() {
    try {
        const res  = await fetch(`${API}/notifications/unread-count`, { headers: headers() });
        const data = await res.json();
        if ((data.count || 0) > 0) updateNotifBadge(data.count);
    } catch(e) {}
    setTimeout(pollNotifications, 30000);
}

function updateNotifBadge(count) {
    const b = document.getElementById('notifBadge');
    if (count > 0) { b.textContent = count; b.style.display = 'inline'; }
    else           { b.style.display = 'none'; }
}

// ── Logout ─────────────────────────────────────────────────
async function doLogout() {
    await fetch(`${API}/logout`, { method:'POST', headers: headers() }).catch(()=>{});
    localStorage.removeItem('lawyer_token');
    localStorage.removeItem('lawyer_data');
    window.location.href = '/lawyer/login';
}

// ── Helpers ────────────────────────────────────────────────
function capitalize(s) { return s.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()); }

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60)     return 'just now';
    if (diff < 3600)   return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)  return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3500);
}
</script>
@endsection