<?php
/**
 * Farmer - My Milk Reports
 * 
 * Shows this farmer's own milk history for a selected month:
 *   - Month / Year / Shift filters
 *   - Summary cards: total litres, milk amount, morning litres, evening litres
 *   - Calendar view: every day of the month is shown;
 *       - Past days with entries show litres per shift
 *       - Past days without entries show "No entry"
 *       - Future days show "-" (null)
 *   - Detailed table of all entries in the month
 *   - Print button
 * 
 * Access: Only farmers. Enforced by requireRole('farmer').
 * Scope: Only this farmer's own data — every query filters by farmer_id.
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

// =====================================================
// 2. READ FILTER VALUES
// =====================================================
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$shift = isset($_GET['shift']) ? $_GET['shift'] : 'all';

if ($month < 1 || $month > 12) {
    $month = (int)date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = (int)date('Y');
}

// Date bounds
$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day  = date('Y-m-t', strtotime($first_day));
$monthName = date('F Y', strtotime($first_day));
$today     = date('Y-m-d');

// =====================================================
// 3. FETCH MILK ENTRIES FOR THIS FARMER AND MONTH
// =====================================================
$where = "farmer_id = $farmer_id AND entry_date BETWEEN '$first_day' AND '$last_day'";

if ($shift === 'morning' || $shift === 'evening') {
    $where .= " AND shift = '$shift'";
}

$sql = "SELECT id, entry_date, shift, litre, fat, snf, rate_applied, amount
        FROM milk_entries
        WHERE $where
        ORDER BY entry_date ASC, id ASC";

$result = $conn->query($sql);

// Store rows and compute totals
$entries = array();
$totalLitres  = 0;
$totalAmount  = 0;
$morningLitres = 0;
$eveningLitres = 0;

while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
    $totalLitres += $row['litre'];
    $totalAmount += $row['amount'];

    if ($row['shift'] === 'morning') {
        $morningLitres += $row['litre'];
    } elseif ($row['shift'] === 'evening') {
        $eveningLitres += $row['litre'];
    }
}

// =====================================================
// 4. BUILD A LOOKUP: DATE → ENTRIES
// =====================================================
$entriesByDate = array();

foreach ($entries as $e) {
    $d = $e['entry_date'];
    if (!isset($entriesByDate[$d])) {
        $entriesByDate[$d] = array();
    }
    $entriesByDate[$d][] = $e;
}

// =====================================================
// 5. BUILD CALENDAR STRUCTURE
// =====================================================
// Each cell holds: day, date, is_future, entries (or null)
$daysInMonth   = (int)date('t', strtotime($first_day));
$firstWeekday  = (int)date('N', strtotime($first_day)); // 1=Mon, 7=Sun

$cells = array();

// Leading blank cells so the 1st lines up with the correct weekday
for ($i = 1; $i < $firstWeekday; $i++) {
    $cells[] = array(
        'day'    => null,
        'date'   => null,
        'future' => false,
        'entries'=> null,
    );
}

// Actual days of the month
for ($day = 1; $day <= $daysInMonth; $day++) {

    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $isFuture = ($dateStr > $today);

    if ($isFuture) {
        // Future date → entries = null
        $cells[] = array(
            'day'    => $day,
            'date'   => $dateStr,
            'future' => true,
            'entries'=> null,
        );
    } else {
        // Past or today → entries = whatever we found (or empty array)
        $dayEntries = isset($entriesByDate[$dateStr]) ? $entriesByDate[$dateStr] : array();
        $cells[] = array(
            'day'    => $day,
            'date'   => $dateStr,
            'future' => false,
            'entries'=> $dayEntries,
        );
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-2">My Milk Reports</h1>
    <p class="text-gray-600 mb-6">
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        (Farmer ID: <?php echo $farmer_id; ?>)
    </p>

    <!-- ===================================================== -->
    <!-- FILTER FORM                                           -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <!-- Month -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Month</label>
                <select name="month" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <?php
                    $months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
                    foreach ($months as $num => $name) {
                        $sel = ($num == $month) ? 'selected' : '';
                        echo '<option value="' . $num . '" ' . $sel . '>' . $name . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Year -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Year</label>
                <select name="year" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <?php
                    $current = (int)date('Y');
                    for ($y = $current; $y >= $current - 5; $y--) {
                        $sel = ($y == $year) ? 'selected' : '';
                        echo '<option value="' . $y . '" ' . $sel . '>' . $y . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Shift (README requirement) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Shift</label>
                <select name="shift" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <option value="all"     <?php echo ($shift === 'all')     ? 'selected' : ''; ?>>All Shifts</option>
                    <option value="morning" <?php echo ($shift === 'morning') ? 'selected' : ''; ?>>Morning</option>
                    <option value="evening" <?php echo ($shift === 'evening') ? 'selected' : ''; ?>>Evening</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Show
                </button>
                <a href="/farmer/myreport.php"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Reset
                </a>
                <button type="button" onclick="window.print()"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Print
                </button>
            </div>

        </form>

    </div>

    <!-- ===================================================== -->
    <!-- SUMMARY CARDS                                         -->
    <!-- ===================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 uppercase">Total Litres</p>
            <p class="text-2xl font-bold text-blue-600">
                <?php echo number_format($totalLitres, 2); ?> L
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 uppercase">Total Amount</p>
            <p class="text-2xl font-bold text-green-600">
                Rs. <?php echo number_format($totalAmount, 2); ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-sm text-gray-500 uppercase">Morning Litres</p>
            <p class="text-2xl font-bold text-yellow-600">
                <?php echo number_format($morningLitres, 2); ?> L
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 uppercase">Evening Litres</p>
            <p class="text-2xl font-bold text-purple-600">
                <?php echo number_format($eveningLitres, 2); ?> L
            </p>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- CALENDAR VIEW                                         -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            Calendar — <?php echo $monthName; ?>
        </h2>

        <!-- Weekday headers -->
        <div class="grid grid-cols-7 gap-2 mb-2">
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Mon</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Tue</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Wed</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Thu</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Fri</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Sat</div>
            <div class="text-center text-sm font-semibold text-gray-600 p-2">Sun</div>
        </div>

        <!-- Calendar cells -->
        <div class="grid grid-cols-7 gap-2">

            <?php foreach ($cells as $cell): ?>

                <?php if ($cell['day'] === null): ?>

                    <!-- Leading empty cell -->
                    <div class="border border-gray-100 rounded p-2 min-h-20 bg-gray-50"></div>

                <?php elseif ($cell['future']): ?>

                    <!-- Future date → null -->
                    <div class="border border-gray-100 rounded p-2 min-h-20 bg-gray-100">
                        <div class="text-xs text-gray-400"><?php echo $cell['day']; ?></div>
                        <div class="text-xs text-gray-400 italic mt-1">-</div>
                    </div>

                <?php else: ?>

                    <?php
                    // Past or today
                    $count = count($cell['entries']);
                    $cellBg = ($count > 0) ? 'bg-white border-blue-300' : 'bg-white border-gray-200';
                    ?>

                    <div class="border rounded p-2 min-h-20 <?php echo $cellBg; ?>">
                        <div class="text-xs text-gray-600 font-semibold">
                            <?php echo $cell['day']; ?>
                        </div>

                        <?php if ($count > 0): ?>
                            <?php foreach ($cell['entries'] as $e): ?>
                                <div class="text-xs mt-1">
                                    <span class="font-semibold text-blue-600">
                                        <?php echo number_format($e['litre'], 2); ?>L
                                    </span>
                                    <span class="text-gray-500">
                                        (<?php echo substr(ucfirst($e['shift']), 0, 1); ?>)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-xs text-gray-400 italic mt-1">No entry</div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

        <p class="text-xs text-gray-500 mt-4">
            Legend: L = litres, M = morning, E = evening. Future dates are shown as blank.
        </p>

    </div>

    <!-- ===================================================== -->
    <!-- DETAILED ENTRIES TABLE                                -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">
                Detailed Entries — <?php echo $monthName; ?>
            </h2>
            <span class="text-sm text-gray-500"><?php echo count($entries); ?> entries</span>
        </div>

        <?php if (count($entries) == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No milk entries found for the selected filters.
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
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php foreach ($entries as $e): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('d-M-Y', strtotime($e['entry_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                <?php echo ($e['shift'] === 'morning') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo ucfirst($e['shift']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($e['litre'], 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($e['fat'], 1); ?></td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($e['snf'], 1); ?></td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($e['rate_applied'], 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">
                            Rs. <?php echo number_format($e['amount'], 2); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
                <tfoot class="bg-gray-100 font-semibold">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-right">TOTAL</td>
                        <td class="px-4 py-3 text-right"><?php echo number_format($totalLitres, 2); ?></td>
                        <td colspan="2"></td>
                        <td></td>
                        <td class="px-4 py-3 text-right">Rs. <?php echo number_format($totalAmount, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

        <?php endif; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>