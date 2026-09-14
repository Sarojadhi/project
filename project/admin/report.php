<?php
/**
 * Admin - Farmer Ledger Report
 * 
 * Shows a complete financial ledger for every farmer:
 *   - Total milk litres and amount
 *   - Total dana quantity and amount
 *   - Net payable (milk amount − dana amount)
 * 
 * Features:
 *   - Filter by date range
 *   - Search by farmer ID, username, or name
 *   - Grand totals row
 *   - Print button
 * 
 * Access: Only admins. Enforced by requireRole('admin') below.
 * 
 * Note: $conn and session are loaded by auth.php.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// =====================================================
// 1. READ FILTER VALUES FROM URL
// =====================================================
// Default date range: first day of this month → today

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to   = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
$search    = isset($_GET['search'])    ? trim($_GET['search']) : '';

// =====================================================
// 2. BUILD THE SEARCH FILTER (optional)
// =====================================================
// If search is numeric  → match farmer ID
// If search is text     → match username or full name
// Otherwise             → no filter

$searchSql = "";

if ($search !== '') {
    if (is_numeric($search)) {
        $searchSql = " AND f.id = " . (int)$search;
    } else {
        // Simple LIKE search — safe enough for a college project
        $safeSearch = $conn->real_escape_string($search);
        $searchSql = " AND (u.username LIKE '%$safeSearch%' OR u.full_name LIKE '%$safeSearch%')";
    }
}

// =====================================================
// 3. MAIN QUERY — one row per farmer with totals
// =====================================================
// LEFT JOINs ensure farmers with no milk or no dana still appear
// COALESCE(..., 0) turns NULLs into 0 so totals are clean

$sql = "SELECT
            f.id AS farmer_id,
            u.username,
            u.full_name,
            COALESCE(SUM(m.litre), 0)   AS total_litres,
            COALESCE(SUM(m.amount), 0)  AS milk_amount,
            COALESCE(SUM(d.quantity), 0) AS dana_quantity,
            COALESCE(SUM(d.amount), 0)  AS dana_amount
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        LEFT JOIN milk_entries m
               ON m.farmer_id = f.id
               AND m.entry_date BETWEEN '$date_from' AND '$date_to'
        LEFT JOIN dana_entries d
               ON d.farmer_id = f.id
               AND d.entry_date BETWEEN '$date_from' AND '$date_to'
        WHERE u.role = 'farmer'
        $searchSql
        GROUP BY f.id
        ORDER BY u.full_name ASC";

$result = $conn->query($sql);

// =====================================================
// 4. READ ALL ROWS INTO AN ARRAY + CALCULATE GRAND TOTALS
// =====================================================
// We loop through once and store everything so we can:
//   a) display the table
//   b) show grand totals in the footer

$rows = array();

$grand_litres   = 0;
$grand_milk     = 0;
$grand_dana_qty = 0;
$grand_dana_amt = 0;
$grand_net      = 0;

while ($row = $result->fetch_assoc()) {

    // Compute net payable for this farmer
    $row['net_payable'] = $row['milk_amount'] - $row['dana_amount'];

    // Add to grand totals
    $grand_litres   += $row['total_litres'];
    $grand_milk     += $row['milk_amount'];
    $grand_dana_qty += $row['dana_quantity'];
    $grand_dana_amt += $row['dana_amount'];
    $grand_net      += $row['net_payable'];

    $rows[] = $row;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Farmer Ledger</h1>

    <!-- ===================================================== -->
    <!-- FILTER FORM                                           -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                <input type="date" id="date_from" name="date_from"
                       value="<?php echo htmlspecialchars($date_from); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                <input type="date" id="date_to" name="date_to"
                       value="<?php echo htmlspecialchars($date_to); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700">
                    Search (ID / Username / Name)
                </label>
                <input type="text" id="search" name="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="e.g. 5 or ram123 or Ram"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Show
                </button>
                <a href="report.php"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- ===================================================== -->
    <!-- PRINT BUTTON                                          -->
    <!-- ===================================================== -->
    <div class="mb-4">
        <button type="button"
                onclick="window.print()"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Print
        </button>
    </div>

    <!-- ===================================================== -->
    <!-- LEDGER TABLE                                          -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">
                Ledger from <?php echo date('d-M-Y', strtotime($date_from)); ?>
                to <?php echo date('d-M-Y', strtotime($date_to)); ?>
            </h2>
            <span class="text-sm text-gray-500"><?php echo count($rows); ?> farmers</span>
        </div>

        <?php if (count($rows) == 0): ?>

            <div class="p-8 text-center text-gray-500">
                <p>No farmers match the current filters.</p>
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Milk Litres</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Milk Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dana Qty (kg)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dana Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Payable</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php foreach ($rows as $row): ?>

                            <?php
                            // Choose a colour for the net payable cell
                            if ($row['net_payable'] > 0) {
                                $netClass = 'text-green-600';
                            } elseif ($row['net_payable'] < 0) {
                                $netClass = 'text-red-600';
                            } else {
                                $netClass = 'text-gray-500';
                            }
                            ?>

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm"><?php echo $row['farmer_id']; ?></td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['total_litres'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    Rs. <?php echo number_format($row['milk_amount'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['dana_quantity'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    Rs. <?php echo number_format($row['dana_amount'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-bold <?php echo $netClass; ?>">
                                    Rs. <?php echo number_format($row['net_payable'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <a href="/admin/view-farmer.php?id=<?php echo $row['farmer_id']; ?>"
                                       class="text-blue-600 hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                    <!-- GRAND TOTALS ROW -->
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right">GRAND TOTAL</td>
                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($grand_litres, 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grand_milk, 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($grand_dana_qty, 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grand_dana_amt, 2); ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grand_net, 2); ?>
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>