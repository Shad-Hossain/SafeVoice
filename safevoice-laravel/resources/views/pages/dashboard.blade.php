@extends('layouts.app')
@section('title', 'Dashboard — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
/* ══ NOTIFICATION BELL ══════════════════════════════════════════ */
.notif-bell-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
}
.notif-bell-btn {
    position: relative;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    color: #a0b4cc;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s;
    margin-right: 10px;
}
.notif-bell-btn:hover {
    background: rgba(79,158,255,0.15);
    border-color: #4f9eff55;
    color: #4f9eff;
}
.notif-bell-btn.has-unread {
    color: #4f9eff;
    animation: bellShake 2.5s ease infinite;
}
@keyframes bellShake {
    0%,100% { transform: rotate(0deg); }
    10%      { transform: rotate(-12deg); }
    20%      { transform: rotate(12deg); }
    30%      { transform: rotate(-8deg); }
    40%      { transform: rotate(8deg); }
    50%      { transform: rotate(0deg); }
}
.notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #e63946;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #0d1117;
    line-height: 1;
}
.notif-panel {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 360px;
    max-height: 480px;
    background: #111827;
    border: 1px solid #1e2d4a;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,.6), 0 0 0 1px rgba(79,158,255,.08);
    z-index: 9999;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: notifFadeIn .18s ease;
}
@keyframes notifFadeIn {
    from { opacity: 0; transform: translateY(-8px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);   }
}
.notif-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #1e2d4a;
    flex-shrink: 0;
}
.notif-panel-title {
    color: #e2e8f0;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.notif-panel-title i { color: #4f9eff; }
.notif-mark-all-btn {
    background: none;
    border: 1px solid #2a3f5f;
    color: #7b8fa6;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.notif-mark-all-btn:hover {
    background: rgba(79,158,255,.1);
    border-color: #4f9eff55;
    color: #4f9eff;
}
.notif-panel-body {
    overflow-y: auto;
    flex: 1;
    padding: 6px 0;
}
.notif-panel-body::-webkit-scrollbar { width: 4px; }
.notif-panel-body::-webkit-scrollbar-track { background: transparent; }
.notif-panel-body::-webkit-scrollbar-thumb { background: #2a3f5f; border-radius: 2px; }
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background .15s;
    border-bottom: 1px solid #0d1117;
    position: relative;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: rgba(255,255,255,.03); }
.notif-item.unread { background: rgba(79,158,255,.05); }
.notif-item.unread::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: #4f9eff;
    border-radius: 0 2px 2px 0;
}
.notif-icon {
    font-size: 20px;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    display: flex;
    align-items: center;
    justify-content: center;
}
.notif-content { flex: 1; min-width: 0; }
.notif-title {
    color: #e2e8f0;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.notif-item.unread .notif-title { color: #fff; }
.notif-msg {
    color: #7b8fa6;
    font-size: 12px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.notif-time { color: #4f5e70; font-size: 11px; margin-top: 5px; }
.notif-delete-btn {
    background: none;
    border: none;
    color: #4f5e70;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    opacity: 0;
    transition: all .2s;
    flex-shrink: 0;
    font-size: 12px;
}
.notif-item:hover .notif-delete-btn { opacity: 1; }
.notif-delete-btn:hover { color: #e63946; background: rgba(230,57,70,.1); }
.notif-empty {
    text-align: center;
    padding: 40px 20px;
    color: #4f5e70;
}
.notif-empty i { font-size: 36px; margin-bottom: 10px; display: block; }
.notif-empty p { font-size: 13px; margin: 0; }
.notif-loading { text-align: center; padding: 30px; color: #4f5e70; font-size: 13px; }
@media (max-width: 500px) {
    .notif-panel { width: calc(100vw - 24px); right: -16px; }
}
</style>
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
            <li id="nav-sos-responds"><a href="#" onclick="showSection('sos-responds')"><i class="fas fa-hands-helping" style="color:#4f9eff"></i> SOS Responses</a></li>
            <li id="nav-all-sos"><a href="#" onclick="showSection('all-sos')"><i class="fas fa-bell" style="color:#e63946"></i> All SOS Requests</a></li>
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
                <div style="display:flex;align-items:center;gap:10px;">
                    <!-- 🔔 BELL NOTIFICATION ICON -->
                    <div class="notif-bell-wrapper" id="notifBellWrapper">
                        <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifPanel()" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notif-badge" id="notifBadge" style="display:none">0</span>
                        </button>
                        <div class="notif-panel" id="notifPanel" style="display:none;">
                            <div class="notif-panel-header">
                                <span class="notif-panel-title"><i class="fas fa-bell"></i> Notifications</span>
                                <button class="notif-mark-all-btn" onclick="markAllRead()">
                                    <i class="fas fa-check-double"></i> সব পড়েছি
                                </button>
                            </div>
                            <div class="notif-panel-body" id="notifPanelBody">
                                <div class="notif-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                            </div>
                        </div>
                    </div>
                    <a href="/complaint" class="btn-new-complaint"><i class="fas fa-plus"></i> New Complaint</a>
                </div>
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
                        <tr><th>Complaint ID</th><th>Type</th><th>Location</th><th>Date</th><th>Anonymous</th><th>Status</th><th>Consent</th><th>Action</th></tr>
                    </thead>
                    <tbody id="all-tbody">
                        <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-secondary)"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── SOS RESPONSES ── -->
        <div id="view-sos-responds" style="display:none">
            <div class="welcome-bar">
                <h1><i class="fas fa-hands-helping" style="font-size:22px;margin-right:10px;color:#4f9eff"></i>My SOS Responses</h1>
            </div>
            <div id="sos-responds-container">
                <p style="color:var(--text-secondary);text-align:center;padding:40px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </p>
            </div>
        </div>

        <!-- ── ALL SOS REQUESTS ── -->
        <div id="view-all-sos" style="display:none">
            <div class="welcome-bar">
                <div>
                    <h1><i class="fas fa-bell" style="font-size:22px;margin-right:10px;color:#e63946"></i>All SOS Requests</h1>
                    <p style="color:var(--text-secondary);font-size:14px;">আজ পর্যন্ত সকল SOS requests</p>
                </div>
                <button onclick="loadAllSosRequests()" style="background:#1e2d4a;border:1px solid #2a3f5f;color:#a0b4cc;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div id="all-sos-container">
                <p style="color:var(--text-secondary);text-align:center;padding:40px;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </p>
            </div>
        </div>

    </main>
</div>

<!-- Active SOS Modal (last 30 minutes) -->
<div id="active-sos-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.80);z-index:99999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#0d1117;border:1px solid #e6394655;border-radius:16px;padding:0;max-width:480px;width:100%;position:relative;max-height:80vh;display:flex;flex-direction:column;">
        <div style="padding:18px 20px 14px;border-bottom:1px solid #1e2d4a;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:10px;height:10px;border-radius:50%;background:#e63946;display:inline-block;animation:sosRedPulse 1s infinite;"></span>
                <h3 style="margin:0;color:#fff;font-size:16px;font-weight:700;">🚨 Active SOS Alerts Near You</h3>
            </div>
            <button onclick="closeActiveSosModal()" style="background:none;border:none;color:#a0b4cc;font-size:22px;cursor:pointer;line-height:1;">×</button>
        </div>
        <p style="margin:0;padding:8px 20px;font-size:12px;color:#e63946;background:#e6394611;">গত 30 মিনিটের মধ্যে দেওয়া active SOS alerts</p>
        <div id="active-sos-body" style="padding:16px;overflow-y:auto;flex:1;"></div>
    </div>
</div>

<!-- SOS Detail Modal -->
<div id="sos-detail-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:999999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#0d1117;border:1px solid #e6394655;border-radius:16px;max-width:460px;width:100%;position:relative;max-height:85vh;display:flex;flex-direction:column;">
        <div style="padding:16px 20px;border-bottom:1px solid #1e2d4a;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <h3 style="margin:0;color:#fff;font-size:15px;font-weight:700;">🚨 SOS Alert Details</h3>
            <button onclick="closeSosDetailModal()" style="background:none;border:none;color:#a0b4cc;font-size:22px;cursor:pointer;line-height:1;">×</button>
        </div>
        <div id="sos-detail-body" style="padding:16px;overflow-y:auto;flex:1;"></div>
    </div>
</div>

<!-- Red Floating SOS Button -->
<style>
@keyframes sosPulseGlow {
    0%   { box-shadow: 0 0 0 0 rgba(230,57,70,0.7), 0 4px 20px rgba(230,57,70,0.4); }
    50%  { box-shadow: 0 0 0 14px rgba(230,57,70,0), 0 4px 30px rgba(230,57,70,0.8); }
    100% { box-shadow: 0 0 0 0 rgba(230,57,70,0), 0 4px 20px rgba(230,57,70,0.4); }
}
@keyframes sosIconPulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.15); }
}
#sos-float-btn {
    animation: sosPulseGlow 1.8s ease-in-out infinite;
}
#sos-float-btn i {
    animation: sosIconPulse 1.8s ease-in-out infinite;
}
#sos-float-btn:hover {
    animation: none !important;
    box-shadow: 0 0 0 16px rgba(230,57,70,0), 0 6px 28px rgba(230,57,70,0.9) !important;
    transform: scale(1.1);
}
</style>
<button onclick="openActiveSosModal()" id="sos-float-btn"
    style="position:fixed;bottom:30px;right:30px;width:56px;height:56px;border-radius:50%;background:#e63946;border:none;color:#fff;font-size:22px;cursor:pointer;z-index:9998;display:flex;align-items:center;justify-content:center;transition:transform .2s;"
    title="View active SOS alerts (last 30 minutes)">
    <i class="fas fa-exclamation"></i>
