@extends('layouts.app')
@section('title', 'Lawyer Login — SafeVoice')
@section('styles')
<style>
:root { --bg-primary:#070d1a; --bg-card:#0d1526; --bg-input:#0a1020; --border:#1e2d4a; --accent:#4f9eff; --accent-dark:#1a3a6e; --text-main:#e8f0fe; --text-muted:#6b7fa3; --error:#ef4444; }
* { box-sizing:border-box; margin:0; padding:0; }
body { background:var(--bg-primary); color:var(--text-main); font-family:'Segoe UI',sans-serif; }

.login-wrapper { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:80px 20px 60px; }
.login-card { width:100%; max-width:440px; background:var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:40px; }
.login-header { text-align:center; margin-bottom:32px; }
.login-header .icon { width:68px; height:68px; background:linear-gradient(135deg,var(--accent-dark),#0a1e40); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:30px; border:1px solid var(--border); }
.login-header h1 { font-size:22px; font-weight:700; }
.login-header p  { color:var(--text-muted); font-size:13px; margin-top:6px; }

.form-group { margin-bottom:18px; }
.form-group label { display:block; font-size:13px; color:var(--text-muted); margin-bottom:7px; }
.form-group input { width:100%; background:var(--bg-input); border:1px solid var(--border); border-radius:10px; color:var(--text-main); font-size:14px; padding:12px 14px; outline:none; transition:border-color .2s; }
.form-group input:focus { border-color:var(--accent); }

.login-btn { width:100%; padding:14px; background:linear-gradient(135deg,var(--accent),#2563eb); border:none; border-radius:12px; color:#fff; font-size:15px; font-weight:700; cursor:pointer; transition:opacity .2s; margin-top:4px; }
.login-btn:hover { opacity:.9; }
.login-btn:disabled { opacity:.5; cursor:not-allowed; }

.alert { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; display:none; }
.alert-error   { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }
.alert-success { background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3); color:#86efac; }
.alert-warning { background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.3); color:#fcd34d; }

.divider { display:flex; align-items:center; gap:12px; margin:24px 0; color:var(--text-muted); font-size:12px; }
.divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--border); }

.bottom-links { display:flex; justify-content:space-between; margin-top:22px; font-size:13px; color:var(--text-muted); }
.bottom-links a { color:var(--accent); text-decoration:none; }

.demo-box { background:rgba(79,158,255,.06); border:1px solid rgba(79,158,255,.2); border-radius:12px; padding:14px 16px; margin-bottom:20px; }
.demo-box .demo-title { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.8px; color:var(--accent); margin-bottom:10px; }
.demo-acc { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid rgba(79,158,255,.1); font-size:12px; }
.demo-acc:last-child { border-bottom:none; }
.demo-acc .email { color:var(--text-main); }
.demo-acc .pass  { color:var(--text-muted); font-family:monospace; }
.demo-acc .use-btn { background:rgba(79,158,255,.15); border:1px solid rgba(79,158,255,.3); border-radius:6px; color:var(--accent); font-size:11px; padding:3px 10px; cursor:pointer; transition:all .15s; }
.demo-acc .use-btn:hover { background:rgba(79,158,255,.25); }

.spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:8px; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="login-wrapper">
<div class="login-card">

    <div class="login-header">
        <div class="icon">⚖️</div>
        <h1>Lawyer Login</h1>
        <p>Access your SafeVoice legal dashboard</p>
    </div>

    <!-- Demo Accounts -->
    <div class="demo-box">
        <div class="demo-title">🧪 Demo Accounts</div>
        <div class="demo-acc">
            <span class="email">lawyer@safevoice.com</span>
            <span class="pass">lawyer123</span>
            <button class="use-btn" onclick="fillDemo('lawyer@safevoice.com','lawyer123')">Use</button>
        </div>
        <div class="demo-acc">
            <span class="email">lawyer2@safevoice.com</span>
            <span class="pass">lawyer123</span>
            <button class="use-btn" onclick="fillDemo('lawyer2@safevoice.com','lawyer123')">Use</button>
        </div>
        <div class="demo-acc">
            <span class="email">lawyer3@safevoice.com</span>
            <span class="pass">lawyer123</span>
            <button class="use-btn" onclick="fillDemo('lawyer3@safevoice.com','lawyer123')">Use</button>
        </div>
    </div>

    <div class="alert alert-error"   id="errorAlert"></div>
    <div class="alert alert-success" id="successAlert"></div>
    <div class="alert alert-warning" id="pendingAlert"></div>

    <div class="form-group">
        <label>Email Address</label>
        <input type="email" id="email" placeholder="your@email.com">
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" id="password" placeholder="••••••••" onkeydown="if(event.key==='Enter') doLogin()">
    </div>

    <button class="login-btn" id="loginBtn" onclick="doLogin()">Login to Dashboard</button>

    <div class="bottom-links">
        <a href="/lawyer/register">Register as Lawyer</a>
        <a href="/">← Back to SafeVoice</a>
    </div>

</div>
</div>
@endsection

@section('scripts')
<script>
function fillDemo(email, pass) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = pass;
}

async function doLogin() {
    const btn      = document.getElementById('loginBtn');
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) { showError('Please enter email and password.'); return; }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner"></span>Logging in...';
    hideAlerts();

    try {
        const res  = await fetch('/api/lawyer/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();

        if (data.success) {
            localStorage.setItem('lawyer_token', data.token);
            localStorage.setItem('lawyer_data',  JSON.stringify(data.lawyer));
            showSuccess('✅ Login successful! Redirecting...');
            setTimeout(() => window.location.href = '/lawyer/dashboard', 900);
        } else if (data.pending) {
            document.getElementById('pendingAlert').textContent = data.message;
            document.getElementById('pendingAlert').style.display = 'block';
            btn.disabled  = false;
            btn.innerHTML = 'Login to Dashboard';
        } else {
            showError(data.message || 'Login failed.');
            btn.disabled  = false;
            btn.innerHTML = 'Login to Dashboard';
        }
    } catch(err) {
        showError('Network error. Is the server running?');
        btn.disabled  = false;
        btn.innerHTML = 'Login to Dashboard';
    }
}

function showError(msg)   { const e = document.getElementById('errorAlert');   e.textContent = msg; e.style.display='block'; }
function showSuccess(msg) { const e = document.getElementById('successAlert'); e.textContent = msg; e.style.display='block'; }
function hideAlerts()     { ['errorAlert','successAlert','pendingAlert'].forEach(id => document.getElementById(id).style.display='none'); }
</script>
@endsection
