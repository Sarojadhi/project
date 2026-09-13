?<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// Get farmer_id
$sql = "SELECT id FROM farmers WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$farmer_id = $row['id'];

// This month's milk
$sql = "SELECT COALESCE(SUM(litre), 0) AS litres, COALESCE(SUM(amount), 0) AS amount
        FROM milk_entries WHERE farmer_id = $farmer_id AND entry_date BETWEEN '$month_start' AND '$today'";
$result = $conn->query($sql);
$milk = $result->fetch_assoc();

// This month's dana
$sql = "SELECT COALESCE(SUM(amount), 0) AS amount
        FROM dana_entries WHERE farmer_id = $farmer_id AND entry_date BETWEEN '$month_start' AND '$today'";
$result = $conn->query($sql);
$dana = $result->fetch_assoc();

$net_payable = $milk['amount'] - $dana['amount'];

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Farmer Dashboard</h1>
    <p class="text-gray-600 mb-6">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Farmer ID: <?php echo $farmer_id; ?>)</p>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase">This Month Milk</p>
            <p class="text-2xl font-bold"><?php echo number_format($milk['litres'], 2); ?> L</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase">Milk Amount</p>
            <p class="text-2xl font-bold">Rs. <?php echo number_format($milk['amount'], 2); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 uppercase">Dana Deduction</p>
            <p class="text-2xl font-bold">Rs. <?php echo number_format($dana['amount'], 2); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Net Payable</p>
            <p class="text-2xl font-bold">Rs. <?php echo number_format($net_payable, 2); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="my-reports.php" class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">My Milk Reports</a>
        <a href="my-dana.php" class="bg-red-100 hover:bg-red-200 text-red-800 font-semibold py-3 px-4 rounded-lg text-center">My Dana Records</a>
        <a href="my-reports.php" class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-3 px-4 rounded-lg text-center">Full Statement</a>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>>