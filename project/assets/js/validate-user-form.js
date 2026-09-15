document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('userForm');

    if (!form) {
        return;
    }

    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const fullName = document.getElementById('full_name');
    const phonePrefix = document.getElementById('phone_prefix');
    const phoneNumber = document.getElementById('phone_number');
    const phone = document.getElementById('phone');
    const address = document.getElementById('address');
    const joinDate = document.getElementById('join_date');

    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');
    const fullNameError = document.getElementById('full-name-error');
    const phoneError = document.getElementById('phone-error');
    const addressError = document.getElementById('address-error');
    const joinDateError = document.getElementById('join-date-error');

    // Password eye button

    const togglePassword = document.getElementById('togglePassword');

    if (togglePassword && password) {

        togglePassword.style.display = 'none';

        password.addEventListener('input', function () {

            if (password.value.length > 0) {
                togglePassword.style.display = 'block';
            } else {
                togglePassword.style.display = 'none';
                password.type = 'password';
                togglePassword.textContent = '👁';
            }

        });

        togglePassword.addEventListener('click', function () {

            if (password.type === 'password') {

                password.type = 'text';
                togglePassword.textContent = '🙈';

            } else {

                password.type = 'password';
                togglePassword.textContent = '👁';

            }

        });

    }

    // Hide success message after 3 seconds

    const message = document.getElementById('message');

    if (message) {

        setTimeout(function () {
            message.remove();
        }, 3000);

    }

    // Check username

    function checkUsername() {

        if (!username) {
            return true;
        }

        const value = username.value.trim();

        const usernameRegex =
            /^[A-Za-z][A-Za-z0-9_]{2,19}$/;

        if (value === '') {

            usernameError.textContent =
                'Please enter a username.';

            return false;
        }

        if (!usernameRegex.test(value)) {

            usernameError.textContent =
                'Username must start with a letter and contain 3 to 20 characters.';

            return false;
        }

        usernameError.textContent = '';

        return true;
    }

    // Check password

    function checkPassword() {

        if (!password) {
            return true;
        }

        const value = password.value;

        const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/;

        if (value === '') {

            passwordError.textContent =
                'Please enter a password.';

            return false;
        }

        if (!passwordRegex.test(value)) {

            passwordError.textContent =
                'Password must be at least 8 characters with uppercase, lowercase, number and symbol.';

            return false;
        }

        passwordError.textContent = '';

        return true;
    }

    // Check full name

    function checkFullName() {

        const value = fullName.value.trim();

        const nameRegex = /^[A-Za-z ]+$/;

        if (value === '') {

            fullNameError.textContent =
                'Please enter full name.';

            return false;
        }

        if (value.length < 2) {

            fullNameError.textContent =
                'Full name must be at least 2 characters.';

            return false;
        }

        if (!nameRegex.test(value)) {

            fullNameError.textContent =
                'Full name can contain letters and spaces only.';

            return false;
        }

        fullNameError.textContent = '';

        return true;
    }

    // Check phone

    function checkPhone() {

        const prefix = phonePrefix.value;
        const number = phoneNumber.value;

        if (prefix !== '97' && prefix !== '98') {

            phoneError.textContent =
                'Please select 97 or 98.';

            return false;
        }

        if (number === '') {

            phoneError.textContent =
                'Please enter the remaining 8 digits.';

            return false;
        }

        if (!/^\d{8}$/.test(number)) {

            phoneError.textContent =
                'Phone number must contain exactly 8 digits.';

            return false;
        }

        phone.value = prefix + number;

        phoneError.textContent = '';

        return true;
    }

    // Check address

    function checkAddress() {

        const value = address.value.trim();

        const addressRegex = /^[A-Za-z ]+$/;

        if (value === '') {

            addressError.textContent =
                'Please enter an address.';

            return false;
        }

        if (value.length < 3) {

            addressError.textContent =
                'Address must be at least 3 characters.';

            return false;
        }

        if (value.length > 200) {

            addressError.textContent =
                'Address must be 200 characters or less.';

            return false;
        }

        if (!addressRegex.test(value)) {

            addressError.textContent =
                'Address can contain letters and spaces only.';

            return false;
        }

        addressError.textContent = '';

        return true;
    }

    // Check join date

    function checkJoinDate() {

        const value = joinDate.value;

        if (value === '') {

            joinDateError.textContent =
                'Please select today\'s date.';

            return false;
        }

        const today = new Date();

        const year = today.getFullYear();

        const month =
            String(today.getMonth() + 1).padStart(2, '0');

        const day =
            String(today.getDate()).padStart(2, '0');

        const todayDate =
            year + '-' + month + '-' + day;

        if (value !== todayDate) {

            joinDateError.textContent =
                'Join date must be today.';

            return false;
        }

        joinDateError.textContent = '';

        return true;
    }

    // Allow only 8 phone digits

    phoneNumber.addEventListener('input', function () {

        phoneNumber.value =
            phoneNumber.value
                .replace(/\D/g, '')
                .slice(0, 8);

        checkPhone();
    });

    // Validate username

    if (username) {
        username.addEventListener('blur', checkUsername);
    }

    // Validate password

    if (password) {
        password.addEventListener('blur', checkPassword);
    }

    // Validate full name

    fullName.addEventListener('blur', checkFullName);

    // Validate phone

    phonePrefix.addEventListener('change', checkPhone);

    // Validate address

    address.addEventListener('blur', checkAddress);

    // Validate join date

    joinDate.addEventListener('change', checkJoinDate);

    // Validate form

    form.addEventListener('submit', function (event) {

        const validUsername = checkUsername();
        const validPassword = checkPassword();
        const validFullName = checkFullName();
        const validPhone = checkPhone();
        const validAddress = checkAddress();
        const validJoinDate = checkJoinDate();

        if (
            !validUsername ||
            !validPassword ||
            !validFullName ||
            !validPhone ||
            !validAddress ||
            !validJoinDate
        ) {

            event.preventDefault();
        }

    });

});