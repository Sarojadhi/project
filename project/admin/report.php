<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$message = '';
$messageType = '';

// Today's date
$today = date('Y-m-d');

// Get filter values
$dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
    ? $_GET['date_from']
    : date('Y-m-01');

$dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
    ? $_GET['date_to']
    : $today;

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

// Validate future dates
if ($dateFrom > $today) {
    $dateFrom = date('Y-m-01');
    $message = 'Date From cannot be a future date.';
    $messageType = 'error';
}

if ($dateTo > $today) {
    $dateTo = $today;
    $message = 'Date To cannot be a future date.';
    $messageType = 'error';
}

// Validate date range
if ($dateFrom > $dateTo) {
    $dateFrom = date('Y-m-01');
    $dateTo = $today;
    $message = 'Date From cannot be greater than Date To.';
    $messageType = 'error';
}

// Limit search length
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

// Get farmer data for autocomplete
$suggestions = array();

$suggestionSql = "
    SELECT
        f.id AS farmer_id,
        u.username,
        u.full_name
    FROM farmers f
    JOIN users u
        ON f.user_id = u.id
    WHERE u.role = 'farmer'
    ORDER BY u.full_name ASC
";

$suggestionResult = $conn->query($suggestionSql);

if ($suggestionResult) {

    while ($suggestionRow = $suggestionResult->fetch_assoc()) {

        $suggestions[] = array(
            'id' => (string) $suggestionRow['farmer_id'],
            'username' => $suggestionRow['username'],
            'name' => $suggestionRow['full_name']
        );
    }
}

// Build search query
$searchSql = '';

if ($search !== '') {

    $safeSearch = $conn->real_escape_string($search);

    if (ctype_digit($search)) {

        $farmerId = (int) $search;

        $searchSql = "
            AND (
                f.id = $farmerId
                OR u.username LIKE '%$safeSearch%'
            )
        ";

    } else {

        $searchSql = "
            AND (
                u.username LIKE '%$safeSearch%'
                OR u.full_name LIKE '%$safeSearch%'
            )
        ";
    }
}

// Get farmer ledger
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
            (float) $row['milk_amount'] -
            (float) $row['dana_amount'];

        $grandLitres += (float) $row['total_litres'];
        $grandMilk += (float) $row['milk_amount'];
        $grandDanaQty += (float) $row['dana_quantity'];
        $grandDanaAmount += (float) $row['dana_amount'];
        $grandNet += (float) $row['net_payable'];

        $rows[] = $row;
    }
}

include __DIR__ . '/../includes/header.php';

?>

