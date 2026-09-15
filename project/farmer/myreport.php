<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = $_SESSION['user_id'];

$farmerId = getFarmerIdFromUserId($conn, $userId);

if ($farmerId == 0) {
    header('Location: ' . BASE_URL . '/farmer/dashboard.php');
    exit;
}


// Selected month, year and shift
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$shift = $_GET['shift'] ?? 'all';


// Validate month
if ($month < 1 || $month > 12) {
    $month = (int) date('m');
}


// Validate year
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}


// Validate shift
if (
    $shift !== 'morning' &&
    $shift !== 'evening' &&
    $shift !== 'all'
) {
    $shift = 'all';
}


// Month dates
$firstDay = sprintf('%04d-%02d-01', $year, $month);
$nextMonth = date('Y-m-01', strtotime($firstDay . ' +1 month'));
$monthName = date('F Y', strtotime($firstDay));

$today = date('Y-m-d');


// Get milk entries
if ($shift === 'morning' || $shift === 'evening') {

    $stmt = $conn->prepare(
        "SELECT entry_date, shift, litre, fat, snf, rate_applied, amount
         FROM milk_entries
         WHERE farmer_id = ?
         AND entry_date >= ?
         AND entry_date < ?
         AND shift = ?
         ORDER BY entry_date ASC, id ASC"
    );

    $stmt->bind_param(
        'isss',
        $farmerId,
        $firstDay,
        $nextMonth,
        $shift
    );

} else {

    $stmt = $conn->prepare(
        "SELECT entry_date, shift, litre, fat, snf, rate_applied, amount
         FROM milk_entries
         WHERE farmer_id = ?
         AND entry_date >= ?
         AND entry_date < ?
         ORDER BY entry_date ASC, id ASC"
    );

    $stmt->bind_param(
        'iss',
        $farmerId,
        $firstDay,
        $nextMonth
    );
}

$stmt->execute();

$result = $stmt->get_result();


// Store entries and calculate totals
$entries = array();

$totalLitres = 0;
$totalAmount = 0;
$morningLitres = 0;
$eveningLitres = 0;

while ($row = $result->fetch_assoc()) {

    $entries[] = $row;

    $totalLitres += $row['litre'];
    $totalAmount += $row['amount'];

    if ($row['shift'] === 'morning') {
        $morningLitres += $row['litre'];
    } else {
        $eveningLitres += $row['litre'];
    }
}


// Group entries by date
$entriesByDate = array();

foreach ($entries as $entry) {

    $date = $entry['entry_date'];

    if (!isset($entriesByDate[$date])) {
        $entriesByDate[$date] = array();
    }

    $entriesByDate[$date][] = $entry;
}


// Create calendar
$daysInMonth = (int) date('t', strtotime($firstDay));
$firstWeekday = (int) date('N', strtotime($firstDay));

$calendar = array();


// Empty cells before first day
for ($i = 1; $i < $firstWeekday; $i++) {

    $calendar[] = array(
        'day' => null,
        'date' => null,
        'future' => false,
        'entries' => array()
    );
}


