@extends('layouts.app')
@section('title', 'Settings — SafeVoice')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

/* ── LAYOUT ── */
.settings-layout {
    display: flex;
    min-height: calc(100vh - 70px);
    background: #070d1a;
}

/* ── SIDEBAR ── */
.settings-sidebar {
    width: 260px;
    background: #0d1526;
    border-right: 1px solid #1a2a45;
    padding: 32px 0 24px;
    position: sticky;
    top: 70px;
    height: calc(100vh - 70px);
    overflow-y: auto;
    flex-shrink: 0;
}

.settings-sidebar-header {
    padding: 0 24px 24px;
    border-bottom: 1px solid #1a2a45;
    margin-bottom: 8px;
}

.settings-sidebar-header .back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4f9eff;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 16px;
    opacity: .8;
    transition: opacity .2s;
}
.settings-sidebar-header .back-link:hover { opacity: 1; }

.settings-sidebar-header h2 {
    color: #fff;
    font-size: 18px;
    font-weight: 800;
    margin: 0 0 4px;
    letter-spacing: -.3px;
}
.settings-sidebar-header p {
    color: #4f6080;
    font-size: 12px;
    margin: 0;
}

.settings-nav {
    list-style: none;
    padding: 8px 12px;
    margin: 0;
}

.settings-nav li {
    margin-bottom: 2px;
}

.settings-nav li a, .settings-nav li button {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 10px;
    color: #7a90ab;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    transition: all .18s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.settings-nav li a:hover, .settings-nav li button:hover {
    background: #1a2a45;
    color: #d0dff0;
}

.settings-nav li.active a, .settings-nav li.active button {
    background: rgba(79,158,255,.12);
    color: #4f9eff;
    font-weight: 600;
}

.settings-nav li.active a .nav-icon,
.settings-nav li.active button .nav-icon {
    color: #4f9eff;
}

.settings-nav .nav-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(255,255,255,.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    transition: background .18s;
}

.settings-nav li.active .nav-icon {
    background: rgba(79,158,255,.15);
}

.settings-nav .nav-divider {
    height: 1px;
    background: #1a2a45;
    margin: 10px 0;
}

.nav-danger button {
    color: #e63946 !important;
}
.nav-danger .nav-icon {
    background: rgba(230,57,70,.1) !important;
}

/* ── MAIN CONTENT ── */
.settings-main {
    flex: 1;
    padding: 40px 48px;
    max-width: 760px;
    overflow-y: auto;
}

/* ── SECTION ── */
.settings-section {
    display: none;
}
.settings-section.active {
    display: block;
    animation: sectionFadeIn .22s ease;
}

@keyframes sectionFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.section-header {
    margin-bottom: 32px;
}

.section-header h1 {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
    margin: 0 0 6px;
    letter-spacing: -.4px;
}

.section-header p {
    color: #4f6080;
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
}

/* ── CARD ── */
.settings-card {
    background: #0d1526;
    border: 1px solid #1a2a45;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
}

.settings-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #4f9eff;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── FORM FIELDS ── */
.field-group {
    margin-bottom: 18px;
}

.field-group:last-child { margin-bottom: 0; }

.field-label {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #7a90ab;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 8px;
}

.field-label .lock-icon {
    color: #2a3f5f;
    font-size: 10px;
}

.field-locked {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #060c18;
    border: 1px solid #1a2a45;
    border-radius: 10px;
    padding: 12px 16px;
    color: #3a4f6a;
    font-size: 14px;
}

.field-locked .lock-icon {
    color: #2a3f5f;
    font-size: 12px;
    flex-shrink: 0;
}

