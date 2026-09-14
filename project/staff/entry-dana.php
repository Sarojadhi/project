<?php
/**
 * Staff - Enter Dana / Chowker
 * 
 * Allows staff to record dana/chowker issued to a farmer:
 *   - Choose farmer, date, item
 *   - Enter quantity in kg
 *   - Server looks up price_per_unit from dana_price table
 *   - Server computes amount = price × quantity
 *   - Amount is SAVED to the database but NEVER shown to the staff
 * 
 * Access: Only staff. Enforced by requireRole('staff').
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

$message = "";
$messageType = "";   // 'success' or 'error'

// =====================================================
// 1. HANDLE FORM SUBMISSION
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmer_id  = (int)$_POST['farmer_id'];
    $entry_date = $_POST['entry_date'];
    $item_id    = (int)$_POST['item_id'];
    $quantity   = (float)$_POST['quantity'];

    // Look up item name and price from dana_price table
    $sql = "SELECT item_name, price_per_unit FROM dana_price WHERE id = $item_id";
    $result = $conn->query($sql);
    $item = $result->fetch_assoc();

    if (!$item) {
        // Item not found — probably a tampered or stale form
        $message = "Selected item is not available. Please choose another.";
        $messageType = "error";
    } else {

        $item_name = $item['item_name'];
        $price     = $item['price_per_unit'];
        $amount    = $price * $quantity;

        $entered_by = $_SESSION['user_id'];

        // Insert into dana_entries
        $sql = "INSERT INTO dana_entries
                (farmer_id, entry_date, item_name, quantity, price_applied, amount, entered_by)
                VALUES
                ('$farmer_id', '$entry_date', '$item_name', '$quantity', '$price', '$amount', '$entered_by')";

        if ($conn->query($sql) === TRUE) {
            $message = "Dana entry saved successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to save entry: " . $conn->error;
            $messageType = "error";
        }
    }
}

// =====================================================
// 2. LOAD ACTIVE FARMERS FOR DROPDOWN
// =====================================================
$sql = "SELECT f.id, u.full_name
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        WHERE f.status = 'active' AND u.status = 'active'
        ORDER BY u.full_name ASC";
$farmerList = $conn->query($sql);

// =====================================================
// 3. LOAD DANA ITEMS FOR DROPDOWN (name only — no price shown)
// =====================================================
$sql = "SELECT id, item_name FROM dana_price ORDER BY item_name ASC";
$itemList = $conn->query($sql);

// =====================================================
// 4. LOAD RECENT ENTRIES BY THIS STAFF (last 5)
// =====================================================
$staff_id = $_SESSION['user_id'];

$sql = "SELECT d.entry_date, d.item_name, d.quantity,
               u.full_name AS farmer_name
        FROM dana_entries d
        JOIN farmers f ON d.farmer_id = f.id
        JOIN users u ON f.user_id = u.id
        WHERE d.entered_by = $staff_id
        ORDER BY d.id DESC
        LIMIT 5";
$recentList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Enter Dana / Chowker</h1>

    <!-- ===================================================== -->
    <!-- MESSAGE BOX                                           -->
    <!-- ===================================================== -->
    <?php if ($message != ""): ?>
        <div class="mb-4 p-4 rounded
            <?php echo ($messageType === 'success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- ===================================================== -->
    <!-- ENTRY FORM                                            -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">

        <form method="POST" id="danaEntryForm">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Farmer -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Farmer</label>
                    <select name="farmer_id" id="farmer_id"
                            class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="0">-- Select Farmer --</option>
                        <?php while ($f = $farmerList->fetch_assoc()): ?>
                            <option value="<?php echo $f['id']; ?>">
                                <?php echo htmlspecialchars($f['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <span id="farmer-error" class="text-sm text-red-600"></span>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="entry_date" id="entry_date"
                           value="<?php echo date('Y-m-d'); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="date-error" class="text-sm text-red-600"></span>
                </div>

                <!-- Item (name only — price is hidden) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Item</label>
                    <select name="item_id" id="item_id"
                            class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="0">-- Select Item --</option>
                        <?php while ($i = $itemList->fetch_assoc()): ?>
                            <option value="<?php echo $i['id']; ?>">
                                <?php echo htmlspecialchars($i['item_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <span id="item-error" class="text-sm text-red-600"></span>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity (kg)</label>
                    <input type="number" step="0.01" name="quantity" id="quantity"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2"
                           placeholder="e.g. 10.00">
                    <span id="quantity-error" class="text-sm text-red-600"></span>
                </div>

            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" name="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Save Entry
                </button>
                <button type="reset"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">
                    Reset Form
                </button>
            </div>

        </form>

    </div>

    <!-- ===================================================== -->
    <!-- RECENT ENTRIES BY THIS STAFF                          -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-700">Your Recent Dana Entries</h2>
            <span class="text-sm text-gray-500">Last 5 entries</span>
        </div>

        <?php if ($recentList->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">
                No entries yet. Fill the form above to add one.
            </div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farmer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity (kg)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $recentList->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <?php echo date('d-M-Y', strtotime($row['entry_date'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium">
                            <?php echo htmlspecialchars($row['farmer_name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo htmlspecialchars($row['item_name']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['quantity'], 2); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<script src="/assets/js/validate-dana-entry.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>