// ── FAKE DATA ──
let complaints = [
    { id: 'SV-2026-1001', type: 'Harassment',     location: 'Mirpur, Dhaka',    date: 'May 01, 2026', reporter: 'Shad Hossain',  anonymous: false, status: 'resolved', officer: 'Inspector Karim',   desc: 'Harassed at workplace by senior colleague repeatedly over 2 weeks.',          adminMsg: 'Case resolved. Accused has been warned officially.' },
    { id: 'SV-2026-2002', type: 'Fare Overcharge', location: 'Motijheel, Dhaka', date: 'May 03, 2026', reporter: 'Anonymous',      anonymous: true,  status: 'pending',  officer: 'Not Assigned',      desc: 'CNG driver charged triple fare from Motijheel to Banani.',                     adminMsg: '' },
    { id: 'SV-2026-3003', type: 'Corruption',      location: 'Gulshan, Dhaka',   date: 'May 05, 2026', reporter: 'Tania Begum',    anonymous: false, status: 'review',   officer: 'Inspector Rahman',  desc: 'Government officer demanded bribe for trade license renewal.',                 adminMsg: 'Under investigation. Initial evidence collected.' },
    { id: 'SV-2026-4004', type: 'Crime',           location: 'Rampura, Dhaka',   date: 'May 06, 2026', reporter: 'Anonymous',      anonymous: true,  status: 'pending',  officer: 'Not Assigned',      desc: 'Mobile phone snatching incident at night near Rampura bridge.',               adminMsg: '' },
    { id: 'SV-2026-5005', type: 'Harassment',      location: 'Dhanmondi, Dhaka', date: 'May 07, 2026', reporter: 'Nadia Islam',    anonymous: false, status: 'review',   officer: 'Inspector Sadia',   desc: 'Street harassment while walking to university campus in morning.',             adminMsg: 'Officer assigned. Reviewing CCTV footage.' },
    { id: 'SV-2026-6006', type: 'Fare Overcharge', location: 'Uttara, Dhaka',    date: 'May 08, 2026', reporter: 'Arif Hossain',   anonymous: false, status: 'resolved', officer: 'Inspector Faruk',   desc: 'Rickshaw puller demanded excessive fare and became aggressive when disputed.', adminMsg: 'Resolved. Complaint noted in local authority records.' },
    { id: 'SV-2026-7007', type: 'Other',           location: 'Badda, Dhaka',     date: 'May 09, 2026', reporter: 'Anonymous',      anonymous: true,  status: 'pending',  officer: 'Not Assigned',      desc: 'Noise pollution from nearby construction site at midnight regularly.',         adminMsg: '' },
];

let activeId = null;

// INIT
document.addEventListener('DOMContentLoaded', () => {
    renderTable(complaints);
    updateCounts(complaints);
});

// RENDER TABLE 
function renderTable(data) {
    const tbody = document.getElementById('complaintsBody');
    const empty = document.getElementById('emptyState');

    if (data.length === 0) {
        tbody.innerHTML = '';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';

    tbody.innerHTML = data.map(c => {
        const reporterHTML = c.anonymous
            ? `<div class="reporter-cell"><div class="anon-icon"><i class="fas fa-user-secret"></i></div><span>Anonymous</span></div>`
            : `<div class="reporter-cell"><div class="anon-icon"><i class="fas fa-user"></i></div><span>${c.reporter}</span></div>`;

        const statusLabel = c.status === 'resolved' ? 'Resolved'
                          : c.status === 'review'   ? 'Under Review'
                          : 'Pending';

        return `
            <tr>
                <td><strong style="color:#4f9eff">${c.id}</strong></td>
                <td>${c.type}</td>
                <td>${c.location}</td>
                <td>${c.date}</td>
                <td>${reporterHTML}</td>
                <td><span class="status ${c.status}">${statusLabel}</span></td>
                <td>
                    <button class="btn-view" onclick="openModal('${c.id}')">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// COUNTS 
function updateCounts(data) {
    document.getElementById('totalCount').textContent   = data.length;
    document.getElementById('pendingCount').textContent = data.filter(c => c.status === 'pending').length;
    document.getElementById('reviewCount').textContent  = data.filter(c => c.status === 'review').length;
    document.getElementById('resolvedCount').textContent= data.filter(c => c.status === 'resolved').length;
}

// FILTERS
function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const type   = document.getElementById('typeFilter').value;

    const filtered = complaints.filter(c => {
        const matchSearch = !search
            || c.id.toLowerCase().includes(search)
            || c.type.toLowerCase().includes(search)
            || c.location.toLowerCase().includes(search)
            || c.reporter.toLowerCase().includes(search);

        const matchStatus = !status || c.status === status;
        const matchType   = !type   || c.type === type;

        return matchSearch && matchStatus && matchType;
    });

    renderTable(filtered);
}

function resetFilters() {
    document.getElementById('searchInput').value  = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value   = '';
    renderTable(complaints);
    updateCounts(complaints);
}

// MODAL OPEN
function openModal(id) {
    const c = complaints.find(x => x.id === id);
    if (!c) return;
    activeId = id;

    document.getElementById('dId').textContent       = c.id;
    document.getElementById('dType').textContent     = c.type;
    document.getElementById('dDate').textContent     = c.date;
    document.getElementById('dLocation').textContent = c.location;
    document.getElementById('dReporter').textContent = c.anonymous ? '🔒 Hidden (Anonymous)' : c.reporter;
    document.getElementById('dAnon').textContent     = c.anonymous ? 'Yes' : 'No';
    document.getElementById('dDesc').textContent     = c.desc;

    document.getElementById('statusUpdate').value  = c.status;
    document.getElementById('officerAssign').value = c.officer;
    document.getElementById('adminMsgInput').value = c.adminMsg || '';

    document.getElementById('detailModal').classList.add('active');
}

// SAVE CHANGES 
function saveChanges() {
    const c = complaints.find(x => x.id === activeId);
    if (!c) return;

    c.status   = document.getElementById('statusUpdate').value;
    c.officer  = document.getElementById('officerAssign').value;
    c.adminMsg = document.getElementById('adminMsgInput').value.trim();

    closeModal();
    applyFilters();
    updateCounts(complaints);
    showToast();
}

// MODAL CLOSE 
function closeModal() {
    document.getElementById('detailModal').classList.remove('active');
    activeId = null;
}

// Close on overlay click
document.getElementById('detailModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});

// TOAST
function showToast() {
    const t = document.getElementById('toast');
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}