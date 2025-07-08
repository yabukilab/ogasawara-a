document.addEventListener('DOMContentLoaded', () => {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const registerBtn = document.getElementById('registerBtn');
    const message = document.getElementById('mismatch-message');

    function checkMatch() {
        if (password.value && confirm.value && password.value === confirm.value) {
            registerBtn.disabled = false;
            message.style.display = 'none';
        } else {
            registerBtn.disabled = true;
            message.style.display = 'block';
        }
    }

    password.addEventListener('input', checkMatch);
    confirm.addEventListener('input', checkMatch);
});
