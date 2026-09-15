<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];

$today = date('Y-m-d');
$firstDay = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));


// Today's milk
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_entries,
            COALESCE(SUM(litre), 0) AS total_litres
     FROM milk_entries
     WHERE entered_by = ?
     AND entry_date = ?"
);

$stmt->bind_param('is', $userId, $today);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$todayMilkEntries = $row['total_entries'];
$todayMilkLitres = $row['total_litres'];


// Today's dana
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total_entries,
            COALESCE(SUM(quantity), 0) AS total_quantity
     FROM dana_entries
     WHERE entered_by = ?
     AND entry_date = ?"
);

$stmt->bind_param('is', $userId, $today);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$todayDanaEntries = $row['total_entries'];
$todayDanaQuantity = $row['total_quantity'];


// This month's milk entries
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM milk_entries
     WHERE entered_by = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $userId, $firstDay, $nextMonth);
$stmt->execute();

$monthMilk = $stmt->get_result()->fetch_assoc()['total'];


// This month's dana entries
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM dana_entries
     WHERE entered_by = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $userId, $firstDay, $nextMonth);
$stmt->execute();

$monthDana = $stmt->get_result()->fetch_assoc()['total'];

$monthTotal = $monthMilk + $monthDana;


// Last 7 days milk
$chartData = array();

for ($i = 6; $i >= 0; $i--) {

    $date = date('Y-m-d', strtotime("-$i days"));

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(litre), 0) AS total
         FROM milk_entries
         WHERE entered_by = ?
         AND entry_date = ?"
    );

    $stmt->bind_param('is', $userId, $date);
    $stmt->execute();

    $litres = $stmt->get_result()->fetch_assoc()['total'];

    $chartData[] = array(
        'date' => date('d M', strtotime($date)),
        'litres' => (float) $litres
    );
}


// Find maximum litres for chart
$maxLitres = 1;

foreach ($chartData as $item) {

    if ($item['litres'] > $maxLitres) {
        $maxLitres = $item['litres'];
    }
}


// Recent milk entries
$stmt = $conn->prepare(
    "SELECT
        m.entry_date,
        m.shift,
        m.litre,
        m.fat,
        m.snf,
        u.full_name AS farmer_name
     FROM milk_entries m
     JOIN farmers f ON m.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     WHERE m.entered_by = ?
     ORDER BY m.id DESC
     LIMIT 8"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$recent = $stmt->get_result();

include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page heading -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Staff Dashboard
        </h1>

        <p class="text-gray-500 mt-1">
            Welcome, <?php echo e($_SESSION['full_name']); ?>
        </p>

    </div>


    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <!-- Today's Milk -->
        <div class="bg-white border rounded-lg p-4">

            <p class="text-sm text-gray-500">
                Today's Milk
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo $todayMilkEntries; ?>
            </p>

            <p class="text-sm text-gray-500 mt-1">
                <?php echo number_format($todayMilkLitres, 1); ?> L
            </p>

        </div>


        <!-- Today's Dana -->
        <div class="bg-white border rounded-lg p-4">

            <p class="text-sm text-gray-500">
                Today's Dana
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo $todayDanaEntries; ?>
            </p>

            <p class="text-sm text-gray-500 mt-1">
                <?php echo number_format($todayDanaQuantity, 1); ?> kg
            </p>

        </div>


        <!-- This Month -->
        <div class="bg-white border rounded-lg p-4">

            <p class="text-sm text-gray-500">
                This Month
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo $monthTotal; ?>
            </p>

            <p class="text-sm text-gray-500 mt-1">
                <?php echo $monthMilk; ?> milk +
                <?php echo $monthDana; ?> dana
            </p>

        </div>


        <!-- Quick Links -->
        <div class="bg-white border rounded-lg p-4">

            <p class="text-sm text-gray-500">
                Quick Links
            </p>

            <div class="mt-2 space-y-1">

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-milk.php"
                    class="block text-blue-600 hover:underline text-sm"
                >
                    Add Milk
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-dana.php"
                    class="block text-green-600 hover:underline text-sm"
                >
                    Add Dana
                </a>

            </div>

        </div>

    </div>


    <!-- Chart and Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Chart -->
        <div class="lg:col-span-2 bg-white border rounded-lg">

            <div class="px-5 py-4 border-b">

                <h2 class="font-semibold text-gray-800">
                    My 7-Day Milk
                </h2>

            </div>

            <div class="p-5">

                <div class="flex items-end gap-3 h-48">

                    <?php foreach ($chartData as $item): ?>

                        <?php

                        $height = ($item['litres'] / $maxLitres) * 100;

                        if ($height < 3 && $item['litres'] > 0) {
                            $height = 3;
                        }

                        ?>

                        <div
                            class="flex-1 flex flex-col items-center justify-end h-full"
                        >

                            <span class="text-xs text-gray-500 mb-1">
                                <?php echo number_format($item['litres'], 0); ?>L
                            </span>

                            <div
                                class="w-full max-w-12 bg-blue-500 rounded-t"
                                style="height: <?php echo $height; ?>%;"
                            ></div>

                            <span class="text-xs text-gray-500 mt-2">
                                <?php echo e($item['date']); ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>


        <!-- Quick Actions -->
        <div class="bg-white border rounded-lg">

            <div class="px-5 py-4 border-b">

                <h2 class="font-semibold text-gray-800">
                    Quick Actions
                </h2>

            </div>

            <div class="p-4 space-y-2">

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-milk.php"
                    class="block bg-blue-100 text-blue-800 text-center py-2 rounded hover:bg-blue-200"
                >
                    Enter Milk
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-dana.php"
                    class="block bg-green-100 text-green-800 text-center py-2 rounded hover:bg-green-200"
                >
                    Enter Dana
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/staff/search-farmer.php"
                    class="block bg-yellow-100 text-yellow-800 text-center py-2 rounded hover:bg-yellow-200"
                >
                    Search Farmer
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/staff/report.php"
                    class="block bg-purple-100 text-purple-800 text-center py-2 rounded hover:bg-purple-200"
                >
                    Reports
                </a>

            </div>

        </div>

    </div>


    <!-- Recent Milk Entries -->
    <div class="bg-white border rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                My Recent Milk Entries
            </h2>

            <a
                href="<?php echo BASE_URL; ?>/staff/report.php"
                class="text-sm text-blue-600 hover:underline"
            >
                View All
            </a>

        </div>


        <?php if ($recent && $recent->num_rows > 0): ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer
                            </th>

                            <th class="px-4 py-3 text-center">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right">
                                Litres
                            </th>

                            <th class="px-4 py-3 text-right">
                                FAT
                            </th>

                            <th class="px-4 py-3 text-right">
                                SNF
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php while ($row = $recent->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo e($row['farmer_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <?php echo e(ucfirst($row['shift'])); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['litre'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['fat'], 1); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['snf'], 1); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries yet.
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>