.field-input {
    width: 100%;
    background: #0a1628;
    border: 1.5px solid #1e2d4a;
    border-radius: 10px;
    padding: 12px 16px;
    color: #fff;
    font-size: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.field-input:focus {
    border-color: #4f9eff;
    box-shadow: 0 0 0 3px rgba(79,158,255,.1);
}

.field-input::placeholder { color: #2a3f5f; }

.field-hint {
    color: #4f6080;
    font-size: 11.5px;
    margin-top: 6px;
    display: flex;
    align-items: flex-start;
    gap: 5px;
    line-height: 1.5;
}

/* ── BUTTONS ── */
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #185FA5, #378ADD);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    width: 100%;
    margin-top: 6px;
}
.btn-primary:hover { opacity: .9; transform: translateY(-1px); }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

.btn-warning {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #854F0B, #d97706);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    width: 100%;
}
.btn-warning:hover { opacity: .9; }
.btn-warning:disabled { opacity: .5; cursor: not-allowed; }

.btn-success {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #166534, #16a34a);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 700;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: opacity .2s;
    width: 100%;
}
.btn-success:hover { opacity: .9; }

.btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: none;
    color: #4f6080;
    border: 1px solid #1a2a45;
    border-radius: 10px;
    padding: 11px 20px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
}
.btn-ghost:hover { background: #1a2a45; color: #d0dff0; border-color: #2a3f5f; }

/* ── ALERT MESSAGES ── */
.alert {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 500;
    margin-top: 14px;
    display: none;
    align-items: center;
    gap: 8px;
}
.alert.show { display: flex; }
.alert-success { background: #0a2010; border: 1px solid #166534; color: #4ade80; }
.alert-error   { background: #1a0a0a; border: 1px solid #ef4444; color: #f87171; }
.alert-info    { background: #071428; border: 1px solid #1e3a5a; color: #60a5fa; }

/* ── OTP INPUT ── */
.otp-input {
    letter-spacing: 10px;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
}

/* ── PROFILE AVATAR ── */
.profile-avatar-area {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #0a1628, #0d1f3a);
    border: 1px solid #1a2a45;
    border-radius: 14px;
    margin-bottom: 24px;
}

.profile-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e3a5a, #2a5080);
    border: 2px solid #2a3f5f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #4f9eff;
    flex-shrink: 0;
}

.profile-avatar-info h3 {
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px;
}

.profile-avatar-info p {
    color: #4f6080;
    font-size: 12px;
    margin: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(46,204,113,.1);
    border: 1px solid rgba(46,204,113,.3);
    color: #2ecc71;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 700;
    margin-top: 6px;
}

/* ── DANGER ZONE ── */
.danger-zone-card {
    background: #0d1526;
    border: 1px solid #3a1515;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 20px;
}

.danger-zone-title {
    font-size: 13px;
    font-weight: 700;
    color: #e63946;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.danger-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid #1a2a45;
}
.danger-item:last-child { border-bottom: none; padding-bottom: 0; }

.danger-item-info h4 {
    color: #d0dff0;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 3px;
}
.danger-item-info p {
    color: #4f6080;
    font-size: 12px;
    margin: 0;
    line-height: 1.4;
}

.btn-danger {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(230,57,70,.1);
    border: 1px solid rgba(230,57,70,.3);
    color: #e63946;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
    flex-shrink: 0;
}
.btn-danger:hover { background: rgba(230,57,70,.2); border-color: rgba(230,57,70,.5); }

/* ── NOTIFICATION SETTINGS ── */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #0f1e35;
}
.toggle-row:last-child { border-bottom: none; padding-bottom: 0; }

.toggle-info h4 {
    color: #d0dff0;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 2px;
}
.toggle-info p {
    color: #4f6080;
    font-size: 12px;
    margin: 0;
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: #1a2a45;
    border-radius: 24px;
    cursor: pointer;
    transition: background .25s;
}
.toggle-slider:before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    top: 3px;
    background: #4f6080;
    border-radius: 50%;
    transition: transform .25s, background .25s;
}
.toggle-switch input:checked + .toggle-slider { background: rgba(79,158,255,.25); }
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
    background: #4f9eff;
}

/* ── SECURITY ITEMS ── */
.security-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #0f1e35;
}
.security-item:last-child { border-bottom: none; padding-bottom: 0; }

