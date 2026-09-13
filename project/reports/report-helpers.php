<?php
/**
 * Shared Report Helpers
 * 
 * This file contains reusable functions for building report queries.
 * It is used by admin, staff, and farmer report pages.
 * 
 * The same functions work for all roles by passing a scope:
 *   - scope = 'all'      → admin sees all farmers
 *   - scope = 'staff'    → staff sees entries entered by them or all quantity (per role)
 *   - scope = 'farmer'   → farmer sees only their own data
 * 
 * No server-side validation. No icons. Plain PHP.
 */

/**
 * Get milk report data.
 * 
 * @param mysqli $conn          Database connection
 * @param string $dateFrom      Start date (Y-m-d)
 * @param string $dateTo        End date (Y-m-d)
 * @param int    $farmerId      Filter by farmer (0 = all)
 * @param int    $staffId       Filter by staff who entered (0 = all)
 * @param string $shift         'morning', 'evening', or 'all'
 * @param string $searchText    Search by farmer ID or username (empty = no search)
 * @param string $scope         'all', 'staff', or 'farmer'
 * @param int    $scopeId       ID for scope (staff user ID or farmer ID); 0 if not applicable
 * @param bool   $showAmounts   Whether to include rate/amount in result (true for admin/farmer, false for staff)
 * 
 * @return array                Array of milk entries
 */
function getMilkReport($conn, $dateFrom, $dateTo, $farmerId, $staffId, $shift, $searchText, $scope, $scopeId, $showAmounts)
{
    // Base query - choose fields based on showAmounts
    if ($showAmounts) {
        $sql = "SELECT 
                    m.id,
                    m.entry_date,
                    m.shift,
                    m.litre,
                    m.fat,
                    m.snf,
                    m.rate_applied,
                    m.amount,
                    f.id AS farmer_id,
                    u.username AS farmer_username,
                    u.full_name AS farmer_name,
                    e.full_name AS entered_by_name
                FROM milk_entries m
                JOIN farmers f ON m.farmer_id = f.id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN users e ON m.entered_by = e.id
                WHERE DATE(m.entry_date) BETWEEN ? AND ?";
    } else {
        // Staff version - no rate/amount
        $sql = "SELECT 
                    m.id,
                    m.entry_date,
                    m.shift,
                    m.litre,
                    m.fat,
                    m.snf,
                    f.id AS farmer_id,
                    u.username AS farmer_username,
                    u.full_name AS farmer_name,
                    e.full_name AS entered_by_name
                FROM milk_entries m
                JOIN farmers f ON m.farmer_id = f.id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN users e ON m.entered_by = e.id
                WHERE DATE(m.entry_date) BETWEEN ? AND ?";
    }
    
    $params = array($dateFrom, $dateTo);
    $types = 'ss';
    
    // ----- Scope filter -----
    if ($scope === 'farmer' && $scopeId > 0) {
        $sql .= " AND m.farmer_id = ?";
        $params[] = $scopeId;
        $types .= 'i';
    }
    
    if ($scope === 'staff' && $scopeId > 0) {
        $sql .= " AND m.entered_by = ?";
        $params[] = $scopeId;
        $types .= 'i';
    }
    
    // ----- Farmer filter (from dropdown) -----
    if ($farmerId > 0) {
        $sql .= " AND m.farmer_id = ?";
        $params[] = $farmerId;
        $types .= 'i';
    }
    
    // ----- Staff filter (from dropdown) -----
    if ($staffId > 0) {
        $sql .= " AND m.entered_by = ?";
        $params[] = $staffId;
        $types .= 'i';
    }
    
    // ----- Shift filter -----
    if ($shift === 'morning' || $shift === 'evening') {
        $sql .= " AND m.shift = ?";
        $params[] = $shift;
        $types .= 's';
    }
    
    // ----- Search filter (farmer ID or username) -----
    if ($searchText !== '') {
        if (is_numeric($searchText)) {
            $sql .= " AND f.id = ?";
            $params[] = (int)$searchText;
            $types .= 'i';
        } else {
            $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ?)";
            $like = '%' . $searchText . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
    }
    
    $sql .= " ORDER BY m.entry_date DESC, m.shift ASC";
    
    // Run prepared statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = array();
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    $stmt->close();
    
    return $entries;
}

