<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/admin/report.php');
    exit;
}

// Today's date
$today = date('Y-m-d');

// Get selected dates
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : $today;

// Prevent future dates
if ($dateFrom > $today) {
    $dateFrom = date('Y-m-01');
}

if ($dateTo > $today) {
    $dateTo = $today;
}

// Prevent invalid date range
if ($dateFrom > $dateTo) {
    $dateFrom = date('Y-m-01');
    $dateTo = $today;
}


// Get Farmer

$stmt = $conn->prepare(
    "SELECT
        f.id,
        f.address,
        f.join_date,
        f.status,
        u.username,
        u.full_name,
        u.phone
     FROM farmers f
     JOIN users u ON f.user_id = u.id
     WHERE f.id = ?"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

$stmt->close();


if (!$farmer) {

    include __DIR__ . '/../includes/header.php';

    ?>

    <div class="max-w-7xl mx-auto px-4 py-6">

        <div class="bg-red-50 border border-red-200 rounded-lg p-6">

            <h1 class="text-xl font-bold text-red-700 mb-2">
                Farmer Not Found
            </h1>

            <p class="text-sm text-red-600 mb-4">
                The farmer you are looking for does not exist.
            </p>

            <a
                href="<?php echo BASE_URL; ?>/admin/report.php"
                class="text-blue-600 hover:underline"
            >
                Back to Ledger
            </a>

        </div>

    </div>

    <?php

    include __DIR__ . '/../includes/footer.php';
    exit;
}


// Get Milk Entries

$milkRows = array();

$totalLitres = 0;
$totalMilkAmount = 0;

$stmt = $conn->prepare(
    "SELECT
        id,
        entry_date,
        shift,
        litre,
        fat,
        snf,
        rate_applied,
        amount
     FROM milk_entries
     WHERE farmer_id = ?
     AND entry_date BETWEEN ? AND ?
     ORDER BY entry_date DESC, id DESC"
);

$stmt->bind_param('iss', $id, $dateFrom, $dateTo);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $milkRows[] = $row;

    $totalLitres += $row['litre'];
    $totalMilkAmount += $row['amount'];
}

$stmt->close();


// Get Dana Entries

$danaRows = array();

$totalDanaQty = 0;
$totalDanaAmount = 0;

$stmt = $conn->prepare(
    "SELECT
        id,
        entry_date,
        item_name,
        quantity,
        price_applied,
        amount
     FROM dana_entries
     WHERE farmer_id = ?
     AND entry_date BETWEEN ? AND ?
     ORDER BY entry_date DESC, id DESC"
);

$stmt->bind_param('iss', $id, $dateFrom, $dateTo);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $danaRows[] = $row;

    $totalDanaQty += $row['quantity'];
    $totalDanaAmount += $row['amount'];
}

$stmt->close();


// Calculate Net Payable

$netPayable = $totalMilkAmount - $totalDanaAmount;

if ($netPayable > 0) {
    $netClass = 'text-green-600';
} elseif ($netPayable < 0) {
    $netClass = 'text-red-600';
} else {
    $netClass = 'text-gray-600';
}


