<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

$sql = "SELECT f.id, u.full_name,
        COALESCE((SELECT SUM(litre) FROM milk_entries WHERE farmer_id = f.id AND entry_date BETWEEN '$date_from' AND '$date_to'), 0) AS total_litres,
        COALESCE((SELECT SUM(amount) FROM milk_entries WHERE farmer_id = f.id AND entry_date BETWEEN '$date_from' AND '$date_to'), 0) AS milk_amount,
        COALESCE((SELECT SUM(amount) FROM dana_entries WHERE farmer_id = f.id AND entry_date BETWEEN '$date_from' AND '$date_to'), 0) AS dana_amount
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        ORDER BY u.full_name";
$result = $conn->query($sql);

$total_litres = 0;
$total_milk = 0;
$total_dana = 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Full Reports</h1>

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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Milk Amount</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dana Deduction</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Payable</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while ($row = $result->fetch_assoc()) {
                $net = $row['milk_amount'] - $row['dana_amount'];
                $total_litres += $row['total_litres'];
                $total_milk += $row['milk_amount'];
                $total_dana += $row['dana_amount'];

                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . htmlspecialchars($row['full_name']) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['total_litres'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">Rs. ' . number_format($row['milk_amount'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">Rs. ' . number_format($row['dana_amount'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right font-semibold">Rs. ' . number_format($net, 2) . '</td>';
                echo '</tr>';
            }
            ?>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
                <tr>
                    <td class="px-4 py-3 text-right">TOTAL</td>
                    <td class="px-4 py-3 text-right"><?php echo number_format($total_litres, 2); ?></td>
                    <td class="px-4 py-3 text-right">Rs. <?php echo number_format($total_milk, 2); ?></td>
                    <td class="px-4 py-3 text-right">Rs. <?php echo number_format($total_dana, 2); ?></td>
                    <td class="px-4 py-3 text-right">Rs. <?php echo number_format($total_milk - $total_dana, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>