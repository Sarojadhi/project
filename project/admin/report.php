<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$dateFrom = isset($_GET['date_from'])
    ? $_GET['date_from']
    : date('Y-m-01');

$dateTo = isset($_GET['date_to'])
    ? $_GET['date_to']
    : date('Y-m-d');

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$searchSql = '';

if ($search !== '') {

    if (is_numeric($search)) {

        $farmerId = (int) $search;

        $searchSql = "AND f.id = $farmerId";

    } else {

        $safeSearch = $conn->real_escape_string($search);

        $searchSql = "
            AND (
                u.username LIKE '%$safeSearch%'
                OR u.full_name LIKE '%$safeSearch%'
            )
        ";
    }
}

/*
|--------------------------------------------------------------------------
| Get farmer ledger
|--------------------------------------------------------------------------
|
| Milk and dana are calculated separately.
| This prevents totals from being multiplied when a farmer
| has multiple milk and dana records.
|
*/

$sql = "
    SELECT
        f.id AS farmer_id,
        u.username,
        u.full_name,

        (
            SELECT COALESCE(SUM(m.litre), 0)
            FROM milk_entries m
            WHERE m.farmer_id = f.id
            AND m.entry_date BETWEEN '$dateFrom' AND '$dateTo'
        ) AS total_litres,

        (
            SELECT COALESCE(SUM(m.amount), 0)
            FROM milk_entries m
            WHERE m.farmer_id = f.id
            AND m.entry_date BETWEEN '$dateFrom' AND '$dateTo'
        ) AS milk_amount,

        (
            SELECT COALESCE(SUM(d.quantity), 0)
            FROM dana_entries d
            WHERE d.farmer_id = f.id
            AND d.entry_date BETWEEN '$dateFrom' AND '$dateTo'
        ) AS dana_quantity,

        (
            SELECT COALESCE(SUM(d.amount), 0)
            FROM dana_entries d
            WHERE d.farmer_id = f.id
            AND d.entry_date BETWEEN '$dateFrom' AND '$dateTo'
        ) AS dana_amount

    FROM farmers f

    JOIN users u
        ON f.user_id = u.id

    WHERE u.role = 'farmer'
    $searchSql

    ORDER BY u.full_name ASC
";

$result = $conn->query($sql);

$rows = array();

$grandLitres = 0;
$grandMilk = 0;
$grandDanaQty = 0;
$grandDanaAmount = 0;
$grandNet = 0;

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $row['net_payable'] =
            $row['milk_amount'] - $row['dana_amount'];

        $grandLitres += $row['total_litres'];
        $grandMilk += $row['milk_amount'];
        $grandDanaQty += $row['dana_quantity'];
        $grandDanaAmount += $row['dana_amount'];
        $grandNet += $row['net_payable'];

        $rows[] = $row;
    }
}

include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        Farmer Ledger
    </h1>


    <!-- Filters -->

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div>

                <label
                    for="date_from"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Date From
                </label>

                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    value="<?php echo htmlspecialchars($dateFrom); ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2"
                >

            </div>


            <div>

                <label
                    for="date_to"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Date To
                </label>

                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    value="<?php echo htmlspecialchars($dateTo); ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2"
                >

            </div>


            <div class="md:col-span-2">

                <label
                    for="search"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="ID, username or name"
                    class="w-full border border-gray-300 rounded-md px-3 py-2"
                >

            </div>


            <div class="flex items-end gap-2">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md"
                >
                    Show
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/admin/report.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- Print -->

    <div class="mb-4">

        <button
            type="button"
            onclick="window.print()"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md"
        >
            Print
        </button>

    </div>


    <!-- Ledger -->

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200 flex justify-between">

            <h2 class="font-semibold text-gray-800">

                Ledger from
                <?php echo formatDate($dateFrom); ?>

                to

                <?php echo formatDate($dateTo); ?>

            </h2>

            <span class="text-sm text-gray-500">
                <?php echo count($rows); ?> farmers
            </span>

        </div>


        <?php if (count($rows) === 0): ?>

            <div class="p-8 text-center text-gray-500">
                No farmers match the current filters.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                ID
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Username
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Milk Litres
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Milk Amount
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Dana Qty
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Dana Amount
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                Net Payable
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-200">

                        <?php foreach ($rows as $row): ?>

                            <?php

                            if ($row['net_payable'] > 0) {
                                $netClass = 'text-green-600';
                            } elseif ($row['net_payable'] < 0) {
                                $netClass = 'text-red-600';
                            } else {
                                $netClass = 'text-gray-500';
                            }

                            ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-sm">
                                    <?php echo $row['farmer_id']; ?>
                                </td>

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

                                <td class="px-4 py-3 text-center">

                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/view-farmer.php?id=<?php echo $row['farmer_id']; ?>"
                                        class="text-blue-600 hover:underline"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>


                    <!-- Grand Total -->

                    <tfoot class="bg-gray-100 font-semibold">

                        <tr>

                            <td
                                colspan="3"
                                class="px-4 py-3 text-right"
                            >
                                GRAND TOTAL
                            </td>

                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($grandLitres, 2); ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grandMilk, 2); ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <?php echo number_format($grandDanaQty, 2); ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grandDanaAmount, 2); ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                Rs. <?php echo number_format($grandNet, 2); ?>
                            </td>

                            <td></td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>