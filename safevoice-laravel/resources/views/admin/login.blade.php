@extends('layouts.admin')
@section('title', 'Admin Login — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
@endsection

@section('content')
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-shield"></i>
                <h2>Admin Panel</h2>
                <p>Authorized access only</p>
            </div>
            <div class="auth-form">
                <div id="loginError" style="display:none;color:#e63946;font-size:13px;margin-bottom:10px;padding:10px;background:rgba(230,57,70,0.1);border-radius:8px;">
                    <i class="fas fa-exclamation-circle"></i> <span id="loginErrorMsg"></span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="adminEmail" placeholder="Enter admin email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="adminPassword" placeholder="Enter password">
                    </div>
                </div>
                <button class="btn-auth" id="loginBtn" onclick="doAdminLogin()">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </button>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
const API = '{{ url("/api") }}';
async function doAdminLogin() {
    const email    = document.getElementById('adminEmail').value.trim();
    const password = document.getElementById('adminPassword').value.trim();
    const btn      = document.getElementById('loginBtn');
    const errorBox = document.getElementById('loginError');
    const errorMsg = document.getElementById('loginErrorMsg');

    errorBox.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';

    try {
        const res  = await fetch(`${API}/admin/login`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('isAdminLoggedIn', 'true');
            window.location.href = '{{ route("admin.dashboard") }}';
        } else {
            errorMsg.textContent = data.message || 'Invalid credentials';
            errorBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Admin Login';
        }
    } catch(e) {
        errorMsg.textContent = 'Server error.';
        errorBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Admin Login';
    }
}
</script>
@endsection
