@extends('layouts.app')
@section('title', 'Register — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p>Join SafeVoice to report and track incidents</p>
            </div>
            <div class="auth-form">
                <div id="regError" style="display:none;color:#e63946;font-size:13px;margin-bottom:10px;padding:10px;background:rgba(230,57,70,0.1);border-radius:8px;">
                    <i class="fas fa-exclamation-circle"></i> <span id="regErrorMsg"></span>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" placeholder="Enter your full name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" placeholder="Enter your email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="text" id="phone" placeholder="01XXXXXXXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" placeholder="Minimum 8 characters">
                    </div>
                </div>
                <div class="form-group">
                    <label>ID Type</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card"></i>
                        <select id="id_type">
                            <option value="">Select ID Type</option>
                            <option value="nid">National ID (NID)</option>
                            <option value="birth_certificate">Birth Certificate</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>ID Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" id="id_number" placeholder="Enter ID number">
                    </div>
                </div>
                <button class="btn-auth" id="regBtn" onclick="doRegister()">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                <p style="text-align:center;margin-top:15px;">
                    Already have an account? <a href="{{ route('login') }}">Login</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
const API = '{{ url("/api") }}';
async function doRegister() {
    const btn      = document.getElementById('regBtn');
    const errorBox = document.getElementById('regError');
    const errorMsg = document.getElementById('regErrorMsg');

    errorBox.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

    const formData = new FormData();
    formData.append('name',      document.getElementById('name').value.trim());
    formData.append('email',     document.getElementById('email').value.trim());
    formData.append('phone',     document.getElementById('phone').value.trim());
    formData.append('password',  document.getElementById('password').value.trim());
    formData.append('id_type',   document.getElementById('id_type').value);
    formData.append('id_number', document.getElementById('id_number').value.trim());

    try {
        const res  = await fetch(`${API}/register`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = '{{ route("dashboard") }}';
        } else {
            errorMsg.textContent = data.message;
            errorBox.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Register';
        }
    } catch(e) {
        errorMsg.textContent = 'Server error.';
        errorBox.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Register';
    }
}
</script>
@endsection
