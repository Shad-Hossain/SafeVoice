@extends('layouts.app')
@section('title', 'My Legal Cases — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
.cases-layout { min-height:calc(100vh - 70px); padding:40px 20px; }
.cases-container { max-width:860px; margin:0 auto; }
.page-header { margin-bottom:30px; }
.page-header h1 { font-size:26px;font-weight:800; }
.page-header p  { color:#a0b4cc;margin-top:4px; }

.case-row {
    background:#0d1526;
    border:1px solid #1e2d4a;
    border-radius:16px;
    padding:20px 24px;
    margin-bottom:14px;
    display:flex;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
    transition:.2s;
    cursor:pointer;
}
.case-row:hover { border-color:#2e4a7a;background:#0f1a30; }

.status-dot {
    width:10px;height:10px;border-radius:50%;flex-shrink:0;
}
.case-id   { font-family:monospace;font-size:15px;font-weight:700;color:#4f9eff; }
.case-meta { color:#a0b4cc;font-size:12px;margin-top:3px; }
.badge     { display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600; }

.countdown-inline {
    font-family:monospace;font-size:13px;font-weight:700;
    color:#4f9eff;background:#4f9eff15;
    border:1px solid #4f9eff30;
    border-radius:8px;padding:3px 10px;
}
.countdown-inline.urgent { color:#ef4444;background:#ef444415;border-color:#ef444430; }
.countdown-inline.expired { color:#6b7280;background:#6b728015;border-color:#6b728030; }

.empty-state { text-align:center;padding:60px 20px;color:#6b7280; }
.empty-state i { font-size:48px;margin-bottom:16px;display:block;color:#1e2d4a; }
</style>
@endsection

@section('content')
<div class="cases-layout">
<div class="cases-container">
    <div class="page-header">
        <h1><i class="fas fa-gavel" style="color:#4f9eff;margin-right:10px;"></i>My Legal Cases</h1>
        <p>Track all your submitted legal requests</p>
    </div>

    <!-- Quick search -->
    <div style="display:flex;gap:10px;margin-bottom:24px;align-items:center;">
        <div style="position:relative;flex:1;max-width:320px;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#4f9eff;font-size:13px;"></i>
            <input id="quickSearch" type="text" placeholder="Search by Case ID..."
                style="width:100%;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;padding:10px 12px 10px 36px;color:#fff;font-size:14px;outline:none;box-sizing:border-box;font-family:monospace;"
                oninput="filterCases(this.value.toUpperCase())" />
        </div>
        <a href="/legal" style="background:#4f9eff;color:#000;border-radius:10px;padding:10px 18px;font-weight:700;font-size:13px;text-decoration:none;white-space:nowrap;">
            <i class="fas fa-plus"></i> New Request
        </a>
    </div>

    <div id="casesList">
        <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading your cases...</p></div>
    </div>
</div>
</div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
let allCases = [];
const timers = {};

async function loadMyCases() {
    try {
        const res  = await fetch('/api/legal-request/my-requests');
        const data = await res.json();

        if (!data.success) {
            document.getElementById('casesList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock"></i>
                    <p>Please <a href="/login" style="color:#4f9eff">log in</a> to see your cases.</p>
                </div>`;
            return;
        }

        allCases = data.requests || [];
        renderCases(allCases);
    } catch(e) {
        document.getElementById('casesList').innerHTML = `
            <div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error loading cases.</p></div>`;
    }
}

function renderCases(cases) {
    Object.values(timers).forEach(clearInterval);

    if (!cases.length) {
        document.getElementById('casesList').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-gavel"></i>
                <h3 style="color:#fff;margin-bottom:8px;">No Legal Cases Yet</h3>
                <p style="margin-bottom:20px;">Submit a request to get connected with a lawyer.</p>
                <a href="/legal" style="background:#4f9eff;color:#000;border-radius:10px;padding:12px 24px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-plus"></i> Submit Legal Request
                </a>
            </div>`;
        return;
    }

    document.getElementById('casesList').innerHTML = cases.map((r, i) => {
        const statusColor = {
            open:'#4f9eff', bidding:'#fbbf24', accepted:'#22c55e',
            in_progress:'#22c55e', completed:'#22c55e', expired:'#ef4444', cancelled:'#6b7280'
        }[r.status] || '#6b7280';

        const statusLabel = {
            open:'Waiting', bidding:'Bids In', accepted:'Accepted',
            in_progress:'In Progress', completed:'Completed', expired:'Expired', cancelled:'Cancelled'
        }[r.status] || r.status;

        const typeCap = (r.issue_type||'').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
        const date    = new Date(r.created_at).toLocaleDateString('en-BD',{day:'numeric',month:'short',year:'numeric'});
        const bidCount= (r.bids||[]).length;

        return `
        <div class="case-row" onclick="window.location='/legal/track?id=${r.request_id}'" data-id="${r.request_id}">
            <div class="status-dot" style="background:${statusColor};box-shadow:0 0 6px ${statusColor}60;"></div>
            <div style="flex:1;min-width:0;">
                <div class="case-id">${r.request_id}</div>
                <div class="case-meta">${typeCap} · Submitted ${date}</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                ${bidCount > 0 ? `<span class="badge" style="background:#22c55e15;color:#22c55e;border:1px solid #22c55e30;">⚖️ ${bidCount} bid${bidCount!==1?'s':''}</span>` : ''}
                ${r.budget_max ? `<span class="badge" style="background:#4f9eff15;color:#4f9eff;border:1px solid #4f9eff30;">৳${Number(r.budget_max).toLocaleString()}</span>` : ''}
                <span class="badge" style="background:${statusColor}15;color:${statusColor};border:1px solid ${statusColor}30;">${statusLabel}</span>
                ${r.deadline && ['open','bidding'].includes(r.status) ? `<span class="countdown-inline" id="ct-${i}">...</span>` : ''}
                ${r.status === 'expired' ? `<span class="countdown-inline expired">Expired</span>` : ''}
            </div>
            <i class="fas fa-chevron-right" style="color:#4a5568;font-size:13px;"></i>
        </div>`;
    }).join('');

    // Start countdowns
    cases.forEach((r, i) => {
        if (!r.deadline || !['open','bidding'].includes(r.status)) return;
        timers[i] = setInterval(() => {
            const el   = document.getElementById('ct-' + i);
            if (!el) { clearInterval(timers[i]); return; }
            const diff = new Date(r.deadline) - new Date();
            if (diff <= 0) {
                el.textContent = 'Expired'; el.classList.add('expired'); el.classList.remove('urgent');
                clearInterval(timers[i]); return;
            }
            const h  = Math.floor(diff / 3600000);
            const m  = Math.floor((diff % 3600000) / 60000);
            const s  = Math.floor((diff % 60000) / 1000);
            const pad = n => String(n).padStart(2,'0');
            el.textContent = h > 0 ? `${h}h ${pad(m)}m` : `${pad(m)}m ${pad(s)}s`;
            if (diff < 3600000) el.classList.add('urgent');
        }, 1000);
    });
}

function filterCases(q) {
    if (!q) { renderCases(allCases); return; }
    renderCases(allCases.filter(r => r.request_id.includes(q)));
}

loadMyCases();
</script>
@endsection
