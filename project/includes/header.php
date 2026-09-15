<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$fullName = $_SESSION['full_name'] ?? '';

$menuLinks = array();


// Admin menu
if ($role === 'admin') {

    $menuLinks = array(
        array(
            'label' => 'Dashboard',
            'url' => BASE_URL . '/admin/dashboard.php'
        ),
        array(
            'label' => 'Staff',
            'url' => BASE_URL . '/admin/manage-staff.php'
        ),
        array(
            'label' => 'Farmers',
            'url' => BASE_URL . '/admin/manage-farmer.php'
        ),
        array(
            'label' => 'Rates',
            'url' => BASE_URL . '/admin/rate-setting.php'
        ),
        array(
            'label' => 'Ledger',
            'url' => BASE_URL . '/admin/report.php'
        )
    );


// Staff menu
} elseif ($role === 'staff') {

    $menuLinks = array(
        array(
            'label' => 'Dashboard',
            'url' => BASE_URL . '/staff/dashboard.php'
        ),
        array(
            'label' => 'Milk Entry',
            'url' => BASE_URL . '/staff/entry-milk.php'
        ),
        array(
            'label' => 'Dana Entry',
            'url' => BASE_URL . '/staff/entry-dana.php'
        ),
        array(
            'label' => 'Search Farmer',
            'url' => BASE_URL . '/staff/search-farmer.php'
        ),
        array(
            'label' => 'Reports',
            'url' => BASE_URL . '/staff/report.php'
        )
    );


// Farmer menu
} elseif ($role === 'farmer') {

    $menuLinks = array(
        array(
            'label' => 'Dashboard',
            'url' => BASE_URL . '/farmer/dashboard.php'
        ),
        array(
            'label' => 'My Milk',
            'url' => BASE_URL . '/farmer/myreport.php'
        ),
        array(
            'label' => 'My Dana',
            'url' => BASE_URL . '/farmer/mydana.php'
        )
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Shree Tri Shakti Dairy</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

<?php if ($loggedIn): ?>

<nav class="bg-white shadow">

    <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">

        <!-- Website name -->
        <div class="font-bold text-gray-800">
            Shree Tri Shakti Dairy
        </div>


        <!-- Navigation -->
        <div class="flex items-center gap-5">

            <?php foreach ($menuLinks as $link): ?>

                <a
                    href="<?php echo e($link['url']); ?>"
                    class="text-sm text-gray-700 hover:text-blue-600"
                >
                    <?php echo e($link['label']); ?>
                </a>

            <?php endforeach; ?>

        </div>


        <!-- User information -->
        <div class="flex items-center gap-3">

            <span class="text-sm text-gray-600">

                <?php echo e($fullName); ?>

                (<?php echo e(ucfirst($role)); ?>)

            </span>


            <a
                href="<?php echo BASE_URL; ?>/logout.php"
                class="text-sm text-red-600 border border-red-300 px-3 py-1 rounded hover:bg-red-50"
            >
                Logout
            </a>

        </div>

    </div>

</nav>

<?php endif; ?>