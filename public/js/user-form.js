document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => avatarPreview.src = e.target.result;
            reader.readAsDataURL(file);
        });
    }

    const form = document.getElementById('userForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const password = document.getElementById('password');
            const confirmField = document.querySelector('[name="password_confirmation"]');
            if (password && password.value && password.value !== confirmField.value) {
                e.preventDefault();
                alert('Passwords do not match.');
                confirmField.focus();
            }
        });
    }
});
