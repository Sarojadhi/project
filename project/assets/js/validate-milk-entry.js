document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('milkEntryForm');

    if (!form) {
        return;
    }


    // Form fields
    const farmerSearch = document.getElementById('farmer_search');
    const farmerId = document.getElementById('farmer_id');
    const farmerSuggestions = document.getElementById('farmer-suggestions');

    const date = document.getElementById('entry_date');
    const shift = document.getElementById('shift');
    const litre = document.getElementById('litre');
    const fat = document.getElementById('fat');
    const snf = document.getElementById('snf');


    // Error fields
    const farmerError = document.getElementById('farmer-error');
    const dateError = document.getElementById('date-error');
    const litreError = document.getElementById('litre-error');
    const fatError = document.getElementById('fat-error');
    const snfError = document.getElementById('snf-error');


    // Price fields
    const ratePreview = document.getElementById('rate-preview');
    const amountPreview = document.getElementById('amount-preview');


    // Reset button
    const resetButton = document.getElementById('resetButton');


    // PHP farmer data
    const farmers = Array.isArray(window.milkFarmers)
        ? window.milkFarmers
        : [];


    // PHP previous FAT/SNF data
    const previousValues =
        window.previousMilkValues || {};


    // PHP milk rates
    const fatRate = Number(
        window.milkRates?.fatRate ?? 8.50
    );

    const snfRate = Number(
        window.milkRates?.snfRate ?? 4.00
    );


    // Track keyboard selected suggestion
    let currentMatches = [];

    let highlightedIndex = -1;


    // Search farmers
    function showFarmerSuggestions() {

        const searchValue =
            farmerSearch.value.trim().toLowerCase();


        farmerSuggestions.innerHTML = '';

        highlightedIndex = -1;


        if (searchValue === '') {

            farmerId.value = '';

            farmerSuggestions.classList.add('hidden');

            fat.value = '';
            snf.value = '';

            currentMatches = [];

            updatePrice();

            return;
        }


        // Clear selected farmer when typing
        farmerId.value = '';

        fat.value = '';
        snf.value = '';


        currentMatches = farmers.filter(function (farmer) {

            const fullName =
                String(farmer.full_name || '').toLowerCase();

            const username =
                String(farmer.username || '').toLowerCase();

            const userId =
                String(farmer.user_id || '').toLowerCase();


            return (
                fullName.includes(searchValue) ||
                username.includes(searchValue) ||
                userId.includes(searchValue)
            );

        });


        if (currentMatches.length === 0) {

            farmerSuggestions.classList.add('hidden');

            updatePrice();

            return;
        }


        currentMatches
            .slice(0, 8)
            .forEach(function (farmer, index) {

                const button =
                    document.createElement('button');


                button.type = 'button';


                button.className =
                    'w-full text-left px-4 py-3 hover:bg-gray-100 border-b border-gray-100';


                button.dataset.index = index;


                // Farmer name
                const name =
                    document.createElement('div');


                name.className =
                    'font-medium text-gray-800';


                name.textContent =
                    farmer.full_name;


                // User ID
                const userId =
                    document.createElement('div');


                userId.className =
                    'text-xs text-gray-500 mt-1';


                userId.textContent =
                    'User ID: ' + farmer.user_id;


                button.appendChild(name);

                button.appendChild(userId);


                // Select with mouse
                button.addEventListener(
                    'mousedown',
                    function (event) {

                        event.preventDefault();

                        selectFarmer(farmer);

                    }
                );


                farmerSuggestions.appendChild(button);

            });


        farmerSuggestions.classList.remove('hidden');

    }


    // Highlight suggestion
    function highlightSuggestion(index) {

        const buttons =
            farmerSuggestions.querySelectorAll('button');


        if (buttons.length === 0) {
            return;
        }


        if (index < 0) {
            index = buttons.length - 1;
        }


        if (index >= buttons.length) {
            index = 0;
        }


        highlightedIndex = index;


        buttons.forEach(function (button, buttonIndex) {

            if (buttonIndex === highlightedIndex) {

                button.classList.add('bg-gray-100');

                button.scrollIntoView({
                    block: 'nearest'
                });

            } else {

                button.classList.remove('bg-gray-100');

            }

        });

    }


    // Handle keyboard navigation
    farmerSearch.addEventListener(
        'keydown',
        function (event) {

            const buttons =
                farmerSuggestions.querySelectorAll('button');


            if (
                farmerSuggestions.classList.contains('hidden') ||
                buttons.length === 0
            ) {

                return;

            }


            // Arrow down
            if (event.key === 'ArrowDown') {

                event.preventDefault();

                highlightSuggestion(
                    highlightedIndex + 1
                );

            }


            // Arrow up
            else if (event.key === 'ArrowUp') {

                event.preventDefault();

                highlightSuggestion(
                    highlightedIndex - 1
                );

            }


            // Select highlighted farmer
            else if (event.key === 'Enter') {

                event.preventDefault();


                if (highlightedIndex >= 0) {

                    const selectedFarmer =
                        currentMatches[highlightedIndex];


                    if (selectedFarmer) {

                        selectFarmer(selectedFarmer);

                    }

                } else {

                    selectFarmer(
                        currentMatches[0]
                    );

                }

            }


            // Close suggestions
            else if (event.key === 'Escape') {

                event.preventDefault();

                farmerSuggestions.classList.add('hidden');

                highlightedIndex = -1;

            }

        }
    );


    // Select farmer
    function selectFarmer(farmer) {

        // Show username only in input
        farmerSearch.value =
            farmer.username;


        // Store actual farmers.id
        farmerId.value =
            String(farmer.farmer_id);


        farmerSuggestions.classList.add('hidden');

        farmerError.textContent = '';

        highlightedIndex = -1;

        currentMatches = [];


        // Load shift-specific FAT/SNF
        loadPreviousValues();

    }


    // Load previous FAT/SNF according to farmer and shift
    function loadPreviousValues() {

        const selectedFarmerId =
            String(farmerId.value);


        const selectedShift =
            shift.value;


        if (!selectedFarmerId) {

            fat.value = '';
            snf.value = '';

            updatePrice();

            return;

        }


        const farmerValues =
            previousValues[selectedFarmerId];


        if (!farmerValues) {

            fat.value = '';
            snf.value = '';

            updatePrice();

            return;

        }


        const shiftValues =
            farmerValues[selectedShift];


        if (
            shiftValues &&
            shiftValues.fat !== undefined &&
            shiftValues.snf !== undefined
        ) {

            fat.value =
                Number(shiftValues.fat).toFixed(1);

            snf.value =
                Number(shiftValues.snf).toFixed(1);

        } else {

            fat.value = '';
            snf.value = '';

        }


        checkFat();

        checkSnf();

        updatePrice();

    }


    // Calculate milk price
    function updatePrice() {

        const litreValue =
            parseFloat(litre.value);


        const fatValue =
            parseFloat(fat.value);


        const snfValue =
            parseFloat(snf.value);


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
            (fatValue * fatRate) +
            (snfValue * snfRate);


        let amount = 0;


        if (
            !isNaN(litreValue) &&
            litreValue > 0
        ) {

            amount =
                rate * litreValue;

        }


        ratePreview.textContent =
            'Rs. ' + rate.toFixed(2);


        amountPreview.textContent =
            'Rs. ' + amount.toFixed(2);

    }


    // Validate farmer
    function checkFarmer() {

        const value =
            farmerSearch.value.trim();


        if (value === '') {

            farmerError.textContent =
                'Please enter farmer name or user ID.';

            return false;

        }


        if (farmerId.value === '') {

            farmerError.textContent =
                'Please select a farmer from the suggestions.';

            return false;

        }


        const selectedFarmer =
            farmers.find(function (farmer) {

                return String(farmer.farmer_id) ===
                    String(farmerId.value);

            });


        if (!selectedFarmer) {

            farmerError.textContent =
                'Please select a valid farmer.';

            farmerId.value = '';

            return false;

        }


        farmerError.textContent = '';

        return true;

    }


    // Validate date
    function checkDate() {

        const now =
            new Date();


        const year =
            now.getFullYear();


        const month =
            String(now.getMonth() + 1)
                .padStart(2, '0');


        const day =
            String(now.getDate())
                .padStart(2, '0');


        const currentDate =
            year +
            '-' +
            month +
            '-' +
            day;


        if (date.value !== currentDate) {

            dateError.textContent =
                'Only today\'s date is allowed.';

            return false;

        }


        dateError.textContent = '';

        return true;

    }


    // Validate litres
    function checkLitre() {

        const value =
            parseFloat(litre.value);


        if (
            isNaN(value) ||
            value <= 0
        ) {

            litreError.textContent =
                'Litres must be greater than 0.';

            updatePrice();

            return false;

        }


        litreError.textContent = '';

        updatePrice();

        return true;

    }


    // Validate FAT
    function checkFat() {

        const value =
            parseFloat(fat.value);


        if (isNaN(value)) {

            fatError.textContent =
                'Please enter FAT.';

            updatePrice();

            return false;

        }


        if (
            value <= 1.6 ||
            value >= 8
        ) {

            fatError.textContent =
                'FAT must be greater than 1.6 and less than 8.';

            updatePrice();

            return false;

        }


        fatError.textContent = '';

        updatePrice();

        return true;

    }


    // Validate SNF
    function checkSnf() {

        const value =
            parseFloat(snf.value);


        if (isNaN(value)) {

            snfError.textContent =
                'Please enter SNF.';

            updatePrice();

            return false;

        }


        if (
            value <= 3 ||
            value >= 9
        ) {

            snfError.textContent =
                'SNF must be greater than 3 and less than 9.';

            updatePrice();

            return false;

        }


        snfError.textContent = '';

        updatePrice();

        return true;

    }


    // Farmer search
    farmerSearch.addEventListener(
        'input',
        showFarmerSuggestions
    );


    // Close suggestions
    document.addEventListener(
        'click',
        function (event) {

            if (
                !farmerSearch.contains(event.target) &&
                !farmerSuggestions.contains(event.target)
            ) {

                farmerSuggestions.classList.add('hidden');

            }

        }
    );


    // Validate farmer when leaving search
    farmerSearch.addEventListener(
        'blur',
        function () {

            setTimeout(function () {

                checkFarmer();

            }, 200);

        }
    );


    // Change FAT/SNF when shift changes
    shift.addEventListener(
        'change',
        function () {

            loadPreviousValues();

        }
    );


    // Update price when litres change
    litre.addEventListener(
        'input',
        function () {

            checkLitre();

        }
    );


    // Update price when FAT changes
    fat.addEventListener(
        'input',
        function () {

            checkFat();

        }
    );


    // Update price when SNF changes
    snf.addEventListener(
        'input',
        function () {

            checkSnf();

        }
    );


    // Validate before submit
    form.addEventListener(
        'submit',
        function (event) {

            const validFarmer =
                checkFarmer();


            const validDate =
                checkDate();


            const validLitre =
                checkLitre();


            const validFat =
                checkFat();


            const validSnf =
                checkSnf();


            if (
                !validFarmer ||
                !validDate ||
                !validLitre ||
                !validFat ||
                !validSnf
            ) {

                event.preventDefault();

            }

        }
    );


    // Reset form
    if (resetButton) {

        resetButton.addEventListener(
            'click',
            function () {

                setTimeout(function () {

                    farmerSearch.value = '';

                    farmerId.value = '';

                    fat.value = '';

                    snf.value = '';

                    litre.value = '';


                    farmerSuggestions.classList.add(
                        'hidden'
                    );


                    farmerError.textContent = '';

                    dateError.textContent = '';

                    litreError.textContent = '';

                    fatError.textContent = '';

                    snfError.textContent = '';


                    currentMatches = [];

                    highlightedIndex = -1;


                    updatePrice();

                }, 0);

            }
        );

    }


    // Initial price
    updatePrice();

});