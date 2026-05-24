@extends('layouts.app')
@section('title', 'Login — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
        .toast {
            position: fixed; bottom: 30px; right: 30px;
            padding: 14px 22px; border-radius: 10px;
            font-size: 14px; font-weight: 600; color: #fff;
            z-index: 9999; display: flex; align-items: center; gap: 10px;
            transform: translateY(100px); opacity: 0;
            transition: all 0.4s ease; max-width: 340px;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: #1a7f4b; }
        .toast.error   { background: #c0392b; }
        .toast.info    { background: #1a5fa8; }
        .btn-auth .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: inline-block; vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-auth:disabled { opacity: 0.7; cursor: not-allowed; }
        .fp-modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.75);
            z-index: 1000; display: flex; align-items: center;
            justify-content: center; padding: 20px;
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .fp-modal-overlay.active { opacity: 1; pointer-events: all; }
        .fp-modal {
            background: #0d1526; border: 1px solid #1e2d4a;
            border-radius: 20px; padding: 35px; width: 100%; max-width: 420px;
            transform: translateY(20px); transition: transform 0.3s; position: relative;
        }
        .fp-modal-overlay.active .fp-modal { transform: translateY(0); }
        .fp-modal h3 {
            font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 8px;
            display: flex; align-items: center; gap: 10px;
        }
        .fp-modal h3 i { color: #4f9eff; }
        .fp-modal p { color: #a0b4cc; font-size: 14px; margin-bottom: 20px; line-height: 1.6; }
        .fp-step { display: none; }
        .fp-step.active { display: block; }
        .otp-row { display: flex; gap: 10px; justify-content: center; margin: 20px 0; }
        .otp-input {
            width: 50px; height: 55px; background: #0a0f1e;
            border: 2px solid #1e2d4a; border-radius: 10px;
            text-align: center; font-size: 22px; font-weight: 700;
            color: #fff; outline: none; transition: border 0.2s;
        }
        .otp-input:focus, .otp-input.filled { border-color: #4f9eff; }
        .resend-row { text-align: center; margin-top: 12px; font-size: 13px; color: #a0b4cc; }
        .resend-row a { color: #4f9eff; cursor: pointer; text-decoration: none; }
        .resend-row a.disabled { color: #4a5568; pointer-events: none; }
        .timer-text { color: #4f9eff; font-weight: 600; }
        .fp-close {
            position: absolute; top: 20px; right: 20px;
            font-size: 20px; color: #a0b4cc; cursor: pointer;
        }
        .fp-close:hover { color: #fff; }
        .strength-bar { height: 4px; border-radius: 4px; background: #1e2d4a; margin-top: 8px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; transition: width 0.3s, background 0.3s; border-radius: 4px; }
        .strength-text { font-size: 12px; color: #a0b4cc; margin-top: 4px; }
</style>
@endsection

@section('content')
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Welcome Back</h2>
                <p>Login to your SafeVoice account</p>
            </div>
            <div class="auth-form">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="loginEmail" placeholder="Enter your email" autocomplete="email" />
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPassword" placeholder="Enter your password" autocomplete="current-password" />
                        <i class="fas fa-eye toggle-password" id="togglePwd"></i>
                    </div>
                </div>
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" id="rememberMe" /><span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password" id="forgotPasswordLink">Forgot password?</a>
                </div>
                <button class="btn-auth" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <div class="auth-divider"><span>or</span></div>
                <p class="auth-switch">Don't have an account? <a href="/register">Register here</a></p>
            </div>
        </div>
    </div>
</section>

<!-- FORGOT PASSWORD MODAL -->
<div class="fp-modal-overlay" id="fpModal">
    <div class="fp-modal">
        <i class="fas fa-times fp-close" id="fpClose"></i>
        <div class="fp-step active" id="fpStep1">
            <h3><i class="fas fa-envelope"></i> Forgot Password</h3>
            <p>Enter your registered email address. We'll send a 6-digit OTP to reset your password.</p>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="fpEmail" placeholder="your@email.com" />
                </div>
            </div>
            <button class="btn-auth" id="fpSendOtpBtn" style="margin-top:10px;">
                <i class="fas fa-paper-plane"></i> Send OTP
            </button>
        </div>
        <div class="fp-step" id="fpStep2">
            <h3><i class="fas fa-key"></i> Enter OTP</h3>
            <p>A 6-digit code was sent to <strong id="fpEmailDisplay" style="color:#4f9eff;"></strong></p>
            <div class="otp-row">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
                <input class="otp-input" maxlength="1" type="text" inputmode="numeric">
            </div>
            <div class="resend-row">
                Didn't receive it? <a id="resendLink" class="disabled">Resend</a>
                <span class="timer-text" id="timerDisplay"></span>
            </div>
            <button class="btn-auth" id="fpVerifyOtpBtn" style="margin-top:16px;">
                <i class="fas fa-check"></i> Verify OTP
            </button>
        </div>
        <div class="fp-step" id="fpStep3">
            <h3><i class="fas fa-lock"></i> New Password</h3>
            <p>Create a strong new password for your account.</p>
            <div class="form-group">
                <label>New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="fpNewPwd" placeholder="Min 8 characters" />
                    <i class="fas fa-eye toggle-password" id="toggleNewPwd"></i>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-text" id="strengthText"></div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="fpConfirmPwd" placeholder="Confirm new password" />
                </div>
            </div>
            <button class="btn-auth" id="fpResetBtn" style="margin-top:10px;">
                <i class="fas fa-check-circle"></i> Reset Password
            </button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API_BASE = '/api';

function showToast(msg, type='info', dur=3500) {
    const icons = {success:'fa-check-circle', error:'fa-exclamation-circle', info:'fa-info-circle'};
    const t = document.getElementById('toast');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas ${icons[type]}"></i> ${msg}`;
    t.classList.add('show');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), dur);
}

function btnLoading(btn, text) { btn.disabled = true; btn.innerHTML = `<span class="spinner"></span> ${text}`; }
function btnReset(btn, html)   { btn.disabled = false; btn.innerHTML = html; }

function setupToggle(toggleId, inputId) {
    const tog = document.getElementById(toggleId);
    const inp = document.getElementById(inputId);
    if (!tog || !inp) return;
    tog.addEventListener('click', function() {
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');
    });
}
setupToggle('togglePwd', 'loginPassword');

const remembered = localStorage.getItem('sv_remember_email');
if (remembered) { document.getElementById('loginEmail').value = remembered; document.getElementById('rememberMe').checked = true; }

// ─── LOGIN ─────────────────────────────────────────────────────
document.getElementById('loginBtn').addEventListener('click', async function() {
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPassword').value;
    const rem   = document.getElementById('rememberMe').checked;
    if (!email || !pass) { showToast('Please fill in all fields.', 'error'); return; }
    btnLoading(this, 'Logging in…');
    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            credentials: 'include',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, password: pass })
        });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('sv_user',    JSON.stringify(data.user));
            localStorage.setItem('sv_token',   data.token);
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userName',   data.user.name);
            if (rem) localStorage.setItem('sv_remember_email', email);
            else localStorage.removeItem('sv_remember_email');
            showToast(`Welcome back, ${data.user.name}!`, 'success');
            setTimeout(() => window.location.href = '/dashboard', 1000);
        } else if (data.suspended) {
            btnReset(this, '<i class="fas fa-sign-in-alt"></i> Login');
            showAccountBlockModal({
                type:        'suspended',
                strike:       data.strike,
                ordinal:      data.strike_ordinal,
                activateOn:   data.activation_date,
                remaining:    data.remaining,
            });
        } else if (data.banned) {
            btnReset(this, '<i class="fas fa-sign-in-alt"></i> Login');
            showAccountBlockModal({ type: 'banned' });
        } else {
            showToast(data.message || 'Login failed.', 'error');
            btnReset(this, '<i class="fas fa-sign-in-alt"></i> Login');
        }
    } catch(e) {
        showToast('Server error. Check your connection.', 'error');
        btnReset(this, '<i class="fas fa-sign-in-alt"></i> Login');
    }
});

// ── Account Block Modal ────────────────────────────────────────
function showAccountBlockModal({ type, strike, ordinal, activateOn, remaining }) {
    const existing = document.getElementById('accountBlockModal');
    if (existing) existing.remove();

    let icon, title, color, body;

    if (type === 'banned') {
        icon  = '🚫';
        title = 'Account Permanently Banned';
        color = '#e63946';
        body  = `
            <p style="color:#cbd5e1;font-size:14px;line-height:1.7;margin:0 0 12px;">
                Your account has been <strong style="color:#e63946;">permanently banned</strong>
                from SafeVoice due to repeated fake complaint submissions.
            </p>
            <p style="color:#6b7280;font-size:13px;margin:0;">This action cannot be reversed.</p>`;
    } else {
        icon  = '⚠️';
        title = 'Account Suspended';
        color = '#f39c12';
        const remainMsg = remaining > 0
            ? `<span style="color:#e63946;font-weight:700;">${remaining} more suspension${remaining === 1 ? '' : 's'}</span> and your account will be <strong>permanently banned</strong>.`
            : `<span style="color:#e63946;font-weight:700;">This is your final warning.</span>`;
        body  = `
            <div style="background:#0a0f1e;border:1px solid #f39c1240;border-radius:10px;padding:14px 18px;margin-bottom:16px;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="color:#a0b4cc;font-size:12px;font-weight:600;padding:6px 0;width:130px;">Suspension</td>
                        <td style="color:#f39c12;font-size:13px;font-weight:700;">${ordinal} suspension (${strike}/3)</td>
                    </tr>
                    <tr>
                        <td style="color:#a0b4cc;font-size:12px;font-weight:600;padding:6px 0;">Duration</td>
                        <td style="color:#fff;font-size:13px;">60 days</td>
                    </tr>
                    <tr>
                        <td style="color:#a0b4cc;font-size:12px;font-weight:600;padding:6px 0;">Reactivation</td>
                        <td style="color:#2ecc71;font-size:13px;font-weight:700;">${activateOn}</td>
                    </tr>
                </table>
            </div>
            <p style="color:#a0b4cc;font-size:13px;line-height:1.6;margin:0;">${remainMsg}</p>`;
    }

    const modal = document.createElement('div');
    modal.id = 'accountBlockModal';
    modal.style.cssText = 'position:fixed;inset:0;background:#00000088;display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px;';
    modal.innerHTML = `
        <div style="background:#0d1525;border:1px solid ${color}40;border-radius:16px;max-width:420px;width:100%;padding:32px 28px;text-align:center;box-shadow:0 20px 60px #00000060;">
            <div style="font-size:40px;margin-bottom:12px;">${icon}</div>
            <h3 style="color:#fff;font-size:18px;font-weight:700;margin:0 0 20px;">${title}</h3>
            <div style="text-align:left;">${body}</div>
            <button onclick="document.getElementById('accountBlockModal').remove()"
                style="margin-top:24px;background:${color};color:#fff;border:none;border-radius:10px;padding:11px 32px;font-size:14px;font-weight:700;cursor:pointer;width:100%;">
                OK
            </button>
        </div>`;
    document.body.appendChild(modal);

document.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !document.getElementById('fpModal').classList.contains('active'))
        document.getElementById('loginBtn').click();
});
}
// ─── FORGOT PASSWORD ───────────────────────────────────────────
let fpEmail='', fpOtp='', timerInterval=null;
const fpModal = document.getElementById('fpModal');
document.getElementById('forgotPasswordLink').addEventListener('click', e => { e.preventDefault(); fpModal.classList.add('active'); showStep(1); });
document.getElementById('fpClose').addEventListener('click', () => { fpModal.classList.remove('active'); clearInterval(timerInterval); });
fpModal.addEventListener('click', e => { if(e.target===fpModal) { fpModal.classList.remove('active'); clearInterval(timerInterval); } });

function showStep(n) { document.querySelectorAll('.fp-step').forEach(s=>s.classList.remove('active')); document.getElementById(`fpStep${n}`).classList.add('active'); }

document.getElementById('fpSendOtpBtn').addEventListener('click', async function() {
    fpEmail = document.getElementById('fpEmail').value.trim().toLowerCase();
    if (!fpEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fpEmail)) { showToast('Please enter a valid email address.', 'error'); return; }
    btnLoading(this, 'Sending…');
    try {
        const res  = await fetch('/api/forget_password', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'send_otp',email:fpEmail})});
        const data = await res.json();
        if (data.success) { document.getElementById('fpEmailDisplay').textContent = fpEmail; showToast('OTP sent to your email!', 'success'); setTimeout(()=>{ showStep(2); startTimer(600); }, 700); }
        else { showToast(data.message||'Failed to send OTP.', 'error'); }
    } catch { showToast('Server error.', 'error'); }
    btnReset(this, '<i class="fas fa-paper-plane"></i> Send OTP');
});

