<?php
/**
 * Staff Dashboard
 * 
 * Shows the staff member's own daily summary:
 *   - Today's milk entries and total litres (entered by this staff)
 *   - Today's dana entries and total quantity (entered by this staff)
 *   - This month's total entries
 *   - Recent entries made today
 *   - Quick links to entry forms and search
 * 
 * Access: Only staff. Enforced by requireRole('staff').
 * 
 * IMPORTANT: Staff must NOT see rupee amounts (rate, amount, net payable).
 * Only quantities (litres, FAT, SNF, dana kg) are shown here.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

// Get the logged-in staff user's ID
$staff_user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

// =====================================================
// 1. TODAY'S MILK STATS (this staff only)
// =====================================================
$sql = "SELECT COUNT(*) AS total, COALESCE(SUM(litre), 0) AS litres
        FROM milk_entries
        WHERE entered_by = $staff_user_id
          AND DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayMilkEntries = $row['total'];
$todayLitres = $row['litres'];

// =====================================================
// 2. TODAY'S DANA STATS (this staff only)
// =====================================================
$sql = "SELECT COUNT(*) AS total, COALESCE(SUM(quantity), 0) AS qty
        FROM dana_entries
        WHERE entered_by = $staff_user_id
          AND DATE(entry_date) = '$today'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$todayDanaEntries = $row['total'];
$todayDanaQty = $row['qty'];

// =====================================================
// 3. THIS MONTH'S TOTAL ENTRIES (milk + dana)
// =====================================================
$sql = "SELECT COUNT(*) AS total
        FROM milk_entries
        WHERE entered_by = $staff_user_id
          AND entry_date >= '$monthStart'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$monthMilkEntries = $row['total'];

$sql = "SELECT COUNT(*) AS total
        FROM dana_entries
        WHERE entered_by = $staff_user_id
          AND entry_date >= '$monthStart'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$monthDanaEntries = $row['total'];

$monthTotalEntries = $monthMilkEntries + $monthDanaEntries;

// =====================================================
// 4. RECENT ENTRIES (last 5 milk entries entered today)
// =====================================================
$sql = "SELECT m.entry_date, m.shift, m.litre, m.fat, m.snf,
               u.full_name AS farmer_name
        FROM milk_entries m
        JOIN farmers f ON m.farmer_id = f.id
        JOIN users u ON f.user_id = u.id
        WHERE m.entered_by = $staff_user_id
        ORDER BY m.id DESC
        LIMIT 5";
$recentMilk = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <!-- PAGE HEADING -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Staff Dashboard</h1>
        <p class="text-gray-600 mt-1">
            Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        </p>
    </div>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS (no rupee amounts shown)                -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <!-- Today's Milk -->
        <a href="/staff/entry-milk.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Today's Milk</p>
            <p class="text-2xl font-bold text-blue-600">
                <?php echo $todayMilkEntries; ?> entries
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo number_format($todayLitres, 2); ?> L total
            </p>
        </a>

        <!-- Today's Dana -->
        <a href="/staff/entry-dana.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Today's Dana</p>
            <p class="text-2xl font-bold text-green-600">
                <?php echo $todayDanaEntries; ?> entries
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo number_format($todayDanaQty, 2); ?> kg total
            </p>
        </a>

        <!-- This Month's Entries -->
        <a href="/staff/report.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">This Month</p>
            <p class="text-2xl font-bold text-purple-600">
                <?php echo $monthTotalEntries; ?> entries
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <?php echo $monthMilkEntries; ?> milk + <?php echo $monthDanaEntries; ?> dana
            </p>
        </a>

        <!-- Search Farmer -->
        <a href="/staff/search-farmer.php"
           class="block bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500 hover:shadow-md transition">
            <p class="text-sm text-gray-500 uppercase">Quick Search</p>
            <p class="text-2xl font-bold text-yellow-600">Find</p>
            <p class="text-sm text-gray-600 mt-1">Search farmer by ID or name</p>
        </a>

    </div>

    <!-- ===================================================== -->
    <!-- QUICK ACTION BUTTONS                                  -->
    <!-- ===================================================== -->
    <h2 class="text-lg font-semibold text-gray-700 mb-3">Quick Actions</h2>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="/staff/entry-milk.php"
           class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-3 px-4 rounded-lg text-center">
            Enter Milk
        </a>
        <a href="/staff/entry-dana.php"
           class="bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-4 rounded-lg text-center">
            Enter Dana
        </a>
        <a href="/staff/search-farmer.php"
           class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold py-3 px-4 rounded-lg text-center">
            Search Farmer
        </a>
        <a href="/staff/report.php"
           class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-3 px-4 rounded-lg text-center">
            Reports
        </a>
    </div>

    <!-- ===================================================== -->
    <!-- RECENT ENTRIES (last 5 milk entries by this staff)    -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">Recent Milk Entries</h2>
            <a href="/staff/report.php" class="text-sm text-blue-600 hover:underline">View All</a>
        </div>

        <?php if ($recentMilk->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries yet. Click "Enter Milk" to add one.
            </div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Shift</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $recentMilk->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">
                            <?php echo htmlspecialchars($row['farmer_name']); ?>
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
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>