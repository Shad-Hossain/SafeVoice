{{--
  resources/views/layouts/app.blade.php
  পুরো file replace করো — Lawyer login/register nav link যোগ হয়েছে
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SafeVoice')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    @yield('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<nav class="navbar">
    <div class="nav-overlay" id="navOverlay"></div>
    <div class="nav-container">
        <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
        </div>
        <a href="{{ route('home') }}" class="logo" id="sv-logo-link">
            <i class="fas fa-shield-alt"></i>
            <span>SafeVoice</span>
        </a>
        <ul class="nav-links" id="sv-nav-links">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('leaderboard') }}">Leaderboard</a></li>

            {{-- ⚖️ Lawyer portal link --}}
            <li>
                <a href="/lawyer/login" id="lawyerNavLink" style="
                    display:flex; align-items:center; gap:6px;
                    color:#a0b4cc; font-size:14px; text-decoration:none;
                    padding:6px 12px; border-radius:8px;
                    border:1px solid #1e2d4a;
                    transition:all .2s;
                " onmouseover="this.style.borderColor='#4f9eff';this.style.color='#4f9eff'"
                   onmouseout="this.style.borderColor='#1e2d4a';this.style.color='#a0b4cc'">
                    ⚖️ Lawyer Portal
                </a>
            </li>

            <li class="sv-guest-only"><a href="{{ route('login') }}" class="btn-login">Login</a></li>
            <li class="sv-guest-only"><a href="{{ route('admin.login') }}" class="btn-login">Admin</a></li>
            <li class="sv-guest-only"><a href="{{ route('register') }}" class="btn-register">Register</a></li>
        </ul>
    </div>
</nav>

@yield('content')

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <i class="fas fa-shield-alt"></i>
            <span>SafeVoice</span>
            <p>Your voice matters. Stay safe.</p>
        </div>
        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('leaderboard') }}">Leaderboard</a></li>
                <li><a href="{{ route('legal') }}">Legal Help</a></li>
                <li><a href="/lawyer/register">Join as Lawyer</a></li>
            </ul>
        </div>
        <div class="footer-contact">
            <h4>Contact</h4>
            <p><i class="fas fa-envelope"></i> support@safevoice.com</p>
            <p><i class="fas fa-phone"></i> +880 1700-000000</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 SafeVoice. All rights reserved.</p>
    </div>
</footer>

<script src="{{ asset('js/main.js') }}"></script>
<script>
(function(){
    const path = window.location.pathname;
    if (path.startsWith('/dashboard')) document.body.classList.add('page-dashboard');

    // Lawyer logged in হলে nav তে dashboard link দেখাও
    const ld = localStorage.getItem('lawyer_data');
    if (ld && path.startsWith('/lawyer')) {
        const d = JSON.parse(ld);
        const link = document.getElementById('lawyerNavLink');
        if (link) {
            link.href        = '/lawyer/dashboard';
            link.textContent = '⚖️ ' + (d.full_name?.split(' ')[0] || 'Dashboard');
        }
    }
})();
</script>
@yield('scripts')
</body>
</html>
