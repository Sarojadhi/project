/**
 * Milk entry form validation
 * 
 * Rules:
 * - Farmer must be selected
 * - Date must not be empty
 * - Date must not be in the future
 * - Litres must be greater than 0
 * - FAT must be between 0 and 10
 * - SNF must be between 0 and 15
 */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('milkEntryForm');
    var farmer = document.getElementById('farmer_id');
    var date = document.getElementById('entry_date');
    var litre = document.getElementById('litre');
    var fat = document.getElementById('fat');
    var snf = document.getElementById('snf');

    var farmerError = document.getElementById('farmer-error');
    var dateError = document.getElementById('date-error');
    var litreError = document.getElementById('litre-error');
    var fatError = document.getElementById('fat-error');
    var snfError = document.getElementById('snf-error');

    function checkFarmer() {
        if (farmer.value == '0' || farmer.value === '') {
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

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var selected = new Date(date.value);
        selected.setHours(0, 0, 0, 0);

        if (selected > today) {
            dateError.textContent = 'Date cannot be in the future.';
            date.value = '';
            return false;
        }

        dateError.textContent = '';
        return true;
    }

    function checkLitre() {
        var val = parseFloat(litre.value);
        if (isNaN(val) || val <= 0) {
            litreError.textContent = 'Litres must be greater than 0.';
            litre.value = '';
            return false;
        }
        litreError.textContent = '';
        return true;
    }

    function checkFat() {
        var val = parseFloat(fat.value);
        if (isNaN(val)) {
            fatError.textContent = 'Please enter FAT percentage.';
            return false;
        }
        if (val < 0 || val > 10) {
            fatError.textContent = 'FAT must be between 0 and 10.';
            fat.value = '';
            return false;
        }
        fatError.textContent = '';
        return true;
    }

    function checkSnf() {
        var val = parseFloat(snf.value);
        if (isNaN(val)) {
            snfError.textContent = 'Please enter SNF percentage.';
            return false;
        }
        if (val < 0 || val > 15) {
            snfError.textContent = 'SNF must be between 0 and 15.';
            snf.value = '';
            return false;
        }
        snfError.textContent = '';
        return true;
    }

    // Attach events
    farmer.addEventListener('change', checkFarmer);
    farmer.addEventListener('blur', checkFarmer);

    date.addEventListener('change', checkDate);
    date.addEventListener('blur', checkDate);

    litre.addEventListener('blur', checkLitre);
    litre.addEventListener('input', function () {
        if (this.value !== '') {
            checkLitre();
        }
    });

    fat.addEventListener('blur', checkFat);
    fat.addEventListener('input', function () {
        if (this.value !== '') {
            checkFat();
        }
    });

    snf.addEventListener('blur', checkSnf);
    snf.addEventListener('input', function () {
        if (this.value !== '') {
            checkSnf();
        }
    });

    // Submit handler
    form.addEventListener('submit', function (e) {
        var okFarmer = checkFarmer();
        var okDate = checkDate();
        var okLitre = checkLitre();
        var okFat = checkFat();
        var okSnf = checkSnf();

        if (!okFarmer || !okDate || !okLitre || !okFat || !okSnf) {
            e.preventDefault();

            // Focus first invalid field
            if (!okFarmer) farmer.focus();
            else if (!okDate) date.focus();
            else if (!okLitre) litre.focus();
            else if (!okFat) fat.focus();
            else if (!okSnf) snf.focus();

            return false;
        }
        return true;
    });

});