const otpInputs = document.querySelectorAll('.otp-input');
otpInputs.forEach((inp,i) => {
    inp.addEventListener('input', function(){ this.value=this.value.replace(/\D/g,''); if(this.value && i<5) otpInputs[i+1].focus(); this.classList.toggle('filled',!!this.value); });
    inp.addEventListener('keydown', function(e){ if(e.key==='Backspace'&&!this.value&&i>0) otpInputs[i-1].focus(); });
    inp.addEventListener('paste', function(e){ e.preventDefault(); const p=e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6); p.split('').forEach((c,idx)=>{ if(otpInputs[idx]){otpInputs[idx].value=c;otpInputs[idx].classList.add('filled');} }); (otpInputs[Math.min(p.length,5)]||otpInputs[5]).focus(); });
});
function getOtp() { return [...otpInputs].map(i=>i.value).join(''); }

function startTimer(sec) {
    clearInterval(timerInterval); let r=sec;
    const disp=document.getElementById('timerDisplay'), res=document.getElementById('resendLink');
    res.classList.add('disabled');
    function tick(){ const m=String(Math.floor(r/60)).padStart(2,'0'), s=String(r%60).padStart(2,'0'); disp.textContent=`${m}:${s}`; if(--r<0){clearInterval(timerInterval);disp.textContent='';res.classList.remove('disabled');} }
    tick(); timerInterval=setInterval(tick,1000);
}

