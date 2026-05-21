const API = '/api';
let complaints = [];
let activeId   = null;

document.addEventListener('DOMContentLoaded', () => { loadComplaints(); });

async function loadComplaints() {
    try {
        const res  = await fetch(`${API}/complaints`, { credentials: 'include' });
        const data = await res.json();
        complaints = (data.success && data.complaints) ? data.complaints : [];
    } catch (e) {
        complaints = [];
    }
    renderTable(complaints);
    updateCounts(complaints);
}

function renderTable(data) {
    const tbody = document.getElementById('complaintsBody');
    const empty = document.getElementById('emptyState');
    if (!tbody) return;
    if (!data || data.length === 0) {
        tbody.innerHTML = '';
        if (empty) empty.style.display = 'block';
        return;
    }
    if (empty) empty.style.display = 'none';
    tbody.innerHTML = data.map(c => {
        const anon      = c.is_anonymous == 1;
        const reporter  = anon ? '<div class="reporter-cell"><div class="anon-icon"><i class="fas fa-user-secret"></i></div><span>Anonymous</span></div>'
                               : `<div class="reporter-cell"><div class="anon-icon"><i class="fas fa-user"></i></div><span>${esc(c.reporter_name || 'User #'+c.user_id)}</span></div>`;
        const status    = (c.status||'').toLowerCase().replace(/\s+/g,'-');
        const dateStr   = c.incident_date ? new Date(c.incident_date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—';
        return `<tr>
            <td><strong style="color:#4f9eff">${esc(c.complaint_id)}</strong></td>
            <td>${esc(c.type||'—')}</td><td>${esc(c.location||'—')}</td>
            <td>${dateStr}</td><td>${reporter}</td>
            <td><span class="status ${status}">${esc(c.status||'Submitted')}</span></td>
            <td><button class="btn-view" onclick="openModal('${esc(c.complaint_id)}')"><i class="fas fa-eye"></i> View</button></td>
        </tr>`;
    }).join('');
}

function updateCounts(data) {
    document.getElementById('totalCount').textContent    = data.length;
    document.getElementById('pendingCount').textContent  = data.filter(c => /submitted|pending/i.test(c.status||'')).length;
    document.getElementById('reviewCount').textContent   = data.filter(c => /review|investigation/i.test(c.status||'')).length;
    document.getElementById('resolvedCount').textContent = data.filter(c => /resolved/i.test(c.status||'')).length;
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const type   = document.getElementById('typeFilter').value.toLowerCase();
    renderTable(complaints.filter(c => {
        const matchSearch = !search || (c.complaint_id+'').toLowerCase().includes(search) || (c.type||'').toLowerCase().includes(search) || (c.location||'').toLowerCase().includes(search);
        const matchStatus = !status || (c.status||'').toLowerCase().includes(status);
        const matchType   = !type   || (c.type||'').toLowerCase().includes(type);
        return matchSearch && matchStatus && matchType;
    }));
}

function resetFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value   = '';
    renderTable(complaints);
    updateCounts(complaints);
}

async function loadEvidence(complaint_id) {
    const box = document.getElementById('evidenceList');
    box.innerHTML = '<p style="color:#4a5568;font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading evidence...</p>';
    try {
        const res  = await fetch(`${API}/get_complaints_evidence?complaint_id=${encodeURIComponent(complaint_id)}`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success || !data.files || data.files.length === 0) {
            box.innerHTML = '<p style="color:#4a5568;font-size:13px;"><i class="fas fa-folder-open"></i> No evidence files uploaded yet.</p>';
            return;
        }
        box.innerHTML = data.files.map(f => {
            const isPdf = f.file_name.toLowerCase().endsWith('.pdf');
            const icon  = isPdf ? 'fa-file-pdf' : 'fa-file-image';
            const url   = `/${f.file_path}`;
            const date  = new Date(f.uploaded_at).toLocaleString('en-GB');
            return `<div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:#0a0f1e;border:1px solid #1e2d4a;border-radius:10px;margin-bottom:8px;">
                <i class="fas ${icon}" style="color:#4f9eff;font-size:22px;flex-shrink:0;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(f.file_name)}</div>
                    <div style="color:#4a5568;font-size:11px;margin-top:2px;">Uploaded: ${date}</div>
                </div>
                <a href="${url}" target="_blank" style="background:#1e2d4a;color:#4f9eff;border:1px solid #4f9eff;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
                    <i class="fas fa-eye"></i> View
                </a>
                <a href="${url}" download style="background:#1e2d4a;color:#2ecc71;border:1px solid #2ecc71;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
                    <i class="fas fa-download"></i>
                </a>
            </div>`;
        }).join('');
    } catch (e) {
        box.innerHTML = '<p style="color:#e63946;font-size:13px;"><i class="fas fa-exclamation-circle"></i> Could not load evidence.</p>';
    }
}

