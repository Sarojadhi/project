document.addEventListener('DOMContentLoaded', function () {

    const rateForm = document.getElementById('rateForm');
    const danaForm = document.getElementById('danaForm');

    if (!rateForm && !danaForm) {
        return;
    }

    // Rate form elements
    const fat = document.getElementById('fat');
    const snf = document.getElementById('snf');
    const rate = document.getElementById('rate');

    const fatError = document.getElementById('fat-error');
    const snfError = document.getElementById('snf-error');
    const rateError = document.getElementById('rate-error');

    function checkFat() {
        const value = parseFloat(fat.value);

        if (isNaN(value) || value <= 0 || value > 10) {
            fatError.textContent = 'FAT must be between 0 and 10.';
            return false;
        }

        fatError.textContent = '';
        return true;
    }

    function checkSnf() {
        const value = parseFloat(snf.value);

        if (isNaN(value) || value <= 0 || value > 15) {
            snfError.textContent = 'SNF must be between 0 and 15.';
            return false;
        }

        snfError.textContent = '';
        return true;
    }

    function checkRate() {
        const value = parseFloat(rate.value);

        if (isNaN(value) || value <= 0) {
            rateError.textContent = 'Rate must be greater than 0.';
            return false;
        }

        rateError.textContent = '';
        return true;
    }

    // Rate form validation
    if (rateForm) {

        if (fat && snf && rate && fatError && snfError && rateError) {

            fat.addEventListener('blur', checkFat);
            snf.addEventListener('blur', checkSnf);
            rate.addEventListener('blur', checkRate);

            rateForm.addEventListener('submit', function (event) {

                const validFat = checkFat();
                const validSnf = checkSnf();
                const validRate = checkRate();

                if (!validFat || !validSnf || !validRate) {
                    event.preventDefault();
                }
            });
        }
    }

    // Dana form elements
    const itemName = document.getElementById('item_name');
    const price = document.getElementById('price');

    const itemNameError = document.getElementById('item_name-error');
    const priceError = document.getElementById('price-error');

    function checkItemName() {
        const value = itemName.value.trim();

        if (value === '' || value.length < 2) {
            itemNameError.textContent = 'Item name must be at least 2 characters.';
            return false;
        }

        itemNameError.textContent = '';
        return true;
    }

    function checkPrice() {
        const value = parseFloat(price.value);

        if (isNaN(value) || value <= 0) {
            priceError.textContent = 'Price must be greater than 0.';
            return false;
        }

        priceError.textContent = '';
        return true;
    }

    // Dana form validation
    if (danaForm) {

        if (itemName && price && itemNameError && priceError) {

            itemName.addEventListener('blur', checkItemName);
            price.addEventListener('blur', checkPrice);

            danaForm.addEventListener('submit', function (event) {

                const validItemName = checkItemName();
                const validPrice = checkPrice();

                if (!validItemName || !validPrice) {
                    event.preventDefault();
                }
            });
        }
    }

});