<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = $_SESSION['user_id'];

$farmerId = getFarmerIdFromUserId($conn, $userId);

if ($farmerId == 0) {
    header('Location: ' . BASE_URL . '/farmer/dashboard.php');
    exit;
}


// Selected month and year
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');


// Validate month
if ($month < 1 || $month > 12) {
    $month = (int) date('m');
}


// Validate year
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}


// Month dates
$firstDay = sprintf('%04d-%02d-01', $year, $month);
$nextMonth = date('Y-m-01', strtotime($firstDay . ' +1 month'));
$monthName = date('F Y', strtotime($firstDay));


// Get dana records
$stmt = $conn->prepare(
    "SELECT *
     FROM dana_entries
     WHERE farmer_id = ?
     AND entry_date >= ?
     AND entry_date < ?
     ORDER BY entry_date ASC, id ASC"
);

$stmt->bind_param('iss', $farmerId, $firstDay, $nextMonth);
$stmt->execute();

$result = $stmt->get_result();

$records = array();
$totalQuantity = 0;
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {

    $records[] = $row;

    $totalQuantity += $row['quantity'];
    $totalAmount += $row['amount'];
}


// All-time dana deduction
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM dana_entries
     WHERE farmer_id = ?"
);

$stmt->bind_param('i', $farmerId);
$stmt->execute();

$result = $stmt->get_result();

$allTimeAmount = $result->fetch_assoc()['total'];

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page heading -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            My Dana Records
        </h1>

        <p class="text-gray-600">
            View your feed records and deductions
        </p>

    </div>


    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Month -->
            <div>

                <label
                    for="month"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Month
                </label>

                <select
                    id="month"
                    name="month"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                    <?php

                    $months = array(
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    );

                    foreach ($months as $number => $name) {

                        $selected = ($number == $month) ? 'selected' : '';

                        echo '<option value="' . $number . '" ' .
                            $selected . '>' .
                            $name .
                            '</option>';
                    }

                    ?>

                </select>

            </div>


            <!-- Year -->
            <div>

                <label
                    for="year"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Year
                </label>

                <select
                    id="year"
                    name="year"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                    <?php

                    $currentYear = (int) date('Y');

                    for (
                        $y = $currentYear;
                        $y >= $currentYear - 5;
                        $y--
                    ) {

                        $selected = ($y == $year) ? 'selected' : '';

                        echo '<option value="' . $y . '" ' .
                            $selected . '>' .
                            $y .
                            '</option>';
                    }

                    ?>

                </select>

            </div>


            <!-- Buttons -->
            <div class="md:col-span-2 flex items-end gap-2">

                <button
                    type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded"
                >
                    Show
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/farmer/mydana.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                >
                    Reset
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded"
                >
                    Print
                </button>

            </div>

        </form>

    </div>


    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                This Month Quantity
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($totalQuantity, 2); ?> kg
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                This Month Deduction
            </p>

            <p class="text-2xl font-bold mt-1">
                Rs. <?php echo number_format($totalAmount, 2); ?>
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                All-Time Deduction
            </p>

            <p class="text-2xl font-bold mt-1">
                Rs. <?php echo number_format($allTimeAmount, 2); ?>
            </p>

        </div>

    </div>


    <!-- Records -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-5 py-4 border-b bg-gray-50 flex justify-between">

            <h2 class="font-semibold text-gray-700">
                Records - <?php echo htmlspecialchars($monthName); ?>
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo count($records); ?> entries
            </span>

        </div>


        <?php if (count($records) == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No dana records for this month.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-right">Quantity</th>
                            <th class="px-4 py-3 text-right">Price</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Running Total</th>
                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php

                        $runningTotal = 0;
                        $number = 1;

                        foreach ($records as $row):

                            $runningTotal += $row['amount'];

                        ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo $number++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['quantity'], 2); ?> kg
                                </td>

                                <td class="px-4 py-3 text-right">
                                    Rs. <?php echo number_format($row['price_applied'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    Rs. <?php echo number_format($row['amount'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold">
                                    Rs. <?php echo number_format($runningTotal, 2); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>


                    <tfoot class="bg-gray-100 font-semibold">

                        <tr>

                            <td colspan="3" class="px-4 py-3 text-right">
                                TOTAL
                            </td>

                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($totalQuantity, 2); ?> kg
                            </td>

                            <td></td>

                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($totalAmount, 2); ?>
                            </td>

                            <td></td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>