async function saveChanges() {
    if (!activeId) return;
    const status = document.getElementById('statusUpdate').value;
    const msg    = document.getElementById('adminMsgInput').value.trim();
    try {
        const res  = await fetch(`${API}/complaints/update-status`, {
            method:'POST', credentials:'include',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ complaint_id: activeId, status, admin_message: msg })
        });
        const data = await res.json();
        if (!data.success) { alert('Save failed: ' + (data.message||'Unknown error')); return; }
    } catch(e) { /* fallback: update local */ }
    const c = complaints.find(x => x.complaint_id === activeId);
    if (c) { c.status = status; c.admin_message = msg; }
    closeModal();
    applyFilters();
    updateCounts(complaints);
    showToast();
}

function closeModal() {
    const m = document.getElementById('detailModal');
    if (m) m.classList.remove('active');
    activeId = null;
}
function showToast() {
    const t = document.getElementById('toast');
    if (t) { t.classList.add('show'); setTimeout(() => t.classList.remove('show'), 3000); }
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Safe init — DOM ready হলে event bind করো
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    }
    const reqModal = document.getElementById('reqEvidenceModal');
    if (reqModal) {
        reqModal.addEventListener('click', function(e) { if (e.target === this) closeReqEvidenceModal(); });
    }
    // Check for expired evidence requests on page load
    checkExpiredEvidenceRequests();
});

// ══════════════════════════════════════════════════════════════
// REQUEST EVIDENCE FEATURE
// ══════════════════════════════════════════════════════════════

async function openModal(id) {
    const c = complaints.find(x => x.complaint_id === id);
    if (!c) return;
    activeId = id;
    const anon    = c.is_anonymous == 1;
    const dateStr = c.incident_date ? new Date(c.incident_date).toLocaleString('en-GB') : '—';
    document.getElementById('dId').textContent       = c.complaint_id;
    document.getElementById('dType').textContent     = c.type || '—';
    document.getElementById('dDate').textContent     = dateStr;
    document.getElementById('dLocation').textContent = c.location || '—';
    document.getElementById('dReporter').textContent = anon ? '🔒 Hidden (Anonymous)' : (c.reporter_name || 'User #'+c.user_id);
    document.getElementById('dAnon').textContent     = anon ? 'Yes' : 'No';
    document.getElementById('dDesc').textContent     = c.description || '—';

    // ── Consent fields ────────────────────────────────────────
    const lc = c.legal_consent;
    const pc = c.publish_consent;
    const legalEl   = document.getElementById('dLegalConsent');
    const publishEl = document.getElementById('dPublishConsent');
    if (legalEl) {
        if      (lc === 'yes') legalEl.innerHTML = '<span style="color:#4f9eff;font-weight:700;font-size:14px;">✅ Yes — Wants to proceed legally</span>';
        else if (lc === 'no')  legalEl.innerHTML = '<span style="color:#e63946;font-weight:700;font-size:14px;">❌ No — Does not want legal action</span>';
        else                   legalEl.innerHTML = '<span style="color:#6b7280;font-size:13px;">— Not answered</span>';
    }
    if (publishEl) {
        if      (pc === 'yes') publishEl.innerHTML = '<span style="color:#2ecc71;font-weight:700;font-size:14px;">✅ Yes — Agreed to publish on social media</span>';
        else if (pc === 'no')  publishEl.innerHTML = '<span style="color:#e63946;font-weight:700;font-size:14px;">❌ No — Does not want case published</span>';
        else                   publishEl.innerHTML = '<span style="color:#6b7280;font-size:13px;">— Not answered</span>';
    }
    const sel = document.getElementById('statusUpdate');
    sel.value = c.status || 'Submitted';
    document.getElementById('adminMsgInput').value = c.admin_message || '';

    // Hide/show Request Evidence button — only show if complaint has a user (not 100% anonymous with no user_id)
    const reqBtn = document.getElementById('btnReqEvidence');
    if (reqBtn) {
        reqBtn.style.display = (anon && !c.user_id) ? 'none' : 'inline-flex';
    }

    document.getElementById('detailModal').classList.add('active');
    loadEvidence(id);
    loadEvidenceRequestStatus(id);
}

