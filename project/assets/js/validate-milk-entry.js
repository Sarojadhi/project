document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('milkEntryForm');

    if (!form) {
        return;
    }

    const farmer = document.getElementById('farmer_id');
    const date = document.getElementById('entry_date');
    const litre = document.getElementById('litre');
    const fat = document.getElementById('fat');
    const snf = document.getElementById('snf');

    const farmerError = document.getElementById('farmer-error');
    const dateError = document.getElementById('date-error');
    const litreError = document.getElementById('litre-error');
    const fatError = document.getElementById('fat-error');
    const snfError = document.getElementById('snf-error');

    // Check that all required elements exist
    if (
        !farmer ||
        !date ||
        !litre ||
        !fat ||
        !snf ||
        !farmerError ||
        !dateError ||
        !litreError ||
        !fatError ||
        !snfError
    ) {
        return;
    }

    function checkFarmer() {
        if (farmer.value === '0' || farmer.value === '') {
            farmerError.textContent = 'Please select a farmer.';
            return false;
        }

        farmerError.textContent = '';
        return true;
    }

    function checkDate() {
        if (date.value === '') {
            dateError.textContent = 'Please select a date.';
            return false;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const selectedDate = new Date(date.value);
        selectedDate.setHours(0, 0, 0, 0);

        if (selectedDate > today) {
            dateError.textContent = 'Date cannot be in the future.';
            return false;
        }

        dateError.textContent = '';
        return true;
    }

    function checkLitre() {
        const value = parseFloat(litre.value);

        if (isNaN(value) || value <= 0) {
            litreError.textContent = 'Litres must be greater than 0.';
            return false;
        }

        litreError.textContent = '';
        return true;
    }

    function checkFat() {
        const value = parseFloat(fat.value);

        if (isNaN(value) || value < 0 || value > 10) {
            fatError.textContent = 'FAT must be between 0 and 10.';
            return false;
        }

        fatError.textContent = '';
        return true;
    }

    function checkSnf() {
        const value = parseFloat(snf.value);

        if (isNaN(value) || value < 0 || value > 15) {
            snfError.textContent = 'SNF must be between 0 and 15.';
            return false;
        }

        snfError.textContent = '';
        return true;
    }

    // Validate when the user leaves the field
    farmer.addEventListener('change', checkFarmer);
    date.addEventListener('blur', checkDate);
    litre.addEventListener('blur', checkLitre);
    fat.addEventListener('blur', checkFat);
    snf.addEventListener('blur', checkSnf);

    // Final validation before submitting
    form.addEventListener('submit', function (event) {

        const validFarmer = checkFarmer();
        const validDate = checkDate();
        const validLitre = checkLitre();
        const validFat = checkFat();
        const validSnf = checkSnf();

        if (
            !validFarmer ||
            !validDate ||
            !validLitre ||
            !validFat ||
            !validSnf
        ) {
            event.preventDefault();
        }
    });

});