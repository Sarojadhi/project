<?php
/**
 * Admin - Rate Settings
 * 
 * Allows the admin to manage two pricing tables:
 *   1. FAT / SNF rate chart (reference — admin sets expected rates)
 *   2. Dana / Chowker prices (used by entry-dana.php to compute cost)
 * 
 * Note: The milk rate is calculated on the fly in entry-milk.php using
 * the formula Rate = FAT × SNF. The rate_chart table below is a
 * reference table for the admin, not a lookup source.
 * 
 * Access: Only admins. Enforced by requireRole('admin').
 * 
 * Client-side validation is loaded from /assets/js/validate-rate-settings.js.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$message = "";
$messageType = "";   // 'success' or 'error'

// =====================================================
// 1. ADD RATE TO CHART
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rate'])) {

    $fat  = $_POST['fat'];
    $snf  = $_POST['snf'];
    $rate = $_POST['rate'];

    $sql = "INSERT INTO rate_chart (fat_value, snf_value, rate_per_litre, effective_from)
            VALUES ('$fat', '$snf', '$rate', CURDATE())";

    if ($conn->query($sql) === TRUE) {
        $message = "Rate added successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to add rate: " . $conn->error;
        $messageType = "error";
    }
}

// =====================================================
// 2. UPDATE RATE IN CHART
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rate'])) {

    $id   = (int)$_POST['rate_id'];
    $fat  = $_POST['fat'];
    $snf  = $_POST['snf'];
    $rate = $_POST['rate'];

    $sql = "UPDATE rate_chart
            SET fat_value = '$fat', snf_value = '$snf', rate_per_litre = '$rate'
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $message = "Rate updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update rate: " . $conn->error;
        $messageType = "error";
    }
}

// =====================================================
// 3. DELETE RATE FROM CHART
// =====================================================
if (isset($_GET['del_rate'])) {

    $id = (int)$_GET['del_rate'];

    if ($conn->query("DELETE FROM rate_chart WHERE id = $id") === TRUE) {
        $message = "Rate deleted.";
        $messageType = "success";
    } else {
        $message = "Failed to delete rate: " . $conn->error;
        $messageType = "error";
    }

    header('Location: /admin/rate-setting.php');
    exit;
}

// =====================================================
// 4. ADD DANA PRICE
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dana'])) {

    $item  = $_POST['item_name'];
    $price = $_POST['price'];

    $sql = "INSERT INTO dana_price (item_name, price_per_unit, effective_from)
            VALUES ('$item', '$price', CURDATE())";

    if ($conn->query($sql) === TRUE) {
        $message = "Dana price added successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to add dana price: " . $conn->error;
        $messageType = "error";
    }
}

// =====================================================
// 5. UPDATE DANA PRICE
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dana'])) {

    $id    = (int)$_POST['dana_id'];
    $item  = $_POST['item_name'];
    $price = $_POST['price'];

    $sql = "UPDATE dana_price
            SET item_name = '$item', price_per_unit = '$price'
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $message = "Dana price updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update dana price: " . $conn->error;
        $messageType = "error";
    }
}

// =====================================================
// 6. DELETE DANA PRICE
// =====================================================
if (isset($_GET['del_dana'])) {

    $id = (int)$_GET['del_dana'];

    if ($conn->query("DELETE FROM dana_price WHERE id = $id") === TRUE) {
        $message = "Dana price deleted.";
        $messageType = "success";
    } else {
        $message = "Failed to delete dana price: " . $conn->error;
        $messageType = "error";
    }

    header('Location: /admin/rate-setting.php');
    exit;
}

// =====================================================
// 7. LOAD EDIT DATA (if ?edit_rate=ID or ?edit_dana=ID)
// =====================================================
$editRate = null;
$editDana = null;

if (isset($_GET['edit_rate'])) {
    $id = (int)$_GET['edit_rate'];
    $result = $conn->query("SELECT * FROM rate_chart WHERE id = $id");
    $editRate = $result->fetch_assoc();
}

if (isset($_GET['edit_dana'])) {
    $id = (int)$_GET['edit_dana'];
    $result = $conn->query("SELECT * FROM dana_price WHERE id = $id");
    $editDana = $result->fetch_assoc();
}

// =====================================================
// 8. LOAD ALL RATES AND DANA PRICES
// =====================================================
$rateList = $conn->query("SELECT * FROM rate_chart ORDER BY fat_value, snf_value");
$danaList = $conn->query("SELECT * FROM dana_price ORDER BY item_name");

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Rate Settings</h1>

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
    <!-- RATE CHART SECTION                                    -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">

        <h2 class="text-xl font-semibold mb-4">
            <?php echo $editRate ? 'Edit Rate Entry' : 'Add FAT / SNF Rate'; ?>
        </h2>

        <p class="text-sm text-gray-500 mb-4">
            This is a reference chart. Milk rate is calculated in the entry form as FAT × SNF.
        </p>

        <form method="POST" id="rateForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

            <?php if ($editRate): ?>
                <input type="hidden" name="rate_id" value="<?php echo $editRate['id']; ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">FAT %</label>
                <input type="number" step="0.1" id="fat" name="fat"
                       value="<?php echo $editRate ? $editRate['fat_value'] : ''; ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                <span id="fat-error" class="text-sm text-red-600"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">SNF %</label>
                <input type="number" step="0.1" id="snf" name="snf"
                       value="<?php echo $editRate ? $editRate['snf_value'] : ''; ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                <span id="snf-error" class="text-sm text-red-600"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Rate (Rs./L)</label>
                <input type="number" step="0.01" id="rate" name="rate"
                       value="<?php echo $editRate ? $editRate['rate_per_litre'] : ''; ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                <span id="rate-error" class="text-sm text-red-600"></span>
            </div>

            <div class="flex items-end gap-2">
                <?php if ($editRate): ?>
                    <button type="submit" name="update_rate"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Update Rate
                    </button>
                    <a href="/admin/rate-setting.php"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add_rate"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add Rate
                    </button>
                <?php endif; ?>
            </div>

        </form>

        <?php if ($rateList->num_rows == 0): ?>

            <div class="text-center text-gray-500 py-4">No rate entries yet.</div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">FAT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SNF</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $rateList->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $row['id']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo number_format($row['fat_value'], 1); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo number_format($row['snf_value'], 1); ?></td>
                        <td class="px-4 py-3 text-sm">Rs. <?php echo number_format($row['rate_per_litre'], 2); ?></td>
                        <td class="px-4 py-3 text-sm space-x-3">
                            <a href="/admin/rate-setting.php?edit_rate=<?php echo $row['id']; ?>"
                               class="text-blue-600 hover:underline">Edit</a>
                            <a href="/admin/rate-setting.php?del_rate=<?php echo $row['id']; ?>"
                               class="text-red-600 hover:underline"
                               onclick="return confirm('Delete this rate entry?');">
                                Delete
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

    <!-- ===================================================== -->
    <!-- DANA PRICES SECTION                                   -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6">

        <h2 class="text-xl font-semibold mb-4">
            <?php echo $editDana ? 'Edit Dana Price' : 'Add Dana / Chowker Price'; ?>
        </h2>

        <form method="POST" id="danaForm" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <?php if ($editDana): ?>
                <input type="hidden" name="dana_id" value="<?php echo $editDana['id']; ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">Item Name</label>
                <input type="text" id="item_name" name="item_name"
                       value="<?php echo $editDana ? htmlspecialchars($editDana['item_name']) : ''; ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                <span id="item_name-error" class="text-sm text-red-600"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Price (Rs./Kg)</label>
                <input type="number" step="0.01" id="price" name="price"
                       value="<?php echo $editDana ? $editDana['price_per_unit'] : ''; ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                <span id="price-error" class="text-sm text-red-600"></span>
            </div>

            <div class="flex items-end gap-2">
                <?php if ($editDana): ?>
                    <button type="submit" name="update_dana"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Update Dana Price
                    </button>
                    <a href="/admin/rate-setting.php"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add_dana"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Add Dana Price
                    </button>
                <?php endif; ?>
            </div>

        </form>

        <?php if ($danaList->num_rows == 0): ?>

            <div class="text-center text-gray-500 py-4">No dana prices yet.</div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $danaList->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $row['id']; ?></td>
                        <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['item_name']); ?></td>
                        <td class="px-4 py-3 text-sm">Rs. <?php echo number_format($row['price_per_unit'], 2); ?></td>
                        <td class="px-4 py-3 text-sm space-x-3">
                            <a href="/admin/rate-setting.php?edit_dana=<?php echo $row['id']; ?>"
                               class="text-blue-600 hover:underline">Edit</a>
                            <a href="/admin/rate-setting.php?del_dana=<?php echo $row['id']; ?>"
                               class="text-red-600 hover:underline"
                               onclick="return confirm('Delete this dana price?');">
                                Delete
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<script src="/assets/js/validate-rate-settings.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>