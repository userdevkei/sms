document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.auth-password-toggle');
    var passwordInput = document.getElementById('password');

    if (toggle && passwordInput) {
        toggle.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggle.querySelector('i').classList.toggle('bi-eye');
            toggle.querySelector('i').classList.toggle('bi-eye-slash');
            toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    }
});