/**
 * Get dana report data.
 * 
 * Same parameters as getMilkReport, but for dana_entries.
 * 
 * @return array Array of dana entries
 */
function getDanaReport($conn, $dateFrom, $dateTo, $farmerId, $staffId, $searchText, $scope, $scopeId, $showAmounts)
{
    if ($showAmounts) {
        $sql = "SELECT 
                    d.id,
                    d.entry_date,
                    d.item_name,
                    d.quantity,
                    d.price_applied,
                    d.amount,
                    f.id AS farmer_id,
                    u.username AS farmer_username,
                    u.full_name AS farmer_name,
                    e.full_name AS entered_by_name
                FROM dana_entries d
                JOIN farmers f ON d.farmer_id = f.id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN users e ON d.entered_by = e.id
                WHERE DATE(d.entry_date) BETWEEN ? AND ?";
    } else {
        // Staff version - no price/amount
        $sql = "SELECT 
                    d.id,
                    d.entry_date,
                    d.item_name,
                    d.quantity,
                    f.id AS farmer_id,
                    u.username AS farmer_username,
                    u.full_name AS farmer_name,
                    e.full_name AS entered_by_name
                FROM dana_entries d
                JOIN farmers f ON d.farmer_id = f.id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN users e ON d.entered_by = e.id
                WHERE DATE(d.entry_date) BETWEEN ? AND ?";
    }
    
    $params = array($dateFrom, $dateTo);
    $types = 'ss';
    
    // Scope filter
    if ($scope === 'farmer' && $scopeId > 0) {
        $sql .= " AND d.farmer_id = ?";
        $params[] = $scopeId;
        $types .= 'i';
    }
    
    if ($scope === 'staff' && $scopeId > 0) {
        $sql .= " AND d.entered_by = ?";
        $params[] = $scopeId;
        $types .= 'i';
    }
    
    // Farmer filter
    if ($farmerId > 0) {
        $sql .= " AND d.farmer_id = ?";
        $params[] = $farmerId;
        $types .= 'i';
    }
    
    // Staff filter
    if ($staffId > 0) {
        $sql .= " AND d.entered_by = ?";
        $params[] = $staffId;
        $types .= 'i';
    }
    
    // Search filter
    if ($searchText !== '') {
        if (is_numeric($searchText)) {
            $sql .= " AND f.id = ?";
            $params[] = (int)$searchText;
            $types .= 'i';
        } else {
            $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ?)";
            $like = '%' . $searchText . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
    }
    
    $sql .= " ORDER BY d.entry_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = array();
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    $stmt->close();
    
    return $entries;
}

/**
 * Get totals for a list of milk entries.
 * 
 * @param array $entries      Array from getMilkReport()
 * @param bool  $showAmounts  Whether to include amount total
 * @return array              ['total_litres' => x, 'total_amount' => y, 'count' => n]
 */
function getMilkTotals($entries, $showAmounts)
{
    $totalLitres = 0;
    $totalAmount = 0;
    
    foreach ($entries as $entry) {
        $totalLitres += $entry['litre'];
        if ($showAmounts && isset($entry['amount'])) {
            $totalAmount += $entry['amount'];
        }
    }
    
    return array(
        'total_litres' => $totalLitres,
        'total_amount' => $totalAmount,
        'count' => count($entries)
    );
}

/**
 * Get totals for a list of dana entries.
 * 
 * @param array $entries      Array from getDanaReport()
 * @param bool  $showAmounts  Whether to include amount total
 * @return array              ['total_quantity' => x, 'total_amount' => y, 'count' => n]
 */
function getDanaTotals($entries, $showAmounts)
{
    $totalQuantity = 0;
    $totalAmount = 0;
    
    foreach ($entries as $entry) {
        $totalQuantity += $entry['quantity'];
        if ($showAmounts && isset($entry['amount'])) {
            $totalAmount += $entry['amount'];
        }
    }
    
    return array(
        'total_quantity' => $totalQuantity,
        'total_amount' => $totalAmount,
        'count' => count($entries)
    );
}

/**
 * Get farmer-wise summary for a date range.
 * Used by admin reports to show one row per farmer with totals.
 * 
 * @param mysqli $conn       Database connection
 * @param string $dateFrom   Start date
 * @param string $dateTo     End date
 * @param int    $farmerId   Filter by specific farmer (0 = all)
 * @param string $searchText Search by farmer ID or username
 * @return array             One row per farmer with totals
 */
