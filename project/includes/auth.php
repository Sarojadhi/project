<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin/dashboard.php');
            exit;
        } elseif ($_SESSION['role'] === 'staff') {
            header('Location: /staff/dashboard.php');
            exit;
        } elseif ($_SESSION['role'] === 'farmer') {
            header('Location: /farmer/dashboard.php');
            exit;
        } else {
            header('Location: /login.php');
            exit;
        }
    }
}
?>