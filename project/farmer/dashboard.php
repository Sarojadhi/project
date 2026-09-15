<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = $_SESSION['user_id'];

$farmerId = getFarmerIdFromUserId($conn, $userId);

if ($farmerId == 0) {

    include __DIR__ . '/../includes/header.php';

    echo '
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="bg-red-100 border border-red-300 text-red-800 rounded-lg p-4">
            Profile not found. Contact admin.
        </div>
    </div>';

    include __DIR__ . '/../includes/footer.php';

    exit;
}

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));


// Today's milk
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(litre), 0) AS total
     FROM milk_entries
     WHERE farmer_id = ?
     AND entry_date = ?"
);

$stmt->bind_param('is', $farmerId, $today);
$stmt->execute();

$result = $stmt->get_result();
$todayLitres = $result->fetch_assoc()['total'];


// This month's milk
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(litre), 0) AS total
     FROM milk_entries
     WHERE farmer_id = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $farmerId, $monthStart, $nextMonth);
$stmt->execute();

$result = $stmt->get_result();
$monthLitres = $result->fetch_assoc()['total'];


// This month's milk earnings
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM milk_entries
     WHERE farmer_id = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $farmerId, $monthStart, $nextMonth);
$stmt->execute();

$result = $stmt->get_result();
$monthEarning = $result->fetch_assoc()['total'];


// This month's dana deduction
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM dana_entries
     WHERE farmer_id = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $farmerId, $monthStart, $nextMonth);
$stmt->execute();

$result = $stmt->get_result();
$monthDana = $result->fetch_assoc()['total'];


// Net payable
$netPayable = $monthEarning - $monthDana;

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Farmer Dashboard
        </h1>

        <p class="text-gray-600">
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </p>
    </div>


    <!-- Summary cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">
                Today's Milk
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($todayLitres, 1); ?> L
            </p>
        </div>


        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">
                This Month
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($monthLitres, 1); ?> L
            </p>
        </div>


        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">
                Milk Earnings
            </p>

            <p class="text-2xl font-bold mt-1">
                Rs. <?php echo number_format($monthEarning, 2); ?>
            </p>
        </div>


        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">
                Dana Deduction
            </p>

            <p class="text-2xl font-bold mt-1">
                Rs. <?php echo number_format($monthDana, 2); ?>
            </p>
        </div>

    </div>


    <!-- Net payable and quick links -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="bg-white rounded-lg shadow p-6">

            <h2 class="text-lg font-semibold text-gray-700">
                Net Payable
            </h2>

            <?php
            if ($netPayable > 0) {
                $amountClass = 'text-green-600';
            } elseif ($netPayable < 0) {
                $amountClass = 'text-red-600';
            } else {
                $amountClass = 'text-gray-600';
            }
            ?>

            <p class="text-3xl font-bold mt-2 <?php echo $amountClass; ?>">
                Rs. <?php echo number_format($netPayable, 2); ?>
            </p>

            <p class="text-sm text-gray-500 mt-2">
                Milk earnings minus dana deduction
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-6">

            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                Quick Links
            </h2>

            <div class="space-y-3">

                <a
                    href="<?php echo BASE_URL; ?>/farmer/myreport.php"
                    class="block bg-blue-100 text-blue-800 px-4 py-3 rounded text-center font-semibold hover:bg-blue-200"
                >
                    My Milk Reports
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/farmer/mydana.php"
                    class="block bg-red-100 text-red-800 px-4 py-3 rounded text-center font-semibold hover:bg-red-200"
                >
                    My Dana Records
                </a>

            </div>

        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>