include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <a
        href="<?php echo BASE_URL; ?>/admin/report.php"
        class="text-blue-600 hover:underline text-sm"
    >
        &larr; Back to Ledger
    </a>


    <!-- Farmer Information -->

    <div class="bg-white border border-gray-200 rounded-lg p-6 mt-4 mb-6">

        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">

            <div>

                <h1 class="text-2xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($farmer['full_name']); ?>
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Farmer ID: <?php echo $farmer['id']; ?>
                    |
                    Username: <?php echo htmlspecialchars($farmer['username']); ?>
                </p>

            </div>


            <div>

                <?php if ($farmer['status'] === 'active'): ?>

                    <span class="px-3 py-1 text-sm rounded bg-green-100 text-green-700">
                        Active
                    </span>

                <?php else: ?>

                    <span class="px-3 py-1 text-sm rounded bg-red-100 text-red-700">
                        Inactive
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 pt-5 border-t">

            <div>
                <p class="text-xs text-gray-500">Phone</p>

                <p class="text-sm font-medium text-gray-800 mt-1">
                    <?php echo htmlspecialchars($farmer['phone']); ?>
                </p>
            </div>


            <div>
                <p class="text-xs text-gray-500">Address</p>

                <p class="text-sm font-medium text-gray-800 mt-1">
                    <?php echo htmlspecialchars($farmer['address']); ?>
                </p>
            </div>


            <div>
                <p class="text-xs text-gray-500">Join Date</p>

                <p class="text-sm font-medium text-gray-800 mt-1">
                    <?php echo formatDate($farmer['join_date']); ?>
                </p>
            </div>

        </div>

    </div>


    <!-- Date Filter -->

    <form
        method="GET"
        class="bg-white border border-gray-200 rounded-lg p-5 mb-6"
    >

        <input
            type="hidden"
            name="id"
            value="<?php echo $farmer['id']; ?>"
        >

        <div class="flex flex-col md:flex-row md:items-end gap-4">

            <div class="flex-1">

                <label
                    for="date_from"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Date From
                </label>

                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    value="<?php echo htmlspecialchars($dateFrom); ?>"
                    max="<?php echo $today; ?>"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >

            </div>


            <div class="flex-1">

                <label
                    for="date_to"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    Date To
                </label>

                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    value="<?php echo htmlspecialchars($dateTo); ?>"
                    max="<?php echo $today; ?>"
                    min="<?php echo htmlspecialchars($dateFrom); ?>"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >

            </div>


            <div>

                <button
                    type="submit"
                    class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg transition"
                >
                    Show
                </button>

            </div>

        </div>

        <p class="text-xs text-gray-500 mt-3">
            Select a date range to view milk and dana transactions. Future dates are not available.
        </p>

    </form>


    <!-- Summary -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <div class="bg-white border border-gray-200 rounded-lg p-5">

            <p class="text-sm text-gray-500">
                Total Milk
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo number_format($totalLitres, 2); ?> L
            </p>

        </div>


        <div class="bg-white border border-gray-200 rounded-lg p-5">

            <p class="text-sm text-gray-500">
                Milk Amount
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                Rs. <?php echo number_format($totalMilkAmount, 2); ?>
            </p>

        </div>


        <div class="bg-white border border-gray-200 rounded-lg p-5">

            <p class="text-sm text-gray-500">
                Dana Deduction
            </p>

            <p class="text-2xl font-bold text-gray-800 mt-1">
                Rs. <?php echo number_format($totalDanaAmount, 2); ?>
            </p>

        </div>


        <div class="bg-white border border-gray-200 rounded-lg p-5">

            <p class="text-sm text-gray-500">
                Net Payable
            </p>

            <p class="text-2xl font-bold <?php echo $netClass; ?> mt-1">
                Rs. <?php echo number_format($netPayable, 2); ?>
            </p>

        </div>

    </div>


    <!-- Milk Entries -->

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between">

            <h2 class="font-semibold text-gray-800">
                Milk Entries
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo count($milkRows); ?> entries
            </span>

        </div>


        <?php if (count($milkRows) === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Litres
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                FAT
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                SNF
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Rate
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Amount
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-200">

                        <?php foreach ($milkRows as $row): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-sm">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm">

                                    <?php if ($row['shift'] === 'morning'): ?>

                                        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                            Morning
                                        </span>

                                    <?php else: ?>

                                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">
                                            Evening
                                        </span>

                                    <?php endif; ?>

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

                            <td
                                colspan="2"
                                class="px-4 py-3 text-right"
                            >
                                TOTAL
                            </td>

                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($totalLitres, 2); ?>
                            </td>

                            <td colspan="3"></td>

                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($totalMilkAmount, 2); ?>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- Dana Entries -->

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between">

            <h2 class="font-semibold text-gray-800">
                Dana Entries
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo count($danaRows); ?> entries
            </span>

        </div>


        <?php if (count($danaRows) === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No dana entries.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Item
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Quantity
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Price
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Amount
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-200">

                        <?php foreach ($danaRows as $row): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-sm">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm font-medium">
                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm text-right">
                                    <?php echo number_format($row['quantity'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-sm text-right">
                                    Rs. <?php echo number_format($row['price_applied'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">
                                    Rs. <?php echo number_format($row['amount'], 2); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>


                    <tfoot class="bg-gray-100 font-semibold">

                        <tr>

                            <td
                                colspan="2"
                                class="px-4 py-3 text-right"
                            >
                                TOTAL
                            </td>

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


<script>

// Keep Date To after or equal to Date From
const dateFrom = document.getElementById('date_from');
const dateTo = document.getElementById('date_to');

dateFrom.addEventListener('change', function () {

    dateTo.min = this.value;

    if (dateTo.value < this.value) {
        dateTo.value = this.value;
    }

});

</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>