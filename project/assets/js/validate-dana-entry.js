/**
 * Dana entry form validation
 * 
 * Rules:
 * - Farmer must be selected
 * - Date must not be empty
 * - Date must not be in the future
 * - Item must be selected
 * - Quantity must be greater than 0
 */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('danaEntryForm');
    var farmer = document.getElementById('farmer_id');
    var date = document.getElementById('entry_date');
    var item = document.getElementById('item_id');
    var quantity = document.getElementById('quantity');

    var farmerError = document.getElementById('farmer-error');
    var dateError = document.getElementById('date-error');
    var itemError = document.getElementById('item-error');
    var quantityError = document.getElementById('quantity-error');

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

    function checkItem() {
        if (item.value == '0' || item.value === '') {
            itemError.textContent = 'Please select an item.';
            return false;
        }
        itemError.textContent = '';
        return true;
    }

    function checkQuantity() {
        var val = parseFloat(quantity.value);
        if (isNaN(val) || val <= 0) {
            quantityError.textContent = 'Quantity must be greater than 0.';
            quantity.value = '';
            return false;
        }
        quantityError.textContent = '';
        return true;
    }

    // Attach events
    farmer.addEventListener('change', checkFarmer);
    farmer.addEventListener('blur', checkFarmer);

    date.addEventListener('change', checkDate);
    date.addEventListener('blur', checkDate);

    item.addEventListener('change', checkItem);
    item.addEventListener('blur', checkItem);

    quantity.addEventListener('blur', checkQuantity);
    quantity.addEventListener('input', function () {
        if (this.value !== '') {
            checkQuantity();
        }
    });

    // Submit handler
    form.addEventListener('submit', function (e) {
        var okFarmer = checkFarmer();
        var okDate = checkDate();
        var okItem = checkItem();
        var okQty = checkQuantity();

        if (!okFarmer || !okDate || !okItem || !okQty) {
            e.preventDefault();

            if (!okFarmer) farmer.focus();
            else if (!okDate) date.focus();
            else if (!okItem) item.focus();
            else if (!okQty) quantity.focus();

            return false;
        }
        return true;
    });

});