</button>

<!-- Responder Evidence Modal -->
<div id="resp-ev-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:99999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#111c33;border:1px solid #1e2d4a;border-radius:16px;padding:24px;max-width:460px;width:100%;position:relative;">
        <button onclick="document.getElementById('resp-ev-overlay').style.display='none'"
            style="position:absolute;top:12px;right:14px;background:none;border:none;color:#a0b4cc;font-size:24px;cursor:pointer;line-height:1;">×</button>
        <h3 style="color:#fff;margin:0 0 6px;font-size:16px;">📷 Submit Response Evidence</h3>
        <p style="color:#a0b4cc;font-size:13px;margin:0 0 16px;">Photo বা video upload করো যা প্রমাণ করে তুমি help করেছ। Admin verify করলে rank বাড়বে।</p>
        <div id="resp-ev-preview" style="margin-bottom:12px;"></div>
        <input type="file" id="resp-ev-file" accept="image/*,video/*"
            style="display:block;width:100%;margin-bottom:12px;color:#fff;font-size:13px;"
            onchange="previewRespEvFile(this)">
        <div id="resp-ev-msg" style="display:none;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;"></div>
        <button onclick="submitRespEvidence()"
            style="width:100%;background:#1a6496;border:none;color:#fff;padding:12px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
            🚀 Submit for Verification
        </button>
    </div>
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
    const sosEl = document.getElementById('view-sos-responds');
    if (sosEl) sosEl.style.display = section === 'sos-responds' ? 'block' : 'none';
    const allSosEl = document.getElementById('view-all-sos');
    if (allSosEl) allSosEl.style.display = section === 'all-sos' ? 'block' : 'none';
    document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
    const navEl = document.getElementById('nav-' + section);
    if (navEl) navEl.classList.add('active');
    if (section === 'mycomplaints') loadAllComplaints();
    if (section === 'sos-responds') loadSosResponds();
    if (section === 'all-sos') loadAllSosRequests();
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
                    ${(['PI Payment Pending','PI Notification Sent','PI Review Pending'].includes(c.status)) && (!c.payment_deadline || new Date(c.payment_deadline) > new Date()) ? `&nbsp;<button class="btn-view" style="background:#2d1a4a;border-color:#a855f7;color:#c084fc;white-space:nowrap;" onclick="openPaymentForComplaint('${c.complaint_id}')"><i class="fas fa-credit-card"></i> Pay for PI</button>` : ''}
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

