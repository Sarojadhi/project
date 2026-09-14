<?php
/**
 * Admin - Farmer Full Ledger (Single Farmer)
 * 
 * Shows the complete financial and transaction history for ONE farmer:
 *   - Farmer profile (name, username, phone, address, join date)
 *   - Summary cards: milk litres, milk amount, dana deduction, net payable
 *   - Table of all milk entries
 *   - Table of all dana entries
 *   - Totals at the bottom of each table
 * 
 * Access: Only admins. Enforced by requireRole('admin').
 * 
 * Note: $conn and session are loaded by auth.php.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

// =====================================================
// 1. READ FARMER ID FROM URL
// =====================================================
// If no ID is given, send the admin back to the report page.

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /admin/report.php');
    exit;
}

// =====================================================
// 2. FETCH FARMER DETAILS
// =====================================================
$sql = "SELECT f.id, f.address, f.join_date, f.status,
               u.username, u.full_name, u.phone
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        WHERE f.id = $id";
$result = $conn->query($sql);
$farmer = $result->fetch_assoc();

// If the farmer does not exist, show a styled error page.
if (!$farmer) {
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-red-100 border border-red-300 text-red-800 rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-2">Farmer Not Found</h1>
            <p class="mb-4">No farmer exists with ID <?php echo $id; ?>.</p>
            <a href="/admin/report.php" class="text-blue-600 hover:underline">
                &larr; Back to Farmer Ledger
            </a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// =====================================================
// 3. FETCH ALL MILK ENTRIES FOR THIS FARMER
// =====================================================
$sql = "SELECT id, entry_date, shift, litre, fat, snf, rate_applied, amount
        FROM milk_entries
        WHERE farmer_id = $id
        ORDER BY entry_date DESC, id DESC";
$result = $conn->query($sql);

$milkRows = array();
$totalLitres = 0;
$totalMilkAmount = 0;

while ($row = $result->fetch_assoc()) {
    $milkRows[] = $row;
    $totalLitres += $row['litre'];
    $totalMilkAmount += $row['amount'];
}

// =====================================================
// 4. FETCH ALL DANA ENTRIES FOR THIS FARMER
// =====================================================
$sql = "SELECT id, entry_date, item_name, quantity, price_applied, amount
        FROM dana_entries
        WHERE farmer_id = $id
        ORDER BY entry_date DESC, id DESC";
$result = $conn->query($sql);

$danaRows = array();
$totalDanaQty = 0;
$totalDanaAmount = 0;

while ($row = $result->fetch_assoc()) {
    $danaRows[] = $row;
    $totalDanaQty += $row['quantity'];
    $totalDanaAmount += $row['amount'];
}

// =====================================================
// 5. CALCULATE NET PAYABLE
// =====================================================
$netPayable = $totalMilkAmount - $totalDanaAmount;

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <!-- Back link -->
    <a href="/admin/report.php" class="text-blue-600 hover:underline">
        &larr; Back to Farmer Ledger
    </a>

    <!-- ===================================================== -->
    <!-- FARMER PROFILE                                        -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mt-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <?php echo htmlspecialchars($farmer['full_name']); ?>
        </h1>
        <div class="mt-2 text-gray-600 space-y-1">
            <p><span class="font-semibold">Farmer ID:</span> <?php echo $farmer['id']; ?></p>
            <p><span class="font-semibold">Username:</span> <?php echo htmlspecialchars($farmer['username']); ?></p>
            <p><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($farmer['phone']); ?></p>
            <p><span class="font-semibold">Address:</span> <?php echo htmlspecialchars($farmer['address']); ?></p>
            <p><span class="font-semibold">Join Date:</span>
                <?php echo date('d-M-Y', strtotime($farmer['join_date'])); ?>
            </p>
            <p>
                <span class="font-semibold">Status:</span>
                <span class="px-2 py-1 text-xs rounded-full
                    <?php echo ($farmer['status'] === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo ucfirst($farmer['status']); ?>
                </span>
            </p>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS                                         -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase">Total Milk</p>
            <p class="text-2xl font-bold text-blue-600">
                <?php echo number_format($totalLitres, 2); ?> L
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase">Milk Amount</p>
            <p class="text-2xl font-bold text-green-600">
                Rs. <?php echo number_format($totalMilkAmount, 2); ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 uppercase">Dana Deduction</p>
            <p class="text-2xl font-bold text-red-600">
                Rs. <?php echo number_format($totalDanaAmount, 2); ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo number_format($totalDanaQty, 2); ?> kg
            </p>
        </div>

        <?php
        // Pick a colour for net payable
        if ($netPayable > 0) {
            $netColor = 'text-green-600';
        } elseif ($netPayable < 0) {
            $netColor = 'text-red-600';
        } else {
            $netColor = 'text-gray-600';
        }
        ?>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Net Payable</p>
            <p class="text-2xl font-bold <?php echo $netColor; ?>">
                Rs. <?php echo number_format($netPayable, 2); ?>
            </p>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- MILK ENTRIES TABLE                                    -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">Milk Entries</h2>
            <span class="text-sm text-gray-500"><?php echo count($milkRows); ?> entries</span>
        </div>

        <?php if (count($milkRows) == 0): ?>

            <div class="p-6 text-center text-gray-500">No milk entries yet.</div>

        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php foreach ($milkRows as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo ucfirst($row['shift']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['litre'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['fat'], 1); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['snf'], 1); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['rate_applied'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    Rs. <?php echo number_format($row['amount'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right">TOTAL</td>
                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($totalLitres, 2); ?>
                            </td>
                            <td colspan="2"></td>
                            <td></td>
                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($totalMilkAmount, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- DANA ENTRIES TABLE                                    -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">Dana / Chowker Entries</h2>
            <span class="text-sm text-gray-500"><?php echo count($danaRows); ?> entries</span>
        </div>

        <?php if (count($danaRows) == 0): ?>

            <div class="p-6 text-center text-gray-500">No dana entries yet.</div>

        <?php else: ?>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity (kg)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price (Rs./kg)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                        <?php foreach ($danaRows as $row): ?>
                            <tr class="hover:bg-gray-50">
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
                                <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">
                                    Rs. <?php echo number_format($row['amount'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                    <tfoot class="bg-gray-100 font-semibold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right">TOTAL</td>
                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($totalDanaQty, 2); ?>
                            </td>
                            <td></td>
                            <td class="px-4 py-3 text-right text-red-600">
                                Rs. <?php echo number_format($totalDanaAmount, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>