@extends('layouts.app')

@section('title', 'SafeVoice — Your Voice, Your Safety')

@section('content')
<section class="hero">
    <div class="hero-content">
        <h1>Your Voice. <span>Your Safety.</span></h1>
        <p>Report incidents anonymously, track your complaints, and get legal help — all in one place.</p>
        <div class="hero-buttons">
            <a href="{{ route('register') }}" class="btn-primary">
                <i class="fas fa-file-alt"></i> Report Incident
            </a>
            <a href="{{ route('sos') }}" class="btn-sos">
                <i class="fas fa-exclamation-triangle"></i> Emergency SOS
            </a>
        </div>
    </div>
    <div class="hero-image">
        <div class="hero-card">
            <i class="fas fa-shield-alt"></i>
            <p>Your identity is protected</p>
        </div>
    </div>
</section>

<section class="stats">
    <div class="stats-container">
        <div class="stat-card"><h2>1,240</h2><p>Total Complaints</p></div>
        <div class="stat-card"><h2>980</h2><p>Resolved Cases</p></div>
        <div class="stat-card"><h2>260</h2><p>Pending</p></div>
        <div class="stat-card"><h2>430</h2><p>SOS Responses</p></div>
    </div>
</section>

<section class="how-it-works">
    <h2>How It Works</h2>
    <div class="steps-container">
        <div class="step">
            <div class="step-icon"><i class="fas fa-user-plus"></i></div>
            <h3>Register</h3><p>Create your account to get started</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-file-alt"></i></div>
            <h3>Report</h3><p>Submit your complaint anonymously</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-search"></i></div>
            <h3>Track</h3><p>Monitor your complaint status</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Resolved</h3><p>Get justice and closure</p>
        </div>
    </div>
</section>

<section class="responders">
    <h2>Top Responders This Month</h2>
    <div class="responders-container">
        <div class="responder-card">
            <div class="rank gold">1</div>
            <div class="avatar"><i class="fas fa-user"></i></div>
            <h3>Rakib Hassan</h3><p>42 responses</p>
            <span class="badge">🏆 Champion</span>
        </div>
        <div class="responder-card">
            <div class="rank silver">2</div>
            <div class="avatar"><i class="fas fa-user"></i></div>
            <h3>Shefa Rahman</h3><p>38 responses</p>
            <span class="badge">🥈 Runner Up</span>
        </div>
        <div class="responder-card">
            <div class="rank bronze">3</div>
            <div class="avatar"><i class="fas fa-user"></i></div>
            <h3>Nadia Islam</h3><p>31 responses</p>
            <span class="badge">🥉 Third Place</span>
        </div>
    </div>
</section>
@endsection
