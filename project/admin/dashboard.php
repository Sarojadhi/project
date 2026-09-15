<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Active farmers
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM farmers
     WHERE status = 'active'"
);

$totalFarmers = $result->fetch_assoc()['total'];


// Today's milk
$result = $conn->query(
    "SELECT
        COALESCE(SUM(litre), 0) AS litres,
        COUNT(*) AS entries
     FROM milk_entries
     WHERE entry_date = CURDATE()"
);

$todayMilk = $result->fetch_assoc();


// Current month milk revenue
$result = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM milk_entries
     WHERE entry_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     AND entry_date < DATE_ADD(
         DATE_FORMAT(CURDATE(), '%Y-%m-01'),
         INTERVAL 1 MONTH
     )"
);

$monthRevenue = $result->fetch_assoc()['total'];


// Current month dana
$result = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM dana_entries
     WHERE entry_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
     AND entry_date < DATE_ADD(
         DATE_FORMAT(CURDATE(), '%Y-%m-01'),
         INTERVAL 1 MONTH
     )"
);

$monthDana = $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| 7-Day Milk Collection
|--------------------------------------------------------------------------
*/

$chartData = array();

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        'Y-m-d',
        strtotime("-$i days")
    );

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(litre), 0) AS litres
         FROM milk_entries
         WHERE entry_date = ?"
    );

    $stmt->bind_param('s', $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $chartData[] = array(
        'date' => date('D', strtotime($date)),
        'litres' => $row['litres']
    );
}


// Find highest value for chart height
$maxLitres = 1;

foreach ($chartData as $item) {

    if ($item['litres'] > $maxLitres) {
        $maxLitres = $item['litres'];
    }
}


/*
|--------------------------------------------------------------------------
| Recent Milk Collections
|--------------------------------------------------------------------------
*/

$recent = $conn->query(
    "SELECT
        m.entry_date,
        m.shift,
        m.litre,
        m.amount,
        u.full_name AS farmer_name
     FROM milk_entries m
     JOIN farmers f ON m.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     ORDER BY m.id DESC
     LIMIT 8"
);


include __DIR__ . '/../includes/header.php';
?>


<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page title -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Admin Dashboard
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Overview of dairy operations
        </p>

    </div>


    <!-- Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <!-- Farmers -->
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Active Farmers
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo $totalFarmers; ?>
            </p>

        </div>


        <!-- Today's Milk -->
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Today's Milk
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo number_format($todayMilk['litres'], 1); ?> L
            </p>

            <p class="text-xs text-gray-500 mt-1">
                <?php echo $todayMilk['entries']; ?> entries
            </p>

        </div>


        <!-- Month Revenue -->
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Month Revenue
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                Rs. <?php echo number_format($monthRevenue, 0); ?>
            </p>

        </div>


        <!-- Month Dana -->
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Month Dana
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                Rs. <?php echo number_format($monthDana, 0); ?>
            </p>

        </div>

    </div>


    <!-- Chart + Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Milk Chart -->
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg shadow-sm">

            <div class="px-5 py-4 border-b border-gray-200">

                <h2 class="font-semibold text-gray-800">
                    7-Day Milk Collection
                </h2>

            </div>


            <div class="p-5">

                <div class="flex items-end gap-3 h-52">

                    <?php foreach ($chartData as $item): ?>

                        <?php

                        $height = ($item['litres'] / $maxLitres) * 100;

                        if ($height < 3 && $item['litres'] > 0) {
                            $height = 3;
                        }

                        ?>

                        <div class="flex-1 flex flex-col items-center justify-end h-full">

                            <span class="text-xs text-gray-500 mb-1">
                                <?php echo number_format($item['litres'], 0); ?>L
                            </span>

                            <div
                                class="w-full max-w-12 bg-blue-500 rounded-t"
                                style="height: <?php echo $height; ?>%;"
                            ></div>

                            <span class="text-xs text-gray-500 mt-2">
                                <?php echo htmlspecialchars($item['date']); ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>


        <!-- Quick Actions -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">

            <div class="px-5 py-4 border-b border-gray-200">

                <h2 class="font-semibold text-gray-800">
                    Quick Actions
                </h2>

            </div>


            <div class="p-5 space-y-3">

                <a
                    href="<?php echo BASE_URL; ?>/admin/manage-staff.php"
                    class="block text-center bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium py-2.5 rounded-md"
                >
                    Manage Staff
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/admin/manage-farmer.php"
                    class="block text-center bg-green-50 hover:bg-green-100 text-green-700 font-medium py-2.5 rounded-md"
                >
                    Manage Farmers
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/admin/rate-setting.php"
                    class="block text-center bg-amber-50 hover:bg-amber-100 text-amber-700 font-medium py-2.5 rounded-md"
                >
                    Rate Settings
                </a>


                <a
                    href="<?php echo BASE_URL; ?>/admin/report.php"
                    class="block text-center bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium py-2.5 rounded-md"
                >
                    Farmer Ledger
                </a>

            </div>

        </div>

    </div>


    <!-- Recent Collections -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                Recent Collections
            </h2>

            <a
                href="<?php echo BASE_URL; ?>/admin/report.php"
                class="text-sm text-blue-600 hover:underline"
            >
                View All
            </a>

        </div>


        <?php if ($recent->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500 text-sm">
                No milk entries yet.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Farmer
                            </th>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Date
                            </th>

                            <th class="px-4 py-3 text-center text-gray-600">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right text-gray-600">
                                Litres
                            </th>

                            <th class="px-4 py-3 text-right text-gray-600">
                                Amount
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php while ($row = $recent->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <?php echo htmlspecialchars($row['farmer_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-gray-600">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 text-center">

                                    <?php if ($row['shift'] === 'morning'): ?>

                                        <span class="px-2 py-1 text-xs rounded bg-amber-100 text-amber-800">
                                            Morning
                                        </span>

                                    <?php else: ?>

                                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                            Evening
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="px-4 py-3 text-right font-medium">
                                    <?php echo number_format($row['litre'], 2); ?> L
                                </td>

                                <td class="px-4 py-3 text-right font-medium text-green-700">
                                    Rs. <?php echo number_format($row['amount'], 2); ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>