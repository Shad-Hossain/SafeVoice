// FAKE DATA 
const leaderboardData = [
    { rank: 1, name: 'Rakib Hassan',   responses: 42, streak: 7,  badge: '🏆 Champion'   },
    { rank: 2, name: 'Shefa Akter',    responses: 38, streak: 5,  badge: '🥈 Runner Up'  },
    { rank: 3, name: 'Nadia Islam',    responses: 31, streak: 4,  badge: '🥉 Third Place' },
    { rank: 4, name: 'Arif Hossain',   responses: 27, streak: 3,  badge: '⭐ Top Responder' },
    { rank: 5, name: 'Tania Begum',    responses: 24, streak: 2,  badge: '⭐ Top Responder' },
    { rank: 6, name: 'Jahid Khan',     responses: 19, streak: 1,  badge: '🎖️ Active'     },
    { rank: 7, name: 'Shad Hossain',   responses: 12, streak: 2,  badge: '🎖️ Active', isYou: true },
    { rank: 8, name: 'Mim Akhter',     responses: 10, streak: 0,  badge: '🎖️ Active'     },
    { rank: 9, name: 'Sumon Roy',      responses: 8,  streak: 1,  badge: '🎖️ Active'     },
    { rank: 10, name: 'Farida Khanam', responses: 5,  streak: 0,  badge: '🎖️ Active'    },
];

// RENDER TABLE 
function renderLeaderboard() {
    const tbody = document.getElementById('leaderboardBody');
    if (!tbody) return;

    tbody.innerHTML = leaderboardData.map(p => {
        const rankClass = p.rank === 1 ? 'gold-bg'
                        : p.rank === 2 ? 'silver-bg'
                        : p.rank === 3 ? 'bronze-bg'
                        : p.isYou     ? 'you-bg'
                        : '';

        const youTag = p.isYou
            ? '<span class="you-tag" style="font-size:11px; background:#4f9eff; color:#fff; padding:2px 7px; border-radius:10px; margin-left:6px;">You</span>'
            : '';

        const streakHTML = p.streak > 0
            ? `<span class="streak-badge">🔥 ${p.streak} day</span>`
            : `<span style="color:#4a5568; font-size:13px;">—</span>`;

        const rowStyle = p.isYou ? 'background: #4f9eff08;' : '';

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
                <td>${streakHTML}</td>
                <td><span class="badge-pill">${p.badge}</span></td>
            </tr>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', renderLeaderboard);