<div class="min-h-screen flex flex-col">

    <main class="flex-1">

        <div class="max-w-7xl mx-auto px-4 py-6">

            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                Farmer Ledger
            </h1>

            <!-- Show validation message -->
            <?php if ($message !== ''): ?>

                <div
                    class="mb-6 px-4 py-3 rounded-md border
                    <?php
                    echo $messageType === 'error'
                        ? 'bg-red-50 border-red-200 text-red-700'
                        : 'bg-green-50 border-green-200 text-green-700';
                    ?>"
                >
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">

                <form
                    method="GET"
                    id="filterForm"
                    class="grid grid-cols-1 md:grid-cols-5 gap-4"
                    novalidate
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
                            id="date_from"
                            name="date_from"
                            value="<?php echo htmlspecialchars($dateFrom); ?>"
                            max="<?php echo $today; ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                            required
                        >

                        <p
                            id="dateFromError"
                            class="hidden text-red-600 text-sm mt-1"
                        ></p>

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
                            id="date_to"
                            name="date_to"
                            value="<?php echo htmlspecialchars($dateTo); ?>"
                            max="<?php echo $today; ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                            required
                        >

                        <p
                            id="dateToError"
                            class="hidden text-red-600 text-sm mt-1"
                        ></p>

                    </div>

                    <!-- Search -->
                    <div class="md:col-span-2 relative">

                        <label
                            for="search"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Search Farmer
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="ID, username or name"
                            autocomplete="off"
                            maxlength="100"
                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                        >

                        <!-- Autocomplete suggestions -->
                        <div
                            id="suggestions"
                            class="hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                        ></div>

                        <p
                            id="searchError"
                            class="hidden text-red-600 text-sm mt-1"
                        ></p>

                    </div>

                    <!-- Buttons -->
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

            <!-- Print button -->
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

                <div
                    class="px-6 py-4 border-b border-gray-200 flex justify-between"
                >

                    <h2 class="font-semibold text-gray-800">

                        Ledger from

                        <?php echo formatDate($dateFrom); ?>

                        to

                        <?php echo formatDate($dateTo); ?>

                    </h2>

                    <span class="text-sm text-gray-500">

                        <?php echo count($rows); ?>

                        farmers

                    </span>

                </div>

                <!-- No results -->
                <?php if (count($rows) === 0): ?>

                    <div class="p-8 text-center text-gray-500">

                        <div class="text-lg font-medium mb-1">
                            No farmers found
                        </div>

                        <div class="text-sm">
                            No farmer matches your current search or filters.
                        </div>

                    </div>

                <?php else: ?>

                    <!-- Ledger table -->
                    <div class="overflow-x-auto">

                        <table class="min-w-full">

                            <thead class="bg-gray-50">

                                <tr>

                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        ID
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Name
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500"
                                    >
                                        Username
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500"
                                    >
                                        Milk Litres
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500"
                                    >
                                        Milk Amount
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500"
                                    >
                                        Dana Qty
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500"
                                    >
                                        Dana Amount
                                    </th>

                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500"
                                    >
                                        Net Payable
                                    </th>

                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500"
                                    >
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

                                        <td
                                            class="px-4 py-3 text-sm font-medium"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $row['full_name']
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-gray-600"
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $row['username']
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-right"
                                        >

                                            <?php
                                            echo number_format(
                                                $row['total_litres'],
                                                2
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-right"
                                        >

                                            Rs.

                                            <?php
                                            echo number_format(
                                                $row['milk_amount'],
                                                2
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-right"
                                        >

                                            <?php
                                            echo number_format(
                                                $row['dana_quantity'],
                                                2
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-right"
                                        >

                                            Rs.

                                            <?php
                                            echo number_format(
                                                $row['dana_amount'],
                                                2
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-sm text-right font-bold <?php echo $netClass; ?>"
                                        >

                                            Rs.

                                            <?php
                                            echo number_format(
                                                $row['net_payable'],
                                                2
                                            );
                                            ?>

                                        </td>

                                        <td
                                            class="px-4 py-3 text-center"
                                        >

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

                            <!-- Grand total -->
                            <tfoot class="bg-gray-100 font-semibold">

                                <tr>

                                    <td
                                        colspan="3"
                                        class="px-4 py-3 text-right"
                                    >
                                        GRAND TOTAL
                                    </td>

                                    <td
                                        class="px-4 py-3 text-right"
                                    >

                                        <?php
                                        echo number_format(
                                            $grandLitres,
                                            2
                                        );
                                        ?>

                                    </td>

                                    <td
                                        class="px-4 py-3 text-right"
                                    >

                                        Rs.

                                        <?php
                                        echo number_format(
                                            $grandMilk,
                                            2
                                        );
                                        ?>

                                    </td>

                                    <td
                                        class="px-4 py-3 text-right"
                                    >

                                        <?php
                                        echo number_format(
                                            $grandDanaQty,
                                            2
                                        );
                                        ?>

                                    </td>

                                    <td
                                        class="px-4 py-3 text-right"
                                    >

                                        Rs.

                                        <?php
                                        echo number_format(
                                            $grandDanaAmount,
                                            2
                                        );
                                        ?>

                                    </td>

                                    <td
                                        class="px-4 py-3 text-right"
                                    >

                                        Rs.

                                        <?php
                                        echo number_format(
                                            $grandNet,
                                            2
                                        );
                                        ?>

                                    </td>

                                    <td></td>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<script>

// Farmer data loaded from PHP
const farmers = <?php echo json_encode(
    $suggestions,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
); ?>;

// Get form elements
const searchInput = document.getElementById('search');
const suggestionsBox = document.getElementById('suggestions');

const dateFrom = document.getElementById('date_from');
const dateTo = document.getElementById('date_to');

const filterForm = document.getElementById('filterForm');

const dateFromError =
    document.getElementById('dateFromError');

const dateToError =
    document.getElementById('dateToError');

const searchError =
    document.getElementById('searchError');


// Search farmer suggestions
searchInput.addEventListener('input', function () {

    const value =
        this.value.trim().toLowerCase();

    suggestionsBox.innerHTML = '';

    if (value === '') {

        suggestionsBox.classList.add('hidden');

        return;
    }

    // Find matching farmers
    const matches = farmers
        .filter(function (farmer) {

            return (
                farmer.id
                    .toLowerCase()
                    .includes(value)

                ||

                farmer.username
                    .toLowerCase()
                    .includes(value)

                ||

                farmer.name
                    .toLowerCase()
                    .includes(value)
            );

        })
        .slice(0, 10);

    // Show no result message
    if (matches.length === 0) {

        suggestionsBox.innerHTML = `
            <div class="px-4 py-3 text-sm text-gray-500">
                No farmer found
            </div>
        `;

        suggestionsBox.classList.remove('hidden');

        return;
    }

    // Create suggestions
    matches.forEach(function (farmer) {

        const item =
            document.createElement('button');

        item.type = 'button';

        item.className =
            'w-full text-left px-4 py-3 hover:bg-gray-100 border-b border-gray-100';

        item.innerHTML = `
            <div class="font-medium text-gray-800">
                ${escapeHtml(farmer.name)}
            </div>

            <div class="text-sm text-gray-500">
                ID: ${escapeHtml(farmer.id)}
                &nbsp; | &nbsp;
                Username: ${escapeHtml(farmer.username)}
            </div>
        `;

        // Select farmer
        item.addEventListener('click', function () {

            searchInput.value =
                farmer.username;

            suggestionsBox.classList.add('hidden');

            searchInput.focus();

        });

        suggestionsBox.appendChild(item);

    });

    suggestionsBox.classList.remove('hidden');

});


// Close suggestions when clicking outside
document.addEventListener('click', function (event) {

    if (
        !searchInput.contains(event.target)
        &&
        !suggestionsBox.contains(event.target)
    ) {

        suggestionsBox.classList.add('hidden');
    }

});


// Prevent HTML injection in suggestions
function escapeHtml(value) {

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


// Client-side form validation
filterForm.addEventListener('submit', function (event) {

    let valid = true;

    // Clear old errors
    dateFromError.classList.add('hidden');
    dateToError.classList.add('hidden');
    searchError.classList.add('hidden');

    // Validate Date From
    if (dateFrom.value === '') {

        dateFromError.textContent =
            'Please select a Date From.';

        dateFromError.classList.remove('hidden');

        valid = false;

    } else if (
        dateFrom.value > '<?php echo $today; ?>'
    ) {

        dateFromError.textContent =
            'Date From cannot be a future date.';

        dateFromError.classList.remove('hidden');

        valid = false;
    }

    // Validate Date To
    if (dateTo.value === '') {

        dateToError.textContent =
            'Please select a Date To.';

        dateToError.classList.remove('hidden');

        valid = false;

    } else if (
        dateTo.value > '<?php echo $today; ?>'
    ) {

        dateToError.textContent =
            'Date To cannot be a future date.';

        dateToError.classList.remove('hidden');

        valid = false;
    }

    // Validate date range
    if (
        dateFrom.value !== ''
        &&
        dateTo.value !== ''
        &&
        dateFrom.value > dateTo.value
    ) {

        dateFromError.textContent =
            'Date From cannot be greater than Date To.';

        dateFromError.classList.remove('hidden');

        valid = false;
    }

    // Validate search field
    if (searchInput.value.length > 100) {

        searchError.textContent =
            'Search cannot be more than 100 characters.';

        searchError.classList.remove('hidden');

        valid = false;
    }

    // Stop form submission if invalid
    if (!valid) {

        event.preventDefault();
    }

});


// Set minimum Date To
dateFrom.addEventListener('change', function () {

    dateTo.min = this.value;

});


// Set initial minimum Date To
if (dateFrom.value !== '') {

    dateTo.min = dateFrom.value;

}

</script>


<style>

@media print {

    header,
    nav,
    footer,
    button,
    form,
    .mb-4 {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .min-h-screen {
        min-height: auto !important;
    }

    main {
        width: 100% !important;
    }

}

</style>


<?php

include __DIR__ . '/../includes/footer.php';

?>