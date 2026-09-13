<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
} elseif ($_SESSION['role'] === 'staff') {
    header('Location: staff/dashboard.php');
    exit;
} elseif ($_SESSION['role'] === 'farmer') {
    header('Location: farmer/dashboard.php');
    exit;
} else {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>