
const togglePassword = document.querySelector('#togglePassword');
const passwordInput = document.querySelector('#password');

togglePassword.addEventListener('click', function () {
    const inputType = passwordInput.getAttribute('type') === 'password'
        ? 'text'
        : 'password';

    passwordInput.setAttribute('type', inputType);

    this.textContent = inputType === 'password' ? '👁️' : '🔒';
});
