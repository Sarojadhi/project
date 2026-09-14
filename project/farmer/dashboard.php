<?php
/**
 * Farmer Dashboard
 * 
 * Shows this farmer's own summary for the current month:
 *   - Total milk litres and amount
 *   - Total dana deduction
 *   - Net payable
 *   - Recent entries (last 5 milk entries)
 *   - Quick links to reports and dana pages
 * 
 * Access: Only farmers. Enforced by requireRole('farmer').
 * Scope: Every query filters by this farmer's own farmer_id — 
 *        no other farmer's data can leak here.
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

// Safety: if the user has role='farmer' but no farmers row, show a message
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

// Date ranges
$today      = date('Y-m-d');
$monthStart = date('Y-m-01');

// =====================================================
// 2. THIS MONTH'S MILK TOTALS
// =====================================================
$sql = "SELECT COALESCE(SUM(litre), 0)  AS litres,
               COALESCE(SUM(amount), 0) AS amount,
               COUNT(*)                 AS entry_count
        FROM milk_entries
        WHERE farmer_id = $farmer_id
          AND entry_date BETWEEN '$monthStart' AND '$today'";
$result = $conn->query($sql);
$milk = $result->fetch_assoc();

// =====================================================
// 3. THIS MONTH'S DANA TOTALS
// =====================================================
$sql = "SELECT COALESCE(SUM(quantity), 0) AS qty,
               COALESCE(SUM(amount), 0)   AS amount,
               COUNT(*)                   AS entry_count
        FROM dana_entries
        WHERE farmer_id = $farmer_id
          AND entry_date BETWEEN '$monthStart' AND '$today'";
$result = $conn->query($sql);
$dana = $result->fetch_assoc();

// =====================================================
// 4. NET PAYABLE
// =====================================================
$net_payable = $milk['amount'] - $dana['amount'];

// Pick a colour for net payable
if ($net_payable > 0) {
    $netColor = 'text-green-600';
} elseif ($net_payable < 0) {
    $netColor = 'text-red-600';
} else {
    $netColor = 'text-gray-600';
}

// =====================================================
// 5. RECENT MILK ENTRIES (last 5)
// =====================================================
$sql = "SELECT entry_date, shift, litre, fat, snf, amount
        FROM milk_entries
        WHERE farmer_id = $farmer_id
        ORDER BY entry_date DESC, id DESC
        LIMIT 5";
$recentMilk = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <!-- PAGE HEADING -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Farmer Dashboard</h1>
        <p class="text-gray-600 mt-1">
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            (Farmer ID: <?php echo $farmer_id; ?>)
        </p>
    </div>

    <p class="text-sm text-gray-500 mb-6">
        Showing summary for:
        <?php echo date('d-M-Y', strtotime($monthStart)); ?>
        to
        <?php echo date('d-M-Y', strtotime($today)); ?>
    </p>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS (clickable)                             -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Milk litres this month -->
        <a href="/farmer/myreport.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">This Month's Milk</p>
            <p class="text-2xl font-bold text-blue-600">
                <?php echo number_format($milk['litres'], 2); ?> L
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo $milk['entry_count']; ?> entries
            </p>
        </a>

        <!-- Milk amount -->
        <a href="/farmer/myreport.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Milk Amount</p>
            <p class="text-2xl font-bold text-green-600">
                Rs. <?php echo number_format($milk['amount'], 2); ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">Total earned this month</p>
        </a>

        <!-- Dana deduction -->
        <a href="/farmer/mydana.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-red-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Dana Deduction</p>
            <p class="text-2xl font-bold text-red-600">
                Rs. <?php echo number_format($dana['amount'], 2); ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo number_format($dana['qty'], 2); ?> kg
                (<?php echo $dana['entry_count']; ?> entries)
            </p>
        </a>

        <!-- Net payable -->
        <a href="/farmer/myreport.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Net Payable</p>
            <p class="text-2xl font-bold <?php echo $netColor; ?>">
                Rs. <?php echo number_format($net_payable, 2); ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">Milk − Dana deduction</p>
        </a>

    </div>

    <!-- ===================================================== -->
    <!-- QUICK ACTION BUTTONS                                  -->
    <!-- ===================================================== -->
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Quick Links</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <a href="/farmer/myreport.php"
           class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">
            My Milk Reports
        </a>
        <a href="/farmer/mydana.php"
           class="bg-red-100 hover:bg-red-200 text-red-800 font-semibold py-3 px-4 rounded-lg text-center">
            My Dana Records
        </a>
    </div>

    <!-- ===================================================== -->
    <!-- RECENT MILK ENTRIES                                   -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">Recent Milk Entries</h2>
            <a href="/farmer/myreport.php" class="text-sm text-blue-600 hover:underline">
                View All
            </a>
        </div>

        <?php if ($recentMilk->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries recorded yet.
            </div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Shift</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $recentMilk->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                <?php echo ($row['shift'] === 'morning') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo ucfirst($row['shift']); ?>
                            </span>
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
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            Rs. <?php echo number_format($row['amount'], 2); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>