<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

$message = "";

// Add rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rate'])) {
    $fat = $_POST['fat'];
    $snf = $_POST['snf'];
    $rate = $_POST['rate'];

    $sql = "INSERT INTO rate_chart (fat_value, snf_value, rate_per_litre, effective_from)
            VALUES ('$fat', '$snf', '$rate', CURDATE())";
    if ($conn->query($sql)) {
        $message = "Rate added.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Add dana price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dana'])) {
    $item = $_POST['item_name'];
    $price = $_POST['price'];

    $sql = "INSERT INTO dana_price (item_name, price_per_unit, effective_from)
            VALUES ('$item', '$price', CURDATE())";
    if ($conn->query($sql)) {
        $message = "Dana price added.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Delete rate
if (isset($_GET['del_rate'])) {
    $id = (int)$_GET['del_rate'];
    $conn->query("DELETE FROM rate_chart WHERE id = $id");
    header('Location: rate-settings.php');
    exit;
}

// Delete dana
if (isset($_GET['del_dana'])) {
    $id = (int)$_GET['del_dana'];
    $conn->query("DELETE FROM dana_price WHERE id = $id");
    header('Location: rate-settings.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Rate Settings</h1>

    <?php if ($message != ""): ?>
        <div class="mb-4 p-4 rounded bg-green-100 text-green-700"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Rate chart -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">FAT / SNF Rate Chart</h2>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">FAT %</label>
                <input type="number" step="0.1" name="fat" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">SNF %</label>
                <input type="number" step="0.1" name="snf" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Rate (Rs./L)</label>
                <input type="number" step="0.01" name="rate" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div class="flex items-end">
                <button type="submit" name="add_rate" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Rate</button>
            </div>
        </form>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">FAT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SNF</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = $conn->query("SELECT * FROM rate_chart ORDER BY fat_value");
            while ($row = $result->fetch_assoc()) {
                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . $row['id'] . '</td>';
                echo '<td class="px-4 py-3">' . $row['fat_value'] . '</td>';
                echo '<td class="px-4 py-3">' . $row['snf_value'] . '</td>';
                echo '<td class="px-4 py-3">Rs. ' . number_format($row['rate_per_litre'], 2) . '</td>';
                echo '<td class="px-4 py-3"><a href="?del_rate=' . $row['id'] . '" class="text-red-600 hover:underline">Delete</a></td>';
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

    <!-- Dana prices -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Dana / Chowker Prices</h2>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Item Name</label>
                <input type="text" name="item_name" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Price (Rs./Kg)</label>
                <input type="number" step="0.01" name="price" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
            </div>
            <div class="flex items-end">
                <button type="submit" name="add_dana" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Add Dana Price</button>
            </div>
        </form>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $result = $conn->query("SELECT * FROM dana_price ORDER BY item_name");
            while ($row = $result->fetch_assoc()) {
                echo '<tr class="border-t">';
                echo '<td class="px-4 py-3">' . $row['id'] . '</td>';
                echo '<td class="px-4 py-3">' . htmlspecialchars($row['item_name']) . '</td>';
                echo '<td class="px-4 py-3">Rs. ' . number_format($row['price_per_unit'], 2) . '</td>';
                echo '<td class="px-4 py-3"><a href="?del_dana=' . $row['id'] . '" class="text-red-600 hover:underline">Delete</a></td>';
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>