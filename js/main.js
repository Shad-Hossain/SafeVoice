document.addEventListener('DOMContentLoaded', function () {

    // ── MOBILE NAVBAR HAMBURGER ──
    const hamburger  = document.getElementById('hamburger');
    const navLinks   = document.querySelector('.nav-links');
    const navOverlay = document.getElementById('navOverlay');

    function closeMenu() {
        if (hamburger)  hamburger.classList.remove('active');
        if (navLinks)   navLinks.classList.remove('open');
        if (navOverlay) navOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('open');
            if (navOverlay) navOverlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('open') ? 'hidden' : '';
        });
        if (navOverlay) navOverlay.addEventListener('click', closeMenu);
        navLinks.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    }

    // ── USER DROPDOWN MENU ──
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        if (!document.querySelector('.user-dropdown')) {
            const dropdown = document.createElement('div');
            dropdown.className = 'user-dropdown';
            dropdown.innerHTML = `
                <a href="dashboard.html"><i class="fas fa-home"></i> Dashboard</a>
                <a href="complaint.html"><i class="fas fa-file-alt"></i> New Complaint</a>
                <a href="complaint-track.html"><i class="fas fa-search"></i> Track Complaint</a>
                <a href="legal-help.html"><i class="fas fa-gavel"></i> Legal Help</a>
                <a href="leaderboard.html"><i class="fas fa-trophy"></i> Leaderboard</a>
                <div class="dropdown-divider"></div>
                <a href="login.html" class="dropdown-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            `;
            userMenu.appendChild(dropdown);
        }
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            if (userMenu) userMenu.classList.remove('open');
        });
    }

    // ── SIDEBAR TOGGLE (mobile) ──
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && !document.getElementById('sidebarToggle')) {
        const btn = document.createElement('button');
        btn.id = 'sidebarToggle';
        btn.className = 'sidebar-toggle-btn';
        btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
        btn.innerHTML = '<i class="fas fa-bars"></i> Menu';
        const layout = document.querySelector('.dashboard-layout');
        if (layout) layout.insertBefore(btn, layout.firstChild);
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            btn.querySelector('i').className = sidebar.classList.contains('sidebar-open') ? 'fas fa-times' : 'fas fa-bars';
        });
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
            if (sidebar) sidebar.classList.remove('sidebar-open');
        }
        const st = document.getElementById('sidebarToggle');
        if (st) st.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
    });

    // ── PASSWORD TOGGLE ──
    document.querySelectorAll('.toggle-password').forEach(function (eye) {
        if (eye.classList.contains('fa-crosshairs')) return;
        eye.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }
        });
    });

    // ── GPS LOCATION ──
    const locationInput = document.querySelector('input[placeholder="Auto-detect or enter manually"]');
    const locationIcon  = document.querySelector('.fa-crosshairs');
    if (locationIcon && locationInput) {
        locationIcon.addEventListener('click', function () {
            if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
            locationInput.placeholder = 'Detecting location...';
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
                        .then(r => r.json())
                        .then(data => {
                            locationInput.value = data.display_name || `${pos.coords.latitude}, ${pos.coords.longitude}`;
                            locationInput.placeholder = 'Auto-detect or enter manually';
                        })
                        .catch(() => {
                            locationInput.value = `${pos.coords.latitude}, ${pos.coords.longitude}`;
                            locationInput.placeholder = 'Auto-detect or enter manually';
                        });
                },
                function () { locationInput.placeholder = 'Could not detect. Enter manually'; }
            );
        });
    }

    // ── LOGIN ──
    const authBtn = document.querySelector('.btn-auth');
    const currentPage = window.location.pathname;
    if (authBtn && currentPage.includes('login') && !currentPage.includes('admin')) {
        authBtn.addEventListener('click', function () {
            const email   = document.querySelector('input[type="email"]')?.value;
            const pwInput = document.querySelector('input[placeholder="Enter your password"]');
            if (!email || !pwInput?.value) { alert('Please fill in all fields!'); return; }
            window.location.href = 'dashboard.html';
        });
    }
    if (authBtn && currentPage.includes('register')) {
        authBtn.addEventListener('click', function () {
            const name    = document.querySelector('input[placeholder="Enter your name"]')?.value || '';
            const email   = document.querySelector('input[type="email"]')?.value || '';
            const password= document.getElementById('password1')?.value || '';
            const confirm = document.getElementById('password2')?.value || '';
            const terms   = document.getElementById('termsCheckbox');
            if (!name || !email || !password) { alert('Please fill in all fields!'); return; }
            if (password !== confirm) { alert('Passwords do not match!'); return; }
            if (terms && !terms.checked) { alert('Please accept the Terms & Conditions!'); return; }
            window.location.href = 'dashboard.html';
        });
    }

    injectDropdownStyles();
});

function injectDropdownStyles() {
    if (document.getElementById('sv-dropdown-styles')) return;
    const style = document.createElement('style');
    style.id = 'sv-dropdown-styles';
    style.textContent = `
        .user-menu { position: relative; cursor: pointer; user-select: none; }
        .user-dropdown {
            display: none; position: absolute; top: calc(100% + 12px); right: 0;
            background: #0d1526; border: 1px solid #1e2d4a; border-radius: 12px;
            padding: 8px; min-width: 210px; z-index: 2000;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .user-menu.open .user-dropdown { display: block; }
        .user-dropdown a {
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            color: #a0b4cc; font-size: 14px; border-radius: 8px; transition: all 0.2s;
        }
        .user-dropdown a:hover { background: #1e2d4a; color: #fff; }
        .user-dropdown a i { color: #4f9eff; width: 16px; text-align: center; }
        .dropdown-divider { height: 1px; background: #1e2d4a; margin: 6px 0; }
        .dropdown-logout { color: #e63946 !important; }
        .dropdown-logout i { color: #e63946 !important; }
        .fa-chevron-down { font-size: 11px; transition: transform 0.3s; margin-left: 4px; }
        .user-menu.open .fa-chevron-down { transform: rotate(180deg); }
        .sidebar-toggle-btn {
            display: flex; align-items: center; gap: 10px; width: 100%;
            background: #0d1526; border: none; border-bottom: 1px solid #1e2d4a;
            color: #a0b4cc; padding: 14px 20px; font-size: 15px; font-weight: 600; cursor: pointer;
        }
        .sidebar-toggle-btn i { color: #4f9eff; }
    `;
    document.head.appendChild(style);
}