document.getElementById('resendLink').addEventListener('click', async function(){
    if(this.classList.contains('disabled')) return;
    this.classList.add('disabled');
    try { await fetch('/api/forget_password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'send_otp',email:fpEmail})}); showToast('OTP resent!', 'info'); } catch { showToast('Server error.', 'error'); }
    startTimer(600);
});

document.getElementById('fpVerifyOtpBtn').addEventListener('click', async function(){
    fpOtp=getOtp(); if(fpOtp.length!==6){showToast('Enter all 6 digits.','error');return;}
    btnLoading(this,'Verifying…');
    try {
        const res  = await fetch('/api/forget_password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'verify_otp',email:fpEmail,otp:fpOtp})});
        const data = await res.json();
        if (data.success) { showToast('OTP verified!','success'); clearInterval(timerInterval); setTimeout(()=>showStep(3),600); }
        else { showToast(data.message||'Invalid OTP.','error'); }
    } catch { showToast('Server error.','error'); }
    btnReset(this,'<i class="fas fa-check"></i> Verify OTP');
});

setupToggle('toggleNewPwd','fpNewPwd');
document.getElementById('fpNewPwd').addEventListener('input', function(){
    const v=this.value, s=([v.length>=8,/[A-Z]/.test(v),/[0-9]/.test(v),/[^A-Za-z0-9]/.test(v)].filter(Boolean).length);
    const cfg=[['','',''],['25%','#e63946','Weak'],['50%','#f4a261','Fair'],['75%','#f1c40f','Good'],['100%','#2ecc71','Strong']];
    document.getElementById('strengthFill').style.cssText=`width:${cfg[s][0]};background:${cfg[s][1]}`;
    document.getElementById('strengthText').style.color=cfg[s][1]; document.getElementById('strengthText').textContent=cfg[s][2];
});

document.getElementById('fpResetBtn').addEventListener('click', async function(){
    const p1=document.getElementById('fpNewPwd').value, p2=document.getElementById('fpConfirmPwd').value;
    if(p1.length<8){showToast('Min 8 characters.','error');return;}
    if(p1!==p2){showToast('Passwords do not match.','error');return;}
    btnLoading(this,'Resetting…');
    try {
        const res  = await fetch('/api/forget_password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reset',email:fpEmail,otp:fpOtp,new_password:p1})});
        const data = await res.json();
        if (data.success) { showToast('Password reset! Please login.','success'); fpModal.classList.remove('active'); }
        else { showToast(data.message||'Reset failed.','error'); }
    } catch { showToast('Server error.','error'); }
    btnReset(this,'<i class="fas fa-check-circle"></i> Reset Password');
});
</script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
@endsection

@section('scripts')
<script src="{{ asset('js/theme.js') }}"></script>
@endsection 
