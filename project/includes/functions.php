<?php


// Calculate milk rate
function calculateRate($fat, $snf)
{
    return $fat * $snf;
}


// Calculate milk amount
function calculateAmount($rate, $litre)
{
    return $rate * $litre;
}


// Format date
function formatDate($date)
{
    if (empty($date)) {
        return "-";
    }

    $time = strtotime($date);

    if ($time === false) {
        return "-";
    }

    return date('d-M-Y', $time);
}


// Escape output
function e($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}


// Get farmer ID using user ID
function getFarmerIdFromUserId($conn, $userId)
{
    $userId = (int) $userId;

    $stmt = $conn->prepare(
        "SELECT id FROM farmers WHERE user_id = ?"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }

    return 0;
}