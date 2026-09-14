/**
 * Staff / Farmer form validation
 * 
 * Used by both admin/manage-staff.php and admin/manage-farmer.php.
 * 
 * The same form is reused for ADD and EDIT modes:
 *   - In ADD mode, all fields are present (including username + password).
 *   - In EDIT mode, username and password inputs are hidden.
 *     Leaving password blank means "keep current password".
 * 
 * Rules:
 *   - Username: required (add only), 3-30 chars, letters / digits / underscore
 *   - Password: required (add only), min 6 chars
 *   - Full Name: required, letters and spaces only, 2-50 chars
 *   - Phone: optional, if given must be 10 digits starting 6-9
 *   - Address: optional, max 200 chars
 *   - Join Date: required, not in the future
 * 
 * This script is safe to include on edit pages where some fields don't exist.
 */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('userForm');
    if (!form) return;   // safety: no form on this page → exit quietly

    var username  = document.getElementById('username');
    var password  = document.getElementById('password');
    var fullName  = document.getElementById('full_name');
    var phone     = document.getElementById('phone');
    var address   = document.getElementById('address');
    var joinDate  = document.getElementById('join_date');

    var usernameError = document.getElementById('username-error');
    var passwordError = document.getElementById('password-error');
    var fullNameError = document.getElementById('full_name-error');
    var phoneError    = document.getElementById('phone-error');
    var addressError  = document.getElementById('address-error');
    var joinDateError = document.getElementById('join_date-error');

    // ---- Regex patterns (light, for a college project) ----
    var usernameRegex = /^[a-zA-Z0-9_]{3,30}$/;        // letters, digits, underscore
    var fullNameRegex = /^[a-zA-Z\s]{2,50}$/;          // letters + spaces (English names)
    var phoneRegex    = /^[6-9][0-9]{9}$/;             // 10-digit starting 6-9

    // ---- Helper: attach listener only if element exists ----
    function attach(el, event, fn) {
        if (el) {
            el.addEventListener(event, fn);
        }
    }

    // =====================================================
    // Username — only in ADD mode
    // =====================================================
    function checkUsername() {
        if (!username) return true;          // field hidden in edit mode
        var val = username.value.trim();

        if (val === '') {
            usernameError.textContent = 'Please enter a username.';
            return false;
        }
        if (!usernameRegex.test(val)) {
            usernameError.textContent = 'Username must be 3-30 characters (letters, digits, underscore).';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    // =====================================================
    // Password — only in ADD mode (in edit mode: blank = keep current)
    // =====================================================
    function checkPassword() {
        if (!password) return true;          // field hidden in edit mode

        var val = password.value;

        // In edit mode, blank password means "keep current" → allow it
        // Detect edit mode by the presence of a hidden "staff_id" or "farmer_id" input
        var isEdit = document.querySelector('input[name="staff_id"], input[name="farmer_id"]');
        if (isEdit && val === '') {
            passwordError.textContent = '';
            return true;
        }

        if (val === '') {
            passwordError.textContent = 'Please enter a password.';
            return false;
        }
        if (val.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters.';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    // =====================================================
    // Full Name
    // =====================================================
    function checkFullName() {
        if (!fullName) return true;
        var val = fullName.value.trim();

        if (val === '') {
            fullNameError.textContent = 'Please enter full name.';
            return false;
        }
        if (!fullNameRegex.test(val)) {
            fullNameError.textContent = 'Full name must contain only letters and spaces (2-50 characters).';
            return false;
        }
        fullNameError.textContent = '';
        return true;
    }

    // =====================================================
    // Phone — optional but if provided must be valid
    // =====================================================
    function checkPhone() {
        if (!phone) return true;
        var val = phone.value.trim();

        if (val === '') {
            // Optional — clear any previous error and pass
            phoneError.textContent = '';
            return true;
        }
        if (!phoneRegex.test(val)) {
            phoneError.textContent = 'Enter a valid 10-digit mobile number starting with 6-9.';
            return false;
        }
        phoneError.textContent = '';
        return true;
    }

    // =====================================================
    // Address — optional, max 200 chars
    // =====================================================
    function checkAddress() {
        if (!address) return true;
        var val = address.value.trim();

        if (val.length > 200) {
            addressError.textContent = 'Address must be 200 characters or fewer.';
            return false;
        }
        addressError.textContent = '';
        return true;
    }

    // =====================================================
    // Join Date — required, not in the future
    // =====================================================
    function checkJoinDate() {
        if (!joinDate) return true;
        var val = joinDate.value;

        if (val === '') {
            joinDateError.textContent = 'Please select join date.';
            return false;
        }

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var selected = new Date(val);
        selected.setHours(0, 0, 0, 0);

        if (selected > today) {
            joinDateError.textContent = 'Join date cannot be in the future.';
            return false;
        }

        joinDateError.textContent = '';
        return true;
    }

    // =====================================================
    // Attach events — only where elements exist
    // =====================================================
    attach(username, 'blur', checkUsername);
    attach(username, 'input', function () {
        if (usernameError.textContent !== '') checkUsername();
    });

    attach(password, 'blur', checkPassword);
    attach(password, 'input', function () {
        if (passwordError.textContent !== '') checkPassword();
    });

    attach(fullName, 'blur', checkFullName);
    attach(fullName, 'input', function () {
        if (fullNameError.textContent !== '') checkFullName();
    });

    attach(phone, 'blur', checkPhone);
    attach(phone, 'input', function () {
        if (phoneError.textContent !== '') checkPhone();
    });

    attach(address, 'blur', checkAddress);
    attach(address, 'input', function () {
        if (addressError.textContent !== '') checkAddress();
    });

    attach(joinDate, 'blur', checkJoinDate);
    attach(joinDate, 'change', checkJoinDate);

    // =====================================================
    // Submit handler
    // =====================================================
    form.addEventListener('submit', function (e) {
        var okUser  = checkUsername();
        var okPass  = checkPassword();
        var okName  = checkFullName();
        var okPhone = checkPhone();
        var okAddr  = checkAddress();
        var okDate  = checkJoinDate();

        if (!okUser || !okPass || !okName || !okPhone || !okAddr || !okDate) {
            e.preventDefault();

            // Focus first invalid field that actually exists
            if (!okUser  && username) username.focus();
            else if (!okPass  && password) password.focus();
            else if (!okName  && fullName) fullName.focus();
            else if (!okPhone && phone)    phone.focus();
            else if (!okAddr  && address)  address.focus();
            else if (!okDate  && joinDate) joinDate.focus();

            return false;
        }
        return true;
    });

});