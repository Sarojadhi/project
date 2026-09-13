<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

$today = date('Y-m-d');

// Today's milk total
$sql = "SELECT COALESCE(SUM(litre), 0) AS total_litres FROM milk_entries WHERE DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayLitres = $row['total_litres'];

// Today's entry count
$sql = "SELECT COUNT(*) AS total FROM milk_entries WHERE DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayEntries = $row['total'];

// Active farmers
$sql = "SELECT COUNT(*) AS total FROM farmers WHERE status = 'active'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalFarmers = $row['total'];

// Active staff
$sql = "SELECT COUNT(*) AS total FROM staff WHERE status = 'active'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalStaff = $row['total'];

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase">Today's Milk</p>
            <p class="text-2xl font-bold"><?php echo number_format($todayLitres, 2); ?> L</p>
            <p class="text-sm text-gray-600 mt-1"><?php echo $todayEntries; ?> entries</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase">Active Farmers</p>
            <p class="text-2xl font-bold"><?php echo $totalFarmers; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500 uppercase">Active Staff</p>
            <p class="text-2xl font-bold"><?php echo $totalStaff; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Role</p>
            <p class="text-2xl font-bold">Admin</p>
        </div>

    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="manage-staff.php" class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">Manage Staff</a>
        <a href="manage-farmers.php" class="bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-4 rounded-lg text-center">Manage Farmers</a>
        <a href="rate-settings.php" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold py-3 px-4 rounded-lg text-center">Rate Settings</a>
        <a href="reports.php" class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-3 px-4 rounded-lg text-center">Full Reports</a>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>