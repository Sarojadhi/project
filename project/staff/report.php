<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');
require_once __DIR__ . '/../config/db.php';

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where = "DATE(m.entry_date) BETWEEN '$date_from' AND '$date_to'";
if ($search != '') {
    if (is_numeric($search)) {
        $where .= " AND f.id = " . (int)$search;
    } else {
        $where .= " AND u.username LIKE '%$search%'";
    }
}

$sql = "SELECT m.entry_date, m.shift, m.litre, m.fat, m.snf, u.full_name, u.username, f.id AS farmer_id
        FROM milk_entries m
        JOIN farmers f ON m.farmer_id = f.id
        JOIN users u ON f.user_id = u.id
        WHERE $where
        ORDER BY m.entry_date DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Milk Report</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Date From</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date To</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Search (ID or Username)</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Show</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $total += $row['litre'];
                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . date('d-M-Y', strtotime($row['entry_date'])) . '</td>';
                echo '<td class="px-4 py-3">' . $row['farmer_id'] . '</td>';
                echo '<td class="px-4 py-3">' . htmlspecialchars($row['full_name']) . '</td>';
                echo '<td class="px-4 py-3">' . htmlspecialchars($row['username']) . '</td>';
                echo '<td class="px-4 py-3">' . ucfirst($row['shift']) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['litre'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['fat'], 1) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['snf'], 1) . '</td>';
                echo '</tr>';
            }
            ?>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-right">TOTAL</td>
                    <td class="px-4 py-3 text-right"><?php echo number_format($total, 2); ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>