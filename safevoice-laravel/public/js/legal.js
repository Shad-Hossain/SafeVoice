let selectedBudget = '';

// ── BUDGET SELECTION ──
function selectBudget(el, value) {
    document.querySelectorAll('.budget-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    selectedBudget = value;
}

// ── SUBMIT ──
function submitLegalRequest() {
    const issueType = document.getElementById('issueType').value;
    const issueDesc = document.getElementById('issueDesc').value.trim();

    if (!issueType) {
        alert('Please select a legal issue type.');
        return;
    }

    if (issueDesc.length < 20) {
        alert('Please describe your issue in at least 20 characters.');
        return;
    }

    if (!selectedBudget) {
        alert('Please select a budget range.');
        return;
    }

    // Generate request ID
    const id = 'LR-2026-' + Math.floor(1000 + Math.random() * 9000);
    document.getElementById('requestId').textContent = id;
    document.getElementById('successModal').classList.add('active');
}

// ── CLOSE MODAL ──
function closeModal() {
    document.getElementById('successModal').classList.remove('active');
}

// Close on overlay click
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('successModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });
});