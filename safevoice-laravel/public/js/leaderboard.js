// ──────────────────────────────────────────────────────────────
// leaderboard.js  — Real data from /api/leaderboard
// ──────────────────────────────────────────────────────────────

let allData = [];   // full leaderboard array
let myRank  = null; // logged-in user এর rank info

// ── Badge helper ──────────────────────────────────────────────
function getBadge(rank) {
    if (rank === 1) return '🏆 Champion';
    if (rank === 2) return '🥈 Runner Up';
    if (rank === 3) return '🥉 Third Place';
    if (rank <= 10) return '⭐ Top Responder';
    return '🎖️ Active';
}

// ── Render full table ─────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('leaderboardBody');
    if (!tbody) return;

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#888; padding:30px;">
            No verified SOS responders yet. Be the first hero!
        </td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(p => {
        const rankClass = p.rank === 1 ? 'gold-bg'
                        : p.rank === 2 ? 'silver-bg'
                        : p.rank === 3 ? 'bronze-bg'
                        : p.is_you     ? 'you-bg'
                        : '';

        const youTag = p.is_you
            ? '<span class="you-tag" style="font-size:11px; background:#4f9eff; color:#fff; padding:2px 7px; border-radius:10px; margin-left:6px;">You</span>'
            : '';

        const rowStyle = p.is_you ? 'background: #4f9eff08;' : '';

        return `
            <tr style="${rowStyle}">
                <td><div class="rank-num ${rankClass}">${p.rank}</div></td>
                <td>
                    <div class="responder-name-cell">
                        <div class="mini-avatar"><i class="fas fa-user"></i></div>
                        <span>${p.name}${youTag}</span>
                    </div>
                </td>
                <td><strong>${p.responses}</strong></td>
                <td><span style="color:#4a5568; font-size:13px;">—</span></td>
                <td><span class="badge-pill">${p.badge}</span></td>
            </tr>
        `;
    }).join('');
}

// ── Render podium (top 3) ─────────────────────────────────────
function renderPodium(data) {
    const first  = data.find(p => p.rank === 1);
    const second = data.find(p => p.rank === 2);
    const third  = data.find(p => p.rank === 3);

    const setCard = (selector, person) => {
        const card = document.querySelector(selector);
        if (!card || !person) return;
        const nameEl  = card.querySelector('h3');
        const countEl = card.querySelector('p');
        if (nameEl)  nameEl.textContent = person.name;
        if (countEl) countEl.textContent = person.responses + ' responses';
    };

    setCard('.podium-card.gold',   first);
    setCard('.podium-card.silver', second);
    setCard('.podium-card.bronze', third);
}

// ── Render My Rank bar ────────────────────────────────────────
function renderMyRankBar(rankInfo) {
    const bar = document.querySelector('.my-rank-bar');
    if (!bar) return;

    if (!rankInfo) {
        bar.style.display = 'none';
        return;
    }

    bar.style.display = '';
    const nameEl  = bar.querySelector('.my-rank-name');
    const subEl   = bar.querySelector('.my-rank-sub');
    const rankNum = bar.querySelector('.my-rank-num');

    if (nameEl)  nameEl.innerHTML = rankInfo.name + ' <span class="you-tag">You</span>';
    if (subEl)   subEl.textContent = rankInfo.responses + ' verified responses';
    if (rankNum) rankNum.textContent = '#' + rankInfo.rank;
}

// ── Load leaderboard from API ─────────────────────────────────
async function loadLeaderboard() {
    try {
        const res  = await fetch('/api/leaderboard');
        const data = await res.json();

        if (!data.success) return;

        allData = data.leaderboard || [];
        myRank  = data.my_rank    || null;

        // Reset date dynamically দেখাও
        const resetBadge = document.querySelector('.lb-reset-badge');
        if (resetBadge && data.next_reset) {
            resetBadge.innerHTML = `<i class="fas fa-sync-alt"></i> Resets on ${data.next_reset}`;
        }

        renderTable(allData);
        renderPodium(allData);
        renderMyRankBar(myRank);

    } catch (err) {
        console.error('Leaderboard load error:', err);
    }
}

// ── NID / Birth Reg search ────────────────────────────────────
async function searchByIdNumber() {
    const input  = document.getElementById('idSearchInput');
    const result = document.getElementById('searchResult');
    if (!input || !result) return;

    const idNumber = input.value.trim();
    if (!idNumber) {
        result.innerHTML = `<p style="color:#e53e3e;">Please enter your NID or Birth Registration number.</p>`;
        return;
    }

    result.innerHTML = `<p style="color:#888;">Searching...</p>`;

    try {
        const res  = await fetch(`/api/leaderboard/search?id_number=${encodeURIComponent(idNumber)}`);
        const data = await res.json();

        if (!data.success) {
            result.innerHTML = `<p style="color:#e53e3e;">❌ ${data.message || 'Not found'}</p>`;
            return;
        }

        const r = data.result;
        const rankColor = r.rank === 1 ? 'color:#FFD700' : r.rank <= 3 ? 'color:#C0C0C0' : 'color:#4f9eff';

        result.innerHTML = `
            <div style="background:var(--card-bg,#1a1f35); border:1px solid #2d3561; border-radius:12px; padding:20px; margin-top:12px;">
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <div style="width:52px; height:52px; border-radius:50%; background:#2d3561; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-user" style="font-size:22px; color:#4f9eff;"></i>
                    </div>
                    <div>
                        <p style="font-size:18px; font-weight:700; margin:0;">${r.name}</p>
                        <p style="color:#888; margin:4px 0 0;">${r.badge}</p>
                    </div>
                    <div style="margin-left:auto; text-align:right;">
                        <p style="font-size:32px; font-weight:800; margin:0; ${rankColor}">#${r.rank}</p>
                        <p style="color:#888; margin:0; font-size:13px;">${r.responses} verified responses</p>
                    </div>
                </div>
            </div>`;
    } catch (err) {
        result.innerHTML = `<p style="color:#e53e3e;">Error searching. Try again.</p>`;
    }
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadLeaderboard();

    // Search button click
    const searchBtn = document.getElementById('idSearchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', searchByIdNumber);
    }

    // Enter key দিয়েও search করা যাবে
    const searchInput = document.getElementById('idSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') searchByIdNumber();
        });
    }
});