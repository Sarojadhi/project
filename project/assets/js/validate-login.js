/**
 * Login form validation
 * 
 * Rules (login should be permissive — the server does the real check):
 * - Username must not be empty
 * - Password must not be empty
 * 
 * Note: Password complexity is NOT enforced here.
 * Complexity rules belong on the "create account" forms, not on login.
 * The server verifies credentials with password_verify().
 */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('loginForm');
    var username = document.getElementById('username');
    var password = document.getElementById('password');

    var usernameError = document.getElementById('username-error');
    var passwordError = document.getElementById('password-error');

    // ----- Username: only check that it's not empty -----
    function checkUsername() {
        if (username.value.trim() === '') {
            usernameError.textContent = 'Please enter your username.';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    // ----- Password: only check that it's not empty -----
    function checkPassword() {
        if (password.value === '') {
            passwordError.textContent = 'Please enter your password.';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    // Attach events
    username.addEventListener('blur', checkUsername);
    password.addEventListener('blur', checkPassword);

    // Clear error as soon as the user starts typing again
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

    // Submit handler
    form.addEventListener('submit', function (e) {
        var okUser = checkUsername();
        var okPass = checkPassword();

        if (!okUser || !okPass) {
            e.preventDefault();
            if (!okUser) {
                username.focus();
            } else if (!okPass) {
                password.focus();
            }
            return false;
        }
        return true;
    });

});