// ── SOS Responds ───────────────────────────────
async function loadSosResponds() {
    const container = document.getElementById('sos-responds-container');
    if (!container) return;
    container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id;
        if (!userId) {
            container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:40px;">Please log in.</p>';
            return;
        }

        const res  = await fetch('/api/sos/my-responds?user_id=' + userId, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.responds || data.responds.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-secondary);"><i class="fas fa-hands-helping" style="font-size:40px;opacity:.2;display:block;margin-bottom:14px;"></i><p>You have not responded to any SOS alerts yet.</p></div>';
            return;
        }

        const sc = { pending:'#fbbf24', approved:'#2ecc71', rejected:'#e63946', not_submitted:'#4a5568', none:'#4a5568' };
        const sl = { pending:'Pending Review', approved:'Approved', rejected:'Rejected', not_submitted:'Not Submitted', none:'Not Submitted' };

        let rows = '';
        data.responds.forEach(function(r) {
            const ev = r.evidence_status || 'none';
            const canSubmit = (ev === 'not_submitted' || ev === 'none' || ev === 'rejected');
            const color = sc[ev] || '#4a5568';
            const label = sl[ev] || ev;

            let btn = '';
            if (canSubmit) {
                btn = '<button onclick="openResponderEvidenceModal(' + r.sos_id + ')" style="background:#1a4a6e;border:none;color:#fff;padding:5px 12px;border-radius:8px;font-size:12px;cursor:pointer;margin-right:6px;">Submit Evidence</button>';
            }

            const badge = '<span style="font-size:11px;font-weight:700;color:' + color + ';background:' + color + '22;border:1px solid ' + color + '44;border-radius:20px;padding:2px 10px;">' + label + '</span>';

            rows += '<tr>'
                + '<td style="font-weight:600;color:#4f9eff;">#' + r.sos_id + '</td>'
                + '<td>' + (r.victim_name || 'Anonymous') + '</td>'
                + '<td>' + (r.crime_type || '-') + '</td>'
                + '<td style="font-size:12px;color:#a0b4cc;">' + (r.location_text ? r.location_text.substring(0,35) + '...' : '-') + '</td>'
                + '<td>' + btn + badge + '</td>'
                + '</tr>';
        });

        const table = '<div class="complaints-table"><table>'
            + '<thead><tr><th>SOS ID</th><th>Victim</th><th>Type</th><th>Location</th><th>Evidence &amp; Status</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table></div>';

        container.innerHTML = table;

    } catch(e) {
        container.innerHTML = '<p style="text-align:center;color:#e63946;padding:40px;">Error loading data.</p>';
    }
}

let _respEvidenceSosId = null;

