<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');
require_once __DIR__ . '/../config/db.php';

$staff_user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$sql = "SELECT COUNT(*) AS total, COALESCE(SUM(litre), 0) AS litres
        FROM milk_entries
        WHERE entered_by = $staff_user_id AND DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayEntries = $row['total'];
$todayLitres = $row['litres'];

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Staff Dashboard</h1>
    <p class="text-gray-600 mb-6">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase">Today's Entries</p>
            <p class="text-2xl font-bold"><?php echo $todayEntries; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase">Today's Litres</p>
            <p class="text-2xl font-bold"><?php echo number_format($todayLitres, 2); ?> L</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Role</p>
            <p class="text-2xl font-bold">Staff</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="entry-milk.php" class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">Enter Milk</a>
        <a href="entry-dana.php" class="bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-4 rounded-lg text-center">Enter Dana</a>
        <a href="reports.php" class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-3 px-4 rounded-lg text-center">Reports</a>
        <a href="search-farmer.php" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold py-3 px-4 rounded-lg text-center">Search Farmer</a>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>