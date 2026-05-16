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
    <div class="hamburger" id="hamburger">
        <span></span><span></span><span></span>
    </div>
    <div class="nav-overlay" id="navOverlay"></div>
    <div class="nav-container">
        <a href="{{ route('home') }}" class="logo">
            <i class="fas fa-shield-alt"></i>
            <span>SafeVoice</span>
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('leaderboard') }}">Leaderboard</a></li>
            <li><a href="{{ route('login') }}" class="btn-login">Login</a></li>
            <li><a href="{{ route('admin.login') }}" class="btn-login">Admin</a></li>
            <li><a href="{{ route('register') }}" class="btn-register">Register</a></li>
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
@yield('scripts')
</body>
</html>