// ── All SOS Requests ───────────────────────────────
async function loadAllSosRequests() {
    const container = document.getElementById('all-sos-container');
    if (!container) return;
    container.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id;
        if (!userId) {
            container.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:40px;">Please log in.</p>';
            return;
        }

        const res  = await fetch('/api/sos/all-requests?user_id=' + userId, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.alerts || data.alerts.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--text-secondary);"><i class="fas fa-bell" style="font-size:40px;opacity:.2;display:block;margin-bottom:14px;"></i><p>কোনো SOS request পাওয়া যায়নি।</p></div>';
            return;
        }

        const statusColors = { active: '#e63946', cancelled: '#6b7280', resolved: '#2ecc71' };
        const statusLabels = { active: '🔴 Active', cancelled: '⚪ Cancelled', resolved: '✅ Resolved' };

        function timeAgo(dateStr) {
            const diff = Math.floor((Date.now() - new Date(dateStr)) / 60000);
            if (diff < 1)   return 'এইমাত্র';
            if (diff < 60)  return diff + ' মিনিট আগে';
            const h = Math.floor(diff / 60);
            if (h < 24)     return h + ' ঘণ্টা আগে';
            return Math.floor(h / 24) + ' দিন আগে';
        }

        let rows = '';
        data.alerts.forEach(function(a) {
            const statusColor = statusColors[a.status] || '#6b7280';
            const statusLabel = statusLabels[a.status] || a.status;
            const locationShort = a.location_text ? a.location_text.substring(0, 40) + (a.location_text.length > 40 ? '...' : '') : '-';
            rows += `<tr>
                <td style="font-weight:600;color:#e63946;">#${a.id}</td>
                <td>${a.victim_name || 'Anonymous'}</td>
                <td>${a.crime_type || '-'}</td>
                <td style="font-size:12px;color:#a0b4cc;">${locationShort}</td>
                <td><span style="font-size:11px;font-weight:700;color:${statusColor};background:${statusColor}22;border:1px solid ${statusColor}44;border-radius:20px;padding:2px 10px;">${statusLabel}</span></td>
                <td style="color:#a0b4cc;font-size:12px;">${timeAgo(a.created_at)}</td>
                <td style="font-size:12px;color:#4f9eff;">${a.responder_count} জন</td>
            </tr>`;
        });

        container.innerHTML = `<div class="complaints-table"><table>
            <thead><tr>
                <th>SOS ID</th><th>Victim</th><th>ধরন</th><th>Location</th><th>Status</th><th>সময়</th><th>Responders</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;

    } catch(e) {
        container.innerHTML = '<p style="text-align:center;color:#e63946;padding:40px;">Error loading data.</p>';
    }
}

// ── Active SOS Modal (Red Floating Button) ──────────
async function openActiveSosModal() {
    const modal = document.getElementById('active-sos-modal');
    const body  = document.getElementById('active-sos-body');
    if (!modal || !body) return;

    modal.style.display = 'flex';
    body.innerHTML = '<p style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const userId = svUser.id || svUser.user_id;
        const res    = await fetch('/api/sos/active-recent?user_id=' + userId, { credentials: 'include' });
        const data   = await res.json();

        if (!data.success || !data.alerts || data.alerts.length === 0) {
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#a0b4cc;"><i class="fas fa-shield-alt" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;"></i><p>গত 30 মিনিটে কোনো active SOS নেই।</p></div>';
            return;
        }

        body.innerHTML = data.alerts.map(function(a) {
            const minsAgo = a.minutes_ago != null ? a.minutes_ago : '?';
            const loc = a.location_text || 'Location unavailable';
            return `<div style="background:#1a0a0a;border:1px solid #e6394633;border-radius:12px;padding:16px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                    <div>
                        <span style="font-weight:700;color:#fff;font-size:14px;">👤 ${a.victim_name || 'Anonymous'}</span>
                        ${a.crime_type ? `<span style="margin-left:8px;font-size:11px;background:#e6394622;color:#e63946;border:1px solid #e6394644;border-radius:20px;padding:2px 8px;">${a.crime_type}</span>` : ''}
                    </div>
                    <span style="font-size:11px;color:#e63946;font-weight:600;">${minsAgo} মি. আগে</span>
                </div>
                <div style="font-size:12px;color:#a0b4cc;margin-bottom:10px;"><i class="fas fa-map-marker-alt" style="color:#e63946;margin-right:4px;"></i>${loc.substring(0, 70)}${loc.length > 70 ? '...' : ''}</div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:11px;color:#6b7280;">${a.responder_count} জন respond করেছেন</span>
                    <button onclick="openSosDetailFromDashboard(${a.id})" style="background:#e63946;border:none;color:#fff;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                        <i class="fas fa-eye"></i> View
                    </button>
                </div>
            </div>`;
        }).join('');

    } catch(e) {
        body.innerHTML = '<p style="text-align:center;color:#e63946;padding:30px;">Error loading data.</p>';
    }
}

function closeActiveSosModal() {
    const modal = document.getElementById('active-sos-modal');
    if (modal) modal.style.display = 'none';
}

// ── SOS Detail Modal from Dashboard ─────────────────────────
async function openSosDetailFromDashboard(sosId) {
    // Active SOS modal লুকাই আগে
    const activeModal = document.getElementById('active-sos-modal');
    if (activeModal) activeModal.style.display = 'none';

    // Details modal দেখাই
    const detailModal = document.getElementById('sos-detail-modal');
    const detailBody  = document.getElementById('sos-detail-body');
    if (!detailModal || !detailBody) return;

    detailModal.style.display = 'flex';
    detailBody.innerHTML = '<p style="text-align:center;padding:30px;color:#a0b4cc;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    try {
        const res  = await fetch('/api/sos/alerts?sos_id=' + sosId, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.sos) throw new Error();

        const s = data.sos;
        const lat = s.latitude;
        const lng = s.longitude;
        const mapsUrl = (lat && lng) ? `https://maps.google.com?q=${lat},${lng}` : null;

        detailBody.innerHTML = `
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#1a0a0a;border-radius:10px;">
                    <i class="fas fa-user-circle" style="color:#e63946;font-size:20px;"></i>
                    <div>
                        <div style="font-size:11px;color:#6b7280;">Victim</div>
                        <div style="color:#fff;font-weight:700;">${s.victim_name || 'Anonymous'}</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;padding:12px;background:#1a0a0a;border-radius:10px;">
                    <i class="fas fa-map-marker-alt" style="color:#e63946;font-size:20px;"></i>
                    <div>
                        <div style="font-size:11px;color:#6b7280;">Location</div>
                        <div style="color:#fff;font-size:13px;">${s.location_text || 'Not available'}</div>
                    </div>
                </div>
                ${s.crime_type ? `<div style="display:flex;align-items:center;gap:10px;padding:12px;background:#1a0a0a;border-radius:10px;">
                    <i class="fas fa-exclamation-triangle" style="color:#e63946;font-size:20px;"></i>
                    <div>
                        <div style="font-size:11px;color:#6b7280;">Crime Type</div>
                        <div style="color:#e63946;font-weight:700;">${s.crime_type}</div>
                    </div>
                </div>` : ''}
                ${s.description ? `<div style="padding:12px;background:#1a0a0a;border-radius:10px;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:4px;">Description</div>
                    <div style="color:#e2e8f0;font-size:13px;line-height:1.5;">${s.description}</div>
                </div>` : ''}
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px;">
                    ${mapsUrl ? `<a href="${mapsUrl}" target="_blank" style="flex:1;background:#1a4a6e;border:none;color:#fff;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;min-width:140px;">
                        <i class="fas fa-directions"></i> Navigate to Spot
                    </a>` : ''}
                    ${s.victim_phone ? `<a href="tel:${s.victim_phone}" style="flex:1;background:#1a4a1a;border:none;color:#2ecc71;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;border:1px solid #2ecc7140;min-width:140px;">
                        <i class="fas fa-phone"></i> Call Victim
                    </a>` : ''}
                    <a href="tel:999" style="flex:1;background:#e6394611;border:1px solid #e6394640;color:#e63946;padding:10px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;min-width:120px;">
                        <i class="fas fa-shield-alt"></i> Call Police
                    </a>
                </div>
            </div>`;
    } catch(e) {
        detailBody.innerHTML = '<p style="text-align:center;color:#e63946;padding:30px;">Details load করা যায়নি।</p>';
    }
}

