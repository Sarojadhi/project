document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('milkEntryForm');

    if (!form) {
        return;
    }


    const farmerSearch = document.getElementById('farmer_search');
    const farmerId = document.getElementById('farmer_id');
    const farmerSuggestions = document.getElementById('farmer-suggestions');

    const date = document.getElementById('entry_date');
    const shift = document.getElementById('shift');
    const litre = document.getElementById('litre');
    const fat = document.getElementById('fat');
    const snf = document.getElementById('snf');

    const farmerError = document.getElementById('farmer-error');
    const dateError = document.getElementById('date-error');
    const litreError = document.getElementById('litre-error');
    const fatError = document.getElementById('fat-error');
    const snfError = document.getElementById('snf-error');

    const ratePreview = document.getElementById('rate-preview');
    const litrePreview = document.getElementById('litre-preview');
    const amountPreview = document.getElementById('amount-preview');

    const resetButton = document.getElementById('resetButton');


    if (
        !farmerSearch ||
        !farmerId ||
        !farmerSuggestions ||
        !date ||
        !shift ||
        !litre ||
        !fat ||
        !snf
    ) {
        return;
    }


    // Store farmer names and IDs
    const farmers = [

        <?php
        /*
         * This section is not executed because this file is external JavaScript.
         * Farmer data is loaded below from a PHP-created global variable.
         */
        ?>

    ];


    // Use farmer data created by PHP
    const farmerData = window.milkFarmers || [];


    // Store separate FAT/SNF values for each shift
    const shiftValues = {
        morning: {
            fat: 4.0,
            snf: 9.0
        },
        evening: {
            fat: 4.0,
            snf: 9.0
        }
    };


    // Load saved shift values
    try {

        const savedValues = JSON.parse(
            localStorage.getItem('milkShiftValues')
        );

        if (savedValues) {

            if (savedValues.morning) {
                shiftValues.morning = savedValues.morning;
            }

            if (savedValues.evening) {
                shiftValues.evening = savedValues.evening;
            }

        }

    } catch (error) {
        // Use default values
    }


    // Save shift values
    function saveShiftValues() {

        localStorage.setItem(
            'milkShiftValues',
            JSON.stringify(shiftValues)
        );

    }


    // Load FAT/SNF for selected shift
    function loadShiftValues() {

        const selectedShift = shift.value;

        fat.value = Number(
            shiftValues[selectedShift].fat
        ).toFixed(1);

        snf.value = Number(
            shiftValues[selectedShift].snf
        ).toFixed(1);

        calculateAmount();

    }


    // Save current FAT/SNF for selected shift
    function saveCurrentShiftValues() {

        const selectedShift = shift.value;

        const fatValue = parseFloat(fat.value);
        const snfValue = parseFloat(snf.value);

        if (!isNaN(fatValue)) {
            shiftValues[selectedShift].fat = fatValue;
        }

        if (!isNaN(snfValue)) {
            shiftValues[selectedShift].snf = snfValue;
        }

        saveShiftValues();

    }


    // Search farmers
    function showFarmerSuggestions() {

        const searchValue = farmerSearch.value
            .trim()
            .toLowerCase();

        farmerSuggestions.innerHTML = '';

        if (searchValue === '') {

            farmerSuggestions.classList.add('hidden');
            farmerId.value = '';

            return;
        }


        const matches = farmerData.filter(function (farmer) {

            return farmer.name
                .toLowerCase()
                .includes(searchValue);

        });


        if (matches.length === 0) {

            farmerSuggestions.classList.add('hidden');
            farmerId.value = '';

            return;
        }


        matches.slice(0, 8).forEach(function (farmer) {

            const option = document.createElement('button');

            option.type = 'button';

            option.className =
                'w-full text-left px-4 py-2 hover:bg-gray-100 border-b border-gray-100';

            option.textContent =
                farmer.name;

            option.addEventListener('click', function () {

                farmerSearch.value = farmer.name;
                farmerId.value = farmer.id;

                farmerSuggestions.classList.add('hidden');
                farmerError.textContent = '';

            });

            farmerSuggestions.appendChild(option);

        });

        farmerSuggestions.classList.remove('hidden');

    }


    farmerSearch.addEventListener(
        'input',
        showFarmerSuggestions
    );


    document.addEventListener('click', function (event) {

        if (
            !farmerSearch.contains(event.target) &&
            !farmerSuggestions.contains(event.target)
        ) {
            farmerSuggestions.classList.add('hidden');
        }

    });


    // Check farmer
    function checkFarmer() {

        if (
            farmerId.value === '' ||
            farmerId.value === '0'
        ) {

            farmerError.textContent =
                'Please select a farmer from the suggestions.';

            return false;
        }

        farmerError.textContent = '';

        return true;

    }


    // Check today's date
    function checkDate() {

        const today = new Date();

        const formattedToday =
            today.getDate().toString().padStart(2, '0') +
            '-' +
            today.toLocaleString('en-US', {
                month: 'short'
            }) +
            '-' +
            today.getFullYear();


        if (date.value !== formattedToday) {

            dateError.textContent =
                'Only today\'s date is allowed.';

            return false;
        }

        dateError.textContent = '';

        return true;

    }


    // Check litres
    function checkLitre() {

        const value = parseFloat(litre.value);

        if (isNaN(value) || value <= 0) {

            litreError.textContent =
                'Litres must be greater than 0.';

            return false;
        }

        litreError.textContent = '';

        return true;

    }


    // Check FAT
    function checkFat() {

        const value = parseFloat(fat.value);

        if (
            isNaN(value) ||
            value <= 1.6 ||
            value >= 8
        ) {

            fatError.textContent =
                'FAT must be greater than 1.6 and less than 8.';

            return false;
        }

        fatError.textContent = '';

        return true;

    }


    // Check SNF
    function checkSnf() {

        const value = parseFloat(snf.value);

        if (
            isNaN(value) ||
            value <= 3 ||
            value >= 9
        ) {

            snfError.textContent =
                'SNF must be greater than 3 and less than 9.';

            return false;
        }

        snfError.textContent = '';

        return true;

    }


    // Calculate rate and total amount
    function calculateAmount() {

        const fatValue = parseFloat(fat.value);
        const snfValue = parseFloat(snf.value);
        const litreValue = parseFloat(litre.value);

        if (
            isNaN(fatValue) ||
            isNaN(snfValue)
        ) {

            ratePreview.textContent =
                'Rs. 0.00';

            amountPreview.textContent =
                'Rs. 0.00';

            return;
        }


        const rate =
            (fatValue * milkFatRate) +
            (snfValue * milkSnfRate);


        const amount =
            !isNaN(litreValue)
                ? rate * litreValue
                : 0;


        ratePreview.textContent =
            'Rs. ' + rate.toFixed(2);

        litrePreview.textContent =
            (!isNaN(litreValue)
                ? litreValue.toFixed(1)
                : '0.0') + ' L';

        amountPreview.textContent =
            'Rs. ' + amount.toFixed(2);

    }


    // Save FAT/SNF when changed
    fat.addEventListener('input', function () {

        saveCurrentShiftValues();

        fatError.textContent = '';

        calculateAmount();

    });


    snf.addEventListener('input', function () {

        saveCurrentShiftValues();

        snfError.textContent = '';

        calculateAmount();

    });


    // Change FAT/SNF when shift changes
    shift.addEventListener('change', function () {

        loadShiftValues();

    });


    litre.addEventListener('input', function () {

        litreError.textContent = '';

        calculateAmount();

    });


    farmerSearch.addEventListener('blur', function () {

        setTimeout(function () {
            checkFarmer();
        }, 200);

    });


    // Validate on blur
    date.addEventListener('blur', checkDate);
    litre.addEventListener('blur', checkLitre);
    fat.addEventListener('blur', checkFat);
    snf.addEventListener('blur', checkSnf);


    // Final validation
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

            return;
        }


        saveCurrentShiftValues();

    });


    // Reset form
    if (resetButton) {

        resetButton.addEventListener('click', function () {

            setTimeout(function () {

                farmerSearch.value = '';
                farmerId.value = '';

                litre.value = '';

                farmerSuggestions.classList.add('hidden');

                farmerError.textContent = '';
                dateError.textContent = '';
                litreError.textContent = '';
                fatError.textContent = '';
                snfError.textContent = '';

                loadShiftValues();

            }, 0);

        });

    }


    loadShiftValues();
    calculateAmount();

});