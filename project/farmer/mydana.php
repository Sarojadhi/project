<?php
/**
 * Farmer - My Dana / Chowker Records
 * 
 * Shows this farmer's dana/chowker records for a selected month:
 *   - Date, item, quantity, price, amount
 *   - Running total column (shows cumulative deduction through the month)
 *   - Summary cards: total quantity and total deduction
 *   - Month/year filter
 *   - Print button
 * 
 * Access: Only farmers. Enforced by requireRole('farmer').
 * Scope: Only this farmer's own data — every query filters by farmer_id.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');

// =====================================================
// 1. FIND THIS FARMER'S ID
// =====================================================
$user_id = $_SESSION['user_id'];

$sql = "SELECT id FROM farmers WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// Safety: farmer profile must exist
if (!$row) {
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-red-100 border border-red-300 text-red-800 rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-2">Profile Not Found</h1>
            <p>Your farmer profile has not been set up. Please contact the dairy admin.</p>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$farmer_id = $row['id'];

// =====================================================
// 2. READ MONTH AND YEAR FROM URL
// =====================================================
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// Keep month/year within sensible bounds
if ($month < 1 || $month > 12) {
    $month = (int)date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = (int)date('Y');
}

// First and last day of the selected month
$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day  = date('Y-m-t', strtotime($first_day));

// Human-readable month name
$monthName = date('F Y', strtotime($first_day));

// =====================================================
// 3. LOAD DANA ENTRIES FOR THIS FARMER AND MONTH
// =====================================================
$sql = "SELECT id, entry_date, item_name, quantity, price_applied, amount
        FROM dana_entries
        WHERE farmer_id = $farmer_id
          AND entry_date BETWEEN '$first_day' AND '$last_day'
        ORDER BY entry_date ASC, id ASC";

$result = $conn->query($sql);

// We'll store rows and totals while looping
$rows = array();
$totalQty    = 0;
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $totalQty    += $row['quantity'];
    $totalAmount += $row['amount'];
}

// =====================================================
// 4. ALL-TIME TOTAL DEDUCTION (for extra context)
// =====================================================
$sql = "SELECT COALESCE(SUM(amount), 0) AS all_time
        FROM dana_entries
        WHERE farmer_id = $farmer_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$allTimeDeduction = $row['all_time'];

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-2">My Dana / Chowker Records</h1>
    <p class="text-gray-600 mb-6">
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        (Farmer ID: <?php echo $farmer_id; ?>)
    </p>

    <!-- ===================================================== -->
    <!-- FILTER FORM                                           -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Month -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Month</label>
                <select name="month" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <?php
                    $months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');

                    foreach ($months as $num => $name) {
                        $sel = ($num == $month) ? 'selected' : '';
                        echo '<option value="' . $num . '" ' . $sel . '>' . $name . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Year -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Year</label>
                <select name="year" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <?php
                    $current = (int)date('Y');
                    for ($y = $current; $y >= $current - 5; $y--) {
                        $sel = ($y == $year) ? 'selected' : '';
                        echo '<option value="' . $y . '" ' . $sel . '>' . $y . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Show
                </button>
                <a href="/farmer/mydana.php"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Reset
                </a>
                <button type="button" onclick="window.print()"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Print
                </button>
            </div>

        </form>

    </div>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS                                         -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 uppercase">This Month's Quantity</p>
            <p class="text-2xl font-bold text-red-600">
                <?php echo number_format($totalQty, 2); ?> kg
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500 uppercase">This Month's Deduction</p>
            <p class="text-2xl font-bold text-orange-600">
                Rs. <?php echo number_format($totalAmount, 2); ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Total Deduction (All Time)</p>
            <p class="text-2xl font-bold text-purple-600">
                Rs. <?php echo number_format($allTimeDeduction, 2); ?>
            </p>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- DANA TABLE                                            -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">
                Dana Records for <?php echo $monthName; ?>
            </h2>
            <span class="text-sm text-gray-500"><?php echo count($rows); ?> entries</span>
        </div>

        <?php if (count($rows) == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No dana records found for <?php echo $monthName; ?>.
            </div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity (kg)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price (Rs./kg)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (Rs.)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Running Total (Rs.)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php
                $running = 0;
                $rowNum = 1;
                foreach ($rows as $row):
                    $running += $row['amount'];
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $rowNum; ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">
                            <?php echo htmlspecialchars($row['item_name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['quantity'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['price_applied'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['amount'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">
                            <?php echo number_format($running, 2); ?>
                        </td>
                    </tr>
                <?php
                    $rowNum++;
                endforeach;
                ?>

                </tbody>
                <tfoot class="bg-gray-100 font-semibold">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right">TOTAL</td>
                        <td class="px-4 py-3 text-right">
                            <?php echo number_format($totalQty, 2); ?>
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-right">
                            <?php echo number_format($totalAmount, 2); ?>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- HELP NOTE                                             -->
    <!-- ===================================================== -->
    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
        <p class="font-semibold mb-1">Note:</p>
        <p>This deduction is subtracted from your total milk amount when calculating your net payable. See your dashboard for net payable details.</p>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>