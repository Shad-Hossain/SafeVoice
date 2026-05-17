<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — SafeVoice</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:        #07090f;
    --surface:   #0d1117;
    --card:      #111827;
    --border:    #1f2937;
    --border2:   #374151;
    --gold:      #f59e0b;
    --gold-dim:  #f59e0b18;
    --gold-glow: #f59e0b40;
    --blue:      #3b82f6;
    --blue-dim:  #3b82f618;
    --green:     #10b981;
    --green-dim: #10b98118;
    --red:       #ef4444;
    --red-dim:   #ef444418;
    --purple:    #a855f7;
    --purple-dim:#a855f718;
    --text:      #f9fafb;
    --text-2:    #9ca3af;
    --text-3:    #6b7280;
    --radius:    14px;
    --sidebar-w: 230px;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── TOPBAR ── */
.topbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    height: 60px;
    background: rgba(7,9,15,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; padding: 0 24px;
    gap: 16px;
}
.topbar-logo {
    display: flex; align-items: center; gap: 10px;
    font-family: 'Syne', sans-serif; font-weight: 800; font-size: 17px;
    color: var(--text); text-decoration: none;
    margin-right: auto;
}
.topbar-logo .crown { color: var(--gold); font-size: 18px; }
.topbar-badge {
    background: var(--gold-dim);
    border: 1px solid var(--gold-glow);
    color: var(--gold);
    font-size: 10px; font-weight: 700;
    padding: 3px 8px; border-radius: 6px;
    letter-spacing: .6px; text-transform: uppercase;
}
.topbar-btn {
    background: transparent; border: 1px solid var(--border);
    color: var(--text-2); font-size: 12px; font-weight: 600;
    padding: 7px 14px; border-radius: 8px; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: .2s;
}
.topbar-btn:hover { border-color: var(--border2); color: var(--text); }
.topbar-btn.danger:hover { border-color: var(--red); color: var(--red); }

/* ── LAYOUT ── */
.layout { display: flex; padding-top: 60px; min-height: 100vh; }

/* ── SIDEBAR ── */
.sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    position: fixed; top: 60px; left: 0; bottom: 0;
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    overflow-y: auto; z-index: 90;
}
.sidebar-section { padding: 20px 14px 8px; }
.sidebar-label {
    font-size: 10px; font-weight: 700; color: var(--text-3);
    text-transform: uppercase; letter-spacing: 1px; padding: 0 8px;
    margin-bottom: 6px;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    color: var(--text-2); font-size: 13px; font-weight: 500;
    cursor: pointer; transition: .18s; text-decoration: none;
    margin-bottom: 2px; border: 1px solid transparent;
}
.nav-item:hover { background: var(--card); color: var(--text); }
.nav-item.active {
    background: var(--gold-dim);
    border-color: var(--gold-glow);
    color: var(--gold); font-weight: 600;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; }
.sidebar-footer {
    margin-top: auto; padding: 16px;
    border-top: 1px solid var(--border);
}
.user-pill {
    display: flex; align-items: center; gap: 10px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: 10px 12px;
}
.user-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--gold-dim); border: 1.5px solid var(--gold-glow);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); font-size: 13px; flex-shrink: 0;
}
.user-name { font-size: 13px; font-weight: 600; }
.user-role { font-size: 11px; color: var(--text-3); margin-top: 1px; }

/* ── MAIN ── */
.main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 28px;
    min-height: calc(100vh - 60px);
}
.section { display: none; }
.section.active { display: block; }

/* ── PAGE HEADER ── */
.page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
}
.page-title {
    font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800;
    line-height: 1.1; margin-bottom: 4px;
}
.page-sub { font-size: 13px; color: var(--text-2); }

