
// Calculate milk rate
function calculateTotalRate() {

    const fatValue = parseFloat(fatRate.value);
    const snfValue = parseFloat(snfRate.value);

    if (
        isNaN(fatValue) ||
        isNaN(snfValue) ||
        fatValue <= 0 ||
        snfValue <= 0
    ) {
        totalRate.textContent = 'Rs. 0.00 / litre';
        return;
    }

    // Fixed milk rates
    const fatRateValue = 8.5;
    const snfRateValue = 4;

    // Calculate rate per litre
    const ratePerLitre =
        (fatValue * fatRateValue) +
        (snfValue * snfRateValue);

    totalRate.textContent =
        'Rs. ' + ratePerLitre.toFixed(2) + ' / litre';
}