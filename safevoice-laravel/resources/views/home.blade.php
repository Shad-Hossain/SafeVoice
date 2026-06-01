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
        <div class="stat-card"><h2 id="stat-home-total">—</h2><p>Total Complaints</p></div>
        <div class="stat-card"><h2 id="stat-home-resolved">—</h2><p>Resolved Cases</p></div>
        <div class="stat-card"><h2 id="stat-home-pending">—</h2><p>Pending</p></div>
        <div class="stat-card"><h2 id="stat-home-sos">—</h2><p>SOS Responses</p></div>
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
    <div class="responders-container" id="homeTopResponders">
        {{-- Real data /api/leaderboard থেকে JS দিয়ে load হবে --}}
        <div style="color:#888; text-align:center; padding:20px; width:100%;">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
    </div>
</section>

<script>
(async function loadHomeStats() {
    try {
        const res  = await fetch('/api/stats');
        const data = await res.json();
        if (!data.success) return;
        const fmt = n => n >= 1000 ? (n/1000).toFixed(1).replace(/\.0$/,'') + 'k' : n;
        document.getElementById('stat-home-total').textContent    = fmt(data.total    || 0);
        document.getElementById('stat-home-resolved').textContent = fmt(data.resolved || 0);
        document.getElementById('stat-home-pending').textContent  = fmt(data.pending  || 0);
        document.getElementById('stat-home-sos').textContent      = fmt(data.sos      || 0);
    } catch(e) {}
})();

// ── Homepage Top 3 Responders — Real data from /api/leaderboard ──
(async function loadHomeLeaderboard() {
    const container = document.getElementById('homeTopResponders');
    if (!container) return;

    try {
        const res  = await fetch('/api/leaderboard');
        const data = await res.json();

        if (!data.success || !data.leaderboard || data.leaderboard.length === 0) {
            container.innerHTML = '<p style="color:#888; text-align:center; width:100%; padding:20px;">No SOS responders yet. Be the first hero!</p>';
            return;
        }

        const top3      = data.leaderboard.slice(0, 3);
        const rankClass = ['gold', 'silver', 'bronze'];
        const badges    = ['🏆 Champion', '🥈 Runner Up', '🥉 Third Place'];

        container.innerHTML = top3.map((p, i) => `
            <div class="responder-card">
                <div class="rank ${rankClass[i]}">${p.rank}</div>
                <div class="avatar"><i class="fas fa-user"></i></div>
                <h3>${p.name}</h3>
                <p>${p.responses} responses</p>
                <span class="badge">${badges[i]}</span>
            </div>
        `).join('');

    } catch(e) {
        container.innerHTML = '<p style="color:#888; text-align:center; width:100%; padding:20px;">Could not load leaderboard.</p>';
    }
})();
</script>
@endsection