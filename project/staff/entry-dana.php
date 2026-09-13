<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');
require_once __DIR__ . '/../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmer_id = (int)$_POST['farmer_id'];
    $entry_date = $_POST['entry_date'];
    $item_id = (int)$_POST['item_id'];
    $quantity = (float)$_POST['quantity'];

    // Get item name and price
    $sql = "SELECT item_name, price_per_unit FROM dana_price WHERE id = $item_id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();

    $item_name = $item['item_name'];
    $price = $item['price_per_unit'];
    $amount = $price * $quantity;
    $entered_by = $_SESSION['user_id'];

    $sql = "INSERT INTO dana_entries (farmer_id, entry_date, item_name, quantity, price_applied, amount, entered_by)
            VALUES ('$farmer_id', '$entry_date', '$item_name', '$quantity', '$price', '$amount', '$entered_by')";

    if ($conn->query($sql)) {
        $message = "Entry saved.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

$farmers = $conn->query("SELECT f.id, u.full_name FROM farmers f JOIN users u ON f.user_id = u.id WHERE u.status = 'active' ORDER BY u.full_name");
$dana_items = $conn->query("SELECT * FROM dana_price ORDER BY item_name");

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Enter Dana / Chowker</h1>

    <?php if ($message != ""): ?>
        <div class="mb-4 p-4 rounded bg-green-100 text-green-700"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" id="danaEntryForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Farmer</label>
                    <select name="farmer_id" id="farmer_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="0">-- Select Farmer --</option>
                        <?php
                        while ($f = $farmers->fetch_assoc()) {
                            echo '<option value="' . $f['id'] . '">' . htmlspecialchars($f['full_name']) . '</option>';
                        }
                        ?>
                    </select>
                    <span id="farmer-error" class="text-sm text-red-600"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="entry_date" id="entry_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Item</label>
                    <select name="item_id" id="item_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="0">-- Select Item --</option>
                        <?php
                        while ($i = $dana_items->fetch_assoc()) {
                            echo '<option value="' . $i['id'] . '">' . htmlspecialchars($i['item_name']) . ' (Rs. ' . number_format($i['price_per_unit'], 2) . '/kg)</option>';
                        }
                        ?>
                    </select>
                    <span id="item-error" class="text-sm text-red-600"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                    <input type="number" step="0.01" name="quantity" id="quantity" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="quantity-error" class="text-sm text-red-600"></span>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">Save Entry</button>
            </div>
        </form>
    </div>

</div>

<script src="/assets/js/validate-dana-entry.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>