// Days of month
for ($day = 1; $day <= $daysInMonth; $day++) {

    $date = sprintf(
        '%04d-%02d-%02d',
        $year,
        $month,
        $day
    );

    $isFuture = ($date > $today);

    $dayEntries = array();

    if (!$isFuture && isset($entriesByDate[$date])) {
        $dayEntries = $entriesByDate[$date];
    }

    $calendar[] = array(
        'day' => $day,
        'date' => $date,
        'future' => $isFuture,
        'entries' => $dayEntries
    );
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page heading -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            My Milk Reports
        </h1>

        <p class="text-gray-600">
            View your milk records by month and shift
        </p>

    </div>


    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <!-- Month -->
            <div>

                <label
                    for="month"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Month
                </label>

                <select
                    id="month"
                    name="month"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                    <?php

                    $months = array(
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    );

                    foreach ($months as $number => $name) {

                        $selected = ($number == $month)
                            ? 'selected'
                            : '';

                        echo '<option value="' . $number . '" ' .
                            $selected . '>' .
                            $name .
                            '</option>';
                    }

                    ?>

                </select>

            </div>


            <!-- Year -->
            <div>

                <label
                    for="year"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Year
                </label>

                <select
                    id="year"
                    name="year"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                    <?php

                    $currentYear = (int) date('Y');

                    for (
                        $y = $currentYear;
                        $y >= $currentYear - 5;
                        $y--
                    ) {

                        $selected = ($y == $year)
                            ? 'selected'
                            : '';

                        echo '<option value="' . $y . '" ' .
                            $selected . '>' .
                            $y .
                            '</option>';
                    }

                    ?>

                </select>

            </div>


            <!-- Shift -->
            <div>

                <label
                    for="shift"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Shift
                </label>

                <select
                    id="shift"
                    name="shift"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                    <option
                        value="all"
                        <?php echo ($shift === 'all') ? 'selected' : ''; ?>
                    >
                        All Shifts
                    </option>

                    <option
                        value="morning"
                        <?php echo ($shift === 'morning') ? 'selected' : ''; ?>
                    >
                        Morning
                    </option>

                    <option
                        value="evening"
                        <?php echo ($shift === 'evening') ? 'selected' : ''; ?>
                    >
                        Evening
                    </option>

                </select>

            </div>


            <!-- Buttons -->
            <div class="md:col-span-2 flex items-end gap-2">

                <button
                    type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded"
                >
                    Show
                </button>

                <a
                    href="<?php echo BASE_URL; ?>/farmer/myreport.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                >
                    Reset
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="bg-gray-700 hover:bg-gray-800 text-white px-5 py-2 rounded"
                >
                    Print
                </button>

            </div>

        </form>

    </div>


    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                Total Litres
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($totalLitres, 2); ?> L
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                Total Amount
            </p>

            <p class="text-2xl font-bold mt-1">
                Rs. <?php echo number_format($totalAmount, 2); ?>
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                Morning
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($morningLitres, 2); ?> L
            </p>

        </div>


        <div class="bg-white rounded-lg shadow p-5">

            <p class="text-sm text-gray-500">
                Evening
            </p>

            <p class="text-2xl font-bold mt-1">
                <?php echo number_format($eveningLitres, 2); ?> L
            </p>

        </div>

    </div>


    <!-- Calendar -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">

        <h2 class="font-semibold text-gray-700 mb-4">
            Calendar - <?php echo htmlspecialchars($monthName); ?>
        </h2>


        <!-- Week days -->
        <div class="grid grid-cols-7 gap-2 mb-2">

            <?php

            $weekDays = array(
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
                'Sat',
                'Sun'
            );

            foreach ($weekDays as $dayName):

            ?>

                <div class="text-center text-sm font-semibold text-gray-600 p-2">
                    <?php echo $dayName; ?>
                </div>

            <?php endforeach; ?>

        </div>


        <!-- Calendar days -->
        <div class="grid grid-cols-7 gap-2">

            <?php foreach ($calendar as $cell): ?>

                <?php if ($cell['day'] === null): ?>

                    <div class="border rounded p-2 min-h-20 bg-gray-50"></div>

                <?php elseif ($cell['future']): ?>

                    <div class="border rounded p-2 min-h-20 bg-gray-100">

                        <div class="text-xs text-gray-400">
                            <?php echo $cell['day']; ?>
                        </div>

                        <div class="text-xs text-gray-400 mt-1">
                            -
                        </div>

                    </div>

                <?php else: ?>

                    <div class="border rounded p-2 min-h-20">

                        <div class="text-xs font-semibold text-gray-600">
                            <?php echo $cell['day']; ?>
                        </div>

                        <?php if (count($cell['entries']) > 0): ?>

                            <?php foreach ($cell['entries'] as $entry): ?>

                                <div class="text-xs mt-1">

                                    <span class="font-semibold text-blue-600">
                                        <?php echo number_format($entry['litre'], 2); ?> L
                                    </span>

                                    <span class="text-gray-500">
                                        (<?php echo strtoupper(substr($entry['shift'], 0, 1)); ?>)
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="text-xs text-gray-400 mt-1">
                                No entry
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- Detailed entries -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-5 py-4 bg-gray-50 border-b flex justify-between">

            <h2 class="font-semibold text-gray-700">
                Detailed Entries -
                <?php echo htmlspecialchars($monthName); ?>
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo count($entries); ?> entries
            </span>

        </div>


        <?php if (count($entries) == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No entries for the selected filters.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Date
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

                            <th class="px-4 py-3 text-right">
                                Rate
                            </th>

                            <th class="px-4 py-3 text-right">
                                Amount
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php foreach ($entries as $entry): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo formatDate($entry['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <?php echo ucfirst($entry['shift']); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($entry['litre'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($entry['fat'], 1); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($entry['snf'], 1); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    Rs.
                                    <?php echo number_format($entry['rate_applied'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold">
                                    Rs.
                                    <?php echo number_format($entry['amount'], 2); ?>
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
                                Rs.
                                <?php echo number_format($totalAmount, 2); ?>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>