.security-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(79,158,255,.08);
    border: 1px solid rgba(79,158,255,.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #4f9eff;
    flex-shrink: 0;
}

.security-info { flex: 1; }
.security-info h4 {
    color: #d0dff0;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 2px;
}
.security-info p {
    color: #4f6080;
    font-size: 12px;
    margin: 0;
}

/* ── STEP INDICATOR ── */
.step-indicator {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 24px;
}
.step {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}
.step-circle {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #1a2a45;
    border: 2px solid #2a3f5f;
    color: #4f6080;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all .3s;
}
.step.active .step-circle {
    background: rgba(79,158,255,.15);
    border-color: #4f9eff;
    color: #4f9eff;
}
.step.done .step-circle {
    background: rgba(46,204,113,.15);
    border-color: #2ecc71;
    color: #2ecc71;
}
.step-label { font-size: 12px; color: #4f6080; font-weight: 500; }
.step.active .step-label { color: #4f9eff; font-weight: 600; }
.step.done .step-label { color: #2ecc71; }
.step-line {
    flex: 1;
    height: 2px;
    background: #1a2a45;
    margin: 0 8px;
    transition: background .3s;
}
.step-line.done { background: #2ecc71; }

/* ── MOBILE ── */
@media (max-width: 768px) {
    .settings-sidebar {
        display: none;
    }
    .settings-main {
        padding: 24px 20px;
    }
    .mobile-settings-nav {
        display: flex !important;
    }
}

.mobile-settings-nav {
    display: none;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 16px;
    margin-bottom: 24px;
    scrollbar-width: none;
}
.mobile-settings-nav::-webkit-scrollbar { display: none; }

.mobile-nav-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    background: #0d1526;
    border: 1px solid #1a2a45;
    border-radius: 20px;
    color: #7a90ab;
    padding: 8px 14px;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif;
    cursor: pointer;
    transition: all .2s;
    flex-shrink: 0;
}
.mobile-nav-btn.active {
    background: rgba(79,158,255,.1);
    border-color: rgba(79,158,255,.4);
    color: #4f9eff;
}
</style>
@endsection

@section('content')
<div class="settings-layout">

    {{-- ── SIDEBAR ── --}}
    <aside class="settings-sidebar">
        <div class="settings-sidebar-header">
            <a href="/dashboard" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h2>Settings</h2>
            <p>Manage your account</p>
        </div>

        <ul class="settings-nav">
            <li id="snav-profile" class="active">
                <a href="#" onclick="showSettingsSection('profile')">
                    <span class="nav-icon"><i class="fas fa-user"></i></span>
                    Profile Info
                </a>
            </li>
            <li id="snav-password">
                <a href="#" onclick="showSettingsSection('password')">
                    <span class="nav-icon"><i class="fas fa-lock"></i></span>
                    Password
                </a>
            </li>
            <li id="snav-notifications">
                <a href="#" onclick="showSettingsSection('notifications')">
                    <span class="nav-icon"><i class="fas fa-bell"></i></span>
                    Notifications
                </a>
            </li>
            <li id="snav-security">
                <a href="#" onclick="showSettingsSection('security')">
                    <span class="nav-icon"><i class="fas fa-shield-alt"></i></span>
                    Security
                </a>
            </li>

            <li class="nav-divider"></li>

            <li class="nav-danger">
                <button onclick="doLogout()">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    Logout
                </button>
            </li>
        </ul>
    </aside>

    {{-- ── MAIN ── --}}
    <main class="settings-main">

        {{-- Mobile nav chips --}}
        <div class="mobile-settings-nav">
            <button class="mobile-nav-btn active" id="msnav-profile" onclick="showSettingsSection('profile')">
                <i class="fas fa-user"></i> Profile
            </button>
            <button class="mobile-nav-btn" id="msnav-password" onclick="showSettingsSection('password')">
                <i class="fas fa-lock"></i> Password
            </button>
            <button class="mobile-nav-btn" id="msnav-notifications" onclick="showSettingsSection('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <button class="mobile-nav-btn" id="msnav-security" onclick="showSettingsSection('security')">
                <i class="fas fa-shield-alt"></i> Security
            </button>
        </div>

        {{-- ════ PROFILE SECTION ════ --}}
        <div id="section-profile" class="settings-section active">
            <div class="section-header">
                <h1>Profile Information</h1>
                <p>Manage your personal details and contact information.</p>
            </div>

            {{-- Avatar card --}}
            <div class="profile-avatar-area">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-avatar-info">
                    <h3 id="avatarName">Loading...</h3>
                    <p id="avatarEmail" style="color:#4f6080;font-size:12px;"></p>
                    <div class="status-badge">
                        <i class="fas fa-circle" style="font-size:7px;"></i> Active Account
                    </div>
                </div>
            </div>

            {{-- Locked fields --}}
            <div class="settings-card">
                <div class="settings-card-title">
                    <i class="fas fa-lock" style="font-size:11px;"></i> Identity (Read-only)
                </div>

                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-user lock-icon"></i> Full Name
                        <span style="color:#2a3f5f;font-weight:400;text-transform:none;font-size:11px;">(পরিবর্তন করা যাবে না)</span>
                    </div>
                    <div class="field-locked">
                        <i class="fas fa-lock lock-icon"></i>
                        <span id="settingNameDisplay">—</span>
                    </div>
                </div>

                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-id-card lock-icon"></i> NID / Birth Certificate
                        <span style="color:#2a3f5f;font-weight:400;text-transform:none;font-size:11px;">(পরিবর্তন করা যাবে না)</span>
                    </div>
                    <div class="field-locked">
                        <i class="fas fa-lock lock-icon"></i>
                        <span id="settingNidDisplay">••••••••••</span>
                    </div>
                </div>
            </div>

            {{-- Editable fields --}}
            <div class="settings-card">
                <div class="settings-card-title">
                    <i class="fas fa-edit" style="font-size:11px;"></i> Contact Details
                </div>

                <div class="field-group">
                    <div class="field-label">Phone Number</div>
                    <input type="tel" id="settingPhone" class="field-input" placeholder="01XXXXXXXXX" maxlength="11">
                </div>

                <div class="field-group">
                    <div class="field-label">Email Address</div>
                    <input type="email" id="settingEmail" class="field-input" placeholder="you@example.com">
                    <div class="field-hint">
                        <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px;color:#4f9eff;"></i>
                        Email পরিবর্তন হলে তোমার নতুন address এ confirmation mail যাবে।
                    </div>
                </div>

                <div id="profileMsg" class="alert"></div>

                <button class="btn-primary" id="profileSaveBtn" onclick="saveProfileSettings()" style="margin-top:18px;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>

        {{-- ════ PASSWORD SECTION ════ --}}
        <div id="section-password" class="settings-section">
            <div class="section-header">
                <h1>Change Password</h1>
                <p>Update your password to keep your account secure.</p>
            </div>

            {{-- Info card --}}
            <div class="settings-card" style="background:linear-gradient(135deg,#071428,#0a1e38);border-color:#1e3a5a;">
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(79,158,255,.1);border:1px solid rgba(79,158,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-info-circle" style="color:#4f9eff;font-size:16px;"></i>
                    </div>
                    <div>
                        <div style="color:#d0dff0;font-size:14px;font-weight:600;margin-bottom:4px;">OTP Verification Required</div>
                        <div style="color:#4f6080;font-size:13px;line-height:1.6;">Password পরিবর্তন করতে তোমার email এ একটি OTP পাঠানো হবে। OTP verify করার পর নতুন password set করতে পারবে।</div>
                    </div>
                </div>
            </div>

            {{-- Step indicator --}}
            <div class="step-indicator" id="pwStepIndicator">
                <div class="step active" id="pwStepDot1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Send OTP</div>
                </div>
                <div class="step-line" id="pwStepLine1"></div>
                <div class="step active" id="pwStepDot2" style="opacity:.4;">
                    <div class="step-circle">2</div>
                    <div class="step-label">Verify & Reset</div>
                </div>
            </div>

            {{-- Step 1 --}}
            <div class="settings-card" id="pwStep1">
                <div class="settings-card-title">
                    <i class="fas fa-paper-plane" style="font-size:11px;"></i> Step 1 — Request OTP
                </div>
                <p style="color:#7a90ab;font-size:13px;margin:0 0 18px;line-height:1.6;">
                    OTP পাঠানো হবে: <strong id="pwEmailDisplay" style="color:#4f9eff;"></strong>
                </p>
                <button class="btn-warning" id="pwSendOtpBtn" onclick="requestPasswordOTP()">
                    <i class="fas fa-paper-plane"></i> OTP পাঠাও
                </button>
            </div>

            {{-- Step 2 --}}
            <div class="settings-card" id="pwStep2" style="display:none;">
                <div class="settings-card-title">
                    <i class="fas fa-key" style="font-size:11px;"></i> Step 2 — Verify & Set New Password
                </div>

                <div class="field-group">
                    <div class="field-label">OTP Code</div>
                    <input type="text" id="pwOtp" class="field-input otp-input" maxlength="6" placeholder="• • • • • •">
                </div>

                <div class="field-group">
                    <div class="field-label">New Password</div>
                    <input type="password" id="pwNew" class="field-input" placeholder="কমপক্ষে ৮ character">
                </div>

                <div class="field-group">
                    <div class="field-label">Confirm New Password</div>
                    <input type="password" id="pwConfirm" class="field-input" placeholder="Confirm password">
                </div>

                <button class="btn-success" onclick="confirmPasswordChange()">
                    <i class="fas fa-check"></i> Password পরিবর্তন করো
                </button>

                <button onclick="goBackToStep1()" style="background:none;border:none;color:#4f6080;font-size:12px;cursor:pointer;margin-top:12px;display:block;font-family:'Plus Jakarta Sans',sans-serif;">
                    ← আবার OTP নাও
                </button>
            </div>

            <div id="pwMsg" class="alert"></div>
        </div>

        {{-- ════ NOTIFICATIONS SECTION ════ --}}
        <div id="section-notifications" class="settings-section">
            <div class="section-header">
                <h1>Notification Preferences</h1>
                <p>Choose what notifications you want to receive.</p>
            </div>

            <div class="settings-card">
                <div class="settings-card-title">
                    <i class="fas fa-bell" style="font-size:11px;"></i> Push Notifications
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>🚨 SOS Alerts</h4>
                        <p>Nearby emergency SOS alerts receive করো</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="notif-sos">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>📋 Complaint Updates</h4>
                        <p>তোমার complaint এর status change এ notify করো</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="notif-complaint">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>🕵️ PI Assignments</h4>
                        <p>Private Investigator assign হলে notify করো</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="notif-pi">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>🏆 Leaderboard</h4>
                        <p>Rank পরিবর্তন ও achievements এ notify করো</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="notif-leaderboard">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-title">
                    <i class="fas fa-envelope" style="font-size:11px;"></i> Email Notifications
                </div>
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>📧 Email Alerts</h4>
                        <p>Important updates email এ পাঠাও</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked id="notif-email">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <button class="btn-primary" onclick="saveNotifSettings()">
                <i class="fas fa-save"></i> Save Preferences
            </button>
            <div id="notifSettingsMsg" class="alert" style="margin-top:12px;"></div>
        </div>

        {{-- ════ SECURITY SECTION ════ --}}
        <div id="section-security" class="settings-section">
            <div class="section-header">
                <h1>Security</h1>
                <p>Manage your account security and active sessions.</p>
            </div>

            <div class="settings-card">
                <div class="settings-card-title">
                    <i class="fas fa-shield-alt" style="font-size:11px;"></i> Account Security
                </div>

                <div class="security-item">
                    <div class="security-icon"><i class="fas fa-lock"></i></div>
                    <div class="security-info">
                        <h4>Password</h4>
                        <p>শেষবার পরিবর্তন হয়নি বা অজানা</p>
                    </div>
                    <button class="btn-ghost" onclick="showSettingsSection('password')">Change</button>
                </div>

                <div class="security-item">
                    <div class="security-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div class="security-info">
                        <h4>Push Notifications (FCM)</h4>
                        <p id="fcmStatusText">এই device এ FCM registered</p>
                    </div>
                    <button class="btn-ghost" onclick="refreshFcmToken()">Refresh</button>
                </div>

                <div class="security-item">
                    <div class="security-icon" style="background:rgba(46,204,113,.08);border-color:rgba(46,204,113,.2);color:#2ecc71;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="security-info">
                        <h4>Account Status</h4>
                        <p id="accountStatusText">Active — no issues found</p>
                    </div>
                </div>
            </div>

            <div class="danger-zone-card">
                <div class="danger-zone-title">
                    <i class="fas fa-exclamation-triangle"></i> Danger Zone
                </div>

                <div class="danger-item">
                    <div class="danger-item-info">
                        <h4>Logout from this device</h4>
                        <p>Current session শেষ করো এবং login page এ যাও</p>
                    </div>
                    <button class="btn-danger" onclick="doLogout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>

                <div class="danger-item">
                    <div class="danger-item-info">
                        <h4>Clear all local data</h4>
                        <p>Browser এ saved সব data মুছে ফেলো (logout হবে)</p>
                    </div>
                    <button class="btn-danger" onclick="clearAllData()">
                        <i class="fas fa-trash"></i> Clear Data
                    </button>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
// ── Auth Guard ────────────────────────────────────────────
document.documentElement.style.visibility = 'hidden';
(function() {
    const token  = localStorage.getItem('sv_token');
    const svUser = localStorage.getItem('sv_user');
    if (!token || !svUser) {
        window.location.href = '/login';
        return;
    }
    document.documentElement.style.visibility = 'visible';
})();

// ── Section Navigation ────────────────────────────────────
function showSettingsSection(section) {
    // Desktop sidebar
    document.querySelectorAll('.settings-nav li').forEach(li => li.classList.remove('active'));
    const snavEl = document.getElementById('snav-' + section);
    if (snavEl) snavEl.classList.add('active');

    // Mobile chips
    document.querySelectorAll('.mobile-nav-btn').forEach(b => b.classList.remove('active'));
    const mnavEl = document.getElementById('msnav-' + section);
    if (mnavEl) mnavEl.classList.add('active');

    // Sections
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    const secEl = document.getElementById('section-' + section);
    if (secEl) secEl.classList.add('active');
}

// ── Load Profile ──────────────────────────────────────────
async function loadProfileData() {
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    const name   = svUser.name  || '—';
    const email  = svUser.email || '';
    const phone  = svUser.phone || '';

    document.getElementById('avatarName').textContent  = name;
    document.getElementById('avatarEmail').textContent = email;
    document.getElementById('settingNameDisplay').textContent = name;
    document.getElementById('settingEmail').value = email;
    document.getElementById('settingPhone').value = phone;

    // Password tab email display
    const pwEmail = document.getElementById('pwEmailDisplay');
    if (pwEmail) pwEmail.textContent = email || '(no email set)';

    // Load NID from server
    try {
        const uid = svUser.id || svUser.user_id || '';
        const res  = await fetch('/api/profile?user_id=' + uid, { credentials: 'include' });
        const data = await res.json();
        if (data.success && data.user) {
            const nidEl = document.getElementById('settingNidDisplay');
            if (nidEl) {
                nidEl.textContent = data.user.id_number
                    ? data.user.id_number.toString().replace(/\d(?=\d{4})/g, '•')
                    : '—';
            }
            // Update account status
            const statusEl = document.getElementById('accountStatusText');
            if (statusEl && data.user.status) {
                statusEl.textContent = 'Status: ' + data.user.status;
            }
        }
    } catch(e) {}
}

// ── Save Profile ──────────────────────────────────────────
async function saveProfileSettings() {
    const email = document.getElementById('settingEmail').value.trim();
    const phone = document.getElementById('settingPhone').value.trim();
    const msg   = document.getElementById('profileMsg');
    const btn   = document.getElementById('profileSaveBtn');

    if (!phone && !email) {
        showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> Phone বা Email দিতে হবে।');
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    msg.classList.remove('show');

    try {
        const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
        const res  = await fetch('/api/profile/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ user_id: svUser.id || '', email, phone })
        });
        const data = await res.json();

        if (data.success) {
            svUser.email = data.email || email;
            svUser.phone = data.phone || phone;
            localStorage.setItem('sv_user', JSON.stringify(svUser));
            document.getElementById('avatarEmail').textContent = svUser.email;
            showAlert(msg, 'success', '<i class="fas fa-check-circle"></i> ' + (data.message || 'Changes saved!'));
        } else {
            showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Error saving.'));
        }
    } catch(e) {
        showAlert(msg, 'error', '<i class="fas fa-wifi"></i> Could not connect. Try again.');
    }

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
}

// ── Password OTP ──────────────────────────────────────────
async function requestPasswordOTP() {
    const btn    = document.getElementById('pwSendOtpBtn');
    const msg    = document.getElementById('pwMsg');
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');
    const email  = svUser.email || '';

    if (!email) {
        showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> Email পাওয়া যায়নি। Profile থেকে email আগে set করো।');
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    msg.classList.remove('show');

    try {
        const res  = await fetch('/api/forget_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'send_otp', email })
        });
        const data = await res.json();

        if (data.success) {
            // Show step 2
            document.getElementById('pwStep1').style.display = 'none';
            document.getElementById('pwStep2').style.display = 'block';
            // Update step indicator
            document.getElementById('pwStepDot1').classList.add('done');
            document.getElementById('pwStepLine1').classList.add('done');
            document.getElementById('pwStepDot2').style.opacity = '1';
            document.getElementById('pwStepDot2').classList.add('active');
            showAlert(msg, 'success', '<i class="fas fa-check-circle"></i> OTP পাঠানো হয়েছে ' + email + ' এ।');
        } else {
            showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Failed to send OTP.'));
        }
    } catch(e) {
        showAlert(msg, 'error', '<i class="fas fa-wifi"></i> Could not connect.');
    }

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> OTP পাঠাও';
}

function goBackToStep1() {
    document.getElementById('pwStep1').style.display = 'block';
    document.getElementById('pwStep2').style.display = 'none';
    document.getElementById('pwStepDot1').classList.remove('done');
    document.getElementById('pwStepDot1').classList.add('active');
    document.getElementById('pwStepLine1').classList.remove('done');
    document.getElementById('pwStepDot2').style.opacity = '.4';
    document.getElementById('pwStepDot2').classList.remove('active', 'done');
    document.getElementById('pwMsg').classList.remove('show');
}

async function confirmPasswordChange() {
    const otp  = document.getElementById('pwOtp').value.trim();
    const np   = document.getElementById('pwNew').value;
    const cp   = document.getElementById('pwConfirm').value;
    const msg  = document.getElementById('pwMsg');
    const svUser = JSON.parse(localStorage.getItem('sv_user') || '{}');

    if (!otp || otp.length !== 6) {
        showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> ৬-digit OTP দাও।'); return;
    }
    if (np.length < 8) {
        showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> Password কমপক্ষে ৮ character হতে হবে।'); return;
    }
    if (np !== cp) {
        showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> Password দুটো মিলছে না।'); return;
    }

    msg.classList.remove('show');

    try {
        const res  = await fetch('/api/forget_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'reset', email: svUser.email, otp, new_password: np })
        });
        const data = await res.json();

        if (data.success) {
            // Mark step 2 done
            document.getElementById('pwStepDot2').classList.remove('active');
            document.getElementById('pwStepDot2').classList.add('done');
            showAlert(msg, 'success', '<i class="fas fa-check-circle"></i> Password পরিবর্তন হয়েছে! পরের বার নতুন password দিয়ে login করো।');
            document.getElementById('pwOtp').value = '';
            document.getElementById('pwNew').value = '';
            document.getElementById('pwConfirm').value = '';
            // Go back to step 1 after 3s
            setTimeout(goBackToStep1, 3000);
        } else {
            showAlert(msg, 'error', '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Failed.'));
        }
    } catch(e) {
        showAlert(msg, 'error', '<i class="fas fa-wifi"></i> Could not connect.');
    }
}

// ── Notification Settings ─────────────────────────────────
function saveNotifSettings() {
    const prefs = {
        sos:         document.getElementById('notif-sos').checked,
        complaint:   document.getElementById('notif-complaint').checked,
        pi:          document.getElementById('notif-pi').checked,
        leaderboard: document.getElementById('notif-leaderboard').checked,
        email:       document.getElementById('notif-email').checked,
    };
    localStorage.setItem('sv-notif-prefs', JSON.stringify(prefs));
    const msg = document.getElementById('notifSettingsMsg');
    showAlert(msg, 'success', '<i class="fas fa-check-circle"></i> Notification preferences saved!');
    setTimeout(() => msg.classList.remove('show'), 2500);
}

function loadNotifPrefs() {
    try {
        const prefs = JSON.parse(localStorage.getItem('sv-notif-prefs') || '{}');
        if (prefs.sos         !== undefined) document.getElementById('notif-sos').checked         = prefs.sos;
        if (prefs.complaint   !== undefined) document.getElementById('notif-complaint').checked   = prefs.complaint;
        if (prefs.pi          !== undefined) document.getElementById('notif-pi').checked          = prefs.pi;
        if (prefs.leaderboard !== undefined) document.getElementById('notif-leaderboard').checked = prefs.leaderboard;
        if (prefs.email       !== undefined) document.getElementById('notif-email').checked       = prefs.email;
    } catch(e) {}
}

// ── Security ──────────────────────────────────────────────
function refreshFcmToken() {
    const el = document.getElementById('fcmStatusText');
    if (el) el.textContent = 'Refreshing...';
    if (typeof initFCM === 'function') {
        initFCM().then(() => {
            if (el) el.textContent = 'FCM token refreshed ✓';
        }).catch(() => {
            if (el) el.textContent = 'FCM refresh failed';
        });
    } else {
        setTimeout(() => {
            if (el) el.textContent = 'এই device এ FCM registered';
        }, 1000);
    }
}

function clearAllData() {
    if (!confirm('সব local data মুছে ফেলা হবে এবং logout হবে। Continue?')) return;
    localStorage.clear();
    sessionStorage.clear();
    window.location.replace('/login');
}

// ── Logout ────────────────────────────────────────────────
async function doLogout() {
    if (typeof unregisterFcmToken === 'function') {
        await unregisterFcmToken().catch(() => {});
    }
    try {
        await fetch('/api/logout', { method: 'POST', credentials: 'include', cache: 'no-store' });
    } catch(e) {}
    localStorage.removeItem('sv_user');
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userName');
    sessionStorage.clear();
    window.location.replace('/login');
}

// ── Alert helper ──────────────────────────────────────────
function showAlert(el, type, html) {
    el.className = 'alert alert-' + type + ' show';
    el.innerHTML = html;
}

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadProfileData();
    loadNotifPrefs();

    // Handle hash navigation: /settings#password → show password section
    const hash = window.location.hash.replace('#', '');
    if (['profile','password','notifications','security'].includes(hash)) {
        showSettingsSection(hash);
    }
});
</script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
<script src="{{ asset('js/fcm.js') }}"></script>
@endsection