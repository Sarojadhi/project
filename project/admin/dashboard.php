<?php
/**
 * Admin Dashboard
 * 
 * Shows the admin's summary of the dairy:
 *   - Today's milk litres and entry count
 *   - Total active farmers and staff
 *   - All-time milk total
 *   - Quick links to every admin function
 * 
 * Access: Only admins. Enforced by requireRole('admin') below.
 * 
 * Note: $conn and session are loaded by auth.php — no need
 * to include db.php again here.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// Get today's date for filtering
$today = date('Y-m-d');

// ----- Today's total milk litres -----
$sql = "SELECT COALESCE(SUM(litre), 0) AS total_litres 
        FROM milk_entries 
        WHERE DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayLitres = $row['total_litres'];

// ----- Today's milk entry count -----
$sql = "SELECT COUNT(*) AS total 
        FROM milk_entries 
        WHERE DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayEntries = $row['total'];

// ----- Active farmers count -----
$sql = "SELECT COUNT(*) AS total 
        FROM farmers 
        WHERE status = 'active'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalFarmers = $row['total'];

// ----- Active staff count -----
$sql = "SELECT COUNT(*) AS total 
        FROM staff 
        WHERE status = 'active'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$totalStaff = $row['total'];

// ----- All-time milk total -----
$sql = "SELECT COALESCE(SUM(litre), 0) AS total_litres 
        FROM milk_entries";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$allTimeLitres = $row['total_litres'];

// ----- Total dana given (all time) -----
$sql = "SELECT COALESCE(SUM(quantity), 0) AS total_qty 
        FROM dana_entries";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$allTimeDana = $row['total_qty'];

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <!-- PAGE HEADING -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
        <p class="text-gray-600 mt-1">
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </p>
    </div>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS (each one is clickable)                 -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- Today's Milk -->
        <a href="/admin/report.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Today's Milk</p>
            <p class="text-2xl font-bold text-blue-600">
                <?php echo number_format($todayLitres, 2); ?> L
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo $todayEntries; ?> entries
            </p>
        </a>

        <!-- Active Farmers -->
        <a href="/admin/manage-farmer.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Active Farmers</p>
            <p class="text-2xl font-bold text-green-600">
                <?php echo $totalFarmers; ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">Click to manage</p>
        </a>

        <!-- Active Staff -->
        <a href="/admin/manage-staff.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Active Staff</p>
            <p class="text-2xl font-bold text-yellow-600">
                <?php echo $totalStaff; ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">Click to manage</p>
        </a>

    </div>

    <!-- ===================================================== -->
    <!-- SECOND ROW: ALL-TIME STATS                            -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        <!-- All-time Milk -->
        <a href="/admin/report.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">All-time Milk Collected</p>
            <p class="text-2xl font-bold text-purple-600">
                <?php echo number_format($allTimeLitres, 2); ?> L
            </p>
            <p class="text-sm text-gray-600 mt-1">View full ledger</p>
        </a>

        <!-- All-time Dana -->
        <a href="/admin/report.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-red-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">All-time Dana Given</p>
            <p class="text-2xl font-bold text-red-600">
                <?php echo number_format($allTimeDana, 2); ?> kg
            </p>
            <p class="text-sm text-gray-600 mt-1">View deductions</p>
        </a>

    </div>

    <!-- ===================================================== -->
    <!-- QUICK ACTION BUTTONS                                  -->
    <!-- ===================================================== -->
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Quick Actions</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="/admin/manage-staff.php"
           class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">
            Manage Staff
        </a>
        <a href="/admin/manage-farmer.php"
           class="bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-4 rounded-lg text-center">
            Manage Farmers
        </a>
        <a href="/admin/rate-setting.php"
           class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold py-3 px-4 rounded-lg text-center">
            Rate Settings
        </a>
        <a href="/admin/report.php"
           class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-3 px-4 rounded-lg text-center">
            Farmer Ledger
        </a>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>