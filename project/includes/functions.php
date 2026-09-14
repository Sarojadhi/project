<?php
/**
 * Shared Helper Functions
 * 
 * Small utility functions used across the project.
 * Every function here should do ONE thing and be easy to read.
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/functions.php';
 */


// =====================================================================
// MILK PRICE CALCULATION
// =====================================================================
//
// This project uses a simple linear formula for milk rate:
//     Rate per litre = FAT % × SNF %
//     Amount         = Rate × Litres
//
// Example: FAT = 4.0, SNF = 9.0 → Rate = 36.00, Litres = 5 → Amount = 180.00
//
// (The README mentions an alternative "rate chart" method — this project
//  uses the simpler formula instead, so no chart lookup is needed.)

/**
 * Calculate milk rate per litre from FAT and SNF.
 * Formula: Rate = FAT × SNF
 */
function calculateRate($fat, $snf) {
    return $fat * $snf;
}

/**
 * Calculate total amount from rate and litres.
 * Formula: Amount = Rate × Litres
 */
function calculateAmount($rate, $litre) {
    return $rate * $litre;
}


// =====================================================================
// DISPLAY HELPERS
// =====================================================================

/**
 * Format a number as currency with "Rs. " prefix.
 * Example: 1234.5 → "Rs. 1,234.50"
 */
function formatCurrency($amount) {
    return "Rs. " . number_format($amount, 2);
}

/**
 * Format a date for display.
 * Input:  '2025-01-15'  → Output: '15-Jan-2025'
 * Empty input returns '-'.
 * Invalid input also returns '-' (so the page never crashes).
 */
function formatDate($date) {
    if (empty($date)) {
        return "-";
    }

    $timestamp = strtotime($date);

    // strtotime() returns false if it can't understand the input
    if ($timestamp === false) {
        return "-";
    }

    return date('d-M-Y', $timestamp);
}

/**
 * Format a number with 2 decimal places.
 * Example: 12.3456 → "12.35"
 */
function formatNumber($value) {
    return number_format($value, 2);
}


// =====================================================================
// SHIFT HELPERS
// =====================================================================

/**
 * Return the current shift based on the current time.
 * Morning: 04:00 – 11:59
 * Evening: 15:00 – 21:59
 * Outside those hours, returns the nearest upcoming shift.
 */
function getCurrentShift() {
    $hour = (int)date('H');   // 0–23

    if ($hour >= 4 && $hour < 12) {
        return 'morning';
    }

    if ($hour >= 15 && $hour < 22) {
        return 'evening';
    }

    // Fallback — default to morning
    return 'morning';
}

/**
 * Return a nicely-formatted shift label.
 * 'morning' → 'Morning'
 * 'evening' → 'Evening'
 */
function getShiftLabel($shift) {
    if ($shift === 'morning') {
        return 'Morning';
    }
    if ($shift === 'evening') {
        return 'Evening';
    }
    return ucfirst($shift);
}


// =====================================================================
// SECURITY HELPER
// =====================================================================

/**
 * Shortcut for htmlspecialchars with default flags.
 * Use this whenever you print user-provided text inside HTML.
 * Example:  echo e($farmer['full_name']);
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}


// =====================================================================
// STATUS HELPERS
// =====================================================================

/**
 * Return a Tailwind CSS class string for a status badge.
 * 'active'   → green
 * 'inactive' → red
 */
function statusBadgeClass($status) {
    if ($status === 'active') {
        return 'bg-green-100 text-green-800';
    }
    return 'bg-red-100 text-red-800';
}

?>