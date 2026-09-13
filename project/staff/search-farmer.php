<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');
require_once __DIR__ . '/../config/db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = null;
$farmer = null;

if ($search != '') {
    if (is_numeric($search)) {
        $sql = "SELECT f.id, f.address, f.join_date, u.username, u.full_name, u.phone
                FROM farmers f JOIN users u ON f.user_id = u.id
                WHERE f.id = " . (int)$search;
    } else {
        $sql = "SELECT f.id, f.address, f.join_date, u.username, u.full_name, u.phone
                FROM farmers f JOIN users u ON f.user_id = u.id
                WHERE u.username LIKE '%$search%' OR u.full_name LIKE '%$search%'";
    }
    $results = $conn->query($sql);

    if ($results->num_rows == 1) {
        $farmer = $results->fetch_assoc();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Search Farmer</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter Farmer ID or Username" class="flex-1 border border-gray-300 rounded-md p-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">Search</button>
            <a href="search-farmer.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">Clear</a>
        </form>
    </div>

    <?php if ($farmer != null): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($farmer['full_name']); ?></h2>
            <p class="text-gray-600">Farmer ID: <?php echo $farmer['id']; ?></p>
            <p class="text-gray-600">Username: <?php echo htmlspecialchars($farmer['username']); ?></p>
            <p class="text-gray-600">Phone: <?php echo htmlspecialchars($farmer['phone']); ?></p>
            <p class="text-gray-600">Address: <?php echo htmlspecialchars($farmer['address']); ?></p>

            <div class="mt-4 flex gap-2">
                <a href="entry-milk.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Enter Milk</a>
                <a href="entry-dana.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Enter Dana</a>
            </div>
        </div>
    <?php elseif ($search != '' && $results != null && $results->num_rows > 1): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                while ($row = $results->fetch_assoc()) {
                    echo '<tr class="border-t">';
                    echo '<td class="px-4 py-3">' . $row['id'] . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($row['full_name']) . '</td>';
                    echo '<td class="px-4 py-3"><a href="?search=' . $row['id'] . '" class="text-blue-600 hover:underline">View</a></td>';
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($search != ''): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4">
            <p class="font-semibold">No farmer found.</p>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>