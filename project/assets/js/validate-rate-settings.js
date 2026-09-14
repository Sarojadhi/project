/**
 * Rate Settings Form Validation
 * 
 * Validates the two forms on admin/rate-setting.php:
 *   - Rate Chart form   (id="rateForm")   → FAT, SNF, Rate
 *   - Dana Price form   (id="danaForm")   → Item Name, Price
 * 
 * Both forms are always present on the page (whether adding or editing),
 * so we do not need the "if (!form) return" guard as strongly as elsewhere.
 * But we still use the `attach()` helper to avoid crashes if a field is ever hidden.
 * 
 * Rules:
 *   Rate form:
 *     - FAT  : required, numeric, 0.1 – 10.0
 *     - SNF  : required, numeric, 0.1 – 15.0
 *     - Rate : required, numeric, > 0
 *   Dana form:
 *     - Item Name : required, 2 – 100 characters
 *     - Price     : required, numeric, > 0
 */

document.addEventListener('DOMContentLoaded', function () {

    // =====================================================
    // Helper: safely attach an event listener
    // =====================================================
    function attach(el, event, fn) {
        if (el) {
            el.addEventListener(event, fn);
        }
    }

    // =====================================================
    // RATE CHART FORM
    // =====================================================
    var rateForm = document.getElementById('rateForm');
    var fat      = document.getElementById('fat');
    var snf      = document.getElementById('snf');
    var rate     = document.getElementById('rate');

    var fatError  = document.getElementById('fat-error');
    var snfError  = document.getElementById('snf-error');
    var rateError = document.getElementById('rate-error');

    function checkFat() {
        if (!fat) return true;
        var val = parseFloat(fat.value);

        if (isNaN(val) || fat.value.trim() === '') {
            fatError.textContent = 'Please enter FAT percentage.';
            return false;
        }
        if (val < 0.1 || val > 10) {
            fatError.textContent = 'FAT must be between 0.1 and 10.';
            fat.value = '';
            return false;
        }
        fatError.textContent = '';
        return true;
    }

    function checkSnf() {
        if (!snf) return true;
        var val = parseFloat(snf.value);

        if (isNaN(val) || snf.value.trim() === '') {
            snfError.textContent = 'Please enter SNF percentage.';
            return false;
        }
        if (val < 0.1 || val > 15) {
            snfError.textContent = 'SNF must be between 0.1 and 15.';
            snf.value = '';
            return false;
        }
        snfError.textContent = '';
        return true;
    }

    function checkRate() {
        if (!rate) return true;
        var val = parseFloat(rate.value);

        if (isNaN(val) || rate.value.trim() === '') {
            rateError.textContent = 'Please enter a rate.';
            return false;
        }
        if (val <= 0) {
            rateError.textContent = 'Rate must be greater than 0.';
            rate.value = '';
            return false;
        }
        rateError.textContent = '';
        return true;
    }

    // Attach events for rate form
    attach(fat, 'blur', checkFat);
    attach(fat, 'input', function () {
        if (fatError.textContent !== '') checkFat();
    });

    attach(snf, 'blur', checkSnf);
    attach(snf, 'input', function () {
        if (snfError.textContent !== '') checkSnf();
    });

    attach(rate, 'blur', checkRate);
    attach(rate, 'input', function () {
        if (rateError.textContent !== '') checkRate();
    });

    // Submit handler for rate form
    if (rateForm) {
        rateForm.addEventListener('submit', function (e) {
            var okFat  = checkFat();
            var okSnf  = checkSnf();
            var okRate = checkRate();

            if (!okFat || !okSnf || !okRate) {
                e.preventDefault();

                if (!okFat  && fat)  fat.focus();
                else if (!okSnf && snf) snf.focus();
                else if (!okRate && rate) rate.focus();

                return false;
            }
            return true;
        });
    }

    // =====================================================
    // DANA PRICE FORM
    // =====================================================
    var danaForm   = document.getElementById('danaForm');
    var itemName   = document.getElementById('item_name');
    var price      = document.getElementById('price');

    var itemNameError = document.getElementById('item_name-error');
    var priceError    = document.getElementById('price-error');

    function checkItemName() {
        if (!itemName) return true;
        var val = itemName.value.trim();

        if (val === '') {
            itemNameError.textContent = 'Please enter an item name.';
            return false;
        }
        if (val.length < 2) {
            itemNameError.textContent = 'Item name must be at least 2 characters.';
            return false;
        }
        if (val.length > 100) {
            itemNameError.textContent = 'Item name must be 100 characters or fewer.';
            return false;
        }
        itemNameError.textContent = '';
        return true;
    }

    function checkPrice() {
        if (!price) return true;
        var val = parseFloat(price.value);

        if (isNaN(val) || price.value.trim() === '') {
            priceError.textContent = 'Please enter a price.';
            return false;
        }
        if (val <= 0) {
            priceError.textContent = 'Price must be greater than 0.';
            price.value = '';
            return false;
        }
        priceError.textContent = '';
        return true;
    }

    // Attach events for dana form
    attach(itemName, 'blur', checkItemName);
    attach(itemName, 'input', function () {
        if (itemNameError.textContent !== '') checkItemName();
    });

    attach(price, 'blur', checkPrice);
    attach(price, 'input', function () {
        if (priceError.textContent !== '') checkPrice();
    });

    // Submit handler for dana form
    if (danaForm) {
        danaForm.addEventListener('submit', function (e) {
            var okItem  = checkItemName();
            var okPrice = checkPrice();

            if (!okItem || !okPrice) {
                e.preventDefault();

                if (!okItem  && itemName) itemName.focus();
                else if (!okPrice && price) price.focus();

                return false;
            }
            return true;
        });
    }

});