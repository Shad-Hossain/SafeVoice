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
.nav-item#nav-instant.active { background:rgba(245,158,11,.1); color:#f59e0b; }
.nav-item#nav-earnings.active { background:rgba(34,197,94,.1); color:#22c55e; }
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

/* ── Earnings card ───────────────────────────────────────── */
.earnings-banner {
    background: linear-gradient(135deg, #051a0f, #071a12);
    border: 1px solid #22c55e30;
    border-radius: 16px;
    padding: 22px 26px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 32px;
    flex-wrap: wrap;
}
.earnings-banner .earn-title { font-size:13px; color:#6b7fa3; margin-bottom:4px; }
.earnings-banner .earn-amount { font-size:30px; font-weight:800; color:#22c55e; }
.earnings-banner .earn-sub { font-size:11px; color:#6b7fa3; margin-top:2px; }
.earnings-divider { width:1px; height:48px; background:#1e3a28; }

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
.req-actions { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }

/* ── Bid form ───────────────────────────────────────────── */
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

/* ── Payment status badges ───────────────────────────────── */
.pay-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;
}
.pay-pending   { background:#f59e0b15; color:#f59e0b; border:1px solid #f59e0b30; }
.pay-claimed   { background:#4f9eff15; color:#4f9eff; border:1px solid #4f9eff30; }
.pay-confirmed { background:#22c55e15; color:#22c55e; border:1px solid #22c55e30; }
.pay-disputed  { background:#ef444415; color:#ef4444; border:1px solid #ef444430; }

/* ── Resolve button ─────────────────────────────────────── */
.btn-resolve {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff; border: none; border-radius: 8px;
    padding: 8px 18px; font-size: 13px; font-weight: 700;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: opacity .15s;
}
.btn-resolve:hover { opacity: .85; }
.btn-resolve:disabled { opacity: .4; cursor: not-allowed; }

/* ── Payment confirmation card (in notifications) ────────── */
.pay-confirm-card {
    background: #0a1422;
    border: 1px solid #4f9eff25;
    border-radius: 12px;
    padding: 16px 18px;
    margin-top: 12px;
}
.pay-confirm-card .pay-amount { font-size: 22px; font-weight: 800; color: #4f9eff; }
.pay-confirm-actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
.btn-pay-yes {
    background: #22c55e; color: #fff; border: none;
    border-radius: 8px; padding: 10px 22px; font-weight: 700;
    font-size: 13px; cursor: pointer;
}
.btn-pay-yes:hover { opacity: .85; }
.btn-pay-yes:disabled { opacity: .4; cursor: not-allowed; }
.btn-pay-no {
    background: transparent; border: 1px solid #ef4444;
    color: #ef4444; border-radius: 8px; padding: 10px 22px;
    font-weight: 700; font-size: 13px; cursor: pointer;
}
.btn-pay-no:hover { background: rgba(239,68,68,.08); }
.btn-pay-no:disabled { opacity: .4; cursor: not-allowed; }

/* ── Commission payment method buttons ──────────────────── */
.method-btn {
    background: #0a1422; border: 1.5px solid #1e2d4a;
    color: #c9d4e8; border-radius: 10px; padding: 11px 10px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 8px; justify-content: center;
    transition: border-color .15s, background .15s;
}
.method-btn:hover { border-color: #4f9eff60; background: #0f1e35; }
.method-btn.selected { border-color: #4f9eff; background: rgba(79,158,255,.1); color: #4f9eff; }

/* ── Earnings history table ─────────────────────────────── */
.earnings-table { width:100%; border-collapse:collapse; font-size:13px; }
.earnings-table th { text-align:left; padding:10px 14px; color:var(--text-muted); font-size:12px; border-bottom:1px solid var(--border); }
.earnings-table td { padding:12px 14px; border-bottom:1px solid rgba(30,45,74,.5); }
.earnings-table tr:last-child td { border-bottom:none; }
.earnings-table tr:hover td { background:rgba(79,158,255,.03); }

/* ── Notification items ─────────────────────────────────── */
.notif-item {
    display:flex; gap:14px; padding:14px 0;
    border-bottom:1px solid var(--border);
}
.notif-item:last-child { border-bottom:none; }
.notif-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.notif-icon.new_request    { background:rgba(34,197,94,.1); }
.notif-icon.bid_accepted   { background:rgba(79,158,255,.1); }
.notif-icon.bid_rejected   { background:rgba(239,68,68,.1); }
.notif-icon.payment_claimed{ background:rgba(245,158,11,.1); }
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

/* ── Toast ──────────────────────────────────────────────── */
.toast {
    position:fixed; bottom:28px; right:28px; z-index:9999;
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:12px; padding:14px 20px;
    font-size:14px; max-width:360px;
    transform:translateY(80px); opacity:0;
    transition:all .3s; pointer-events:none;
}
.toast.show { transform:translateY(0); opacity:1; }
.toast.success { border-color:rgba(34,197,94,.4); color:#86efac; }
.toast.error   { border-color:rgba(239,68,68,.4);  color:#fca5a5; }
.toast.warning { border-color:rgba(245,158,11,.4); color:#fcd34d; }

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
            <div class="nav-item" onclick="switchPage('instant')" id="nav-instant" style="border-left:2px solid transparent;">
                <span>⚡</span> Instant Cases
                <span class="badge" id="instantBadge" style="display:none;background:#f59e0b;">0</span>
            </div>
            <div class="nav-item" onclick="switchPage('scheduled')" id="nav-scheduled">
                <span>📅</span> Scheduled Cases
                <span class="badge" id="scheduledBadge" style="display:none">0</span>
            </div>
            <div class="nav-item" onclick="switchPage('my-bids')" id="nav-my-bids">
                <span>💬</span> My Bids
            </div>
            <div class="nav-item" onclick="switchPage('cases')" id="nav-cases">
                <span>✅</span> Active Cases
            </div>
            <div class="nav-item" onclick="switchPage('earnings')" id="nav-earnings">
                <span>💰</span> Earnings
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
                <div class="stat-card" style="border-color:#22c55e30;">
                    <div class="label">Total Earned</div>
                    <div class="value" id="statEarned" style="color:#22c55e;">৳0</div>
                    <div class="sub">After 2% commission</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>🆕 New Legal Requests</h3>
                    <button class="btn btn-outline btn-sm" onclick="switchPage('instant')">View All</button>
                </div>
                <div class="panel-body" id="recentRequests">
                    <div class="loading"><span class="spinner"></span>Loading...</div>
                </div>
            </div>
        </div>

        <!-- INSTANT CASES PAGE ⚡ -->
        <div class="page" id="page-instant">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                <div class="page-title" style="margin-bottom:0;">⚡ Instant Cases</div>
                <span style="background:#f59e0b20;color:#f59e0b;border:1px solid #f59e0b40;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">30-MIN SOS</span>
            </div>
            <div class="page-sub">Clients need urgent help — respond within 30 minutes</div>
            <div id="instantBanner" style="display:none;background:linear-gradient(135deg,#1a0f00,#1a1200);border:1px solid #f59e0b40;border-radius:12px;padding:14px 20px;margin-bottom:20px;align-items:center;gap:12px;">
                <span style="font-size:20px;">⏱️</span>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#f59e0b;">Active Instant Requests</div>
                    <div style="font-size:12px;color:#a0b4cc;margin-top:2px;">These expire in under 30 minutes — bid fast!</div>
                </div>
                <div id="instantCount" style="margin-left:auto;font-size:22px;font-weight:800;color:#f59e0b;"></div>
            </div>
            <div id="instantList">
                <div class="loading"><span class="spinner"></span>Loading instant requests...</div>
            </div>
        </div>

        <!-- SCHEDULED CASES PAGE 📅 -->
        <div class="page" id="page-scheduled">
            <div class="page-title">📅 Scheduled Cases</div>
            <div class="page-sub">Regular legal requests — clients have set a deadline for responses</div>
            <div id="scheduledList">
                <div class="loading"><span class="spinner"></span>Loading scheduled requests...</div>
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
            <div class="page-sub">Cases where your bid was accepted — mark as resolved once done</div>
            <div id="casesList">
                <div class="loading"><span class="spinner"></span>Loading...</div>
            </div>
        </div>

        <!-- EARNINGS PAGE -->
        <div class="page" id="page-earnings">
            <div class="page-title">💰 Earnings</div>
            <div class="page-sub">Your payment history and earnings summary</div>

            <!-- Summary cards -->
            <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
                <div class="stat-card" style="border-color:#22c55e30;">
                    <div class="label">Total Earned</div>
                    <div class="value" id="earnTotal" style="color:#22c55e;">৳0</div>
                    <div class="sub">After 2% platform fee</div>
                </div>
                <div class="stat-card" style="border-color:#f59e0b30;">
                    <div class="label">Pending Payments</div>
                    <div class="value" id="earnPending" style="color:#f59e0b;">0</div>
                    <div class="sub">Awaiting client payment</div>
                </div>
                <div class="stat-card" style="border-color:#ef444430;">
                    <div class="label">Disputed</div>
                    <div class="value" id="earnDisputed" style="color:#ef4444;">0</div>
                    <div class="sub">Payment not confirmed</div>
                </div>
            </div>

            <!-- Commission Due Box -->
            <div id="commissionBox" style="margin:18px 0;padding:20px 24px;background:linear-gradient(135deg,#0d1520,#111827);border:1.5px solid #4f9eff30;border-radius:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div style="font-size:12px;color:#6b7fa3;margin-bottom:4px;">Platform Commission Due</div>
                        <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;">
                            <span id="commDue" style="font-size:28px;font-weight:800;color:#f59e0b;">৳0</span>
                            <span id="commDueLabel" style="font-size:12px;color:#6b7fa3;">calculating...</span>
                        </div>
                        <div id="commPendingNote" style="display:none;font-size:11px;color:#4f9eff;margin-top:4px;"></div>
                    </div>
                    <button id="commPayBtn" onclick="openCommissionModal()"
                        style="background:linear-gradient(135deg,#4f9eff,#2563eb);color:#fff;border:none;border-radius:10px;padding:10px 22px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                        💳 Pay Commission
                    </button>
                </div>
                <!-- Commission history mini-list -->
                <div id="commHistory" style="margin-top:16px;border-top:1px solid #1e2d4a;padding-top:12px;display:none;"></div>
                <button onclick="toggleCommHistory()" style="margin-top:10px;background:none;border:none;color:#4f9eff;font-size:12px;cursor:pointer;padding:0;" id="commHistToggle">▼ Show payment history</button>
            </div>

            <!-- Payment history -->
            <div class="panel">
                <div class="panel-header"><h3>📋 Payment History</h3></div>
                <div class="panel-body" id="earningsHistory">
                    <div class="loading"><span class="spinner"></span>Loading...</div>
                </div>
            </div>
        </div>

        <!-- COMMISSION PAYMENT MODAL -->
        <div id="commModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
            <div style="background:#0d1520;border:1px solid #4f9eff30;border-radius:16px;padding:28px;width:90%;max-width:460px;max-height:90vh;overflow-y:auto;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <div style="font-size:16px;font-weight:700;">💳 Pay Platform Commission</div>
                    <button onclick="closeCommissionModal()" style="background:none;border:none;color:#6b7fa3;font-size:20px;cursor:pointer;">✕</button>
                </div>

                <div style="background:#0a1422;border:1px solid #1e2d4a;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                    <div style="font-size:12px;color:#6b7fa3;margin-bottom:4px;">Amount Due</div>
                    <div id="modalDueAmount" style="font-size:24px;font-weight:800;color:#f59e0b;">৳0</div>
                    <div style="font-size:11px;color:#6b7fa3;margin-top:2px;">You can pay partial or full amount</div>
                </div>

                <!-- Payment method -->
                <div style="margin-bottom:16px;">
                    <div style="font-size:12px;color:#6b7fa3;margin-bottom:8px;">Payment Method</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="methodBtns">
                        <button onclick="selectMethod('bkash')" class="method-btn" id="mth-bkash" data-number="01XXXXXXXXX">
                            <span style="font-size:18px;">📱</span> bKash
                        </button>
                        <button onclick="selectMethod('rocket')" class="method-btn" id="mth-rocket" data-number="01XXXXXXXXX">
                            <span style="font-size:18px;">🚀</span> Rocket
                        </button>
                        <button onclick="selectMethod('nagad')" class="method-btn" id="mth-nagad" data-number="01XXXXXXXXX">
                            <span style="font-size:18px;">💛</span> Nagad
                        </button>
                        <button onclick="selectMethod('bank')" class="method-btn" id="mth-bank" data-number="">
                            <span style="font-size:18px;">🏦</span> Bank Transfer
                        </button>
                    </div>
                </div>

                <!-- Payment details after method selected -->
                <div id="methodDetails" style="display:none;margin-bottom:16px;padding:14px;background:#0a1422;border:1px solid #1e2d4a;border-radius:10px;font-size:13px;">
                    <div id="methodInstructions" style="color:#8899b8;margin-bottom:10px;line-height:1.6;"></div>
                </div>

                <!-- Amount input -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px;color:#6b7fa3;display:block;margin-bottom:6px;">Amount (৳)</label>
                    <input type="number" id="commAmount" placeholder="Enter amount"
                        style="width:100%;background:#0a1422;border:1px solid #1e2d4a;border-radius:8px;padding:10px 14px;color:#e2e8f0;font-size:14px;box-sizing:border-box;">
                </div>

                <!-- Transaction ref -->
                <div style="margin-bottom:20px;">
                    <label style="font-size:12px;color:#6b7fa3;display:block;margin-bottom:6px;">Transaction Reference / TxID</label>
                    <input type="text" id="commTxRef" placeholder="e.g. 8N7A3K2L or TXN123456"
                        style="width:100%;background:#0a1422;border:1px solid #1e2d4a;border-radius:8px;padding:10px 14px;color:#e2e8f0;font-size:14px;box-sizing:border-box;">
                    <div style="font-size:11px;color:#6b7fa3;margin-top:4px;">Enter the transaction ID from your payment app</div>
                </div>

                <button onclick="submitCommissionPayment()" id="commSubmitBtn"
                    style="width:100%;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;">
                    ✅ Submit Payment
                </button>
                <div id="commSubmitMsg" style="display:none;margin-top:12px;font-size:13px;text-align:center;"></div>
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
    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
    ...(token() ? { 'Authorization': 'Bearer ' + token() } : {}),
});
const fetchOpts = (extra = {}) => ({ headers: headers(), credentials: 'include', ...extra });

// ── Boot ───────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
    const stored = localStorage.getItem('lawyer_data');
    if (stored) lawyerData = JSON.parse(stored);

    try {
        const res  = await fetch(`${API}/check-session`, fetchOpts());
        const data = await res.json();
        if (!data.loggedIn) { window.location.href = '/lawyer/login'; return; }
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
    document.getElementById('sidebarName').textContent  = lawyerData.full_name;
    document.getElementById('sidebarCode').textContent  = lawyerData.lawyer_code;
    document.getElementById('sidebarCity').textContent  = lawyerData.city || '';
    document.getElementById('welcomeMsg').textContent   = `Welcome back, ${lawyerData.full_name.split(' ')[0]}! Here's your overview.`;

    const avatarEl = document.getElementById('sidebarAvatar');
    if (lawyerData.profile_photo) avatarEl.innerHTML = `<img src="/${lawyerData.profile_photo}" alt="">`;

    updateAvailToggle(lawyerData.is_available);
    updateNotifBadge(lawyerData.unread_notifications || 0);
}

// ── Dashboard data ─────────────────────────────────────────
async function loadDashboard() {
    try {
        const res  = await fetch(`${API}/dashboard`, fetchOpts());
        const data = await res.json();
        if (!data.success) { window.location.href = '/lawyer/login'; return; }

        dashData   = data;
        lawyerData = { ...lawyerData, ...data.lawyer };

        const instantCount   = data.instant_requests?.length   || 0;
        const scheduledCount = data.scheduled_requests?.length || 0;
        const totalOpen      = instantCount + scheduledCount;

        document.getElementById('statOpen').textContent     = totalOpen;
        document.getElementById('statBids').textContent     = data.stats?.pending_bids  || 0;
        document.getElementById('statAccepted').textContent = data.stats?.accepted_bids || 0;

        // Load earnings for stat card
        loadEarningsSummary();

        const iBadge = document.getElementById('instantBadge');
        const iNav   = document.getElementById('nav-instant');
        if (instantCount > 0) {
            iBadge.textContent = instantCount; iBadge.style.display = 'inline';
            iNav.style.borderLeftColor = '#f59e0b'; iNav.style.color = '#f59e0b';
        } else {
            iBadge.style.display = 'none';
            iNav.style.borderLeftColor = 'transparent'; iNav.style.color = '';
        }

        const sBadge = document.getElementById('scheduledBadge');
        if (scheduledCount > 0) { sBadge.textContent = scheduledCount; sBadge.style.display = 'inline'; }
        else                    { sBadge.style.display = 'none'; }

        const combined = [...(data.instant_requests || []), ...(data.scheduled_requests || [])];
        renderRecentRequests(combined);

    } catch(e) { console.error(e); }
}

// ── Earnings summary (for stat card + earnings page) ───────
async function loadEarningsSummary() {
    try {
        const res  = await fetch(`${API}/earnings`, fetchOpts());
        const data = await res.json();
        if (!data.success) return;
        const s = data.summary;

        // Overview stat card
        const earnEl = document.getElementById('statEarned');
        if (earnEl) earnEl.textContent = '৳' + Number(s.total_earned).toLocaleString('en-BD');

    } catch(e) {}
}

async function loadEarningsPage() {
    try {
        const res  = await fetch(`${API}/earnings`, fetchOpts());
        const data = await res.json();
        if (!data.success) return;

        const s = data.summary;
        document.getElementById('earnTotal').textContent    = '৳' + Number(s.total_earned).toLocaleString('en-BD');
        document.getElementById('earnPending').textContent  = s.pending_payments;
        document.getElementById('earnDisputed').textContent = s.disputed_payments;

        const histEl = document.getElementById('earningsHistory');
        const payments = data.payments || [];

        if (!payments.length) {
            histEl.innerHTML = `<div class="empty"><div class="e-icon">💸</div><p>No payments yet. Complete cases and mark them as resolved to start earning.</p></div>`;
            return;
        }

        const statusBadge = {
            pending:   '<span class="pay-badge pay-pending">⏳ Pending</span>',
            claimed:   '<span class="pay-badge pay-claimed" style="animation:pulse 2s infinite;">🔔 Client Paid — Confirm</span>',
            confirmed: '<span class="pay-badge pay-confirmed">✅ Confirmed</span>',
            disputed:  '<span class="pay-badge pay-disputed">⚠️ Disputed</span>',
            overdue:   '<span class="pay-badge pay-disputed">❌ Overdue</span>',
        };

        // ── Per-status action block builder ──────────────────────
        function actionBlock(p) {
            if (p.status === 'confirmed') {
                return `<div style="padding:10px 0 4px;display:flex;align-items:center;gap:8px;color:#22c55e;font-size:12px;font-weight:600;">
                    <span>✅ Payment complete. ৳${Number(p.net_amount).toLocaleString('en-BD')} added to your earnings.</span>
                </div>`;
            }

            if (p.status === 'claimed') {
                return `<div id="earn-action-${p.request_id}" style="margin-top:12px;background:linear-gradient(135deg,#1a1000,#1a0d00);border:1px solid #f59e0b40;border-radius:10px;padding:16px 18px;">
                    <div style="font-size:12px;color:#f59e0b;font-weight:700;margin-bottom:6px;">💰 Client says they paid — Please Confirm</div>
                    <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;">৳${Number(p.gross_amount).toLocaleString('en-BD')}</div>
                    <div style="font-size:12px;color:#8899b8;margin-bottom:12px;">Did you actually receive this payment from the client?</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn-pay-yes" id="pyes-earn-${p.request_id}"
                            onclick="respondToPayment('${p.request_id}', true, this, document.getElementById('pno-earn-${p.request_id}'))">
                            ✅ Yes, I received it
                        </button>
                        <button class="btn-pay-no" id="pno-earn-${p.request_id}"
                            onclick="respondToPayment('${p.request_id}', false, document.getElementById('pyes-earn-${p.request_id}'), this)">
                            ❌ No, I didn't receive
                        </button>
                    </div>
                </div>`;
            }

            if (p.status === 'pending') {
                return `<div id="earn-action-${p.request_id}" style="margin-top:10px;padding:12px 14px;background:rgba(245,158,11,0.05);border:1px solid #f59e0b25;border-radius:10px;">
                    <div style="font-size:12px;color:#f59e0b;font-weight:600;margin-bottom:8px;">⏳ Awaiting client payment — deadline: ${p.payment_deadline ? new Date(p.payment_deadline).toLocaleDateString('en-BD',{day:'numeric',month:'short',year:'numeric'}) : 'N/A'}</div>
                    <div style="font-size:12px;color:#8899b8;margin-bottom:10px;">If the client has not paid after the deadline, you can flag this as a dispute.</div>
                    <button class="btn-pay-no" id="btn-dispute-${p.request_id}"
                        onclick="disputePendingPayment('${p.request_id}', this)"
                        style="font-size:12px;padding:8px 16px;">
                        ⚠️ Flag as Unpaid / Dispute
                    </button>
                </div>`;
            }

            if (p.status === 'disputed') {
                return `<div id="earn-action-${p.request_id}" style="margin-top:10px;padding:12px 14px;background:rgba(239,68,68,0.05);border:1px solid #ef444425;border-radius:10px;">
                    <div style="font-size:12px;color:#ef4444;font-weight:600;margin-bottom:8px;">⚠️ This payment is under dispute</div>
                    <div style="font-size:12px;color:#8899b8;margin-bottom:10px;">Contact admin for resolution. You can also email <strong style="color:#4f9eff;">admin@safevoice.com</strong> with your case details and payment proof.</div>
                    <button class="btn-pay-yes" id="btn-admin-${p.request_id}"
                        onclick="contactAdminForDispute('${p.request_id}', this)"
                        style="font-size:12px;padding:8px 16px;background:#4f9eff;">
                        📩 Notify Admin
                    </button>
                </div>`;
            }

            return '';
        }

        histEl.innerHTML = `
        <table class="earnings-table">
            <thead>
                <tr>
                    <th>Payment Code</th>
                    <th>Case</th>
                    <th>Client</th>
                    <th>Fee</th>
                    <th>You Earn</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                ${payments.map(p => `
                <tr id="pay-row-${p.request_id}" style="vertical-align:top;">
                    <td style="font-family:monospace;font-size:11px;color:#4f9eff;">${p.payment_code}</td>
                    <td>
                        <div style="font-size:13px;font-weight:600;">${capitalize(p.issue_type || 'Case')}</div>
                        <div style="font-size:11px;color:#6b7fa3;font-family:monospace;">${p.request_id || ''}</div>
                    </td>
                    <td style="font-size:13px;">${p.client_name || 'Client'}</td>
                    <td style="font-size:13px;">৳${Number(p.gross_amount).toLocaleString()}</td>
                    <td style="font-size:14px;font-weight:700;color:#22c55e;">৳${Number(p.net_amount).toLocaleString()}</td>
                    <td>${statusBadge[p.status] || p.status}</td>
                    <td style="font-size:12px;color:#6b7fa3;">${timeAgo(p.created_at)}</td>
                </tr>
                <tr id="pay-action-row-${p.request_id}">
                    <td colspan="7" style="padding:0 14px 14px;">
                        ${actionBlock(p)}
                    </td>
                </tr>
                `).join('')}
            </tbody>
        </table>`;
    } catch(e) {
        document.getElementById('earningsHistory').innerHTML = `<div class="empty"><p>Error loading earnings.</p></div>`;
    }
}

// ── ⚠️ Lawyer flags pending payment as disputed ────────────
async function disputePendingPayment(requestId, btn) {
    if (!confirm(`Flag this payment as UNPAID/DISPUTED?\n\nThe client will receive a warning. Only do this if the payment deadline has passed.`)) return;

    btn.disabled = true;
    btn.textContent = '⏳ Flagging...';

    try {
        const res  = await fetch(`/api/case-payment/${requestId}/dispute-pending`, fetchOpts({ method: 'POST' }));
        const data = await res.json();

        if (data.success) {
            showToast('⚠️ Payment flagged as disputed. Client has been warned.', 'warning');
            loadEarningsPage();
            loadCases();
        } else {
            showToast(data.message || 'Failed to flag payment.', 'error');
            btn.disabled = false;
            btn.textContent = '⚠️ Flag as Unpaid / Dispute';
        }
    } catch(e) {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.textContent = '⚠️ Flag as Unpaid / Dispute';
    }
}

// ── 📩 Lawyer contacts admin about disputed payment ────────
async function contactAdminForDispute(requestId, btn) {
    if (!confirm(`Notify admin about this dispute?\n\nAdmin will be alerted to review your case. You can do this once every 24 hours.`)) return;

    btn.disabled = true;
    btn.textContent = '⏳ Sending...';

    try {
        const res  = await fetch(`/api/case-payment/${requestId}/contact-admin`, fetchOpts({ method: 'POST' }));
        const data = await res.json();

        if (data.success) {
            showToast('📩 Admin notified! They will review within 24 hours. You can also email admin@safevoice.com', 'success');
            btn.textContent = '✅ Admin Notified';
        } else {
            showToast(data.message || 'Failed to contact admin.', 'error');
            btn.disabled = false;
            btn.textContent = '📩 Notify Admin';
        }
    } catch(e) {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.textContent = '📩 Notify Admin';
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
    const urgent  = r.is_urgent;
    const instant = r.is_instant;
    const typeCap = (r.issue_type || '').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
    const budgetTxt = r.budget_max ? `৳${Number(r.budget_max).toLocaleString()}` : 'No budget set';
    const ago = timeAgo(r.created_at);
    const rid = (prefix ? prefix + '-' : '') + r.request_id;

    let deadlineHtml = '';
    if (r.deadline) {
        const diff = new Date(r.deadline) - new Date();
        if (diff > 0) {
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const pad = n => String(n).padStart(2,'0');
            const timeLeft = h > 0 ? `${h}h ${pad(m)}m` : `${pad(m)}m left`;
            const urgentColor = diff < 3600000 ? '#ef4444' : (instant ? '#fbbf24' : '#a0b4cc');
            deadlineHtml = `<span style="color:${urgentColor};font-size:11px;font-weight:600;"><i class="fas fa-hourglass-half"></i> ${timeLeft}</span>`;
        }
    }

    const minDate = new Date(Date.now() + 5*60*60*1000);
    const pad = n => String(n).padStart(2,'0');
    const minDT = `${minDate.getFullYear()}-${pad(minDate.getMonth()+1)}-${pad(minDate.getDate())}T${pad(minDate.getHours())}:${pad(minDate.getMinutes())}`;

    return `
    <div class="request-card ${urgent?'urgent':''}" id="rc-${rid}" ${instant ? 'style="border-color:#fbbf2460;background:linear-gradient(135deg,#0d1526,#1a1200);"' : ''}>
        <div class="req-top">
            <div>
                <div class="req-type">${typeCap}</div>
                <div class="req-id">${r.request_id}</div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;flex-wrap:wrap;align-items:center;">
                ${instant ? '<span class="req-badge" style="background:#fbbf2420;color:#fbbf24;border:1px solid #fbbf2440;">⚡ INSTANT</span>' : ''}
                ${urgent && !instant ? '<span class="req-badge badge-urgent">🚨 URGENT</span>' : ''}
                <span class="req-badge badge-open">Open</span>
            </div>
        </div>
        <div class="req-desc">${r.description}</div>
        ${r.document_paths && r.document_paths.length > 0 ? `
        <div style="margin:8px 0;display:flex;flex-wrap:wrap;gap:6px;">
            ${r.document_paths.map((p,i) => {
                const url = '/storage/' + p;
                const ext = p.split('.').pop().toLowerCase();
                const isPdf = ext === 'pdf';
                return `<a href="${url}" target="_blank" style="display:inline-flex;align-items:center;gap:4px;background:#0a1422;border:1px solid #1e2d4a;border-radius:6px;padding:4px 10px;font-size:11px;color:#4f9eff;text-decoration:none;">
                    ${isPdf ? '📄' : '🖼️'} Document ${i+1}
                </a>`;
            }).join('')}
        </div>` : ''}
        <div class="req-meta">
            ${r.location ? `<span><i class="fas fa-map-marker-alt"></i>${r.location}</span>` : ''}
            <span><i class="fas fa-wallet"></i>Budget: ${budgetTxt}</span>
            <span><i class="fas fa-users"></i>${r.bid_count || 0} bid(s) so far</span>
            <span><i class="fas fa-clock"></i>${ago}</span>
            ${deadlineHtml}
        </div>
        <div class="req-actions">
            <button class="btn btn-primary btn-sm" onclick="toggleBidForm('${rid}')">💬 Place Bid</button>
            ${compact ? `<button class="btn btn-outline btn-sm" onclick="switchPage('instant')">View All →</button>` : ''}
        </div>
        <div class="bid-form" id="bf-${rid}">
            <h4>📝 Your Bid for: ${typeCap}</h4>
            ${instant
                ? `<div style="font-size:12px;color:#fbbf24;background:#1a120060;border:1px solid #fbbf2440;border-radius:6px;padding:8px 12px;margin-bottom:12px;">⚡ <b>Instant Request</b> — Client needs immediate help.</div>`
                : `<div style="font-size:12px;color:#6b7fa3;background:#0a102060;border:1px solid #1e2d4a;border-radius:6px;padding:8px 12px;margin-bottom:12px;">📅 <b>Scheduled Request</b> — Client has set a schedule.</div>`
            }
            <div class="bid-row">
                <div>
                    <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Your Fee (৳) *</label>
                    <input type="number" id="fee-${rid}" placeholder="e.g. 2000" min="100">
                </div>
            </div>
            <div style="margin-bottom:10px">
                <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:6px">Office Address <span style="color:#ef4444;">*</span></label>
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
    document.getElementById('nav-'  + name)?.classList.add('active');

    if (name === 'instant' || name === 'requests') { name = 'instant'; loadInstantRequests(); }
    if (name === 'scheduled')     loadScheduledRequests();
    if (name === 'my-bids')       loadMyBids();
    if (name === 'cases')         loadCases();
    if (name === 'earnings')      { loadEarningsPage(); loadCommissionSummary(); }
    if (name === 'notifications') loadNotifications();
}

// ── ⚡ Instant Requests ────────────────────────────────────
async function loadInstantRequests() {
    const el = document.getElementById('instantList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/requests/instant`, fetchOpts());
        const data = await res.json();
        const reqs = data.requests || [];

        const banner  = document.getElementById('instantBanner');
        const countEl = document.getElementById('instantCount');
        if (reqs.length > 0) { banner.style.display = 'flex'; countEl.textContent = reqs.length + ' active'; }
        else                 { banner.style.display = 'none'; }

        el.innerHTML = reqs.length
            ? reqs.map(r => requestCard(r, false, 'inst')).join('')
            : `<div class="empty"><div class="e-icon">⚡</div><p>No instant requests right now.</p></div>`;

        setTimeout(() => {
            if (document.querySelector('.page.active')?.id === 'page-instant') loadInstantRequests();
        }, 30000);

    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading instant requests.</p></div>`; }
}

// ── 📅 Scheduled Requests ──────────────────────────────────
async function loadScheduledRequests() {
    const el = document.getElementById('scheduledList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res  = await fetch(`${API}/requests/scheduled`, fetchOpts());
        const data = await res.json();
        let reqs   = data.requests?.data || data.requests || [];

        reqs.sort((a, b) => {
            if (a.is_urgent && !b.is_urgent) return -1;
            if (!a.is_urgent && b.is_urgent) return 1;
            if (a.deadline && b.deadline) return new Date(a.deadline) - new Date(b.deadline);
            return 0;
        });

        el.innerHTML = reqs.length
            ? reqs.map(r => requestCard(r, false, 'sched')).join('')
            : `<div class="empty"><div class="e-icon">📅</div><p>No scheduled requests in your area right now.</p></div>`;
    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading scheduled requests.</p></div>`; }
}

// ── My Bids ────────────────────────────────────────────────
async function loadMyBids() {
    const el = document.getElementById('myBidsList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res   = await fetch(`${API}/dashboard`, fetchOpts());
        const data  = await res.json();
        const bids  = data.active_bids || [];

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

// ── Cases (with Resolved button) ──────────────────────────
async function loadCases() {
    const el = document.getElementById('casesList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res   = await fetch(`${API}/dashboard`, fetchOpts());
        const data  = await res.json();
        const cases = data.accepted_cases || [];

        if (!cases.length) {
            el.innerHTML = `<div class="empty"><div class="e-icon">📁</div><p>No accepted cases yet. Keep bidding!</p></div>`;
            return;
        }

        el.innerHTML = cases.map(c => {
            const reqId      = c.legal_request?.request_id || '';
            const reqStatus  = c.legal_request?.status     || '';
            const isInstant  = c.legal_request?.is_instant || false;
            const payStatus  = c.payment?.status || '';

            // Payment status badge — payment table status কে priority দাও
            let payStatusBadge = '';
            if (reqStatus === 'completed' || payStatus === 'confirmed') {
                payStatusBadge = '<span class="pay-badge pay-confirmed">✅ Payment Done</span>';
            } else if (payStatus === 'claimed') {
                payStatusBadge = '<span class="pay-badge pay-claimed" style="animation:pulse 2s infinite;cursor:pointer;" onclick="toggleCasePayConfirm(\''+reqId+'\')">🔔 Client Paid — Confirm?</span>';
            } else if (reqStatus === 'resolved_pending_payment' || payStatus === 'pending') {
                payStatusBadge = '<span class="pay-badge pay-pending">⏳ Awaiting Payment</span>';
            } else if (reqStatus === 'payment_disputed' || payStatus === 'disputed') {
                payStatusBadge = '<span class="pay-badge pay-disputed">⚠️ Payment Disputed</span>';
            }

            // Show "Mark as Resolved" only for active cases not yet resolved
            const showResolveBtn = ['accepted', 'in_progress'].includes(reqStatus);
            const resolveBtn = showResolveBtn
                ? `<button class="btn-resolve" onclick="resolveCase('${reqId}', this)">✅ Mark as Resolved</button>`
                : '';

            // Yes/No confirm block — only when payment is claimed
            const claimedConfirmBlock = (payStatus === 'claimed') ? `
            <div id="case-pay-confirm-${reqId}" style="display:none;margin-top:12px;background:linear-gradient(135deg,#0d1a0d,#0a1500);border:1px solid #f59e0b40;border-radius:10px;padding:16px 18px;">
                <div style="font-size:12px;color:#f59e0b;font-weight:700;margin-bottom:6px;">💰 Client says they paid — Please Confirm</div>
                <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:4px;">৳${Number(c.payment?.gross_amount || c.proposed_fee).toLocaleString('en-BD')}</div>
                <div style="font-size:12px;color:#8899b8;margin-bottom:12px;">Did you actually receive this payment from the client?</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn-pay-yes" id="case-pyes-${reqId}"
                        onclick="respondToPayment('${reqId}', true, this, document.getElementById('case-pno-${reqId}'))">
                        ✅ Yes, I received it
                    </button>
                    <button class="btn-pay-no" id="case-pno-${reqId}"
                        onclick="respondToPayment('${reqId}', false, document.getElementById('case-pyes-${reqId}'), this)">
                        ❌ No, I didn't receive
                    </button>
                </div>
            </div>` : '';

            return `
            <div class="request-card" data-case-id="${reqId}">
                <div class="req-top">
                    <div>
                        <div class="req-type">${capitalize(c.legal_request?.issue_type || 'Case')}</div>
                        <div class="req-id">${reqId}</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                        ${isInstant ? '<span class="req-badge" style="background:#fbbf2420;color:#fbbf24;border:1px solid #fbbf2440;">⚡ Instant</span>' : ''}
                        ${payStatusBadge || '<span class="req-badge badge-accepted">✅ Accepted</span>'}
                    </div>
                </div>
                <div class="req-meta">
                    ${c.legal_request?.user_name  ? `<span><i class="fas fa-user"></i>Client: ${c.legal_request.user_name}</span>` : ''}
                    ${c.legal_request?.user_phone ? `<span><i class="fas fa-phone"></i>${c.legal_request.user_phone}</span>` : ''}
                    <span><i class="fas fa-wallet"></i>Fee: <strong style="color:var(--success)">৳${Number(c.proposed_fee).toLocaleString()}</strong></span>
                    <span style="color:#6b7fa3;font-size:11px;">You earn ৳${Math.floor(c.proposed_fee * 0.98).toLocaleString()} after 2% commission</span>
                    <span><i class="fas fa-calendar"></i>${timeAgo(c.bid_at)}</span>
                </div>
                <div class="req-actions">
                    ${resolveBtn}
                </div>
                ${claimedConfirmBlock}
            </div>`;
        }).join('');

    } catch(e) { el.innerHTML = `<div class="empty"><p>Error loading cases.</p></div>`; }
}

// ── ✅ Resolve Case — STEP 1 ───────────────────────────────

// ── Toggle payment confirm row in earnings table ───────────
function togglePayConfirm(requestId) {
    const row = document.getElementById('pay-confirm-row-' + requestId);
    if (!row) return;
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

// ── Toggle payment confirm block in active cases ───────────
function toggleCasePayConfirm(requestId) {
    const block = document.getElementById('case-pay-confirm-' + requestId);
    if (!block) return;
    block.style.display = block.style.display === 'none' ? 'block' : 'none';
}

// ══════════════════════════════════════════════════════════
// COMMISSION SYSTEM
// ══════════════════════════════════════════════════════════

let _commDue = 0;
const PAYMENT_NUMBERS = {
    bkash:  '01700-000000',   // আপনার actual bKash নম্বর দিন
    rocket: '01700-000000',   // আপনার actual Rocket নম্বর দিন
    nagad:  '01700-000000',   // আপনার actual Nagad নম্বর দিন
    bank:   'Bank: SafeVoice Ltd | A/C: 1234567890 | Bank: Dutch-Bangla | Branch: Dhanmondi',
};

async function loadCommissionSummary() {
    try {
        const res  = await fetch(`${API}/commission/summary`, fetchOpts());
        const data = await res.json();
        if (!data.success) return;

        const s = data.summary;
        _commDue = s.due;

        const dueEl    = document.getElementById('commDue');
        const labelEl  = document.getElementById('commDueLabel');
        const noteEl   = document.getElementById('commPendingNote');
        const modalDue = document.getElementById('modalDueAmount');
        const payBtn   = document.getElementById('commPayBtn');

        dueEl.textContent = '৳' + Number(s.due).toLocaleString('en-BD', {minimumFractionDigits:2});

        if (s.balance < 0) {
            // Advance payment — lawyer এগিয়ে দিয়েছে
            labelEl.textContent = `৳${Math.abs(s.balance).toLocaleString('en-BD', {minimumFractionDigits:2})} advance credit`;
            labelEl.style.color = '#22c55e';
            dueEl.style.color   = '#22c55e';
        } else if (s.due === 0) {
            labelEl.textContent = 'All clear ✅';
            labelEl.style.color = '#22c55e';
            dueEl.style.color   = '#22c55e';
        } else {
            labelEl.textContent = `of ৳${Number(s.total_accrued).toLocaleString('en-BD',{minimumFractionDigits:2})} total accrued`;
            labelEl.style.color = '#6b7fa3';
            dueEl.style.color   = '#f59e0b';
        }

        if (s.pending_amount > 0) {
            noteEl.style.display = 'block';
            noteEl.textContent   = `⏳ ৳${Number(s.pending_amount).toLocaleString('en-BD',{minimumFractionDigits:2})} pending admin approval`;
        } else {
            noteEl.style.display = 'none';
        }

        if (modalDue) modalDue.textContent = '৳' + Number(s.due).toLocaleString('en-BD',{minimumFractionDigits:2});
        if (payBtn)   payBtn.disabled = false;

        // Commission history mini list
        const hist    = data.history || [];
        const histEl  = document.getElementById('commHistory');
        const togBtn  = document.getElementById('commHistToggle');
        if (hist.length && histEl) {
            const statusColor = { pending:'#f59e0b', approved:'#22c55e', rejected:'#ef4444' };
            histEl.innerHTML = hist.slice(0,5).map(h => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #1e2d4a20;font-size:12px;">
                    <span style="font-family:monospace;color:#4f9eff;">${h.ref_code}</span>
                    <span style="color:#8899b8;">${h.method.toUpperCase()}</span>
                    <span style="font-weight:700;">৳${Number(h.amount).toLocaleString()}</span>
                    <span style="color:${statusColor[h.status]||'#8899b8'};font-weight:600;">${h.status.charAt(0).toUpperCase()+h.status.slice(1)}</span>
                </div>`).join('');
            togBtn && (togBtn.style.display = 'block');
        } else if (togBtn) {
            togBtn.style.display = 'none';
        }

    } catch(e) { console.error('Commission summary error:', e); }
}

function toggleCommHistory() {
    const el  = document.getElementById('commHistory');
    const btn = document.getElementById('commHistToggle');
    if (!el) return;
    const open = el.style.display === 'block';
    el.style.display  = open ? 'none' : 'block';
    btn.textContent   = open ? '▼ Show payment history' : '▲ Hide payment history';
}

let _selectedMethod = null;

function openCommissionModal() {
    _selectedMethod = null;
    document.getElementById('commModal').style.display = 'flex';
    document.getElementById('methodDetails').style.display = 'none';
    document.getElementById('commAmount').value   = _commDue > 0 ? _commDue.toFixed(2) : '';
    document.getElementById('commTxRef').value    = '';
    document.getElementById('commSubmitMsg').style.display = 'none';
    document.getElementById('commSubmitBtn').disabled = false;
    document.getElementById('commSubmitBtn').textContent = '✅ Submit Payment';
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('modalDueAmount').textContent = '৳' + Number(_commDue).toLocaleString('en-BD',{minimumFractionDigits:2});
}

function closeCommissionModal() {
    document.getElementById('commModal').style.display = 'none';
}

function selectMethod(method) {
    _selectedMethod = method;
    document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('mth-' + method)?.classList.add('selected');

    const detailsEl = document.getElementById('methodDetails');
    const instrEl   = document.getElementById('methodInstructions');
    detailsEl.style.display = 'block';

    const num = PAYMENT_NUMBERS[method] || '';
    if (method === 'bank') {
        instrEl.innerHTML = `<strong style="color:#e2e8f0;">Bank Transfer Details:</strong><br>${num}<br><br>Send the exact due amount and use your Lawyer Code as reference.`;
    } else {
        instrEl.innerHTML = `Send to <strong style="color:#e2e8f0;">${num}</strong> (${method.charAt(0).toUpperCase()+method.slice(1)} Personal/Merchant)<br><br>After sending, enter the transaction ID below.`;
    }
}

async function submitCommissionPayment() {
    if (!_selectedMethod) { showToast('Please select a payment method.', 'error'); return; }

    const amount = parseFloat(document.getElementById('commAmount').value);
    const txRef  = document.getElementById('commTxRef').value.trim();

    if (!amount || amount < 1) { showToast('Enter a valid amount.', 'error'); return; }
    if (!txRef)                 { showToast('Enter your transaction reference.', 'error'); return; }

    const btn = document.getElementById('commSubmitBtn');
    btn.disabled    = true;
    btn.textContent = '⏳ Submitting...';

    try {
        const res  = await fetch(`${API}/commission/pay`, fetchOpts({
            method: 'POST',
            body: JSON.stringify({ amount, method: _selectedMethod, transaction_ref: txRef }),
        }));
        const data = await res.json();

        const msgEl = document.getElementById('commSubmitMsg');
        msgEl.style.display = 'block';

        if (data.success) {
            msgEl.style.color   = '#22c55e';
            msgEl.textContent   = `✅ Submitted! Ref: ${data.ref_code}. Admin will verify within 24 hours.`;
            btn.textContent     = '✅ Submitted';
            loadCommissionSummary();
            setTimeout(closeCommissionModal, 3000);
        } else {
            msgEl.style.color   = '#ef4444';
            msgEl.textContent   = data.message || 'Submission failed.';
            btn.disabled        = false;
            btn.textContent     = '✅ Submit Payment';
        }
    } catch(e) {
        showToast('Network error.', 'error');
        btn.disabled    = false;
        btn.textContent = '✅ Submit Payment';
    }
}

// Close modal on backdrop click
document.getElementById('commModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCommissionModal();
});

async function resolveCase(requestId, btn) {
    if (!confirm(`Mark this case as RESOLVED?\n\nThe client will be notified to pay within 3 days. Only confirm if the legal service is complete.`)) return;

    btn.disabled = true;
    btn.textContent = '⏳ Sending...';

    try {
        const res  = await fetch(`/api/case-payment/${requestId}/resolve`, fetchOpts({ method: 'POST' }));
        const data = await res.json();

        if (data.success) {
            const net = Number(data.net_amount).toLocaleString('en-BD');
            showToast(`✅ Case resolved! Client notified to pay ৳${Number(data.amount).toLocaleString()} within 3 days. You'll earn ৳${net}.`, 'success');
            loadCases();
            loadEarningsSummary();
        } else {
            showToast(data.message || 'Failed to resolve case.', 'error');
            btn.disabled = false;
            btn.textContent = '✅ Mark as Resolved';
        }
    } catch(e) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.textContent = '✅ Mark as Resolved';
    }
}

// ── 💰 Lawyer responds to payment claim — STEP 3 ──────────
async function respondToPayment(requestId, received, yesBtn, noBtn) {
    const label = received ? 'YES, I received the payment' : 'NO, I did NOT receive payment';
    if (!confirm(`Confirm: ${label}?\n\n${received ? 'Your earnings will be updated.' : 'The client will be warned and given 48 hours to provide proof.'}`)) return;

    if (yesBtn) yesBtn.disabled = true;
    if (noBtn)  noBtn.disabled  = true;

    try {
        const res  = await fetch(`/api/case-payment/${requestId}/payment-response`, fetchOpts({
            method: 'POST',
            body: JSON.stringify({ received }),
        }));
        const data = await res.json();

        if (data.success) {
            if (received) {
                showToast(`✅ Payment confirmed! You earned ৳${Number(data.net_earned).toLocaleString('en-BD')} (after ৳${Number(data.commission).toLocaleString()} commission).`, 'success');
                loadEarningsSummary();
            } else {
                showToast('⚠️ Dispute recorded. Client has been warned.', 'warning');
            }
            // Reload both pages to reflect updated status
            loadCases();
            loadEarningsPage();
            // Hide inline confirmation cards
            document.getElementById(`pay-confirm-${requestId}`)?.remove();
            document.getElementById(`case-pay-confirm-${requestId}`)?.remove();
        } else {
            showToast(data.message || 'Error.', 'error');
            if (yesBtn) yesBtn.disabled = false;
            if (noBtn)  noBtn.disabled  = false;
        }
    } catch(e) {
        showToast('Network error.', 'error');
        if (yesBtn) yesBtn.disabled = false;
        if (noBtn)  noBtn.disabled  = false;
    }
}

// ── Notifications (with payment confirmation cards) ────────
async function loadNotifications() {
    const el = document.getElementById('notifList');
    el.innerHTML = '<div class="loading"><span class="spinner"></span>Loading...</div>';
    try {
        const res    = await fetch(`${API}/notifications`, fetchOpts());
        const data   = await res.json();
        const notifs = data.notifications || [];

        updateNotifBadge(0);
        document.getElementById('notifBadge').style.display = 'none';

        const icons = {
            new_request:     '⚖️',
            bid_accepted:    '🎉',
            bid_rejected:    '❌',
            payment_claimed: '💰',
        };

        el.innerHTML = notifs.length ? notifs.map(n => {
            const extra = n.data ? (typeof n.data === 'string' ? JSON.parse(n.data || '{}') : n.data) : {};

            // Payment confirmation card — Yes/No buttons দেখাবে
            let payConfirmHtml = '';
            if (n.type === 'payment_claimed' && extra.action_type === 'payment_confirmation' && extra.request_id) {
                payConfirmHtml = `
                <div class="pay-confirm-card" id="pay-confirm-${extra.request_id}">
                    <div style="font-size:12px;color:#6b7fa3;margin-bottom:6px;">💰 Payment Confirmation Required</div>
                    <div class="pay-amount">৳${Number(extra.amount || 0).toLocaleString('en-BD')}</div>
                    <div style="font-size:13px;color:#8899b8;margin-top:6px;">Did you receive this payment from the client?</div>
                    <div class="pay-confirm-actions">
                        <button class="btn-pay-yes" id="pyes-${extra.request_id}"
                            onclick="respondToPayment('${extra.request_id}', true, this, document.getElementById('pno-${extra.request_id}'))">
                            ✅ Yes, I received it
                        </button>
                        <button class="btn-pay-no" id="pno-${extra.request_id}"
                            onclick="respondToPayment('${extra.request_id}', false, document.getElementById('pyes-${extra.request_id}'), this)">
                            ❌ No, I didn't receive
                        </button>
                    </div>
                </div>`;
            }

            return `
            <div class="notif-item ${n.is_read ? '' : 'notif-unread'}">
                <div class="notif-icon ${n.type}">${icons[n.type] || '🔔'}</div>
                <div style="flex:1;">
                    <div class="notif-title">${n.title}</div>
                    <div class="notif-body">${n.body || ''}</div>
                    ${extra.request_id && n.type !== 'payment_claimed' ? `<div class="notif-body" style="margin-top:4px;color:var(--accent);font-size:11px;">Case: ${extra.request_id}</div>` : ''}
                    <div class="notif-time">${timeAgo(n.created_at)}</div>
                    ${payConfirmHtml}
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
    if (!isOpen) { form.classList.add('show'); form.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
}

async function submitBid(requestId) {
    const fee    = document.getElementById('fee-'    + requestId)?.value;
    const office = document.getElementById('office-' + requestId)?.value?.trim();
    const note   = document.getElementById('note-'   + requestId)?.value;

    const actualRequestId = requestId.replace(/^(ov|inst|sched)-/, '');

    if (!fee || parseFloat(fee) < 100) { showToast('Enter a valid fee (min ৳100)', 'error'); return; }
    if (!office) { showToast('Office address is required.', 'error'); return; }

    try {
        const res  = await fetch(`${API}/bid`, {
            method: 'POST', headers: headers(), credentials: 'include',
            body: JSON.stringify({ request_id: actualRequestId, proposed_fee: parseFloat(fee), cover_note: note || null, office_address: office }),
        });
        const data = await res.json();
        if (!res.ok) { showToast(data.message || ('Server error HTTP ' + res.status), 'error'); return; }

        if (data.success) {
            showToast('✅ Bid submitted! Moving to My Bids...', 'success');
            document.getElementById('bf-' + requestId).classList.remove('show');
            document.getElementById('rc-' + requestId)?.remove();
            await loadDashboard();
            switchPage('my-bids');
        } else {
            showToast(data.message || 'Failed to submit bid.', 'error');
        }
    } catch(e) { showToast('Error: ' + (e.message || 'Unknown'), 'error'); }
}

// ── Availability toggle ────────────────────────────────────
async function toggleAvailability() {
    try {
        const res  = await fetch(`${API}/toggle-availability`, fetchOpts({ method: 'POST' }));
        const data = await res.json();
        if (data.success) { updateAvailToggle(data.is_available); showToast(data.message, data.is_available ? 'success' : 'error'); }
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
        const res  = await fetch(`${API}/notifications/unread-count`, fetchOpts());
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
    await fetch(`${API}/logout`, fetchOpts({ method: 'POST' })).catch(()=>{});
    localStorage.removeItem('lawyer_token');
    localStorage.removeItem('lawyer_data');
    window.location.href = '/lawyer/login';
}

// ── Helpers ────────────────────────────────────────────────
function capitalize(s) { return (s || '').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()); }

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 4000);
}
</script>
@endsection