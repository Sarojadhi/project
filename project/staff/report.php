<?php
/**
 * Staff - Reports
 * 
 * Shows quantity reports for staff:
 *   - Milk entries (date, shift, farmer, litres, FAT, SNF)
 *   - Dana entries (date, farmer, item, quantity)
 * 
 * Filters:
 *   - Date From / Date To
 *   - Shift (All / Morning / Evening) — applies to milk only
 *   - Search by farmer ID, username, or full name
 * 
 * IMPORTANT: No rupee amounts are shown — staff cannot see financial data.
 * 
 * Access: Only staff. Enforced by requireRole('staff').
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

// =====================================================
// 1. READ FILTER VALUES
// =====================================================
$date_from = isset($_GET['date_from']) && $_GET['date_from'] != ''
           ? $_GET['date_from'] : date('Y-m-01');

$date_to   = isset($_GET['date_to']) && $_GET['date_to'] != ''
           ? $_GET['date_to'] : date('Y-m-d');

$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$shift     = isset($_GET['shift'])  ? $_GET['shift'] : 'all';

// =====================================================
// 2. BUILD THE SEARCH FRAGMENT
// =====================================================
// Numeric  → farmer ID
// Text     → username or full name (partial)
// Else     → no filter

$searchFragment = "";

if ($search != '') {
    if (is_numeric($search)) {
        $searchFragment = " AND f.id = " . (int)$search;
    } else {
        $safe = $conn->real_escape_string($search);
        $searchFragment = " AND (u.username LIKE '%$safe%' OR u.full_name LIKE '%$safe%')";
    }
}

// =====================================================
// 3. MILK QUERY (with optional shift filter)
// =====================================================
$milkShiftFragment = "";

if ($shift === 'morning' || $shift === 'evening') {
    $milkShiftFragment = " AND m.shift = '$shift'";
}

$milkSql = "SELECT m.entry_date, m.shift, m.litre, m.fat, m.snf,
                   u.full_name, u.username, f.id AS farmer_id
            FROM milk_entries m
            JOIN farmers f ON m.farmer_id = f.id
            JOIN users u ON f.user_id = u.id
            WHERE DATE(m.entry_date) BETWEEN '$date_from' AND '$date_to'
              $milkShiftFragment
              $searchFragment
            ORDER BY m.entry_date DESC, m.id DESC
            LIMIT 500";

$milkResult = $conn->query($milkSql);

// =====================================================
// 4. DANA QUERY (no shift — dana entries have no shift)
// =====================================================
$danaSql = "SELECT d.entry_date, d.item_name, d.quantity,
                   u.full_name, u.username, f.id AS farmer_id
            FROM dana_entries d
            JOIN farmers f ON d.farmer_id = f.id
            JOIN users u ON f.user_id = u.id
            WHERE DATE(d.entry_date) BETWEEN '$date_from' AND '$date_to'
              $searchFragment
            ORDER BY d.entry_date DESC, d.id DESC
            LIMIT 500";

$danaResult = $conn->query($danaSql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Reports</h1>

    <!-- ===================================================== -->
    <!-- FILTER FORM                                           -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div>
                <label class="block text-sm font-medium text-gray-700">Date From</label>
                <input type="date" name="date_from"
                       value="<?php echo htmlspecialchars($date_from); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date To</label>
                <input type="date" name="date_to"
                       value="<?php echo htmlspecialchars($date_to); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Shift (Milk)</label>
                <select name="shift" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <option value="all"     <?php echo ($shift === 'all')     ? 'selected' : ''; ?>>All Shifts</option>
                    <option value="morning" <?php echo ($shift === 'morning') ? 'selected' : ''; ?>>Morning</option>
                    <option value="evening" <?php echo ($shift === 'evening') ? 'selected' : ''; ?>>Evening</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="ID / Username / Name"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Show
                </button>
                <a href="/staff/report.php"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Reset
                </a>
            </div>

        </form>

    </div>

    <!-- Print button -->
    <div class="mb-4">
        <button type="button" onclick="window.print()"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Print
        </button>
    </div>

    <!-- ===================================================== -->
    <!-- MILK REPORT TABLE                                     -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">
                Milk Report
                (<?php echo date('d-M-Y', strtotime($date_from)); ?>
                 to <?php echo date('d-M-Y', strtotime($date_to)); ?>)
            </h2>
            <span class="text-sm text-gray-500"><?php echo $milkResult->num_rows; ?> entries</span>
        </div>

        <?php if ($milkResult->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries found for the selected filters.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Shift</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                    <?php
                    $totalLitres = 0;
                    while ($row = $milkResult->fetch_assoc()):
                        $totalLitres += $row['litre'];
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo $row['farmer_id']; ?></td>
                            <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?php echo ($row['shift'] === 'morning') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($row['shift']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right"><?php echo number_format($row['litre'], 2); ?></td>
                            <td class="px-4 py-3 text-sm text-right"><?php echo number_format($row['fat'], 1); ?></td>
                            <td class="px-4 py-3 text-sm text-right"><?php echo number_format($row['snf'], 1); ?></td>
                        </tr>
                    <?php endwhile; ?>

                    </tbody>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right">TOTAL</td>
                            <td class="px-4 py-3 text-right"><?php echo number_format($totalLitres, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- DANA REPORT TABLE                                     -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">
                Dana / Chowker Report
                (<?php echo date('d-M-Y', strtotime($date_from)); ?>
                 to <?php echo date('d-M-Y', strtotime($date_to)); ?>)
            </h2>
            <span class="text-sm text-gray-500"><?php echo $danaResult->num_rows; ?> entries</span>
        </div>

        <?php if ($danaResult->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No dana entries found for the selected filters.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                    <?php
                    $totalQty = 0;
                    while ($row = $danaResult->fetch_assoc()):
                        $totalQty += $row['quantity'];
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo $row['farmer_id']; ?></td>
                            <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-right"><?php echo number_format($row['quantity'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>

                    </tbody>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right">TOTAL</td>
                            <td class="px-4 py-3 text-right"><?php echo number_format($totalQty, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>