/* ── STAT GRID ── */
.stat-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; margin-bottom: 24px;
}
.stat-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 18px 20px;
    position: relative; overflow: hidden;
    transition: border-color .2s;
}
.stat-card:hover { border-color: var(--border2); }
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0;
    right: 0; height: 2px;
}
.stat-card.gold::before  { background: var(--gold); }
.stat-card.green::before { background: var(--green); }
.stat-card.blue::before  { background: var(--blue); }
.stat-card.orange::before{ background: #fb923c; }
.stat-num {
    font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800;
    line-height: 1; margin-bottom: 4px;
}
.stat-card.gold  .stat-num { color: var(--gold); }
.stat-card.green .stat-num { color: var(--green); }
.stat-card.blue  .stat-num { color: var(--blue); }
.stat-card.orange .stat-num{ color: #fb923c; }
.stat-lbl { font-size: 12px; color: var(--text-2); font-weight: 500; }
.stat-icon {
    position: absolute; right: 18px; top: 50%;
    transform: translateY(-50%); font-size: 32px; opacity: .06;
}

/* ── TOOLBAR ── */
.toolbar {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 20px; flex-wrap: wrap;
}
.search-input {
    flex: 1; min-width: 200px;
    background: var(--card); border: 1px solid var(--border);
    color: var(--text); font-family: 'DM Sans', sans-serif;
    font-size: 13px; padding: 10px 14px; border-radius: 10px;
    outline: none; transition: border-color .2s;
}
.search-input:focus { border-color: var(--gold-glow); }
.search-input::placeholder { color: var(--text-3); }
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 18px; border-radius: 10px; font-size: 13px;
    font-weight: 600; cursor: pointer; border: none;
    font-family: 'DM Sans', sans-serif; transition: .2s;
    text-decoration: none; white-space: nowrap;
}
.btn-gold {
    background: var(--gold); color: #000;
}
.btn-gold:hover { background: #fbbf24; transform: translateY(-1px); }
.btn-ghost {
    background: transparent; border: 1px solid var(--border);
    color: var(--text-2);
}
.btn-ghost:hover { border-color: var(--border2); color: var(--text); }
.btn-danger {
    background: var(--red-dim); border: 1px solid #ef444430;
    color: var(--red);
}
.btn-danger:hover { background: #ef444428; }
.btn-green {
    background: var(--green-dim); border: 1px solid #10b98130;
    color: var(--green);
}

/* ── RECRUIT FORM ── */
.recruit-panel {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 24px;
    margin-bottom: 24px; display: none;
    animation: slideDown .25s ease;
}
.recruit-panel.open { display: block; }
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.panel-title {
    font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700;
    margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
}
.panel-title i { color: var(--gold); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid .full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 11px; font-weight: 600; color: var(--text-2); text-transform: uppercase; letter-spacing: .5px; }
.form-input {
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text); font-family: 'DM Sans', sans-serif;
    font-size: 13px; padding: 10px 13px; border-radius: 9px;
    outline: none; transition: border-color .2s; width: 100%;
}
.form-input:focus { border-color: var(--gold-glow); }
.form-input::placeholder { color: var(--text-3); }
textarea.form-input { resize: vertical; min-height: 72px; }
.form-actions { display: flex; gap: 10px; margin-top: 18px; }

/* ── PI CARDS ── */
.pi-grid { display: flex; flex-direction: column; gap: 14px; }
.pi-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    transition: border-color .2s;
}
.pi-card:hover { border-color: var(--border2); }
.pi-card.inactive { opacity: .6; }
.pi-card-top {
    display: flex; align-items: center; gap: 16px;
    padding: 18px 20px; border-bottom: 1px solid var(--border);
}
.pi-avatar {
    width: 48px; height: 48px; border-radius: 12px;
    background: var(--purple-dim); border: 1.5px solid #a855f730;
    display: flex; align-items: center; justify-content: center;
    color: var(--purple); font-size: 20px; flex-shrink: 0;
    overflow: hidden;
}
.pi-avatar img { width: 100%; height: 100%; object-fit: cover; }
.pi-name {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 15px;
}
.pi-code-badge {
    font-size: 11px; color: var(--gold); background: var(--gold-dim);
    border: 1px solid var(--gold-glow); padding: 2px 8px;
    border-radius: 6px; font-weight: 700; margin-top: 3px;
    display: inline-block;
}
.pi-status-active {
    background: var(--green-dim); color: var(--green);
    border: 1px solid #10b98130; font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
}
.pi-status-inactive {
    background: var(--red-dim); color: var(--red);
    border: 1px solid #ef444430; font-size: 11px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
}
.pi-cases-num {
    font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800;
    color: #fb923c; line-height: 1;
}
.pi-cases-label { font-size: 11px; color: var(--text-3); }
.pi-card-body { padding: 16px 20px; }
.pi-info-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
    margin-bottom: 14px;
}
.pi-info-item {}
.pi-info-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.pi-info-val { font-size: 13px; color: var(--text); }
.workload-wrap { margin-bottom: 14px; }
.workload-label { font-size: 11px; color: var(--text-3); margin-bottom: 6px; display: flex; justify-content: space-between; }
.workload-track {
    height: 6px; background: var(--border); border-radius: 99px; overflow: hidden;
}
.workload-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--green), #fb923c);
    transition: width .5s ease;
}
.pi-card-actions {
    display: flex; gap: 8px; padding: 14px 20px;
    border-top: 1px solid var(--border); flex-wrap: wrap;
}
.pi-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 13px; border-radius: 8px; font-size: 12px;
    font-weight: 600; cursor: pointer; border: none;
    font-family: 'DM Sans', sans-serif; transition: .18s;
}
.pi-btn-edit   { background: var(--blue-dim);   border: 1px solid #3b82f630; color: var(--blue); }
.pi-btn-pass   { background: var(--green-dim);  border: 1px solid #10b98130; color: var(--green); }
.pi-btn-toggle { background: var(--gold-dim);   border: 1px solid var(--gold-glow); color: var(--gold); }
.pi-btn-delete { background: var(--red-dim);    border: 1px solid #ef444430; color: var(--red); }
.pi-btn:hover { filter: brightness(1.15); transform: translateY(-1px); }

/* ── EMPTY ── */
.empty-state {
    text-align: center; padding: 60px 20px; color: var(--text-3);
}
.empty-icon { font-size: 42px; margin-bottom: 14px; opacity: .3; }

/* ── OVERVIEW ── */
.overview-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 24px; margin-bottom: 16px;
}
.overview-title {
    font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
    color: var(--gold); margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}
.perf-row { margin-bottom: 18px; }
.perf-meta { display: flex; justify-content: space-between; margin-bottom: 7px; font-size: 13px; }
.perf-name { font-weight: 600; }
.perf-count { color: #fb923c; font-weight: 600; }
.info-list { list-style: none; }
.info-list li {
    padding: 10px 0; border-bottom: 1px solid var(--border);
    font-size: 13px; color: var(--text-2); display: flex; gap: 8px;
}
.info-list li:last-child { border-bottom: none; }
.info-list li i { color: var(--gold); width: 16px; flex-shrink: 0; margin-top: 1px; }

/* ── CASES TABLE ── */
.table-wrap {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th {
    text-align: left; padding: 13px 16px;
    background: var(--surface); color: var(--text-3);
    font-size: 11px; text-transform: uppercase; letter-spacing: .6px;
    font-weight: 600; border-bottom: 1px solid var(--border);
}
.data-table td { padding: 13px 16px; border-bottom: 1px solid var(--border); }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: rgba(255,255,255,.015); }
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge-green { background: var(--green-dim); border: 1px solid #10b98130; color: var(--green); }
.badge-yellow { background: var(--gold-dim); border: 1px solid var(--gold-glow); color: var(--gold); }
.badge-purple { background: var(--purple-dim); border: 1px solid #a855f730; color: var(--purple); }

/* ── MODALS ── */
.modal-overlay {
    position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,.7); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity .2s;
}
.modal-overlay.active { opacity: 1; pointer-events: all; }
.modal-box {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 18px; padding: 28px; width: 90%; max-width: 520px;
    max-height: 90vh; overflow-y: auto;
    transform: translateY(10px); transition: transform .2s;
}
.modal-overlay.active .modal-box { transform: translateY(0); }
.modal-title {
    font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 700;
    margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
}
.modal-title i { color: var(--gold); }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }

/* ── CRED MODAL ── */
.cred-box {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 20px; margin: 16px 0;
}
.cred-row { margin-bottom: 14px; }
.cred-row:last-child { margin-bottom: 0; }
.cred-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.cred-val { font-size: 16px; font-weight: 700; }
.cred-val.gold   { color: var(--gold); }
.cred-val.blue   { color: var(--blue); font-size: 14px; }
.cred-val.green  { color: var(--green); }
.cred-check-icon {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--green-dim); border: 2px solid #10b98130;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px; font-size: 24px; color: var(--green);
}
.warning-note {
    background: #f59e0b0d; border: 1px solid #f59e0b25;
    border-radius: 9px; padding: 10px 14px;
    font-size: 12px; color: var(--gold);
    margin-bottom: 16px;
}

/* ── TOAST ── */
.toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 999;
    background: var(--card); border: 1px solid var(--green);
    color: var(--green); padding: 12px 20px; border-radius: 12px;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    animation: toastIn .25s ease;
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
}
.toast.error { border-color: var(--red); color: var(--red); }
@keyframes toastIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* ── LOADING ── */
.loading-state { text-align: center; padding: 50px; color: var(--text-3); font-size: 14px; }
.loading-state i { font-size: 28px; display: block; margin-bottom: 12px; color: var(--gold); }

@media (max-width: 768px) {
    :root { --sidebar-w: 0px; }
    .sidebar { transform: translateX(-100%); }
    .stat-grid { grid-template-columns: 1fr 1fr; }
    .form-grid { grid-template-columns: 1fr; }
    .pi-info-row { grid-template-columns: 1fr; }
    .main { margin-left: 0; padding: 16px; }
}
</style>
</head>
<body>

<script>
    if (localStorage.getItem('isSuperAdminLoggedIn') !== 'true') {
        window.location.href = '/super-admin/login';
    }
</script>

<!-- TOPBAR -->
<header class="topbar">
    <a href="/" class="topbar-logo">
        <i class="fas fa-crown crown"></i>
        SafeVoice
        <span class="topbar-badge">Super Admin</span>
    </a>
    <a href="/admin/dashboard" class="topbar-btn"><i class="fas fa-user-shield"></i> Normal Admin</a>
    <button class="topbar-btn danger" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
</header>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Management</div>
            <a class="nav-item" id="nav-investigators" onclick="showSection('investigators')">
                <i class="fas fa-user-secret"></i> Investigators
            </a>
            <a class="nav-item" id="nav-overview" onclick="showSection('overview')">
                <i class="fas fa-chart-bar"></i> Overview
            </a>
            <a class="nav-item" id="nav-cases" onclick="showSection('cases')">
                <i class="fas fa-folder-open"></i> Active Cases
            </a>
        </div>
        <div class="sidebar-footer">
            <div class="user-pill">
                <div class="user-avatar"><i class="fas fa-crown"></i></div>
                <div>
                    <div class="user-name" id="saUserDisplay">—</div>
                    <div class="user-role">Super Administrator</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <!-- ── INVESTIGATORS ── -->
        <div class="section" id="view-investigators">
            <div class="page-header">
                <div>
                    <div class="page-title"><i class="fas fa-user-secret" style="color:var(--gold);margin-right:10px"></i>Investigators</div>
                    <div class="page-sub">Recruit, manage and monitor all private investigators</div>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card gold">
                    <div class="stat-num" id="stat-total-pi">—</div>
                    <div class="stat-lbl">Total PIs</div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="stat-card green">
                    <div class="stat-num" id="stat-active-pi">—</div>
                    <div class="stat-lbl">Active</div>
                    <i class="fas fa-user-check stat-icon"></i>
                </div>
                <div class="stat-card blue">
                    <div class="stat-num" id="stat-total-cases">—</div>
                    <div class="stat-lbl">Total Cases</div>
                    <i class="fas fa-folder stat-icon"></i>
                </div>
                <div class="stat-card orange">
                    <div class="stat-num" id="stat-active-cases">—</div>
                    <div class="stat-lbl">Active Cases</div>
                    <i class="fas fa-fire stat-icon"></i>
                </div>
            </div>

            <div class="toolbar">
                <input type="text" class="search-input" placeholder="🔍  Search by name, code, email, NID..." oninput="filterPIs(this.value)" id="piSearch">
                <button class="btn btn-gold" onclick="toggleRecruitForm()">
                    <i class="fas fa-user-plus"></i> Recruit New PI
                </button>
            </div>

            <!-- Recruit Form -->
            <div class="recruit-panel" id="recruitForm">
                <div class="panel-title"><i class="fas fa-user-plus"></i> Recruit New Private Investigator</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-input" id="rf_name" placeholder="e.g. Rahim Uddin">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NID Number *</label>
                        <input type="text" class="form-input" id="rf_nid" placeholder="17-digit NID">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-input" id="rf_phone" placeholder="01XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-input" id="rf_email" placeholder="pi@example.com">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Full Address *</label>
                        <input type="text" class="form-input" id="rf_address" placeholder="House, Road, Area, City">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login Email (case notifications) *</label>
                        <input type="email" class="form-input" id="rf_login_email" placeholder="cases will be sent here">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Password *</label>
                        <input type="text" class="form-input" id="rf_password" placeholder="min 8 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Profile Photo URL</label>
                        <input type="text" class="form-input" id="rf_photo" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">NID Photo URL</label>
                        <input type="text" class="form-input" id="rf_nid_photo" placeholder="https://...">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-input" id="rf_notes" placeholder="Optional notes for super admin only"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-gold" onclick="recruitPI()" id="recruitBtn">
                        <i class="fas fa-user-check"></i> Confirm Recruitment
                    </button>
                    <button class="btn btn-ghost" onclick="toggleRecruitForm()">Cancel</button>
                </div>
            </div>

            <!-- PI List -->
            <div id="piList">
                <div class="loading-state"><i class="fas fa-spinner fa-spin"></i>Loading investigators...</div>
            </div>
        </div>

        <!-- ── OVERVIEW ── -->
        <div class="section" id="view-overview">
            <div class="page-header">
                <div>
                    <div class="page-title"><i class="fas fa-chart-bar" style="color:var(--gold);margin-right:10px"></i>Overview</div>
                    <div class="page-sub">Workload distribution and PI performance</div>
                </div>
            </div>
            <div id="overviewContent">
                <div class="loading-state"><i class="fas fa-spinner fa-spin"></i>Loading overview...</div>
            </div>
        </div>

        <!-- ── ACTIVE CASES ── -->
        <div class="section" id="view-cases">
            <div class="page-header">
                <div>
                    <div class="page-title"><i class="fas fa-folder-open" style="color:var(--gold);margin-right:10px"></i>Active Cases</div>
                    <div class="page-sub">Complaints currently assigned to investigators</div>
                </div>
            </div>
            <div id="casesContent">
                <div class="loading-state"><i class="fas fa-spinner fa-spin"></i>Loading cases...</div>
            </div>
        </div>

    </main>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-edit"></i> Edit Investigator</div>
        <input type="hidden" id="edit_id">
        <div class="form-grid">
            <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="form-input" id="edit_name"></div>
            <div class="form-group"><label class="form-label">Phone</label><input type="tel" class="form-input" id="edit_phone"></div>
            <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-input" id="edit_email"></div>
            <div class="form-group"><label class="form-label">NID Number</label><input type="text" class="form-input" id="edit_nid"></div>
            <div class="form-group full"><label class="form-label">Address</label><input type="text" class="form-input" id="edit_address"></div>
            <div class="form-group"><label class="form-label">Photo URL</label><input type="text" class="form-input" id="edit_photo"></div>
            <div class="form-group"><label class="form-label">NID Photo URL</label><input type="text" class="form-input" id="edit_nid_photo"></div>
            <div class="form-group full"><label class="form-label">Notes</label><textarea class="form-input" id="edit_notes"></textarea></div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-gold" onclick="saveEdit()" style="flex:1"><i class="fas fa-save"></i> Save Changes</button>
            <button class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- Password Modal -->
<div class="modal-overlay" id="passModal">
    <div class="modal-box" style="max-width:380px">
        <div class="modal-title"><i class="fas fa-key"></i> Change PI Password</div>
        <input type="hidden" id="pass_pi_id">
        <p style="font-size:13px;color:var(--text-2);margin-bottom:16px" id="pass_pi_name"></p>
        <div class="form-group">
            <label class="form-label">New Password (min 8 characters)</label>
            <input type="text" class="form-input" id="new_password" placeholder="Enter new password">
        </div>
        <div class="modal-actions">
            <button class="btn btn-green" onclick="savePassword()" style="flex:1"><i class="fas fa-key"></i> Update Password</button>
            <button class="btn btn-ghost" onclick="closeModal('passModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- Credentials Modal -->
<div class="modal-overlay" id="credModal">
    <div class="modal-box" style="max-width:420px;text-align:center">
        <div class="cred-check-icon"><i class="fas fa-check"></i></div>
        <div class="modal-title" style="justify-content:center;color:var(--green)">PI Recruited Successfully!</div>
        <p style="font-size:13px;color:var(--text-2);margin-bottom:16px">Save these credentials before closing.</p>
        <div class="cred-box" style="text-align:left">
            <div class="cred-row"><div class="cred-label">PI Code</div><div class="cred-val gold" id="cred_code">—</div></div>
            <div class="cred-row"><div class="cred-label">Login Email</div><div class="cred-val blue" id="cred_email">—</div></div>
            <div class="cred-row"><div class="cred-label">Initial Password</div><div class="cred-val green" id="cred_pass">—</div></div>
        </div>
        <div class="warning-note"><i class="fas fa-exclamation-triangle"></i> Share these credentials securely. They can change the password later.</div>
        <button onclick="closeModal('credModal')" class="btn btn-gold" style="width:100%"><i class="fas fa-check"></i> Done</button>
    </div>
</div>

<script>
const SA_USER = localStorage.getItem('sa_username') || 'superadmin';
const SA_PASS = localStorage.getItem('sa_password') || '';
document.getElementById('saUserDisplay').textContent = SA_USER;

function logout() {
    ['isSuperAdminLoggedIn','sa_username','sa_password'].forEach(k => localStorage.removeItem(k));
    window.location.href = '/super-admin/login';
}

function showSection(s) {
    ['investigators','overview','cases'].forEach(id => {
        document.getElementById('view-'+id).classList.remove('active');
        document.getElementById('nav-'+id)?.classList.remove('active');
    });
    document.getElementById('view-'+s).classList.add('active');
    document.getElementById('nav-'+s)?.classList.add('active');
    if (s === 'investigators') loadPIs();
    if (s === 'overview')      loadOverview();
    if (s === 'cases')         loadCases();
}

let allPIs = [];
const demoPIs = [
    { id:1, pi_code:'PI-001', full_name:'Rahim Uddin Chowdhury', email:'rahim.pi001@safevoice.com', phone:'01711000001', address:'House 12, Road 5, Dhanmondi, Dhaka', nid_number:'1234567890123', login_email:'rahim.pi001@safevoice.com', photo_url:'', nid_photo_url:'', is_active:1, active_cases:2, total_cases:14, joined_at:'2026-01-10', notes:'Specialist in financial fraud cases' },
    { id:2, pi_code:'PI-002', full_name:'Farida Khanam', email:'farida.pi002@safevoice.com', phone:'01811000002', address:'Flat 3B, Block C, Mirpur-10, Dhaka', nid_number:'9876543210987', login_email:'farida.pi002@safevoice.com', photo_url:'', nid_photo_url:'', is_active:1, active_cases:1, total_cases:9, joined_at:'2026-02-15', notes:'Experienced in harassment cases' },
    { id:3, pi_code:'PI-003', full_name:'Kamal Hossain', email:'kamal.pi003@safevoice.com', phone:'01911000003', address:'Village: Narayanganj Sadar, Narayanganj', nid_number:'1122334455667', login_email:'kamal.pi003@safevoice.com', photo_url:'', nid_photo_url:'', is_active:1, active_cases:3, total_cases:22, joined_at:'2025-11-20', notes:'Senior PI - most experienced' }
];

async function loadPIs() {
    try {
        const res  = await fetch('/api/pi');
        const data = await res.json();
        if (data.success && data.investigators && data.investigators.length) {
            allPIs = data.investigators;
        } else {
            throw new Error('no data');
        }
    } catch(e) { allPIs = demoPIs; }
    renderPIs(allPIs);
    updateStats(allPIs);
}

function updateStats(pis) {
    document.getElementById('stat-total-pi').textContent    = pis.length;
    document.getElementById('stat-active-pi').textContent   = pis.filter(p=>p.is_active==1).length;
    document.getElementById('stat-total-cases').textContent = pis.reduce((a,p)=>a+(parseInt(p.total_cases)||0),0);
    document.getElementById('stat-active-cases').textContent= pis.reduce((a,p)=>a+(parseInt(p.active_cases)||0),0);
}

function filterPIs(q) {
    q = q.toLowerCase();
    renderPIs(allPIs.filter(p =>
        p.full_name.toLowerCase().includes(q) ||
        p.pi_code.toLowerCase().includes(q) ||
        p.email.toLowerCase().includes(q) ||
        (p.nid_number||'').includes(q)
    ));
}

function renderPIs(pis) {
    const c = document.getElementById('piList');
    if (!pis.length) {
        c.innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="fas fa-user-secret"></i></div>No investigators found</div>';
        return;
    }
    c.innerHTML = '<div class="pi-grid">' + pis.map(piCard).join('') + '</div>';
}

function piCard(pi) {
    const pct = Math.min((pi.active_cases / 10) * 100, 100);
    const statusBadge = pi.is_active == 1
        ? '<span class="pi-status-active">● Active</span>'
        : '<span class="pi-status-inactive">● Inactive</span>';
    const avatar = pi.photo_url
        ? `<div class="pi-avatar"><img src="${pi.photo_url}" onerror="this.parentElement.innerHTML='<i class=\\'fas fa-user-secret\\'></i>'"></div>`
        : `<div class="pi-avatar"><i class="fas fa-user-secret"></i></div>`;

    return `
    <div class="pi-card ${pi.is_active!=1?'inactive':''}" id="picard-${pi.id}">
        <div class="pi-card-top">
            ${avatar}
            <div style="flex:1;min-width:0">
                <div class="pi-name">${pi.full_name}</div>
                <div class="pi-code-badge"><i class="fas fa-id-badge"></i> ${pi.pi_code}</div>
                <div style="margin-top:6px">${statusBadge}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div class="pi-cases-num">${pi.active_cases}</div>
                <div class="pi-cases-label">active cases</div>
                <div style="font-size:11px;color:var(--text-3);margin-top:2px">${pi.total_cases} total</div>
            </div>
        </div>
        <div class="pi-card-body">
            <div class="workload-wrap">
                <div class="workload-label"><span>Workload</span><span>${pi.active_cases}/10</span></div>
                <div class="workload-track"><div class="workload-fill" style="width:${pct}%"></div></div>
            </div>
            <div class="pi-info-row">
                <div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-phone"></i> Phone</div><div class="pi-info-val">${pi.phone}</div></div>
                <div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-envelope"></i> Email</div><div class="pi-info-val" style="font-size:12px">${pi.email}</div></div>
                <div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-id-card"></i> NID</div><div class="pi-info-val">${pi.nid_number}</div></div>
                <div class="pi-info-item"><div class="pi-info-label"><i class="fas fa-inbox"></i> Case Inbox</div><div class="pi-info-val" style="font-size:12px">${pi.login_email}</div></div>
                <div class="pi-info-item" style="grid-column:1/-1"><div class="pi-info-label"><i class="fas fa-map-marker-alt"></i> Address</div><div class="pi-info-val">${pi.address}</div></div>
                ${pi.notes ? `<div class="pi-info-item" style="grid-column:1/-1"><div class="pi-info-label"><i class="fas fa-sticky-note"></i> Notes</div><div class="pi-info-val" style="color:var(--gold)">${pi.notes}</div></div>` : ''}
            </div>
        </div>
        <div class="pi-card-actions">
            <button class="pi-btn pi-btn-edit" onclick="openEdit(${JSON.stringify(pi).replace(/"/g,'&quot;')})"><i class="fas fa-edit"></i> Edit</button>
            <button class="pi-btn pi-btn-pass" onclick="openPassModal(${pi.id},'${escHtml(pi.full_name)}')"><i class="fas fa-key"></i> Password</button>
            <button class="pi-btn pi-btn-toggle" onclick="togglePI(${pi.id})"><i class="fas fa-${pi.is_active==1?'pause':'play'}"></i> ${pi.is_active==1?'Deactivate':'Activate'}</button>
            <button class="pi-btn pi-btn-delete" onclick="deletePI(${pi.id},'${escHtml(pi.full_name)}')"><i class="fas fa-trash"></i> Remove</button>
        </div>
    </div>`;
}

function escHtml(s) { return (s||'').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

function toggleRecruitForm() {
    const f = document.getElementById('recruitForm');
    f.classList.toggle('open');
    if (f.classList.contains('open')) f.scrollIntoView({behavior:'smooth',block:'start'});
}

async function recruitPI() {
    const fields = {
        full_name: document.getElementById('rf_name').value.trim(),
        nid_number: document.getElementById('rf_nid').value.trim(),
        phone: document.getElementById('rf_phone').value.trim(),
        email: document.getElementById('rf_email').value.trim(),
        address: document.getElementById('rf_address').value.trim(),
        login_email: document.getElementById('rf_login_email').value.trim(),
        initial_password: document.getElementById('rf_password').value.trim(),
        photo_url: document.getElementById('rf_photo').value.trim(),
        nid_photo_url: document.getElementById('rf_nid_photo').value.trim(),
        notes: document.getElementById('rf_notes').value.trim(),
    };
    for (const [k,v] of Object.entries(fields)) {
        if (!v && !['photo_url','nid_photo_url','notes'].includes(k)) {
            showToast('Please fill in: ' + k.replace(/_/g,' '), true); return;
        }
    }
    if (fields.initial_password.length < 8) { showToast('Password must be at least 8 characters', true); return; }

    const btn = document.getElementById('recruitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recruiting...';
    btn.disabled = true;

    try {
        const res = await fetch('/api/super-admin/add-pi', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(fields)
        });
        const data = await res.json();
        if (data.success) {
            showCredentials(data.pi.pi_code, fields.login_email, fields.initial_password);
            await loadPIs(); // DB থেকে fresh list আনো
        } else {
            showToast('Error: ' + (data.message || 'Could not save PI'), true);
        }
    } catch(e) {
        showToast('Network error — PI not saved. Please try again.', true);
    }

    ['rf_name','rf_nid','rf_phone','rf_email','rf_address','rf_login_email','rf_password','rf_photo','rf_nid_photo','rf_notes']
        .forEach(id => document.getElementById(id).value = '');
    document.getElementById('recruitForm').classList.remove('open');
    btn.innerHTML = '<i class="fas fa-user-check"></i> Confirm Recruitment';
    btn.disabled = false;
}

function showCredentials(code, email, pass) {
    document.getElementById('cred_code').textContent  = code;
    document.getElementById('cred_email').textContent = email;
    document.getElementById('cred_pass').textContent  = pass;
    document.getElementById('credModal').classList.add('active');
    showToast('PI ' + code + ' recruited successfully!');
}

function openEdit(pi) {
    if (typeof pi === 'string') pi = JSON.parse(pi);
    ['id','name','phone','email','nid','address','photo','nid_photo','notes'].forEach(f => {
        const el = document.getElementById('edit_'+f);
        if (el) el.value = pi[f === 'name' ? 'full_name' : f === 'nid' ? 'nid_number' : f === 'photo' ? 'photo_url' : f === 'nid_photo' ? 'nid_photo_url' : f] || '';
    });
    document.getElementById('editModal').classList.add('active');
}

async function saveEdit() {
    const id = parseInt(document.getElementById('edit_id').value);
    const payload = {
        action:'update', sa_username:SA_USER, sa_password:SA_PASS, id,
        full_name:     document.getElementById('edit_name').value.trim(),
        phone:         document.getElementById('edit_phone').value.trim(),
        email:         document.getElementById('edit_email').value.trim(),
        nid_number:    document.getElementById('edit_nid').value.trim(),
        address:       document.getElementById('edit_address').value.trim(),
        photo_url:     document.getElementById('edit_photo').value.trim(),
        nid_photo_url: document.getElementById('edit_nid_photo').value.trim(),
        notes:         document.getElementById('edit_notes').value.trim(),
    };
    try {
        const res = await fetch('/api/super-admin/pi/update', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const data = await res.json();
        if (!data.success) { showToast('Update failed: ' + (data.message||''), true); return; }
    } catch(e) { showToast('Network error', true); return; }
    const idx = allPIs.findIndex(p => p.id == id);
    if (idx > -1) Object.assign(allPIs[idx], payload);
    renderPIs(allPIs); closeModal('editModal');
    showToast('PI details updated successfully');
}

function openPassModal(id, name) {
    document.getElementById('pass_pi_id').value = id;
    document.getElementById('pass_pi_name').textContent = 'PI: ' + name;
    document.getElementById('new_password').value = '';
    document.getElementById('passModal').classList.add('active');
}

async function savePassword() {
    const id  = parseInt(document.getElementById('pass_pi_id').value);
    const pwd = document.getElementById('new_password').value.trim();
    if (pwd.length < 8) { showToast('Password must be at least 8 characters', true); return; }
    try {
        const res = await fetch('/api/super-admin/pi/password', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id, new_password:pwd})});
        const data = await res.json();
        if (!data.success) { showToast('Failed: ' + (data.message||''), true); return; }
    } catch(e) { showToast('Network error', true); return; }
    closeModal('passModal'); showToast('Password updated successfully');
}

