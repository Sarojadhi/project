<?php
/**
 * Authentication and Role Guards
 * 
 * This file does three things:
 *   1. Starts the session (so we can remember who logged in).
 *   2. Loads the database connection so $conn is available.
 *   3. Provides functions to protect pages by role.
 * 
 * How to use it in any protected page:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireRole('admin');   // or 'staff' or 'farmer'
 */

// =====================================================================
// STEP 1: START THE SESSION
// =====================================================================
// The session must be started before we can read $_SESSION values.
// session_status() checks if a session is already running so we don't
// start it twice (which would cause a warning).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================================
// STEP 2: LOAD THE DATABASE CONNECTION
// =====================================================================
// Including db.php here means every page that uses auth.php
// automatically gets the $conn variable for free.

require_once __DIR__ . '/../config/db.php';

// =====================================================================
// STEP 3: HELPER FUNCTION — CHECK IF USER IS LOGGED IN
// =====================================================================
// Returns true if a user is logged in, false otherwise.
// Does NOT redirect — just tells you the answer.

function isLoggedIn() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        return true;
    } else {
        return false;
    }
}

// =====================================================================
// STEP 4: HELPER FUNCTION — GET CURRENT USER'S ROLE
// =====================================================================
// Returns 'admin', 'staff', 'farmer', or '' if not logged in.

function getCurrentRole() {
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'];
    } else {
        return '';
    }
}

// =====================================================================
// STEP 5: REDIRECT TO A ROLE'S OWN DASHBOARD
// =====================================================================
// This sends a user to the dashboard for their own role.
// Used when someone with the wrong role tries to open a page.

function redirectToOwnDashboard() {
    $role = getCurrentRole();

    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    }

    if ($role === 'staff') {
        header('Location: /staff/dashboard.php');
        exit;
    }

    if ($role === 'farmer') {
        header('Location: /farmer/dashboard.php');
        exit;
    }

    // If role is unknown, send to login page
    header('Location: /login.php');
    exit;
}

// =====================================================================
// STEP 6: REQUIRE LOGIN (any logged-in user)
// =====================================================================
// Use this on any page that should only be seen by logged-in users.

function requireLogin() {
    if (isLoggedIn() === false) {
        header('Location: /login.php');
        exit;
    }
}

// =====================================================================
// STEP 7: REQUIRE A SPECIFIC ROLE
// =====================================================================
// Use this on pages that only one role should see.
// Example: requireRole('admin') on the admin dashboard.

function requireRole($requiredRole) {
    // First make sure the user is logged in
    requireLogin();

    // Now check if their role matches what we need
    if (getCurrentRole() !== $requiredRole) {
        // Wrong role — send them to their own dashboard
        redirectToOwnDashboard();
    }
}

// =====================================================================
// STEP 8: REQUIRE ADMIN OR STAFF (either one)
// =====================================================================
// Use this on pages that both admin and staff can access,
// but farmers cannot.

function requireAdminOrStaff() {
    requireLogin();

    $role = getCurrentRole();

    if ($role !== 'admin' && $role !== 'staff') {
        // Not admin or staff — send to their own dashboard
        redirectToOwnDashboard();
    }
}

?>