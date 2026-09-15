document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('userForm');

    if (!form) {
        return;
    }

    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const fullName = document.getElementById('full_name');
    const phoneSuffix = document.getElementById('phone_suffix');
    const address = document.getElementById('address');
    const joinDate = document.getElementById('join_date');
    const phoneHidden = document.getElementById('phone');

    const usernameError = document.getElementById('username-error');
    const passwordError = document.getElementById('password-error');
    const fullNameError = document.getElementById('full_name-error');
    const phoneError = document.getElementById('phone-error');
    const addressError = document.getElementById('address-error');
    const joinDateError = document.getElementById('join_date-error');

    const usernameRegex = /^[A-Za-z][A-Za-z0-9_]{2,19}$/;

    const passwordRegex =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/;

    const fullNameRegex = /^[A-Za-z\s]{2,50}$/;

    const phoneSuffixRegex = /^[0-9]{8}$/;


    function checkUsername() {

        if (!username) {
            return true;
        }

        const value = username.value.trim();

        if (value === '') {
            usernameError.textContent = 'Username is required.';
            return false;
        }

        if (!usernameRegex.test(value)) {
            usernameError.textContent =
                'Username must start with a letter and be 3-20 characters.';
            return false;
        }

        usernameError.textContent = '';
        return true;
    }


    function checkPassword() {

        if (!password) {
            return true;
        }

        const value = password.value;

        // Password is optional when editing an existing user
        const editUser =
            document.querySelector(
                'input[name="staff_id"], input[name="farmer_id"]'
            );

        if (editUser && value === '') {
            passwordError.textContent = '';
            return true;
        }

        if (value === '') {
            passwordError.textContent = 'Password is required.';
            return false;
        }

        if (!passwordRegex.test(value)) {
            passwordError.textContent =
                'Password must be 8+ characters with uppercase, lowercase, number and special character.';
            return false;
        }

        passwordError.textContent = '';
        return true;
    }


    function checkFullName() {

        if (!fullName) {
            return true;
        }

        const value = fullName.value.trim();

        if (value === '') {
            fullNameError.textContent = 'Full name is required.';
            return false;
        }

        if (!fullNameRegex.test(value)) {
            fullNameError.textContent =
                'Full name must contain 2-50 letters and spaces only.';
            return false;
        }

        fullNameError.textContent = '';
        return true;
    }


    function checkPhone() {

        if (!phoneSuffix) {
            return true;
        }

        const value = phoneSuffix.value.trim();

        if (value === '') {
            phoneError.textContent = 'Phone is required.';
            return false;
        }

        if (!phoneSuffixRegex.test(value)) {
            phoneError.textContent =
                'Phone must contain exactly 8 digits after the prefix.';
            return false;
        }

        phoneError.textContent = '';
        return true;
    }


    function checkAddress() {

        if (!address) {
            return true;
        }

        const value = address.value.trim();

        if (value === '') {
            addressError.textContent = 'Address is required.';
            return false;
        }

        if (value.length > 200) {
            addressError.textContent =
                'Address must not exceed 200 characters.';
            return false;
        }

        addressError.textContent = '';
        return true;
    }


    function checkJoinDate() {

        if (!joinDate) {
            return true;
        }

        const value = joinDate.value;

        if (value === '') {
            joinDateError.textContent = 'Please select a join date.';
            return false;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const selectedDate = new Date(value);
        selectedDate.setHours(0, 0, 0, 0);

        if (selectedDate > today) {
            joinDateError.textContent =
                'Join date cannot be in the future.';
            return false;
        }

        joinDateError.textContent = '';
        return true;
    }


    // Validate when leaving each field
    if (username) {
        username.addEventListener('blur', checkUsername);
    }

    if (password) {
        password.addEventListener('blur', checkPassword);
    }

    if (fullName) {
        fullName.addEventListener('blur', checkFullName);
    }

    if (phoneSuffix) {
        phoneSuffix.addEventListener('blur', checkPhone);
    }

    if (address) {
        address.addEventListener('blur', checkAddress);
    }

    if (joinDate) {
        joinDate.addEventListener('blur', checkJoinDate);
    }


    // Final validation before submitting
    form.addEventListener('submit', function (event) {

        const validUsername = checkUsername();
        const validPassword = checkPassword();
        const validFullName = checkFullName();
        const validPhone = checkPhone();
        const validAddress = checkAddress();
        const validJoinDate = checkJoinDate();


        // Combine phone prefix and suffix
        const phonePrefix = document.getElementById('phone_prefix');

        if (phonePrefix && phoneSuffix && phoneHidden) {
            phoneHidden.value =
                phonePrefix.value + phoneSuffix.value;
        }


        if (
            !validUsername ||
            !validPassword ||
            !validFullName ||
            !validPhone ||
            !validAddress ||
            !validJoinDate
        ) {
            event.preventDefault();

            if (!validUsername && username) {
                username.focus();
            } else if (!validPassword && password) {
                password.focus();
            } else if (!validFullName && fullName) {
                fullName.focus();
            } else if (!validPhone && phoneSuffix) {
                phoneSuffix.focus();
            } else if (!validAddress && address) {
                address.focus();
            } else if (!validJoinDate && joinDate) {
                joinDate.focus();
            }
        }
    });

});