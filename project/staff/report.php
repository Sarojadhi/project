<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
    ? $_GET['date_from']
    : date('Y-m-01');

$dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
    ? $_GET['date_to']
    : date('Y-m-d');

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';


// Fix invalid date range
if ($dateFrom > $dateTo) {
    $temp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $temp;
}


// Search value
$searchId = 0;

if (is_numeric($search)) {
    $searchId = (int) $search;
}


// Milk report
if ($searchId > 0) {

    $stmt = $conn->prepare(
        "SELECT
            m.entry_date,
            m.shift,
            m.litre,
            m.fat,
            m.snf,
            f.id AS farmer_id,
            u.full_name,
            u.username
         FROM milk_entries m
         JOIN farmers f ON m.farmer_id = f.id
         JOIN users u ON f.user_id = u.id
         WHERE m.entry_date BETWEEN ? AND ?
         AND f.id = ?
         ORDER BY m.entry_date DESC
         LIMIT 500"
    );

    $stmt->bind_param(
        'ssi',
        $dateFrom,
        $dateTo,
        $searchId
    );

} else {

    $searchLike = '%' . $search . '%';

    $stmt = $conn->prepare(
        "SELECT
            m.entry_date,
            m.shift,
            m.litre,
            m.fat,
            m.snf,
            f.id AS farmer_id,
            u.full_name,
            u.username
         FROM milk_entries m
         JOIN farmers f ON m.farmer_id = f.id
         JOIN users u ON f.user_id = u.id
         WHERE m.entry_date BETWEEN ? AND ?
         AND (
             u.username LIKE ?
             OR u.full_name LIKE ?
         )
         ORDER BY m.entry_date DESC
         LIMIT 500"
    );

    $stmt->bind_param(
        'ssss',
        $dateFrom,
        $dateTo,
        $searchLike,
        $searchLike
    );
}

$stmt->execute();
$milkResult = $stmt->get_result();


// Dana report
if ($searchId > 0) {

    $stmt = $conn->prepare(
        "SELECT
            d.entry_date,
            d.item_name,
            d.quantity,
            f.id AS farmer_id,
            u.full_name,
            u.username
         FROM dana_entries d
         JOIN farmers f ON d.farmer_id = f.id
         JOIN users u ON f.user_id = u.id
         WHERE d.entry_date BETWEEN ? AND ?
         AND f.id = ?
         ORDER BY d.entry_date DESC
         LIMIT 500"
    );

    $stmt->bind_param(
        'ssi',
        $dateFrom,
        $dateTo,
        $searchId
    );

} else {

    $searchLike = '%' . $search . '%';

    $stmt = $conn->prepare(
        "SELECT
            d.entry_date,
            d.item_name,
            d.quantity,
            f.id AS farmer_id,
            u.full_name,
            u.username
         FROM dana_entries d
         JOIN farmers f ON d.farmer_id = f.id
         JOIN users u ON f.user_id = u.id
         WHERE d.entry_date BETWEEN ? AND ?
         AND (
             u.username LIKE ?
             OR u.full_name LIKE ?
         )
         ORDER BY d.entry_date DESC
         LIMIT 500"
    );

    $stmt->bind_param(
        'ssss',
        $dateFrom,
        $dateTo,
        $searchLike,
        $searchLike
    );
}

$stmt->execute();
$danaResult = $stmt->get_result();


include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page title -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Reports
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Milk and dana reports
        </p>

    </div>


    <!-- Filter -->
    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">

        <form
            method="GET"
            class="grid grid-cols-1 md:grid-cols-4 gap-4"
        >

            <!-- Date From -->
            <div>

                <label
                    for="date_from"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Date From
                </label>

                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="<?php echo e($dateFrom); ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                >

            </div>


            <!-- Date To -->
            <div>

                <label
                    for="date_to"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Date To
                </label>

                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="<?php echo e($dateTo); ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                >

            </div>


            <!-- Search -->
            <div>

                <label
                    for="search"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    id="search"
                    value="<?php echo e($search); ?>"
                    placeholder="ID / Username / Name"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                >

            </div>


            <!-- Buttons -->
            <div class="flex items-end gap-2">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md"
                >
                    Show
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/staff/report.php"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-md"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- Print -->
    <div class="mb-5">

        <button
            type="button"
            onclick="window.print()"
            class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-md"
        >
            Print
        </button>

    </div>


    <!-- Milk Report -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                Milk Report
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo $milkResult->num_rows; ?> entries
            </span>

        </div>


        <?php if ($milkResult->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries found.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 border-b">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer ID
                            </th>

                            <th class="px-4 py-3 text-left">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left">
                                Username
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


                    <tbody class="divide-y divide-gray-100">

                        <?php
                        $totalLitre = 0;

                        while ($row = $milkResult->fetch_assoc()):

                            $totalLitre += (float) $row['litre'];
                        ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo (int) $row['farmer_id']; ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo e($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-gray-500">
                                    <?php echo e($row['username']); ?>
                                </td>

                                <td class="px-4 py-3 text-center">

                                    <span
                                        class="px-2 py-1 text-xs rounded
                                        <?php
                                        echo $row['shift'] === 'morning'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-blue-100 text-blue-800';
                                        ?>"
                                    >
                                        <?php echo e(ucfirst($row['shift'])); ?>
                                    </span>

                                </td>

                                <td class="px-4 py-3 text-right font-medium">
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


                    <tfoot class="bg-gray-50 border-t">

                        <tr>

                            <td
                                colspan="5"
                                class="px-4 py-3 text-right font-semibold"
                            >
                                TOTAL
                            </td>

                            <td class="px-4 py-3 text-right font-semibold">
                                <?php echo number_format($totalLitre, 2); ?>
                            </td>

                            <td colspan="2"></td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- Dana Report -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                Dana Report
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo $danaResult->num_rows; ?> entries
            </span>

        </div>


        <?php if ($danaResult->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No dana entries found.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 border-b">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer ID
                            </th>

                            <th class="px-4 py-3 text-left">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left">
                                Item
                            </th>

                            <th class="px-4 py-3 text-right">
                                Quantity
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php
                        $totalQuantity = 0;

                        while ($row = $danaResult->fetch_assoc()):

                            $totalQuantity += (float) $row['quantity'];
                        ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo (int) $row['farmer_id']; ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo e($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo e($row['item_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-medium">
                                    <?php echo number_format($row['quantity'], 2); ?>
                                    kg
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>


                    <tfoot class="bg-gray-50 border-t">

                        <tr>

                            <td
                                colspan="4"
                                class="px-4 py-3 text-right font-semibold"
                            >
                                TOTAL
                            </td>

                            <td class="px-4 py-3 text-right font-semibold">
                                <?php echo number_format($totalQuantity, 2); ?>
                                kg
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>