@extends('layouts.app')
@section('title', 'Login — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user"></i>
                <h2>Welcome Back</h2>
                <p>Login to your SafeVoice account</p>
            </div>
            <div class="auth-form">
                <div id="loginError" style="display:none;color:#e63946;font-size:13px;margin-bottom:10px;padding:10px;background:rgba(230,57,70,0.1);border-radius:8px;border:1px solid rgba(230,57,70,0.3);">
                    <i class="fas fa-exclamation-circle"></i> <span id="loginErrorMsg"></span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" placeholder="Enter your email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" placeholder="Enter your password">
                    </div>
                </div>
                <button class="btn-auth" id="loginBtn" onclick="doLogin()">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <p style="text-align:center;margin-top:15px;">
                    Don't have an account? <a href="{{ route('register') }}">Register</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
const API = '{{ url("/api") }}';
async function doLogin() {
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const btn      = document.getElementById('loginBtn');
    const errorBox = document.getElementById('loginError');
    const errorMsg = document.getElementById('loginErrorMsg');

    errorBox.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';

    try {
        const res  = await fetch(`${API}/login`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = '{{ route("dashboard") }}';
        } else {
            errorMsg.textContent = data.message;
            errorBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        }
    } catch(e) {
        errorMsg.textContent = 'Server error.';
        errorBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
    }
}
</script>
@endsection
