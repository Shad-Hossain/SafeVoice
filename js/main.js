document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.toggle-password').forEach(function (eye) {
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


    const loginBtn = document.querySelector('.btn-auth');

    if (loginBtn && window.location.href.includes('login')) {
        loginBtn.addEventListener('click', function () {
            const email = document.querySelector('input[type="email"]').value;
            const password = document.querySelector('input[type="password"], input[type="text"]').value;

            if (email === '' || password === '') {
                alert('Please fill in all fields!');
                return;
            }

            window.location.href = 'dashboard.html';
        });
    }

    if (loginBtn && window.location.href.includes('register')) {
        loginBtn.addEventListener('click', function () {
            const name = document.querySelector('input[type="text"]').value;
            const email = document.querySelector('input[type="email"]').value;

            // password inputs গুলো যেকোনো type এ থাকুক ধরবে
            const passwordInputs = document.querySelectorAll('.input-wrapper input[type="password"], .input-wrapper input[type="text"]');
            const password = passwordInputs[0] ? passwordInputs[0].value : '';
            const confirm = passwordInputs[1] ? passwordInputs[1].value : '';

            const terms = document.getElementById('termsCheckbox');
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