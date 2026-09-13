/**
 * Login form validation
 * 
 * Rules:
 * - Username must not be empty
 * - Password must not be empty
 */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('loginForm');
    var username = document.getElementById('username');
    var password = document.getElementById('password');

    var usernameError = document.getElementById('username-error');
    var passwordError = document.getElementById('password-error');

    function checkUsername() {
        if (username.value.trim() === '') {
            usernameError.textContent = 'Please enter your username.';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    function checkPassword() {
        if (password.value.trim() === '') {
            passwordError.textContent = 'Please enter your password.';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    // Check when user leaves the field
    username.addEventListener('blur', checkUsername);
    password.addEventListener('blur', checkPassword);

    // Check when the form is submitted
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