async function loadEvidenceRequestStatus(complaint_id) {
    const box     = document.getElementById('evReqStatusBox');
    const content = document.getElementById('evReqStatusContent');
    if (!box || !content) return;

    try {
        const res  = await fetch(`/api/evidence-request/admin-list?complaint_id=${encodeURIComponent(complaint_id)}`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.requests || data.requests.length === 0) {
            box.style.display = 'none';
            return;
        }

        box.style.display = 'block';
        const latest = data.requests[0];
        const statusColors = {
            pending:   { color: '#f39c12', icon: 'fa-clock',         label: 'Waiting for User' },
            skipped:   { color: '#a0b4cc', icon: 'fa-forward',       label: 'Snoozed by User' },
            submitted: { color: '#2ecc71', icon: 'fa-check-circle',  label: 'Evidence Submitted ✓' },
            expired:   { color: '#e63946', icon: 'fa-times-circle',  label: 'DEADLINE MISSED — User failed to submit' },
            dismissed: { color: '#4a5568', icon: 'fa-ban',           label: 'Dismissed' },
        };
        const s    = statusColors[latest.status] || { color:'#fff', icon:'fa-question', label: latest.status };
        const dl   = latest.deadline ? new Date(latest.deadline).toLocaleString('en-GB') : '—';
        const sent = latest.requested_at ? new Date(latest.requested_at).toLocaleString('en-GB') : '—';

        content.innerHTML = `
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <i class="fas ${s.icon}" style="color:${s.color};font-size:14px;"></i>
                <span style="color:${s.color};font-weight:700;">${s.label}</span>
            </div>
            <div style="color:#4a5568;font-size:12px;">Sent: ${sent} &nbsp;|&nbsp; Deadline: ${dl}</div>
            ${latest.admin_note ? `<div style="margin-top:6px;color:#a0b4cc;font-size:12px;font-style:italic;">"${esc(latest.admin_note)}"</div>` : ''}
            ${data.requests.length > 1 ? `<div style="margin-top:6px;color:#4a5568;font-size:11px;">${data.requests.length} request(s) total for this case.</div>` : ''}
        `;
    } catch(e) {
        box.style.display = 'none';
    }
}

function openRequestEvidenceModal() {
    if (!activeId) return;
    document.getElementById('reqEvComplaintId').textContent = activeId;
    document.getElementById('reqEvNote').value = '';
    document.getElementById('reqEvStatus').style.display = 'none';
    document.getElementById('reqEvExisting').style.display = 'none';

    // Check if there's already an active request
    fetch(`/api/evidence-request/admin-list?complaint_id=${encodeURIComponent(activeId)}`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.requests && data.requests.length > 0) {
                const active = data.requests.find(r => r.status === 'pending' || r.status === 'skipped');
                if (active) {
                    const box  = document.getElementById('reqEvExisting');
                    const info = document.getElementById('reqEvExistingInfo');
                    box.style.display = 'block';
                    info.textContent  = `Status: ${active.status} | Sent: ${new Date(active.requested_at).toLocaleDateString('en-GB')} | Deadline: ${new Date(active.deadline).toLocaleDateString('en-GB')}. Sending again will refresh the deadline.`;
                }
            }
        }).catch(() => {});

    document.getElementById('reqEvidenceModal').classList.add('active');
}

function closeReqEvidenceModal() {
    document.getElementById('reqEvidenceModal').classList.remove('active');
}

async function sendEvidenceRequest() {
    const note   = document.getElementById('reqEvNote').value.trim();
    const btn    = document.getElementById('reqEvSendBtn');
    const status = document.getElementById('reqEvStatus');

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    try {
        const res  = await fetch('/api/evidence-request/create', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ complaint_id: activeId, admin_note: note }),
        });
        const data = await res.json();

        status.style.display = 'block';
        if (data.success) {
            status.style.background = '#0a1a10';
            status.style.border     = '1px solid #2ecc71';
            status.style.color      = '#2ecc71';
            status.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
            // Refresh evidence request status in main modal
            setTimeout(() => {
                closeReqEvidenceModal();
                loadEvidenceRequestStatus(activeId);
            }, 1500);
        } else {
            status.style.background = '#1a0a0a';
            status.style.border     = '1px solid #e63946';
            status.style.color      = '#e63946';
            status.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to send request.'}`;
        }
    } catch(e) {
        status.style.display    = 'block';
        status.style.background = '#1a0a0a';
        status.style.border     = '1px solid #e63946';
        status.style.color      = '#e63946';
        status.innerHTML        = '<i class="fas fa-exclamation-circle"></i> Network error. Please try again.';
    }

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Request';
}

// Check for expired evidence requests and show admin banner
async function checkExpiredEvidenceRequests() {
    try {
        // First trigger expiry check
        await fetch('/api/evidence-request/check-expired', { method: 'POST', credentials: 'include' });

        // Then get expired list
        const res  = await fetch('/api/evidence-request/expired-list', { credentials: 'include' });
        const data = await res.json();

        if (!data.success || !data.expired || data.expired.length === 0) return;

        const banner = document.getElementById('expiredEvidenceBanner');
        const text   = document.getElementById('expiredBannerText');
        if (!banner || !text) return;

        const cases = data.expired.map(e => e.complaint_id).join(', ');
        text.innerHTML = `${data.expired.length} case(s) failed to submit evidence on time: <strong style="color:#f39c12;">${esc(cases)}</strong>. Consider sending PI notification to the involved users.`;
        banner.style.display = 'flex';

        // Insert banner before the table
        const mainContent = document.querySelector('.main-content');
        const filterBar   = document.querySelector('.filter-bar');
        if (mainContent && filterBar) {
            mainContent.insertBefore(banner, filterBar);
            banner.style.display = 'flex';
        }
    } catch(e) { /* silent */ }
}