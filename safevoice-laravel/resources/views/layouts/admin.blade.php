<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SafeVoice Admin')</title>
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
        <a href="{{ route('admin.dashboard') }}" class="logo">
            <i class="fas fa-shield-alt"></i>
            <span>SafeVoice Admin</span>
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><a href="{{ route('login') }}">User Login</a></li>
        </ul>
    </div>
</nav>

@yield('content')

<script src="{{ asset('js/main.js') }}"></script>
@yield('scripts')
</body>
</html>
