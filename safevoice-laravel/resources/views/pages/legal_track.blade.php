@extends('layouts.app')
@section('title', 'Track Your Legal Case — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/track.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
.case-card {
    background: #0d1526;
    border: 1px solid #1e2d4a;
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 20px;
    animation: fadeIn .3s ease;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
.status-badge { display:inline-block;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
.status-open      { background:#4f9eff20;color:#4f9eff;border:1px solid #4f9eff40; }
.status-bidding   { background:#fbbf2420;color:#fbbf24;border:1px solid #fbbf2440; }
.status-accepted  { background:#22c55e20;color:#22c55e;border:1px solid #22c55e40; }
.status-expired   { background:#ef444420;color:#ef4444;border:1px solid #ef444440; }
.status-cancelled { background:#6b728020;color:#6b7280;border:1px solid #6b728040; }
.countdown-box { background:linear-gradient(135deg,#0a1628,#0d1f3c);border:1px solid #1e3a5f;border-radius:14px;padding:16px 20px;margin:16px 0;display:flex;align-items:center;gap:14px; }
.countdown-box.urgent { border-color:#ef444460;background:linear-gradient(135deg,#1a0808,#2a0f0f); }
.countdown-time { font-size:26px;font-weight:800;font-family:monospace;color:#4f9eff; }
.countdown-box.urgent .countdown-time { color:#ef4444; }
.bid-card { background:#0a0f1e;border:1px solid #1e2d4a;border-radius:14px;padding:16px;margin-bottom:12px;display:flex;align-items:flex-start;gap:14px; }
.bid-avatar { width:46px;height:46px;border-radius:50%;background:#0d1f3c;border:2px solid #1e3a5f;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;overflow:hidden; }
.bid-avatar img { width:100%;height:100%;object-fit:cover; }
.info-row { display:flex;gap:8px;flex-wrap:wrap;margin-top:6px; }
.info-chip { background:#1e2d4a;color:#a0b4cc;border-radius:8px;padding:3px 10px;font-size:11px; }
.expired-alert { background:linear-gradient(135deg,#2a0808,#1a0808);border:1px solid #ef444460;border-radius:14px;padding:20px;text-align:center;margin:16px 0; }
.section-label { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#4f9eff;margin-bottom:12px; }
</style>
@endsection

@section('content')
<div class="track-layout">
<div class="track-container">
    <div class="track-header">
        <i class="fas fa-gavel"></i>
        <h1>Track Your Legal Case</h1>
        <p>Enter your Case ID to see status, bids, and deadline</p>
    </div>

    <div class="search-card">
        <div style="display:flex;gap:10px;align-items:center;">
            <div style="position:relative;flex:1;">
                <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#4f9eff;font-size:14px;"></i>
                <input type="text" id="caseIdInput" placeholder="e.g. LR-20260606-0001"
                    style="width:100%;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:12px;padding:13px 14px 13px 40px;color:#fff;font-size:15px;font-family:monospace;outline:none;box-sizing:border-box;"
                    onkeydown="if(event.key==='Enter') searchCase()"
                    oninput="this.value=this.value.toUpperCase()" />
            </div>
            <button onclick="searchCase()"
                style="background:#4f9eff;color:#000;border:none;border-radius:12px;padding:13px 24px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;">
                <i class="fas fa-search"></i> Track
            </button>
        </div>
        <p style="color:#4a5568;font-size:12px;margin-top:10px;margin-bottom:0;">
            <i class="fas fa-info-circle"></i> Case ID was shown after you submitted your legal request
        </p>
    </div>

    <div id="resultArea"></div>
</div>
</div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
let countdownInterval = null;

window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (id) {
        document.getElementById('caseIdInput').value = id.toUpperCase();
        searchCase();
    }
});

async function searchCase() {
    const id   = document.getElementById('caseIdInput').value.trim();
    if (!id) { alert('Please enter a Case ID.'); return; }
    const area = document.getElementById('resultArea');
    area.innerHTML = `<div style="text-align:center;padding:40px;color:#6b7280;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:12px;">Looking up case...</p></div>`;
    if (countdownInterval) clearInterval(countdownInterval);

    try {
        const res  = await fetch(`/api/legal-request/track/${encodeURIComponent(id)}`);
        const data = await res.json();

        if (!data.success) {
            area.innerHTML = `
                <div class="case-card" style="text-align:center;">
                    <i class="fas fa-search" style="font-size:40px;color:#4a5568;margin-bottom:16px;display:block;"></i>
                    <h3 style="color:#fff;margin-bottom:8px;">Case Not Found</h3>
                    <p style="color:#6b7280;">No case found with ID <strong style="color:#4f9eff;font-family:monospace;">${id}</strong>. Please check and try again.</p>
                </div>`;
            return;
        }

        renderCase(data);
    } catch(e) {
        console.error('Track error:', e);
        area.innerHTML = `<div class="case-card" style="text-align:center;color:#ef4444;"><i class="fas fa-exclamation-circle fa-2x"></i><p style="margin-top:12px;">Error: ${e.message}</p></div>`;
    }
}

function renderCase(data) {
    const r    = data.case || data.request || {};
    const bids = r.bids || data.bids || [];
    const area = document.getElementById('resultArea');

    const statusClass = {
        open:'status-open', bidding:'status-bidding',
        accepted:'status-accepted', expired:'status-expired',
        cancelled:'status-cancelled', in_progress:'status-accepted', completed:'status-accepted',
    }[r.status] || 'status-open';

    const statusLabel = {
        open:'🔵 Open — Waiting for bids',
        bidding:'🟡 Bidding — Lawyers have offered',
        accepted:'🟢 Accepted — Lawyer assigned',
        in_progress:'🟢 In Progress',
        completed:'✅ Completed',
        expired:'🔴 Expired — Deadline passed',
        cancelled:'⛔ Cancelled',
    }[r.status] || r.status;

    const deadline  = r.deadline ? new Date(r.deadline) : null;
    const isExpired = r.is_expired || r.status === 'expired' || (deadline && deadline < new Date() && ['open','bidding'].includes(r.status));
    const typeCap   = (r.issue_type || '').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());

    area.innerHTML = `
    <div class="case-card">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
            <div>
                <div style="font-size:11px;color:#4f9eff;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;">Case ID</div>
                <div style="font-size:22px;font-weight:800;font-family:monospace;color:#fff;">${r.request_id || id}</div>
            </div>
            <span class="status-badge ${statusClass}">${statusLabel}</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px;">
                <div style="color:#6b7280;font-size:11px;margin-bottom:3px;">ISSUE TYPE</div>
                <div style="font-weight:600;">${typeCap || '—'}</div>
            </div>
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px;">
                <div style="color:#6b7280;font-size:11px;margin-bottom:3px;">BUDGET</div>
                <div style="font-weight:600;">${r.budget_max ? '৳' + Number(r.budget_max).toLocaleString() : 'Not specified'}</div>
            </div>
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px;">
                <div style="color:#6b7280;font-size:11px;margin-bottom:3px;">SUBMITTED</div>
                <div style="font-weight:600;">${r.created_at ? new Date(r.created_at).toLocaleString('en-BD') : '—'}</div>
            </div>
            <div style="background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:12px;">
                <div style="color:#6b7280;font-size:11px;margin-bottom:3px;">BIDS RECEIVED</div>
                <div style="font-weight:600;color:${bids.length>0?'#22c55e':'#6b7280'}">${bids.length} bid${bids.length!==1?'s':''}</div>
            </div>
        </div>

        ${deadline && !isExpired ? `
        <div class="countdown-box" id="countdownBox">
            <i class="fas fa-hourglass-half" style="color:#4f9eff;font-size:22px;flex-shrink:0;"></i>
            <div>
                <div style="color:#a0b4cc;font-size:12px;margin-bottom:2px;">TIME REMAINING FOR LAWYER RESPONSE</div>
                <div class="countdown-time" id="countdownTimer">Calculating...</div>
                <div style="color:#6b7280;font-size:11px;margin-top:2px;">Deadline: ${deadline.toLocaleString('en-BD')}</div>
            </div>
        </div>` : ''}

        ${isExpired && ['open','bidding','expired'].includes(r.status) ? `
        <div class="expired-alert">
            <i class="fas fa-clock" style="font-size:30px;color:#ef4444;margin-bottom:10px;display:block;"></i>
            <h3 style="color:#ef4444;margin-bottom:6px;">Deadline Passed — No Lawyer Accepted</h3>
            <p style="color:#a0b4cc;margin-bottom:16px;">The response window has closed. Increase your budget and resubmit to attract more lawyers.</p>
            <a href="/legal" style="background:#4f9eff;color:#000;border-radius:10px;padding:10px 20px;font-weight:700;text-decoration:none;font-size:14px;">
                <i class="fas fa-plus"></i> Submit New Request with Higher Budget
            </a>
        </div>` : ''}

        ${bids.length > 0 ? `
        <div style="margin-top:20px;">
            <div class="section-label">⚖️ ${bids.length} Lawyer Offer${bids.length!==1?'s':''} Received</div>
            ${bids.map(b => {
                const lawyer = b.lawyer || {};
                return `
                <div class="bid-card">
                    <div class="bid-avatar">${lawyer.photo ? `<img src="/${lawyer.photo}">` : '⚖️'}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:14px;">${lawyer.name || lawyer.full_name || 'Lawyer'}</div>
                        <div style="font-size:12px;color:#a0b4cc;margin-top:2px;">${lawyer.city || ''} · ${lawyer.experience_years || 0} yrs · ★ ${lawyer.rating || 'New'}</div>
                        <div class="info-row">
                            <span class="info-chip" style="color:#22c55e;background:#22c55e15;">৳${Number(b.proposed_fee).toLocaleString()} fee</span>
                            ${b.estimated_days ? `<span class="info-chip">${b.estimated_days} day${b.estimated_days>1?'s':''}</span>` : ''}
                        </div>
                        ${b.cover_note ? `<div style="font-size:12px;color:#a0b4cc;margin-top:8px;font-style:italic;">"${b.cover_note}"</div>` : ''}
                    </div>
                </div>`;
            }).join('')}
            <p style="font-size:12px;color:#6b7280;margin-top:8px;"><i class="fas fa-lock"></i> Log in to accept a bid and proceed.</p>
        </div>` : (!isExpired && ['open','bidding'].includes(r.status) ? `
        <div style="text-align:center;padding:20px;color:#6b7280;font-size:13px;">
            <i class="fas fa-hourglass-half" style="font-size:24px;margin-bottom:8px;display:block;color:#4f9eff;"></i>
            Waiting for lawyers to respond...
        </div>` : '')}
    </div>`;

    if (deadline && !isExpired) {
        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            const diff = deadline - new Date();
            if (diff <= 0) {
                clearInterval(countdownInterval);
                const t = document.getElementById('countdownTimer');
                if (t) t.textContent = 'Expired';
                document.getElementById('countdownBox')?.classList.add('urgent');
                return;
            }
            const h   = Math.floor(diff / 3600000);
            const m   = Math.floor((diff % 3600000) / 60000);
            const s   = Math.floor((diff % 60000) / 1000);
            const pad = n => String(n).padStart(2,'0');
            const t   = document.getElementById('countdownTimer');
            if (t) t.textContent = h > 0 ? `${h}h ${pad(m)}m ${pad(s)}s` : `${pad(m)}m ${pad(s)}s`;
            if (diff < 3600000) document.getElementById('countdownBox')?.classList.add('urgent');
        }, 1000);
    }
}
</script>
@endsection