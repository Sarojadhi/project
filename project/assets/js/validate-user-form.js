document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('userForm');

    if (!form) {
        return;
    }

    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const fullName = document.getElementById('full_name');

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

    // Check whether this is Add or Edit
    const actionInput = form.querySelector('input[name="action"]');
    const action = actionInput ? actionInput.value : 'add';

    const isEdit = action === 'edit';

    // Password eye button
    const togglePassword =
        document.getElementById('togglePassword');

    if (togglePassword && password) {

        togglePassword.style.display = 'none';

        password.addEventListener('input', function () {

            if (password.value.length > 0) {

                togglePassword.style.display = 'block';

            } else {

                togglePassword.style.display = 'none';

                password.type = 'password';

                togglePassword.textContent = '👁';

                togglePassword.setAttribute(
                    'aria-label',
                    'Show password'
                );
            }

        });

        togglePassword.addEventListener('click', function (event) {

            event.preventDefault();

            if (password.type === 'password') {

                password.type = 'text';

                togglePassword.textContent = '🙈';

                togglePassword.setAttribute(
                    'aria-label',
                    'Hide password'
                );

            } else {

                password.type = 'password';

                togglePassword.textContent = '👁';

                togglePassword.setAttribute(
                    'aria-label',
                    'Show password'
                );
            }

        });

    }

    // Hide success/error message after 3 seconds
    const message = document.getElementById('message');

    if (message) {

        setTimeout(function () {

            message.style.opacity = '0';

            message.style.transition =
                'opacity 0.3s ease';

            setTimeout(function () {

                message.remove();

            }, 300);

        }, 3000);

    }

    // Check username
    function checkUsername() {

        if (!username || !usernameError) {
            return true;
        }

        const value = username.value.trim();

        const usernameRegex = /^[A-Za-z ]+$/;

        if (value === '') {

            usernameError.textContent =
                'Please enter a username.';

            return false;
        }

        if (value.length < 3) {

            usernameError.textContent =
                'Username must be at least 3 characters.';

            return false;
        }

        if (value.length > 20) {

            usernameError.textContent =
                'Username must be 20 characters or less.';

            return false;
        }

        if (!usernameRegex.test(value)) {

            usernameError.textContent =
                'Username can contain letters and spaces only.';

            return false;
        }

        usernameError.textContent = '';

        return true;
    }

    // Check password
    function checkPassword() {

        if (!password || !passwordError) {
            return true;
        }

        const value = password.value;

        const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/;

        // Password is optional during Edit
        if (isEdit && value === '') {

            passwordError.textContent = '';

            return true;
        }

        // Password is required during Add
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

        if (!fullName || !fullNameError) {
            return true;
        }

        const value = fullName.value.trim();

        const nameRegex = /^[A-Za-z ]+$/;

        if (value === '') {

            fullNameError.textContent =
                'Please enter full name.';

            return false;
        }

        if (value.length < 3) {

            fullNameError.textContent =
                'Full name must be at least 3 characters.';

            return false;
        }

        if (value.length > 100) {

            fullNameError.textContent =
                'Full name must be 100 characters or less.';

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

        if (
            !phoneNumber ||
            !phone ||
            !phoneError
        ) {
            return true;
        }

        const number = phoneNumber.value.trim();

        if (number === '') {

            phoneError.textContent =
                'Please enter a phone number.';

            return false;
        }

        if (!/^\d+$/.test(number)) {

            phoneError.textContent =
                'Phone number can contain digits only.';

            return false;
        }

        // Check 97 or 98 first
        if (
            number.length === 1 ||
            (number.length >= 2 && !/^(97|98)/.test(number))
        ) {

            phoneError.textContent =
                'Phone number must start with 97 or 98.';

            return false;
        }

        // Check total length after prefix
        if (number.length !== 10) {

            phoneError.textContent =
                'Phone number must contain exactly 10 digits.';

            return false;
        }

        phone.value = number;

        phoneError.textContent = '';

        return true;
    }

    // Check address
    function checkAddress() {

        if (!address || !addressError) {
            return true;
        }

        const value = address.value.trim();

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

        // Allow letters, numbers, spaces, hyphen and comma
        if (!/^[A-Za-z0-9 ,\-]+$/.test(value)) {

            addressError.textContent =
                'Address can contain letters, numbers, spaces, - and comma only.';

            return false;
        }

        // Address must contain at least one letter
        if (!/[A-Za-z]/.test(value)) {

            addressError.textContent =
                'Address must contain at least one letter.';

            return false;
        }

        // Allow only one comma
        const commaCount =
            (value.match(/,/g) || []).length;

        if (commaCount > 1) {

            addressError.textContent =
                'Address can contain only one comma.';

            return false;
        }

        addressError.textContent = '';

        return true;
    }

    // Check join date
    function checkJoinDate() {

        if (isEdit) {

            if (joinDateError) {
                joinDateError.textContent = '';
            }

            return true;
        }

        if (!joinDate || !joinDateError) {
            return true;
        }

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

    // Allow only 10 phone digits
    if (phoneNumber) {

        phoneNumber.addEventListener('input', function () {

            phoneNumber.value =
                phoneNumber.value
                    .replace(/\D/g, '')
                    .slice(0, 10);

            checkPhone();

        });

    }

    // Username validation
    if (username) {

        username.addEventListener(
            'blur',
            checkUsername
        );

        username.addEventListener(
            'input',
            checkUsername
        );
    }

    // Password validation
    if (password) {

        password.addEventListener(
            'blur',
            checkPassword
        );

        password.addEventListener(
            'input',
            checkPassword
        );
    }

    // Full name validation
    if (fullName) {

        fullName.addEventListener(
            'blur',
            checkFullName
        );

        fullName.addEventListener(
            'input',
            checkFullName
        );
    }

    // Phone validation
    if (phoneNumber) {

        phoneNumber.addEventListener(
            'blur',
            checkPhone
        );

    }

    // Address validation
    if (address) {

        address.addEventListener(
            'blur',
            checkAddress
        );

        address.addEventListener(
            'input',
            checkAddress
        );
    }

    // Join date validation
    if (joinDate) {

        joinDate.addEventListener(
            'change',
            checkJoinDate
        );
    }

    // Validate form before submit
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