// ===== SAFEVOICE THEME MANAGER =====
// Handles dark/light mode toggle across all pages

(function () {
    // Apply saved theme immediately (before DOM ready) to prevent flash
    const saved = localStorage.getItem('sv-theme');
    if (saved === 'light') {
        document.documentElement.classList.add('light-mode-pending');
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    // Apply pending class to body
    if (document.documentElement.classList.contains('light-mode-pending')) {
        document.body.classList.add('light-mode');
        document.documentElement.classList.remove('light-mode-pending');
    }

    // Inject toggle button into every navbar
    injectThemeToggle();
    updateToggleUI();
});

function injectThemeToggle() {
    const navContainer = document.querySelector('.nav-container');
    if (!navContainer) return;

    // Don't add twice
    if (document.querySelector('.theme-toggle')) return;

    const btn = document.createElement('button');
    btn.className = 'theme-toggle';
    btn.id = 'themeToggle';
    btn.setAttribute('aria-label', 'Toggle theme');
    btn.innerHTML = `<i class="fas fa-moon"></i><span class="toggle-label">Dark</span>`;
    btn.onclick = toggleTheme;

    navContainer.appendChild(btn);
}

function toggleTheme() {
    const isLight = document.body.classList.toggle('light-mode');
    localStorage.setItem('sv-theme', isLight ? 'light' : 'dark');
    updateToggleUI();
}

function updateToggleUI() {
    const btn = document.querySelector('.theme-toggle');
    if (!btn) return;

    const isLight = document.body.classList.contains('light-mode');
    btn.innerHTML = isLight
        ? `<i class="fas fa-sun"></i><span class="toggle-label">Light</span>`
        : `<i class="fas fa-moon"></i><span class="toggle-label">Dark</span>`;
}