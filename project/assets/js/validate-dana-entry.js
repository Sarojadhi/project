document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('danaEntryForm');

    if (!form) {
        return;
    }

    const farmer = document.getElementById('farmer_id');
    const date = document.getElementById('entry_date');
    const item = document.getElementById('item_id');
    const qty = document.getElementById('quantity');

    const farmerError = document.getElementById('farmer-error');
    const dateError = document.getElementById('date-error');
    const itemError = document.getElementById('item-error');
    const qtyError = document.getElementById('quantity-error');

    function checkFarmer() {
        if (farmer.value === '0') {
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

    function checkItem() {
        if (item.value === '0') {
            itemError.textContent = 'Please select an item.';
            return false;
        }

        itemError.textContent = '';
        return true;
    }

    function checkQuantity() {
        const value = parseFloat(qty.value);

        if (isNaN(value) || value <= 0) {
            qtyError.textContent = 'Quantity must be greater than 0.';
            return false;
        }

        qtyError.textContent = '';
        return true;
    }

    farmer.addEventListener('change', checkFarmer);
    date.addEventListener('blur', checkDate);
    item.addEventListener('change', checkItem);
    qty.addEventListener('blur', checkQuantity);

    form.addEventListener('submit', function (event) {

        const validFarmer = checkFarmer();
        const validDate = checkDate();
        const validItem = checkItem();
        const validQuantity = checkQuantity();

        if (
            !validFarmer ||
            !validDate ||
            !validItem ||
            !validQuantity
        ) {
            event.preventDefault();
        }
    });

});