async function togglePI(id) {
    try {
        const res = await fetch('/api/super-admin/pi/toggle', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (data.success) {
            const pi = allPIs.find(p => p.id == id);
            if (pi) pi.is_active = data.is_active ? 1 : 0;
        }
    } catch(e) { showToast('Network error', true); return; }
    renderPIs(allPIs); updateStats(allPIs);
    showToast('PI status updated');
}

async function deletePI(id, name) {
    if (!confirm(`Remove PI: ${name}?\n\nThis cannot be undone.`)) return;
    try {
        const res = await fetch('/api/super-admin/pi/delete', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data = await res.json();
        if (!data.success) { showToast('Delete failed: ' + (data.message||''), true); return; }
    } catch(e) { showToast('Network error', true); return; }
    allPIs = allPIs.filter(p => p.id != id);
    renderPIs(allPIs); updateStats(allPIs);
    showToast('PI removed', true);
}

async function loadOverview() {
    // Fresh PI data নাও — allPIs already loaded থাকলেও re-fetch করো যাতে stats updated থাকে
    try {
        const res  = await fetch('/api/pi');
        const data = await res.json();
        if (data.success && data.investigators && data.investigators.length) {
            allPIs = data.investigators;
        }
    } catch(e) { if (!allPIs.length) allPIs = demoPIs; }

    // Top stats boxes আপডেট করো
    updateStats(allPIs);

    const pis = allPIs.length ? allPIs : demoPIs;
    const maxLoad = Math.max(...pis.map(p=>parseInt(p.active_cases)||0), 1);
    document.getElementById('overviewContent').innerHTML = `
        <div class="overview-card">
            <div class="overview-title"><i class="fas fa-balance-scale"></i> Workload Distribution</div>
            ${pis.map(pi=>`
            <div class="perf-row">
                <div class="perf-meta">
                    <span class="perf-name">${pi.pi_code} — ${pi.full_name}</span>
                    <span class="perf-count">${pi.active_cases} active / ${pi.total_cases} total</span>
                </div>
                <div class="workload-track"><div class="workload-fill" style="width:${Math.min((pi.active_cases/maxLoad)*100,100)}%"></div></div>
                <div style="font-size:11px;color:var(--text-3);margin-top:4px">${pi.is_active==1?'✅ Active':'🔴 Inactive'} · Joined: ${(pi.joined_at||'').split('T')[0]||pi.joined_at}</div>
            </div>`).join('')}
        </div>
        <div class="overview-card">
            <div class="overview-title"><i class="fas fa-info-circle"></i> System Rules</div>
            <ul class="info-list">
                <li><i class="fas fa-robot"></i> Auto-assignment picks the PI with the lowest active case count</li>
                <li><i class="fas fa-eye-slash"></i> PI identity is completely hidden from normal admin and users</li>
                <li><i class="fas fa-envelope"></i> Case emails are sent automatically to the PI's registered login email</li>
                <li><i class="fas fa-chart-line"></i> Workload counter increments on assignment, decrements when resolved</li>
                <li><i class="fas fa-lock"></i> Only super admin can recruit, edit, or remove investigators</li>
            </ul>
        </div>`;
}

async function loadCases() {
    const el = document.getElementById('casesContent');
    el.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i>Loading cases...</div>';
    try {
        // Real PI data + cases একসাথে নাও
        const [cRes, piRes] = await Promise.all([
            fetch('/api/complaints?status=Private%20Investigator%20Assigned'),
            fetch('/api/pi')
        ]);
        const cData  = await cRes.json();
        const piData = await piRes.json();

        if (!cData.success || !cData.complaints.length) throw new Error();

        // Real DB PI data দিয়ে map বানাও (id → pi object)
        const pMap = {};
        (piData.investigators || []).forEach(p => { pMap[p.id] = p; });

        el.innerHTML = `
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr>
                    <th>Complaint ID</th><th>Type</th><th>Location</th>
                    <th>Assigned PI</th><th>Date</th>
                </tr></thead>
                <tbody>${cData.complaints.map(c => {
                    const pi = c.assigned_pi_id ? pMap[c.assigned_pi_id] : null;
                    const piLabel = pi
                        ? `${pi.pi_code} — ${pi.full_name}`
                        : (c.assigned_pi_id ? 'PI-???' : 'Unassigned');
                    return `
                    <tr>
                        <td style="color:var(--blue);font-weight:700">${c.complaint_id}</td>
                        <td>${c.type}</td>
                        <td style="color:var(--text-2)">${c.location||'—'}</td>
                        <td><span class="badge badge-purple">${piLabel}</span></td>
                        <td style="color:var(--text-2)">${(c.pi_assigned_at||c.submitted_at||'').split(' ')[0]}</td>
                    </tr>`;
                }).join('')}
                </tbody>
            </table></div>`;
    } catch(e) {
        el.innerHTML = '<div class="empty-state"><div class="empty-icon"><i class="fas fa-folder-open"></i></div>No active PI cases found</div>';
    }
}

function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function showToast(msg, isError=false) {
    const t = document.createElement('div');
    t.className = 'toast' + (isError?' error':'');
    t.innerHTML = (isError?'<i class="fas fa-times-circle"></i>':'<i class="fas fa-check-circle"></i>') + ' ' + msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

document.addEventListener('DOMContentLoaded', async () => {
    // Page load এই PI data আগে load করো যাতে সব section এ stats ready থাকে
    try {
        const res  = await fetch('/api/pi');
        const data = await res.json();
        if (data.success && data.investigators && data.investigators.length) {
            allPIs = data.investigators;
            updateStats(allPIs);
        }
    } catch(e) {}
    showSection('investigators');
});
</script>
</body>
</html>