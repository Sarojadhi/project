<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT f.id, f.address, f.join_date, f.status, u.username, u.full_name, u.phone
        FROM farmers f JOIN users u ON f.user_id = u.id
        WHERE f.id = $id";
$result = $conn->query($sql);
$farmer = $result->fetch_assoc();

if (!$farmer) {
    echo "Farmer not found.";
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <a href="reports.php" class="text-blue-600 hover:underline">&larr; Back to Reports</a>

    <h1 class="text-3xl font-bold text-gray-800 mt-4 mb-2"><?php echo htmlspecialchars($farmer['full_name']); ?></h1>
    <p class="text-gray-600 mb-6">
        ID: <?php echo $farmer['id']; ?> |
        Username: <?php echo htmlspecialchars($farmer['username']); ?> |
        Phone: <?php echo htmlspecialchars($farmer['phone']); ?>
    </p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold">Milk Entries</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM milk_entries WHERE farmer_id = $id ORDER BY entry_date DESC";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . date('d-M-Y', strtotime($row['entry_date'])) . '</td>';
                echo '<td class="px-4 py-3">' . ucfirst($row['shift']) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['litre'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['fat'], 1) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['snf'], 1) . '</td>';
                echo '<td class="px-4 py-3 text-right">' . number_format($row['rate_applied'], 2) . '</td>';
                echo '<td class="px-4 py-3 text-right">Rs. ' . number_format($row['amount'], 2) . '</td>';
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>