<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('farmer');
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT id FROM farmers WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$farmer_id = $row['id'];

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$first_day = sprintf('%04d-%02d-01', $year, $month);
$last_day = date('Y-m-t', strtotime($first_day));

$sql = "SELECT * FROM dana_entries
        WHERE farmer_id = $farmer_id AND entry_date BETWEEN '$first_day' AND '$last_day'
        ORDER BY entry_date";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">My Dana / Chowker Records</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Month</label>
                <select name="month" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <?php
                    $months = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                                    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');
                    foreach ($months as $num => $name) {
                        $sel = ($num == $month) ? 'selected' : '';
                        echo '<option value="' . $num . '" ' . $sel . '>' . $name . '</option>';
                    }
                    ?>
                </select>
            </div>
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
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Show</button>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Print</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Running Total</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $running = 0;
            while ($row = $result->fetch_assoc()) {
                $running += $row['amount'];
                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . date('d-M-Y', strtotime($row['entry_date'])) . '</td>';
                echo '<td class="px-4 py-3">' . htmlspecialchars($row['item_name']) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['quantity'], 2) . ' kg</td>';
                echo '<td class="px-4 py-3 text-right">Rs. ' . number_format($row['price_applied'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">Rs. ' . number_format($row['amount'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right font-semibold">Rs. ' . number_format($running, 2) . '</td>';
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>