function getFarmerSummary($conn, $dateFrom, $dateTo, $farmerId, $searchText)
{
    $sql = "SELECT 
                f.id AS farmer_id,
                u.username AS farmer_username,
                u.full_name AS farmer_name,
                COALESCE((SELECT SUM(litre) FROM milk_entries 
                          WHERE farmer_id = f.id AND DATE(entry_date) BETWEEN ? AND ?), 0) AS total_litres,
                COALESCE((SELECT SUM(amount) FROM milk_entries 
                          WHERE farmer_id = f.id AND DATE(entry_date) BETWEEN ? AND ?), 0) AS total_milk_amount,
                COALESCE((SELECT SUM(amount) FROM dana_entries 
                          WHERE farmer_id = f.id AND DATE(entry_date) BETWEEN ? AND ?), 0) AS total_dana_amount
            FROM farmers f
            JOIN users u ON f.user_id = u.id
            WHERE u.role = 'farmer'";
    
    $params = array(
        $dateFrom, $dateTo,
        $dateFrom, $dateTo,
        $dateFrom, $dateTo
    );
    $types = 'ssssss';
    
    if ($farmerId > 0) {
        $sql .= " AND f.id = ?";
        $params[] = $farmerId;
        $types .= 'i';
    }
    
    if ($searchText !== '') {
        if (is_numeric($searchText)) {
            $sql .= " AND f.id = ?";
            $params[] = (int)$searchText;
            $types .= 'i';
        } else {
            $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ?)";
            $like = '%' . $searchText . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
    }
    
    $sql .= " ORDER BY u.full_name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        // Compute net payable
        $row['net_payable'] = $row['total_milk_amount'] - $row['total_dana_amount'];
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

/**
 * Get all active farmers for a dropdown.
 * 
 * @param mysqli $conn
 * @return array [id, username, full_name]
 */
function getFarmerList($conn)
{
    $sql = "SELECT f.id, u.username, u.full_name 
            FROM farmers f
            JOIN users u ON f.user_id = u.id
            WHERE u.role = 'farmer' AND u.status = 'active'
            ORDER BY u.full_name ASC";
    $result = $conn->query($sql);
    
    $farmers = array();
    while ($row = $result->fetch_assoc()) {
        $farmers[] = $row;
    }
    return $farmers;
}

/**
 * Get all active staff for a dropdown.
 * 
 * @param mysqli $conn
 * @return array [id, username, full_name]
 */
function getStaffList($conn)
{
    $sql = "SELECT s.id, u.username, u.full_name 
            FROM staff s
            JOIN users u ON s.user_id = u.id
            WHERE u.role = 'staff' AND u.status = 'active'
            ORDER BY u.full_name ASC";
    $result = $conn->query($sql);
    
    $staff = array();
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
    }
    return $staff;
}

/**
 * Get the farmer_id for a logged-in farmer user.
 * 
 * @param mysqli $conn
 * @param int    $userId  The user_id from session
 * @return int            farmer_id, or 0 if not found
 */
function getFarmerIdFromUserId($conn, $userId)
{
    $sql = "SELECT id FROM farmers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        return $row['id'];
    }
    return 0;
}

/**
 * Get the staff_id for a logged-in staff user.
 * 
 * @param mysqli $conn
 * @param int    $userId  The user_id from session
 * @return int            staff_id, or 0 if not found
 */
function getStaffIdFromUserId($conn, $userId)
{
    $sql = "SELECT id FROM staff WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        return $row['id'];
    }
    return 0;
}

/**
 * Format a date string for display.
 * 
 * @param string $dateStr  Date in Y-m-d format
 * @return string          Formatted date like "05-Jan-2025"
 */
function formatDisplayDate($dateStr)
{
    if (empty($dateStr)) {
        return '-';
    }
    return date('d-M-Y', strtotime($dateStr));
}

/**
 * Format a number as currency.
 * 
 * @param float $amount
 * @return string        Like "Rs. 1,234.50"
 */
function formatCurrency($amount)
{
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Format a number with 2 decimal places.
 * 
 * @param float $value
 * @return string
 */
function formatNumber($value)
{
    return number_format($value, 2);
}