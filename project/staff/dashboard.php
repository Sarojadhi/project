<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$firstDay = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));


// Get today's milk by shift
$stmt = $conn->prepare(
    "SELECT
        shift,
        COUNT(*) AS total_entries,
        COALESCE(SUM(litre), 0) AS total_litres
     FROM milk_entries
     WHERE entered_by = ?
     AND entry_date = ?
     GROUP BY shift"
);

$stmt->bind_param('is', $userId, $today);
$stmt->execute();

$result = $stmt->get_result();

$morningEntries = 0;
$eveningEntries = 0;
$morningMilk = 0;
$eveningMilk = 0;

while ($row = $result->fetch_assoc()) {

    if ($row['shift'] === 'morning') {
        $morningEntries = (int) $row['total_entries'];
        $morningMilk = (float) $row['total_litres'];
    }

    if ($row['shift'] === 'evening') {
        $eveningEntries = (int) $row['total_entries'];
        $eveningMilk = (float) $row['total_litres'];
    }
}

$stmt->close();


// Calculate today's total milk
$todayTotalMilk = $morningMilk + $eveningMilk;


// Get monthly milk
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(litre), 0) AS total_litres
     FROM milk_entries
     WHERE entered_by = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $userId, $firstDay, $nextMonth);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$monthlyMilk = (float) $row['total_litres'];

$stmt->close();


// Get monthly dana
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(quantity), 0) AS total_quantity
     FROM dana_entries
     WHERE entered_by = ?
     AND entry_date >= ?
     AND entry_date < ?"
);

$stmt->bind_param('iss', $userId, $firstDay, $nextMonth);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

$monthlyDana = (float) $row['total_quantity'];

$stmt->close();


// Get five recent milk entries
$stmt = $conn->prepare(
    "SELECT
        m.shift,
        m.litre,
        m.amount,
        u.full_name AS farmer_name
     FROM milk_entries m
     JOIN farmers f ON m.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     WHERE m.entered_by = ?
     ORDER BY m.id DESC
     LIMIT 5"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$recent = $stmt->get_result();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

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


    <!-- Dashboard summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <!-- Entries by shift -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <p class="text-sm text-gray-500 font-bold">
                Today's Entries by Shift
            </p>

            <div class="mt-3 space-y-2">

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Morning
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo $morningEntries; ?>
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Evening
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo $eveningEntries; ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- Milk by shift -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <p class="text-sm text-gray-500 font-bold ">
                Today's Milk by Shift
            </p>

            <div class="mt-3 space-y-2">

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Morning
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo number_format($morningMilk, 1); ?> L
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Evening
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo number_format($eveningMilk, 1); ?> L
                    </span>

                </div>

            </div>

        </div>


        <!-- Today's total milk -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 font-bold ">

            <p class="text-sm text-gray-500">
                Today's Total Milk
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-2">
                <?php echo number_format($todayTotalMilk, 1); ?> L
            </p>

        </div>


        <!-- Monthly milk and dana -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <p class="text-sm text-gray-500 font-bold ">
                This Month
            </p>

            <div class="mt-3 space-y-2">

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Milk
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo number_format($monthlyMilk, 1); ?> L
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="text-sm text-gray-600">
                        Dana
                    </span>

                    <span class="font-semibold text-gray-800">
                        <?php echo number_format($monthlyDana, 1); ?> kg
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- Recent milk entries -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-200">

            <h2 class="font-semibold text-gray-800">
                 Recent Milk Entries
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Your latest 5 milk entries.
            </p>

        </div>


        <?php if ($recent && $recent->num_rows > 0): ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                S.N.
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer
                            </th>

                            <th class="px-4 py-3 text-center">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right">
                                Ltr
                            </th>

                            <th class="px-4 py-3 text-right">
                                Rs.
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y">

                        <?php $serialNumber = 1; ?>

                        <?php while ($row = $recent->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-gray-600">
                                    <?php echo $serialNumber; ?>
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <?php echo e($row['farmer_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-center text-gray-600">
                                    <?php echo e(ucfirst($row['shift'])); ?>
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700">
                                    <?php echo number_format((float) $row['litre'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-medium text-gray-800">
                                    Rs.
                                    <?php echo number_format((float) $row['amount'], 2); ?>
                                </td>

                            </tr>

                            <?php $serialNumber++; ?>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


            <!-- Today's total -->
            <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">

                <div class="flex justify-between items-center">

                    <span class="font-semibold text-gray-700">
                        Today's Total Milk
                    </span>

                    <span class="text-lg font-bold text-gray-800">
                        <?php echo number_format($todayTotalMilk, 2); ?> Ltr
                    </span>

                </div>

            </div>

        <?php else: ?>

            <div class="p-8 text-center">

                <p class="text-gray-500">
                    No milk entries yet.
                </p>

            </div>


            <!-- Today's total -->
            <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">

                <div class="flex justify-between items-center">

                    <span class="font-semibold text-gray-700">
                        Today's Total Milk
                    </span>

                    <span class="text-lg font-bold text-gray-800">
                        0.00 Ltr
                    </span>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>