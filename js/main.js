document.addEventListener('DOMContentLoaded', function () {


    // MOBILE NAVBAR — HAMBURGER MENU

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
            document.body.style.overflow =
                navLinks.classList.contains('open') ? 'hidden' : '';
        });

        if (navOverlay) {
            navOverlay.addEventListener('click', closeMenu);
        }

        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMenu);
        });
    }


    // DASHBOARD SIDEBAR TOGGLE (mobile)

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        if (window.innerWidth <= 768) {
            sidebarToggle.style.display = 'flex';
        }

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('sidebar-open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
    }

   
    // WINDOW RESIZE — reset mobile menus
   
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
            if (sidebar) sidebar.classList.remove('sidebar-open');
        }
        if (sidebarToggle) {
            sidebarToggle.style.display =
                window.innerWidth <= 768 ? 'flex' : 'none';
        }
    });


    // PASSWORD TOGGLE (eye icon)

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


    // GPS LOCATION DETECT

    const locationInput = document.querySelector('input[placeholder="Auto-detect or enter manually"]');
    const locationIcon  = document.querySelector('.fa-crosshairs');

    function getLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }
        locationInput.value = '';
        locationInput.placeholder = 'Detecting location...';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                    .then(res => res.json())
                    .then(data => {
                        locationInput.value = data.display_name || `${lat}, ${lng}`;
                        locationInput.placeholder = 'Auto-detect or enter manually';
                    })
                    .catch(() => {
                        locationInput.value = `${lat}, ${lng}`;
                        locationInput.placeholder = 'Auto-detect or enter manually';
                    });
            },
            function () {
                locationInput.placeholder = 'Could not detect. Enter manually';
            }
        );
    }

    if (locationIcon && locationInput) {
        locationIcon.addEventListener('click', getLocation);
    }

   //login
    const authBtn = document.querySelector('.btn-auth');

    if (authBtn && window.location.href.includes('login')) {
        authBtn.addEventListener('click', function () {
            const email    = document.querySelector('input[type="email"]').value;
            const pwInput  = document.querySelector('input[placeholder="Enter your password"]');
            const password = pwInput ? pwInput.value : '';

            if (!email || !password) {
                alert('Please fill in all fields!');
                return;
            }
            window.location.href = 'dashboard.html';
        });
    }

 
    if (authBtn && window.location.href.includes('register')) {
        authBtn.addEventListener('click', function () {
            const name      = document.querySelector('input[placeholder="Enter your name"]')?.value || '';
            const email     = document.querySelector('input[type="email"]')?.value || '';
            const password  = document.getElementById('password1')?.value || '';
            const confirm   = document.getElementById('password2')?.value || '';
            const terms     = document.getElementById('termsCheckbox');

            if (!name || !email || !password) {
                alert('Please fill in all fields!');
                return;
            }
            if (password !== confirm) {
                alert('Passwords do not match!');
                return;
            }
            if (terms && !terms.checked) {
                alert('Please accept the Terms & Conditions!');
                return;
            }
            window.location.href = 'dashboard.html';
        });
    }

}); 