@extends('layouts.app')
@section('title', 'My Legal Cases — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
<style>
:root { --bg:#070d1a; --card:#0d1526; --input:#0a1020; --border:#1e2d4a; --accent:#4f9eff; --success:#22c55e; --warning:#f59e0b; --error:#ef4444; --muted:#6b7fa3; }
.cases-layout { min-height:calc(100vh - 70px); padding:40px 20px; }
.cases-container { max-width:900px; margin:0 auto; }
.page-header { margin-bottom:30px; }
.page-header h1 { font-size:26px; font-weight:800; }
.page-header p  { color:var(--muted); margin-top:4px; }

/* Case rows */
.case-row {
    background:var(--card); border:1px solid var(--border);
    border-radius:16px; padding:20px 24px; margin-bottom:12px;
    cursor:pointer; transition:.2s;
}
.case-row:hover { border-color:#2e4a7a; background:#0f1a30; }
.case-row.expanded { border-color:var(--accent); }
.status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.case-id   { font-family:monospace; font-size:15px; font-weight:700; color:var(--accent); }
.case-meta { color:var(--muted); font-size:12px; margin-top:3px; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.countdown-inline { font-family:monospace; font-size:13px; font-weight:700; color:var(--accent); background:#4f9eff15; border:1px solid #4f9eff30; border-radius:8px; padding:3px 10px; }
.countdown-inline.urgent  { color:var(--error);   background:#ef444415; border-color:#ef444430; }
.countdown-inline.expired { color:var(--muted);   background:#6b728015; border-color:#6b728030; }

/* Bid panel */
.bids-panel {
    display:none; margin-top:18px; padding-top:18px;
    border-top:1px solid var(--border);
}
.bids-panel.show { display:block; }
.bids-panel-title { font-size:14px; font-weight:700; margin-bottom:14px; color:var(--accent); }

/* Lawyer bid card */
.lawyer-bid-card {
    background:var(--input); border:1px solid var(--border);
    border-radius:12px; padding:16px 18px; margin-bottom:10px;
    transition:.2s;
}
.lawyer-bid-card:hover { border-color:#2e4a7a; }
.lbc-top { display:flex; align-items:flex-start; gap:14px; margin-bottom:12px; }
.lbc-avatar {
    width:42px; height:42px; border-radius:50%;
    background:linear-gradient(135deg,#1a3a6e,#0a1e40);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0; overflow:hidden;
    border:2px solid var(--border);
}
.lbc-avatar img { width:100%; height:100%; object-fit:cover; }
.lbc-name  { font-size:14px; font-weight:700; }
.lbc-code  { font-size:11px; color:var(--accent); font-family:monospace; }
.lbc-stars { color:#f59e0b; font-size:12px; margin-top:2px; }
.lbc-meta  { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
.lbc-meta span { font-size:12px; color:var(--muted); }
.lbc-meta span b { color:#e8f0fe; }
.lbc-note  { font-size:13px; color:var(--muted); line-height:1.6; margin-bottom:12px; font-style:italic; }
.lbc-actions { display:flex; gap:10px; flex-wrap:wrap; }

/* Contact card (after accept) */
.contact-card {
    background:linear-gradient(135deg,#051a0f,#071a12);
    border:1px solid #22c55e40; border-radius:12px;
    padding:18px 20px; margin-top:14px;
}
.contact-card h4 { color:var(--success); font-size:14px; font-weight:700; margin-bottom:12px; }
.contact-row { display:flex; gap:10px; align-items:center; margin-bottom:8px; font-size:13px; }
.contact-row i { color:var(--accent); width:16px; }

/* Buttons */
.btn { padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:.15s; display:inline-flex; align-items:center; gap:6px; }
.btn-accept  { background:var(--success); color:#fff; }
.btn-accept:hover  { opacity:.85; }
.btn-reject  { background:transparent; border:1px solid var(--error); color:var(--error); }
.btn-reject:hover  { background:rgba(239,68,68,.08); }
.btn-primary { background:var(--accent); color:#fff; }
.btn-primary:hover { opacity:.85; }
.btn-sm { padding:6px 14px; font-size:12px; }

.empty-state { text-align:center; padding:60px 20px; color:var(--muted); }

/* All-rejected banner */
.all-rejected-banner {
    background:linear-gradient(135deg,#1a0a00,#1a1000);
    border:1px solid #f59e0b40; border-radius:12px;
    padding:18px 20px; margin-top:14px;
}
.all-rejected-banner h4 { color:var(--warning); font-size:14px; font-weight:700; margin-bottom:6px; }
.all-rejected-banner p  { color:var(--muted); font-size:13px; }

/* Toast */
.toast { position:fixed; bottom:28px; right:28px; z-index:9999; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px 20px; font-size:14px; max-width:340px; transform:translateY(80px); opacity:0; transition:all .3s; pointer-events:none; }
.toast.show { transform:translateY(0); opacity:1; }
.toast.success { border-color:rgba(34,197,94,.4); color:#86efac; }
.toast.error   { border-color:rgba(239,68,68,.4);  color:#fca5a5; }
.toast.warning { border-color:rgba(245,158,11,.4); color:#fcd34d; }


/* Payment due banner */
.payment-due-banner {
    background: linear-gradient(135deg, #1a0a00, #1a1000);
    border: 1px solid #f59e0b50;
    border-radius: 12px;
    padding: 18px 20px;
    margin-top: 14px;
}
.payment-due-banner h4 { color: #f59e0b; font-size: 15px; font-weight: 700; margin-bottom: 8px; }
.payment-due-banner .amount { font-size: 26px; font-weight: 800; color: #fff; }
.payment-due-banner .deadline { font-size: 12px; color: #9ca3af; margin-top: 4px; }
.payment-due-banner .days-left { font-weight: 700; }
.days-left.urgent { color: #ef4444; }
.days-left.ok     { color: #f59e0b; }
 
.btn-paid {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff; border: none; border-radius: 10px;
    padding: 12px 28px; font-size: 14px; font-weight: 700;
    cursor: pointer; margin-top: 14px; display: inline-flex;
    align-items: center; gap: 8px;
}
.btn-paid:hover { opacity: .85; }
.btn-paid:disabled { opacity: .5; cursor: not-allowed; }
 
/* Payment claimed state */
.payment-claimed-banner {
    background: linear-gradient(135deg, #0a1520, #071525);
    border: 1px solid #4f9eff30;
    border-radius: 12px;
    padding: 18px 20px;
    margin-top: 14px;
}
.payment-claimed-banner h4 { color: #4f9eff; font-size: 14px; margin-bottom: 6px; }
 
/* Payment disputed banner */
.payment-disputed-banner {
    background: linear-gradient(135deg, #1a0505, #180a0a);
    border: 1px solid #ef444450;
    border-radius: 12px;
    padding: 18px 20px;
    margin-top: 14px;
}
.payment-disputed-banner h4 { color: #ef4444; font-size: 14px; margin-bottom: 8px; }
.payment-disputed-banner p  { color: #9ca3af; font-size: 13px; line-height: 1.6; }
.payment-disputed-banner .admin-mail { color: #4f9eff; font-weight: 700; }

</style>
@endsection

@section('content')
<div class="cases-layout">
<div class="cases-container">
    <div class="page-header">
        <h1><i class="fas fa-gavel" style="color:#4f9eff;margin-right:10px;"></i>My Legal Cases</h1>
        <p>Track your requests and review lawyer bids</p>
    </div>

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
        <div class="empty-state"><i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:12px;display:block;"></i><p>Loading your cases...</p></div>
    </div>
</div>
</div>

<div class="toast" id="toast"></div>

<script src="{{ asset('js/main.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
<script>
let allCases = [];
const timers = {};

// ── Auth helpers ───────────────────────────────────────────
const userToken = () => localStorage.getItem('sv_token') || '';
const authHeaders = () => ({
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
    ...(userToken() ? { 'Authorization': 'Bearer ' + userToken() } : {}),
});
const authFetch = (url, opts = {}) => fetch(url, {
    credentials: 'include',
    ...opts,
    headers: { ...authHeaders(), ...(opts.headers || {}) },
});

// ── Load all cases ─────────────────────────────────────────
async function loadMyCases() {
    try {
        const res  = await authFetch('/api/legal-request/my-requests');
        const data = await res.json();

        if (!data.success) {
            document.getElementById('casesList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock" style="font-size:36px;margin-bottom:12px;display:block;"></i>
                    <p>Please <a href="/login" style="color:#4f9eff">log in</a> to see your cases.</p>
                </div>`;
            return;
        }

        allCases = data.requests || [];
        renderCases(allCases);
    } catch(e) {
        document.getElementById('casesList').innerHTML =
            `<div class="empty-state"><i class="fas fa-exclamation-circle" style="font-size:32px;margin-bottom:12px;display:block;"></i><p>Error loading cases.</p></div>`;
    }
}

// ── Render case list ───────────────────────────────────────
function renderCases(cases) {
    Object.values(timers).forEach(clearInterval);

    if (!cases.length) {
        document.getElementById('casesList').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-gavel" style="font-size:36px;margin-bottom:12px;display:block;"></i>
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
            in_progress:'#22c55e', completed:'#22c55e',
            expired:'#ef4444', cancelled:'#6b7280', exhausted:'#f59e0b',
            resolved_pending_payment:'#f59e0b', payment_disputed:'#ef4444'
        }[r.status] || '#6b7280';

        const statusLabel = {
            open:'Waiting', bidding:'Bids Received', accepted:'Lawyer Accepted',
            in_progress:'In Progress', completed:'Completed',
            expired:'Expired', cancelled:'Cancelled', exhausted:'No Lawyers Left',
            resolved_pending_payment:'Payment Due', payment_disputed:'Payment Issue'
        }[r.status] || r.status;

        const typeCap  = (r.issue_type||'').replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
        const date     = new Date(r.created_at).toLocaleDateString('en-BD', { day:'numeric', month:'short', year:'numeric' });
        const bidCount = (r.bids || []).length;
        const hasBids  = bidCount > 0 && ['open','bidding'].includes(r.status);

        return `
        <div class="case-row" id="case-row-${r.request_id}" onclick="toggleBids('${r.request_id}', ${i})" data-id="${r.request_id}">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div class="status-dot" style="background:${statusColor};box-shadow:0 0 6px ${statusColor}60;"></div>
                <div style="flex:1;min-width:0;">
                    <div class="case-id">${r.request_id}</div>
                    <div class="case-meta">${typeCap} · Submitted ${date}</div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    ${hasBids ? `<span class="badge" style="background:#22c55e15;color:#22c55e;border:1px solid #22c55e30;animation:pulse 2s infinite;">⚖️ ${bidCount} bid${bidCount!==1?'s':''} — Tap to review</span>` : ''}
                    ${r.status === 'accepted' ? `<span class="badge" style="background:#22c55e15;color:#22c55e;border:1px solid #22c55e30;">✅ Lawyer Assigned</span>` : ''}
                    ${r.budget_max ? `<span class="badge" style="background:#4f9eff15;color:#4f9eff;border:1px solid #4f9eff30;">৳${Number(r.budget_max).toLocaleString()}</span>` : ''}
                    <span class="badge" style="background:${statusColor}15;color:${statusColor};border:1px solid ${statusColor}30;">${statusLabel}</span>
                    ${r.deadline && ['open','bidding'].includes(r.status) ? `<span class="countdown-inline" id="ct-${i}">...</span>` : ''}
                    ${r.status === 'expired' ? `<span class="countdown-inline expired">Expired</span>` : ''}
                    ${r.status === 'exhausted' ? `<span class="countdown-inline expired">No lawyers left</span>` : ''}
                </div>
                <i class="fas fa-chevron-down" id="chevron-${r.request_id}" style="color:#4a5568;font-size:13px;transition:.2s;"></i>
            </div>

            <!-- Bids panel — expands on click -->
            <div class="bids-panel" id="bids-${r.request_id}">
                <div id="bids-content-${r.request_id}">
                    <div style="color:var(--muted);font-size:13px;">Loading bids...</div>
                </div>
            </div>
        </div>`;
    }).join('');

    // Countdowns
    cases.forEach((r, i) => {
        if (!r.deadline || !['open','bidding'].includes(r.status)) return;
        timers[i] = setInterval(() => {
            const el   = document.getElementById('ct-' + i);
            if (!el) { clearInterval(timers[i]); return; }
            const diff = new Date(r.deadline) - new Date();
            if (diff <= 0) {
                el.textContent = 'Expired'; el.classList.add('expired');
                clearInterval(timers[i]); return;
            }
            const h   = Math.floor(diff / 3600000);
            const m   = Math.floor((diff % 3600000) / 60000);
            const s   = Math.floor((diff % 60000) / 1000);
            const pad = n => String(n).padStart(2, '0');
            el.textContent = h > 0 ? `${h}h ${pad(m)}m` : `${pad(m)}m ${pad(s)}s`;
            if (diff < 3600000) el.classList.add('urgent');
        }, 1000);
    });
}

// ── Toggle bids panel ─────────────────────────────────────
async function toggleBids(requestId, idx) {
    const panel   = document.getElementById('bids-' + requestId);
    const chevron = document.getElementById('chevron-' + requestId);
    const row     = document.getElementById('case-row-' + requestId);

    if (panel.classList.contains('show')) {
        panel.classList.remove('show');
        chevron.style.transform = 'rotate(0)';
        row.classList.remove('expanded');
        return;
    }

    panel.classList.add('show');
    chevron.style.transform = 'rotate(180deg)';
    row.classList.add('expanded');

    // Load bids from API
    await loadBids(requestId);
}

// ── Load bids for a request ───────────────────────────────
async function loadBids(requestId) {
    const content = document.getElementById('bids-content-' + requestId);
    content.innerHTML = '<div style="color:var(--muted);font-size:13px;padding:8px 0;">Loading bids...</div>';

    // Find case data
    const caseData = allCases.find(c => c.request_id === requestId);
    if (!caseData) return;

    // If already accepted — show contact info
    if (caseData.status === 'accepted' && caseData.assigned_lawyer) {
        content.innerHTML = acceptedLawyerCard(caseData.assigned_lawyer);
        return;
    }

    if (!['open', 'bidding'].includes(caseData.status)) {
        if (caseData.status === 'exhausted') {
            content.innerHTML = `
                <div class="all-rejected-banner">
                    <h4>😔 No More Lawyers Available</h4>
                    <p>All lawyers in your area have been reviewed and declined.</p>
                    <p style="margin-top:8px;color:#fcd34d;">Try increasing your budget or expanding your preferred area to attract more lawyers.</p>
                    <a href="/legal" style="display:inline-block;margin-top:14px;background:#f59e0b;color:#000;border-radius:8px;padding:8px 18px;font-weight:700;font-size:13px;text-decoration:none;">
                        🔄 Submit New Request
                    </a>
                </div>`;

        } else if (caseData.status === 'expired') {
            content.innerHTML = `
                <div style="background:linear-gradient(135deg,#1a0808,#2a0f0f);border:1px solid #ef444460;border-radius:14px;padding:20px;text-align:center;margin:8px 0;">
                    <i class="fas fa-clock" style="font-size:30px;color:#ef4444;margin-bottom:10px;display:block;"></i>
                    <h3 style="color:#ef4444;margin-bottom:6px;">Deadline Passed — No Lawyer Accepted</h3>
                    <p style="color:#a0b4cc;margin-bottom:16px;">The response window has closed. Increase your budget and resubmit.</p>
                    <a href="/legal" style="background:#4f9eff;color:#000;border-radius:10px;padding:10px 20px;font-weight:700;text-decoration:none;font-size:14px;">
                        <i class="fas fa-plus"></i> Submit New Request with Higher Budget
                    </a>
                </div>`;

        } else if (caseData.status === 'resolved_pending_payment') {
            content.innerHTML = `
                <div class="payment-due-banner" id="pay-due-${requestId}">
                    <h4>💳 Payment Required — Case Resolved</h4>
                    <div id="pay-detail-${requestId}">
                        <div style="color:#9ca3af;font-size:13px;">Loading payment details...</div>
                    </div>
                </div>`;
            loadPaymentDetails(requestId);

        } else if (caseData.status === 'payment_disputed') {
            content.innerHTML = `
                <div class="payment-disputed-banner">
                    <h4>⚠️ Payment Not Confirmed</h4>
                    <p>Your lawyer has not confirmed receiving your payment.<br><br>
                    <strong style="color:#fff;">If you paid:</strong> Email proof to
                    <span class="admin-mail">admin@safevoice.com</span> within 48 hours.
                    After 48 hours, legal action will be taken.</p>
                </div>`;

        } else {
            content.innerHTML = `<div style="color:var(--muted);font-size:13px;padding:8px 0;">No bids available for this case.</div>`;
        }
        return;
    }

    try {
        const res  = await authFetch(`/api/legal-request/${requestId}/bids`);
        const data = await res.json();
        const bids = data.bids || [];

        if (!bids.length) {
            content.innerHTML = `
                <div style="text-align:center;padding:20px;color:var(--muted);">
                    <div style="font-size:28px;margin-bottom:8px;">⏳</div>
                    <p>Waiting for lawyers to respond...</p>
                    <p style="font-size:12px;margin-top:4px;">You'll see bids here as lawyers submit them.</p>
                </div>`;
            return;
        }

        content.innerHTML = `
            <div class="bids-panel-title">⚖️ ${bids.length} Lawyer${bids.length !== 1 ? 's' : ''} responded — Review and choose</div>
            ${bids.map(b => lawyerBidCard(b, requestId)).join('')}`;

    } catch(e) {
        content.innerHTML = `<div style="color:var(--error);font-size:13px;">Error loading bids.</div>`;
    }
}

// ── Lawyer bid card HTML ──────────────────────────────────
function lawyerBidCard(b, requestId) {
    const stars = '★'.repeat(Math.round(b.lawyer?.rating || 0)) + '☆'.repeat(5 - Math.round(b.lawyer?.rating || 0));
    const avatar = b.lawyer?.photo
        ? `<img src="/${b.lawyer.photo}" alt="">`
        : '⚖️';

    return `
    <div class="lawyer-bid-card" id="bid-card-${b.id}">
        <div class="lbc-top">
            <div class="lbc-avatar">${avatar}</div>
            <div style="flex:1;min-width:0;">
                <div class="lbc-name">${b.lawyer?.name || 'Advocate'}</div>
                <div class="lbc-stars">${stars} <span style="color:var(--muted);font-size:11px;">(${b.lawyer?.rating_count || 0} reviews)</span></div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:20px;font-weight:800;color:#22c55e;">৳${Number(b.proposed_fee).toLocaleString()}</div>
                <div style="font-size:11px;color:var(--muted);">Proposed fee</div>
            </div>
        </div>

        <div class="lbc-meta">
            ${b.lawyer?.experience ? `<span>🎓 <b>${b.lawyer.experience} yrs</b> experience</span>` : ''}
            ${b.lawyer?.city       ? `<span>📍 <b>${b.lawyer.city}</b></span>` : ''}
            ${b.estimated_days     ? `<span>📅 Est. <b>${b.estimated_days} days</b></span>` : ''}
            ${b.lawyer?.completed_cases ? `<span>✅ <b>${b.lawyer.completed_cases}</b> cases done</span>` : ''}
            ${(b.lawyer?.specializations||[]).length ? `<span>⚖️ ${b.lawyer.specializations.slice(0,2).join(', ')}</span>` : ''}
        </div>

        ${b.cover_note ? `<div class="lbc-note">"${b.cover_note}"</div>` : ''}

        <div class="lbc-actions">
            <button class="btn btn-accept btn-sm" onclick="acceptBid('${requestId}', ${b.id}, event)"
                ${b.status === 'accepted' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}>
                ${b.status === 'accepted' ? '✅ Accepted' : '✅ Accept This Lawyer'}
            </button>
            ${b.status !== 'accepted' ? `<button class="btn btn-reject btn-sm" onclick="rejectBid('${requestId}', ${b.id}, event)">
                ✕ Decline
            </button>` : ''}
        </div>
    </div>`;
}

// ── Accepted lawyer contact card ──────────────────────────
function acceptedLawyerCard(lawyer) {
    return `
    <div class="contact-card">
        <h4>🎉 Lawyer Accepted — Contact Details</h4>
        <div class="contact-row"><i class="fas fa-user"></i> <b>${lawyer.name || 'Your Lawyer'}</b></div>
        ${lawyer.phone  ? `<div class="contact-row"><i class="fas fa-phone"></i> <a href="tel:${lawyer.phone}" style="color:#4f9eff;">${lawyer.phone}</a></div>` : ''}
        ${lawyer.email  ? `<div class="contact-row"><i class="fas fa-envelope"></i> <a href="mailto:${lawyer.email}" style="color:#4f9eff;">${lawyer.email}</a></div>` : ''}
        ${lawyer.office_address ? `<div class="contact-row"><i class="fas fa-map-marker-alt"></i> ${lawyer.office_address}</div>` : ''}
        <div style="margin-top:12px;font-size:12px;color:var(--muted);">The lawyer has been notified and will reach out to you soon.</div>
    </div>`;
}

// ── Accept bid ────────────────────────────────────────────
async function acceptBid(requestId, bidId, e) {
    e.stopPropagation();
    const btn = e.target;
    btn.disabled = true;
    btn.textContent = 'Accepting...';

    try {
        const res  = await authFetch(`/api/legal-request/${requestId}/accept-bid`, {
            method: 'POST',
            body: JSON.stringify({ bid_id: bidId }),
        });
        const data = await res.json();

        if (data.success) {
            showToast('✅ Lawyer accepted! Contact details are now visible.', 'success');
            // Reload cases to get updated data with contact info
            await loadMyCases();
            // Re-open the panel for this case
            setTimeout(() => {
                const panel = document.getElementById('bids-' + requestId);
                if (panel) toggleBids(requestId, 0);
            }, 300);
        } else {
            showToast(data.message || 'Failed to accept.', 'error');
            btn.disabled = false;
            btn.textContent = '✅ Accept This Lawyer';
        }
    } catch(err) {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.textContent = '✅ Accept This Lawyer';
    }
}

// ── Reject bid (Pathao-style soft reject) ─────────────────
async function rejectBid(requestId, bidId, e) {
    e.stopPropagation();
    const btn = e.target;
    btn.disabled = true;
    btn.textContent = 'Declining...';

    try {
        const res  = await authFetch(`/api/legal-request/${requestId}/reject-bid`, {
            method: 'POST',
            body: JSON.stringify({ bid_id: bidId }),
        });
        const data = await res.json();

        if (data.success) {
            // Remove this bid card
            document.getElementById('bid-card-' + bidId)?.remove();

            if (data.all_rejected && data.lawyers_available === 0) {
                // সব lawyer reject বা কেউ bid করেনি — budget বাড়াতে বলো
                const content = document.getElementById('bids-content-' + requestId);
                content.innerHTML = `
                    <div class="all-rejected-banner">
                        <h4>😔 No More Lawyers Available</h4>
                        <p>${data.message}</p>
                        <p style="margin-top:8px;color:#fcd34d;">${data.suggestion || 'Try increasing your budget or expanding your preferred area.'}</p>
                        <a href="/legal" style="display:inline-block;margin-top:14px;background:#f59e0b;color:#000;border-radius:8px;padding:8px 18px;font-weight:700;font-size:13px;text-decoration:none;">
                            🔄 Submit New Request
                        </a>
                    </div>`;
                showToast('No more lawyers available. Try a new request.', 'warning');
            } else {
                showToast('Bid declined. Case is still open for other lawyers.', 'success');
                // Check if there are remaining bid cards
                const remaining = document.querySelectorAll(`#bids-content-${requestId} .lawyer-bid-card`);
                if (remaining.length === 0) {
                    // Reload to get fresh bids if any new ones came in
                    await loadBids(requestId);
                }
            }

            // Update badge count
            await loadMyCases();
            // Keep panel open
            const panel = document.getElementById('bids-' + requestId);
            if (panel) panel.classList.add('show');
        } else {
            showToast(data.message || 'Failed to decline bid.', 'error');
            btn.disabled = false;
            btn.textContent = '✕ Decline';
        }
    } catch(err) {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.textContent = '✕ Decline';
    }
}

function filterCases(q) {
    if (!q) { renderCases(allCases); return; }
    renderCases(allCases.filter(r => r.request_id.includes(q)));
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 4000);
}

loadMyCases();

// ─── Payment status banner renderer ──────────────────────────────────
// loadBids() function এ case status check এর পরে এই function call করো
function renderPaymentBanner(caseData) {
    const status = caseData.status;
 
    // ১. Payment due — user কে pay করতে হবে
    if (status === 'resolved_pending_payment') {
        return renderPaymentDue(caseData);
    }
 
    // ২. User claimed payment, waiting for lawyer
    if (status === 'accepted' && caseData.payment_status === 'claimed') {
        return `
        <div class="payment-claimed-banner">
            <h4>🕐 Waiting for Lawyer Confirmation</h4>
            <p style="color:#8899b8; font-size:13px;">
                You've marked your payment as done. Your lawyer has been notified and will confirm within 48 hours.
            </p>
        </div>`;
    }
 
    // ৩. Payment disputed
    if (status === 'payment_disputed') {
        return `
        <div class="payment-disputed-banner">
            <h4>⚠️ Payment Not Confirmed</h4>
            <p>
                Your lawyer has not confirmed receiving your payment.<br><br>
                <strong style="color:#fff;">If you paid:</strong> Email proof to
                <span class="admin-mail">admin@safevoice.com</span> within 48 hours to stop action against your account.
                After 48 hours, legal action will be taken.
            </p>
        </div>`;
    }
 
    return '';
}
 
// ─── "Payment Due" banner with "I've Paid" button ────────────────────
function renderPaymentDue(caseData) {
    // Payment details আলাদাভাবে fetch করো
    return `
    <div class="payment-due-banner" id="pay-due-${caseData.request_id}">
        <h4>💳 Payment Required — Case Resolved</h4>
        <div id="pay-detail-${caseData.request_id}">
            <div style="color:#9ca3af; font-size:13px;">Loading payment details...</div>
        </div>
    </div>`;
}
 
// Payment details load করো (case-payment/status API)
async function loadPaymentDetails(requestId) {
    try {
        const res  = await authFetch(`/api/case-payment/${requestId}/status`);
        const data = await res.json();
        if (!data.success) return;
 
        const p = data.payment;
        const deadline = new Date(p.payment_deadline);
        const daysLeft = p.days_left;
        const urgentClass = daysLeft <= 1 ? 'urgent' : 'ok';
 
        const detailEl = document.getElementById(`pay-detail-${requestId}`);
        if (!detailEl) return;
 
        detailEl.innerHTML = `
            <div class="amount">৳${Number(p.gross_amount).toLocaleString('en-BD')}</div>
            <div class="deadline">
                Due by: <strong>${deadline.toLocaleDateString('en-BD', {day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})}</strong>
                · <span class="days-left ${urgentClass}">${daysLeft} day${daysLeft !== 1 ? 's' : ''} left</span>
            </div>
            <div style="color:#9ca3af; font-size:12px; margin-top:10px;">
                ℹ️ Pay your lawyer directly (bKash, Bank Transfer, Cash) and then confirm below.
            </div>
            <button class="btn-paid" id="btn-paid-${requestId}"
                onclick="confirmPaid('${requestId}', this)">
                ✅ I've Made the Payment
            </button>`;
 
    } catch(e) {
        console.error('Payment detail load error', e);
    }
}
 
// ─── Step 2: User confirms they paid ─────────────────────────────────
async function confirmPaid(requestId, btn) {
    if (!confirm("Confirm: You have paid your lawyer?\n\nYour lawyer will be asked to confirm receipt. Only click OK if you have actually made the payment.")) return;
 
    btn.disabled = true;
    btn.innerHTML = '⏳ Sending confirmation...';
 
    try {
        const res  = await authFetch(`/api/case-payment/${requestId}/confirm-paid`, { method: 'POST' });
        const data = await res.json();
 
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            // Banner update
            const dueDiv = document.getElementById(`pay-due-${requestId}`);
            if (dueDiv) {
                dueDiv.outerHTML = `
                <div class="payment-claimed-banner">
                    <h4>🕐 Waiting for Lawyer Confirmation</h4>
                    <p style="color:#8899b8; font-size:13px;">
                        Payment confirmation sent. Your lawyer will confirm within 48 hours.
                    </p>
                </div>`;
            }
        } else {
            showToast(data.message || 'Error.', 'error');
            btn.disabled = false;
            btn.innerHTML = '✅ I\'ve Made the Payment';
        }
    } catch(e) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '✅ I\'ve Made the Payment';
    }
}
 
// ─── Hook into existing loadBids() ───────────────────────────────────
// তোমার existing loadBids() function এ এটা যোগ করো:
/*
    // caseData.status check এর পরে, content render হওয়ার পরে:
    const paymentBanner = renderPaymentBanner(caseData);
    if (paymentBanner) {
        content.insertAdjacentHTML('beforeend', paymentBanner);
        // Payment details load করো যদি pending থাকে
        if (caseData.status === 'resolved_pending_payment') {
            loadPaymentDetails(requestId);
        }
    }
*/
 
// ─── Status label + color update ─────────────────────────────────────
// existing statusLabel + statusColor objects এ এগুলো যোগ করো:
/*
    statusColor:
    resolved_pending_payment: '#f59e0b',
    payment_disputed: '#ef4444',
 
    statusLabel:
    resolved_pending_payment: '💳 Payment Due',
    payment_disputed: '⚠️ Payment Issue',
*/

</script>
@endsection