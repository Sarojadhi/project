<?php
/**
 * Shared Header
 * 
 * Displays the navigation bar and opens the HTML document.
 * Every page includes this file at the top of its HTML output.
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireRole('admin');
 *   include __DIR__ . '/../includes/header.php';
 * 
 * The session is already started by auth.php, so we do not start it here.
 */

// Make sure the session is running (safe even if auth.php already started it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Read values we need for the nav bar.
// Use empty strings as defaults so nothing breaks if the session is empty.
$loggedIn = false;
$role = '';
$fullName = '';

if (isset($_SESSION['user_id'])) {
    $loggedIn = true;
}

if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
}

if (isset($_SESSION['full_name'])) {
    $fullName = $_SESSION['full_name'];
}

// Build a list of menu links for the current role.
// Each item is: ['label' => 'Display Name', 'url' => '/absolute/path.php']
$menuLinks = array();

if ($role === 'admin') {
    $menuLinks = array(
        array('label' => 'Dashboard',     'url' => '/admin/dashboard.php'),
        array('label' => 'Manage Staff',  'url' => '/admin/manage-staff.php'),
        array('label' => 'Manage Farmers','url' => '/admin/manage-farmer.php'),
        array('label' => 'Rate Settings', 'url' => '/admin/rate-setting.php'),
        array('label' => 'Reports',       'url' => '/admin/report.php'),
    );
}

if ($role === 'staff') {
    $menuLinks = array(
        array('label' => 'Dashboard',       'url' => '/staff/dashboard.php'),
        array('label' => 'Add Farmer',      'url' => '/staff/manage-farmer.php'),
        array('label' => 'Milk Entry',      'url' => '/staff/entry-milk.php'),
        array('label' => 'Dana Entry',      'url' => '/staff/entry-dana.php'),
        array('label' => 'Search Farmer',   'url' => '/staff/search-farmer.php'),
        array('label' => 'Reports',         'url' => '/staff/report.php'),
    );
}

if ($role === 'farmer') {
    $menuLinks = array(
        array('label' => 'My Dashboard', 'url' => '/farmer/dashboard.php'),
        array('label' => 'My Reports',   'url' => '/farmer/myreport.php'),
        array('label' => 'My Dana',      'url' => '/farmer/mydana.php'),
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shree Tri Shakti Dairy</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php if ($loggedIn === true): ?>
<nav class="bg-white shadow">

    <!-- Top bar -->
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">

        <!-- Brand -->
        <div class="font-bold text-gray-800 text-lg">
            Shree Tri Shakti Dairy
        </div>

        <!-- Desktop menu (hidden on small screens) -->
        <div class="hidden md:flex items-center gap-4">
            <?php
            // Loop through the menu links and print each one
            foreach ($menuLinks as $link) {
                echo '<a href="' . $link['url'] . '" '
                   . 'class="text-sm text-gray-700 hover:text-blue-600 font-medium">'
                   . htmlspecialchars($link['label'])
                   . '</a>';
            }
            ?>
        </div>

        <!-- Right side: user info + logout + mobile menu button -->
        <div class="flex items-center gap-3">

            <!-- User name and role -->
            <span class="text-sm text-gray-600 hidden sm:block">
                <?php echo htmlspecialchars($fullName); ?>
                <span class="text-xs text-gray-400">(<?php echo ucfirst($role); ?>)</span>
            </span>

            <!-- Logout button -->
            <a href="/logout.php"
               class="text-sm text-red-600 hover:text-red-800 font-semibold px-3 py-1 rounded border border-red-300">
                Logout
            </a>

            <!-- Mobile menu button (plain text, no icon) -->
            <button id="mobileMenuBtn"
                    class="md:hidden text-sm text-gray-700 border border-gray-300 rounded px-3 py-1 focus:outline-none">
                Menu
            </button>

        </div>
    </div>

    <!-- Mobile menu (hidden by default) -->
    <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-200 py-3">
        <div class="container mx-auto px-4 flex flex-col gap-2">
            <?php
            // Same menu links, but stacked vertically for mobile
            foreach ($menuLinks as $link) {
                echo '<a href="' . $link['url'] . '" '
                   . 'class="text-sm text-gray-700 hover:text-blue-600 py-2">'
                   . htmlspecialchars($link['label'])
                   . '</a>';
            }
            ?>

            <!-- Logout link in mobile menu -->
            <a href="/logout.php"
               class="text-sm text-red-600 hover:text-red-800 py-2 border-t border-gray-200 pt-2 mt-2">
                Logout
            </a>
        </div>
    </div>

</nav>

<!-- Small JavaScript to toggle the mobile menu -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Find the button and the menu
    var button = document.getElementById('mobileMenuBtn');
    var menu = document.getElementById('mobileMenu');

    // Only wire it up if both exist on the page
    if (button && menu) {
        button.addEventListener('click', function () {
            // Toggle the 'hidden' class to show/hide the menu
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        });
    }

});
</script>

<?php endif; ?>