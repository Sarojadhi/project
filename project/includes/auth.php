<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/dairy');


// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}


// Get current user's role
function getCurrentRole()
{
    return $_SESSION['role'] ?? '';
}


// Redirect user to their dashboard
function redirectToDashboard()
{
    $role = getCurrentRole();

    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: ' . BASE_URL . '/staff/dashboard.php');
    } elseif ($role === 'farmer') {
        header('Location: ' . BASE_URL . '/farmer/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/login.php');
    }

    exit;
}


// Require login
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}


// Require specific role
function requireRole($role)
{
    requireLogin();

    if (getCurrentRole() !== $role) {
        redirectToDashboard();
    }
}


// Allow admin or staff
function requireAdminOrStaff()
{
    requireLogin();

    $role = getCurrentRole();

    if ($role !== 'admin' && $role !== 'staff') {
        redirectToDashboard();
    }
}