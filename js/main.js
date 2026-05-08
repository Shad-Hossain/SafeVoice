document.addEventListener('DOMContentLoaded', function () {

    // ===== TOGGLE PASSWORD =====
    document.querySelectorAll('.toggle-password').forEach(function (eye) {
        // location icon skip করবে
        if (eye.classList.contains('fa-crosshairs')) return;

        eye.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });

    // ===== LOCATION AUTO DETECT =====
    const locationInput = document.querySelector('input[placeholder="Auto-detect or enter manually"]');
    const locationIcon = document.querySelector('.fa-crosshairs');

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

    // Icon click এ location নেবে
    if (locationIcon && locationInput) {
        locationIcon.addEventListener('click', getLocation);
    }

    // ===== LOGIN =====
    const authBtn = document.querySelector('.btn-auth');

    if (authBtn && window.location.href.includes('login')) {
        authBtn.addEventListener('click', function () {
            const email = document.querySelector('input[type="email"]').value;
            const passwordInput = document.querySelector('input[placeholder="Enter your password"]');
            const password = passwordInput ? passwordInput.value : '';

            if (email === '' || password === '') {
                alert('Please fill in all fields!');
                return;
            }

            window.location.href = 'dashboard.html';
        });
    }

    // ===== REGISTER =====
   // ===== REGISTER =====
    if (authBtn && window.location.href.includes('register')) {
        authBtn.addEventListener('click', function () {
            const nameInput = document.querySelector('input[placeholder="Enter your name"]');
            const emailInput = document.querySelector('input[type="email"]');
            const password1 = document.getElementById('password1');
            const password2 = document.getElementById('password2');
            const terms = document.getElementById('termsCheckbox');

            const name = nameInput ? nameInput.value : '';
            const email = emailInput ? emailInput.value : '';
            const password = password1 ? password1.value : '';
            const confirm = password2 ? password2.value : '';
            const termsChecked = terms ? terms.checked : false;

            if (name === '' || email === '' || password === '') {
                alert('Please fill in all fields!');
                return;
            }

            if (password !== confirm) {
                alert('Passwords do not match!');
                return;
            }

            if (!termsChecked) {
                alert('Please accept the Terms & Conditions!');
                return;
            }

            window.location.href = 'dashboard.html';
        });
    }
});