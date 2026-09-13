<?php

// Calculate milk rate from FAT and SNF
function calculateRate($fat, $snf) {
    return $fat * $snf;
}

// Calculate amount from rate and litres
function calculateAmount($rate, $litre) {
    return $rate * $litre;
}

// Format currency
function formatCurrency($amount) {
    return "Rs. " . number_format($amount, 2);
}

// Format date for display
function formatDate($date) {
    if (empty($date)) {
        return "-";
    }
    return date('d-M-Y', strtotime($date));
}

?>