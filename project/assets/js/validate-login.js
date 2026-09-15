
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('loginForm');
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');

    // Stop if the required elements are missing
    if (
        !form ||
        !username ||
        !password ||
        !usernameError ||
        !passwordError
    ) {
        return;
    }

    const usernameRegex = /^[A-Za-z][A-Za-z0-9_]{2,}$/;

    function checkUsername() {
        const value = username.value.trim();

        if (value === '') {
            usernameError.textContent = 'Please enter your username.';
            return false;
        }

        if (!usernameRegex.test(value)) {
            usernameError.textContent =
                'Username must start with a letter and contain at least 3 characters.';
            return false;
        }

        usernameError.textContent = '';
        return true;
    }

    function checkPassword() {
        const value = password.value;

        if (value === '') {
            passwordError.textContent = 'Please enter your password.';
            return false;
        }

        passwordError.textContent = '';
        return true;
    }

    // Check when user leaves the field
    username.addEventListener('blur', checkUsername);
    password.addEventListener('blur', checkPassword);

    // Check again while fixing an error
    username.addEventListener('input', function () {
        if (usernameError.textContent !== '') {
            checkUsername();
        }
    });

    password.addEventListener('input', function () {
        if (passwordError.textContent !== '') {
            checkPassword();
        }
    });

    // Final validation before submitting
    form.addEventListener('submit', function (event) {

        const validUsername = checkUsername();
        const validPassword = checkPassword();

        if (!validUsername || !validPassword) {
            event.preventDefault();

            if (!validUsername) {
                username.focus();
            } else {
                password.focus();
            }
        }
    });

});