function closeSosDetailModal() {
    const modal = document.getElementById('sos-detail-modal');
    if (modal) modal.style.display = 'none';
}

function openResponderEvidenceModal(sosId) {
    _respEvidenceSosId = sosId;
    const overlay = document.getElementById('resp-ev-overlay');
    if (!overlay) return;
    document.getElementById('resp-ev-preview').innerHTML = '';
    document.getElementById('resp-ev-file').value = '';
    const msg = document.getElementById('resp-ev-msg');
    if (msg) msg.style.display = 'none';
    overlay.style.display = 'flex';
}

function previewRespEvFile(input) {
    const preview = document.getElementById('resp-ev-preview');
    const file    = input.files[0];
    if (!file || !preview) return;
    preview.innerHTML = '';
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.cssText = 'max-width:100%;max-height:180px;border-radius:8px;object-fit:cover;display:block;';
        preview.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const vid = document.createElement('video');
        vid.src = URL.createObjectURL(file);
        vid.controls = true;
        vid.style.cssText = 'max-width:100%;border-radius:8px;display:block;';
        preview.appendChild(vid);
    }
}

async function submitRespEvidence() {
    const file = document.getElementById('resp-ev-file').files[0];
    const msg  = document.getElementById('resp-ev-msg');
    if (!file) {
        msg.textContent = 'Please select a file first.';
        msg.style.cssText = 'display:block;background:#e6394622;color:#e63946;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;';
        return;
    }
    if (!_respEvidenceSosId) return;

    msg.textContent = '⏳ Uploading...';
    msg.style.cssText = 'display:block;background:#4f9eff22;color:#4f9eff;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;';

    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const fd = new FormData();
    fd.append('sos_id',   _respEvidenceSosId);
    fd.append('evidence', file);
    fd.append('user_id',  svUser.id || svUser.user_id || '');

    try {
        const res  = await fetch('/api/sos/submit-responder-evidence', {
            method: 'POST', credentials: 'include',
            headers: { 'X-CSRF-TOKEN': csrf },
            body: fd,
        });
        const data = await res.json();
        if (data.success) {
            msg.textContent = '✅ Submitted! Admin will verify soon.';
            msg.style.cssText = 'display:block;background:#2ecc7122;color:#2ecc71;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;';
            setTimeout(() => {
                document.getElementById('resp-ev-overlay').style.display = 'none';
                loadSosResponds();
            }, 1500);
        } else {
            msg.textContent = '❌ ' + (data.message || 'Upload failed.');
            msg.style.cssText = 'display:block;background:#e6394622;color:#e63946;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;';
        }
    } catch(e) {
        msg.textContent = '❌ Network error.';
        msg.style.cssText = 'display:block;background:#e6394622;color:#e63946;padding:10px;border-radius:8px;font-size:13px;margin-bottom:10px;';
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
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-secondary)">No complaints yet. <a href="/complaint" style="color:#4f9eff">Submit one!</a></td></tr>';
            return;
        }
        tbody.innerHTML = data.complaints.map(c => {
            // Consent badge
            const lc = c.legal_consent;
            const pc = c.publish_consent;
            let consentHtml = '';
            if (lc === null && pc === null) {
                consentHtml = '<span style="font-size:11px;color:#4a5568;">—</span>';
            } else {
                const lBadge = lc === 'yes'
                    ? '<span style="font-size:10px;background:#4f9eff22;color:#4f9eff;border:1px solid #4f9eff44;border-radius:20px;padding:1px 7px;white-space:nowrap;">⚖️ Legal</span>'
                    : '<span style="font-size:10px;background:#e6394622;color:#e63946;border:1px solid #e6394644;border-radius:20px;padding:1px 7px;white-space:nowrap;">⚖️ No Legal</span>';
                const pBadge = pc === 'yes'
                    ? '<span style="font-size:10px;background:#2ecc7122;color:#2ecc71;border:1px solid #2ecc7144;border-radius:20px;padding:1px 7px;white-space:nowrap;">📢 Publish</span>'
                    : '<span style="font-size:10px;background:#e6394622;color:#e63946;border:1px solid #e6394644;border-radius:20px;padding:1px 7px;white-space:nowrap;">📢 No Publish</span>';
                consentHtml = '<div style="display:flex;flex-direction:column;gap:3px;">' + lBadge + pBadge + '</div>';
            }

            return `
            <tr>
                <td><strong style="color:#4f9eff">${c.complaint_id}</strong></td>
                <td>${formatType(c.type)}</td>
                <td style="font-size:12px;color:var(--text-secondary);word-break:break-word;max-width:130px;">${c.location || '—'}</td>
                <td style="font-size:12px;color:var(--text-secondary)">${formatDate(c.submitted_at)}</td>
                <td style="text-align:center">${c.is_anonymous == 1 ? '<i class="fas fa-user-secret" style="color:#4f9eff" title="Anonymous"></i>' : '<i class="fas fa-user" style="color:var(--text-secondary)"></i>'}</td>
                <td>${statusBadge(c.status)}</td>
                <td>${consentHtml}</td>
                <td style="white-space:nowrap;">
                    <a href="/track?id=${c.complaint_id}" class="btn-view"><i class="fas fa-eye"></i> Track</a>
                    &nbsp;
                    <button class="btn-view" style="background:#1a3a2a;border-color:#2ecc71;color:#2ecc71;" onclick="openEvidenceModal('${c.complaint_id}')"><i class="fas fa-paperclip"></i> Evidence</button>
                    ${(['PI Payment Pending','PI Notification Sent','PI Review Pending'].includes(c.status)) && (!c.payment_deadline || new Date(c.payment_deadline) > new Date()) ? `&nbsp;<button class="btn-view" style="background:#2d1a4a;border-color:#a855f7;color:#c084fc;white-space:nowrap;" onclick="openPaymentForComplaint('${c.complaint_id}')"><i class="fas fa-credit-card"></i> Pay for PI</button>` : ''}
                </td>
            </tr>`; }).join('');
    } catch(e) {
        console.error('loadAllComplaints error:', e);
        document.getElementById('all-tbody').innerHTML =
            '<tr><td colspan="8" style="text-align:center;padding:30px;color:#e63946"><i class="fas fa-exclamation-circle"></i> Could not load. <a href="#" onclick="loadAllComplaints()" style="color:#4f9eff">Retry</a></td></tr>';
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
    // FCM token মুছো logout এর আগে
    await unregisterFcmToken().catch(() => {});

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

    // Check for pending evidence requests
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    const userId = svUser.id || svUser.user_id || '';
    if (userId) {
        // Slight delay so other modals load first
        setTimeout(() => checkEvidenceRequests(userId), 2000);
    }
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
<!-- EVIDENCE REQUEST NOTIFICATION MODAL -->
<div id="evReqNotifModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.82);z-index:10001;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#0d1526;border:1px solid #d97706;border-radius:20px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 0 40px #d9770640;">

        <!-- Header -->
        <div style="padding:20px 24px;border-bottom:1px solid #1e2d4a;display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#1c0f00,#2d1800);border-radius:20px 20px 0 0;">
            <div style="background:#d9770620;border:1px solid #d97706;border-radius:12px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-file-upload" style="color:#d97706;font-size:20px;"></i>
            </div>
            <div>
                <h3 style="margin:0;color:#fff;font-size:16px;font-weight:700;">Additional Evidence Required</h3>
                <p style="margin:3px 0 0;color:#a0b4cc;font-size:12px;">Admin has requested more evidence for your complaint</p>
            </div>
        </div>

        <!-- Body -->
        <div style="padding:20px 24px;">
            <!-- Complaint ID badge -->
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="margin:0;color:#4a5568;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Complaint</p>
                    <p id="erNotifComplaintId" style="margin:4px 0 0;color:#4f9eff;font-size:16px;font-weight:800;"></p>
                    <p id="erNotifComplaintType" style="margin:2px 0 0;color:#a0b4cc;font-size:12px;"></p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;color:#4a5568;font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Deadline</p>
                    <p id="erNotifDeadline" style="margin:4px 0 0;color:#e63946;font-size:13px;font-weight:700;"></p>
                </div>
            </div>

            <!-- Admin note -->
            <div id="erNotifNoteBox" style="display:none;background:#140d00;border:1px solid #d9770650;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
                <p style="margin:0 0 6px;color:#d97706;font-size:12px;font-weight:700;"><i class="fas fa-comment-alt"></i> Note from Admin</p>
                <p id="erNotifNote" style="margin:0;color:#e5c88a;font-size:13px;line-height:1.6;"></p>
            </div>

            <!-- Upload section -->
            <div>
                <p style="font-size:13px;font-weight:700;color:#a0b4cc;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Upload Evidence Now</p>
                <div id="erUploadBox" style="background:#0a0f1e;border:2px dashed #d97706;border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border .2s;" onclick="document.getElementById('erFileInput').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:28px;color:#d97706;margin-bottom:8px;display:block;"></i>
                    <p style="color:#fff;font-size:13px;margin:0 0 4px;">Click to select files</p>
                    <span style="color:#4a5568;font-size:12px;">JPG, PNG, PDF — max 10MB each</span>
                </div>
                <input type="file" id="erFileInput" accept="image/jpeg,image/png,image/gif,image/webp,.pdf" multiple style="position:absolute;left:-9999px;" onchange="erHandleFiles(this)" />
                <div id="erFileList" style="margin-top:10px;display:none;">
                    <ul id="erFileNames" style="list-style:none;padding:0;margin:0;font-size:13px;color:#a0b4cc;"></ul>
                </div>
                <div id="erUploadMsg" style="margin-top:10px;font-size:13px;text-align:center;display:none;padding:8px 12px;border-radius:8px;"></div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:16px 24px;border-top:1px solid #1e2d4a;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
            <button onclick="erSkipForNow()" style="padding:10px 20px;background:#111c33;color:#a0b4cc;border:1px solid #1e2d4a;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-clock"></i> Skip for Now
            </button>
            <button onclick="rejectEvidenceRequest()"
style="
background:#991b1b;
color:white;
border:none;
padding:12px 18px;
border-radius:10px;
font-weight:700;
cursor:pointer;
margin-left:10px;
">
    Reject Request
</button>
            <button id="erSubmitBtn" onclick="erSubmitEvidence()" style="padding:10px 24px;background:linear-gradient(135deg,#78350f,#d97706);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-paper-plane"></i> Submit Evidence
            </button>
        </div>
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

// ══════════════════════════════════════════════════════════════
// EVIDENCE REQUEST NOTIFICATION SYSTEM
// ══════════════════════════════════════════════════════════════
let erCurrentRequestId   = null;
let erPendingQueue       = [];
let erQueueIndex         = 0;

// Called after user is confirmed logged in — check for pending evidence requests
async function checkEvidenceRequests(userId) {
    try {
        const res  = await fetch(`/api/evidence-request/pending?user_id=${userId}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.requests || data.requests.length === 0) return;
        erPendingQueue = data.requests;
        erQueueIndex   = 0;
        erShowNext();
    } catch(e) { /* silent */ }
}

function erShowNext() {
    if (erQueueIndex >= erPendingQueue.length) return; // all done
    const req = erPendingQueue[erQueueIndex];
    erCurrentRequestId = req.id;

    document.getElementById('erNotifComplaintId').textContent   = req.complaint_id;
    document.getElementById('erNotifComplaintType').textContent = req.complaint_type || '';

    // Deadline display
    const dl = req.deadline ? new Date(req.deadline) : null;
    const dlText = dl ? dl.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '—';
    const daysLeft = dl ? Math.max(0, Math.ceil((dl - Date.now()) / 86400000)) : null;
    document.getElementById('erNotifDeadline').textContent = dl
        ? `${dlText} (${daysLeft} day${daysLeft !== 1 ? 's' : ''} left)`
        : '—';
    document.getElementById('erNotifDeadline').style.color = (daysLeft !== null && daysLeft <= 2) ? '#e63946' : '#f39c12';

    // Admin note
    const noteBox = document.getElementById('erNotifNoteBox');
    const noteEl  = document.getElementById('erNotifNote');
    if (req.admin_note) {
        noteEl.textContent  = req.admin_note;
        noteBox.style.display = 'block';
    } else {
        noteBox.style.display = 'none';
    }

    // Reset upload UI
    document.getElementById('erFileInput').value  = '';
    document.getElementById('erFileList').style.display  = 'none';
    document.getElementById('erFileNames').innerHTML     = '';
    document.getElementById('erUploadMsg').style.display = 'none';
    document.getElementById('erSubmitBtn').disabled      = false;
    document.getElementById('erSubmitBtn').innerHTML     = '<i class="fas fa-paper-plane"></i> Submit Evidence';

    document.getElementById('evReqNotifModal').style.display = 'flex';
}

function erHandleFiles(input) {
    const files = input.files;
    const list  = document.getElementById('erFileList');
    const names = document.getElementById('erFileNames');
    if (!files.length) { list.style.display = 'none'; return; }
    names.innerHTML = '';
    Array.from(files).forEach(f => {
        const li   = document.createElement('li');
        li.style.cssText = 'padding:4px 0;display:flex;align-items:center;gap:8px;';
        const icon = f.name.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file-image';
        li.innerHTML = `<i class="fas ${icon}" style="color:#d97706;width:16px;"></i> ${escDash(f.name)} <span style="color:#4a5568;font-size:12px;">(${(f.size/1024/1024).toFixed(2)} MB)</span>`;
        names.appendChild(li);
    });
    list.style.display = 'block';
}

async function erSubmitEvidence() {
    const input = document.getElementById('erFileInput');
    const btn   = document.getElementById('erSubmitBtn');
    const msg   = document.getElementById('erUploadMsg');

    if (!input.files.length) {
        msg.style.display  = 'block';
        msg.style.background = '#140900';
        msg.style.color    = '#f39c12';
        msg.innerHTML      = '<i class="fas fa-exclamation-triangle"></i> Please select at least one file to upload.';
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    msg.style.display = 'none';

    // Upload files first
    const complaintId = document.getElementById('erNotifComplaintId').textContent;
    const formData    = new FormData();
    formData.append('complaint_id', complaintId);
    for (let i = 0; i < input.files.length; i++) {
        formData.append('evidence[]', input.files[i]);
    }

    try {
        const uploadRes  = await fetch('/api/upload_complaint_evidence', {
            method: 'POST', credentials: 'include', body: formData
        });
        const uploadData = await uploadRes.json();

        if (!uploadData.success) {
            msg.style.display    = 'block';
            msg.style.background = '#1a0a0a';
            msg.style.color      = '#e63946';
            msg.innerHTML        = `<i class="fas fa-exclamation-circle"></i> Upload failed: ${uploadData.message || 'Unknown error'}`;
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence';
            return;
        }

        // Mark evidence request as submitted
        await fetch('/api/evidence-request/mark-submitted', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: erCurrentRequestId }),
        });

        msg.style.display    = 'block';
        msg.style.background = '#0a1a10';
        msg.style.color      = '#2ecc71';
        msg.innerHTML        = `<i class="fas fa-check-circle"></i> ${uploadData.message} Evidence submitted successfully!`;

        setTimeout(() => {
            document.getElementById('evReqNotifModal').style.display = 'none';
            erQueueIndex++;
            erShowNext(); // show next pending request if any
        }, 1800);

    } catch(e) {
        msg.style.display    = 'block';
        msg.style.background = '#1a0a0a';
        msg.style.color      = '#e63946';
        msg.innerHTML        = '<i class="fas fa-exclamation-circle"></i> Upload failed. Check your connection.';
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Evidence';
    }
}

async function erSkipForNow() {
    if (!erCurrentRequestId) {
        document.getElementById('evReqNotifModal').style.display = 'none';
        return;
    }
    try {
        await fetch('/api/evidence-request/skip', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: erCurrentRequestId }),
        });
    } catch(e) { /* silent */ }

    document.getElementById('evReqNotifModal').style.display = 'none';
    erCurrentRequestId = null;
    erQueueIndex++;
    // Show next pending request after a short delay
    setTimeout(erShowNext, 400);
}

async function rejectEvidenceRequest() {
    if (!erCurrentRequestId) {
        document.getElementById('evReqNotifModal').style.display = 'none';
        return;
    }
    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        await fetch('/api/evidence-request/reject', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                request_id: erCurrentRequestId,
                user_id: svUser.id || svUser.user_id,
            }),
        });
    } catch(e) { /* silent */ }

    document.getElementById('evReqNotifModal').style.display = 'none';
    erCurrentRequestId = null;
    erQueueIndex++;
    setTimeout(erShowNext, 400);
}

</script>

@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
<script src="{{ asset('js/fcm.js') }}"></script>
<script>
    // FCM init
    document.addEventListener('DOMContentLoaded', function() {
        const svUser = localStorage.getItem('sv_user');
        if (svUser) {
            setTimeout(() => initFCM(), 3000);
        }
    });
</script>

{{-- ══ NOTIFICATION BELL JAVASCRIPT ══════════════════════════════════ --}}
<script>
// ─── getUserId helper (sv_user থেকে নেয়) ─────────────────────────────
function getUserId() {
    try {
        const u = JSON.parse(localStorage.getItem('sv_user') || '{}');
        return u.id || u.user_id || null;
    } catch(e) { return null; }
}

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ─── State ────────────────────────────────────────────────────────────
let _notifOpen    = false;
let _notifLoaded  = false;
let _pollInterval = null;

// ─── Toggle Panel ─────────────────────────────────────────────────────
function toggleNotifPanel() {
    _notifOpen = !_notifOpen;
    const panel = document.getElementById('notifPanel');
    if (!panel) return;
    panel.style.display = _notifOpen ? 'flex' : 'none';
    if (_notifOpen && !_notifLoaded) loadNotifications();
}

// Outside click এ close করো
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notifBellWrapper');
    if (wrapper && !wrapper.contains(e.target) && _notifOpen) {
        _notifOpen = false;
        const p = document.getElementById('notifPanel');
        if (p) p.style.display = 'none';
    }
});

// ─── Load Notifications ───────────────────────────────────────────────
async function loadNotifications() {
    const userId = getUserId();
    if (!userId) return;
    try {
        const res  = await fetch(`/api/notifications?user_id=${userId}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;
        _notifLoaded = true;
        renderNotifications(data.notifications);
        updateBadge(data.unread_count);
    } catch(e) { console.error('Notification load error:', e); }
}

// ─── Render ───────────────────────────────────────────────────────────
function renderNotifications(notifications) {
    const body = document.getElementById('notifPanelBody');
    if (!body) return;

    if (!notifications || notifications.length === 0) {
        body.innerHTML = `<div class="notif-empty">
            <i class="fas fa-bell-slash"></i>
            <p>কোনো notification নেই</p>
        </div>`;
        return;
    }

    body.innerHTML = notifications.map(n => `
        <div class="notif-item ${n.is_read ? '' : 'unread'}" id="notif-${n.id}"
             onclick="notifClick(${n.id}, '${escJs(n.action_url || '')}')">
            <div class="notif-icon">${n.icon || '🔔'}</div>
            <div class="notif-content">
                <div class="notif-title">${escHtml(n.title)}</div>
                <div class="notif-msg">${escHtml(n.message)}</div>
                <div class="notif-time">${timeAgo(n.created_at)}</div>
            </div>
            <button class="notif-delete-btn" onclick="deleteNotif(event, ${n.id})" title="Delete">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

// ─── Click → Mark Read + Navigate ─────────────────────────────────────
async function notifClick(id, url) {
    const userId = getUserId();
    if (!userId) return;
    try {
        await fetch('/api/notifications/mark-read', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ id, user_id: userId })
        });
    } catch(e) {}
    const el = document.getElementById(`notif-${id}`);
    if (el) el.classList.remove('unread');
    refreshBadge();
    if (url && url !== 'null' && url.trim() !== '') {
        window.location.href = url;
    }
}

// ─── Delete ───────────────────────────────────────────────────────────
async function deleteNotif(event, id) {
    event.stopPropagation();
    const userId = getUserId();
    if (!userId) return;
    try {
        await fetch(`/api/notifications/${id}?user_id=${userId}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'X-CSRF-TOKEN': getCsrf() }
        });
    } catch(e) {}
    const el = document.getElementById(`notif-${id}`);
    if (el) {
        el.style.transition = 'opacity .2s, transform .2s';
        el.style.opacity    = '0';
        el.style.transform  = 'translateX(10px)';
        setTimeout(() => {
            el.remove();
            const body = document.getElementById('notifPanelBody');
            if (body && body.querySelectorAll('.notif-item').length === 0) {
                body.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>কোনো notification নেই</p></div>`;
            }
        }, 200);
    }
    refreshBadge();
}

// ─── Mark All Read ────────────────────────────────────────────────────
async function markAllRead() {
    const userId = getUserId();
    if (!userId) return;
    try {
        await fetch('/api/notifications/mark-read', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ all: true, user_id: userId })
        });
    } catch(e) {}
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    updateBadge(0);
}

// ─── Badge ────────────────────────────────────────────────────────────
function updateBadge(count) {
    const badge = document.getElementById('notifBadge');
    const btn   = document.getElementById('notifBellBtn');
    if (!badge || !btn) return;
    if (count > 0) {
        badge.textContent   = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
        btn.classList.add('has-unread');
    } else {
        badge.style.display = 'none';
        btn.classList.remove('has-unread');
    }
}

async function refreshBadge() {
    const userId = getUserId();
    if (!userId) return;
    try {
        const res  = await fetch(`/api/notifications/unread-count?user_id=${userId}`, { credentials: 'include' });
        const data = await res.json();
        if (data.success !== undefined) updateBadge(data.count);
    } catch(e) {}
}

// ─── Auto Poll every 30s ─────────────────────────────────────────────
function startNotifPolling() {
    refreshBadge();
    _pollInterval = setInterval(() => {
        refreshBadge();
        if (_notifOpen) loadNotifications();
    }, 30000);
}

// ─── Helpers ──────────────────────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
function escJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/\n/g, '');
}
function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'এইমাত্র';
    if (diff < 3600)  return Math.floor(diff / 60)   + ' মিনিট আগে';
    if (diff < 86400) return Math.floor(diff / 3600)  + ' ঘণ্টা আগে';
    return Math.floor(diff / 86400) + ' দিন আগে';
}

// ─── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    if (getUserId()) startNotifPolling();
});
</script>
@endsection