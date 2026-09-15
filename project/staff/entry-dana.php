<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$messageType = '';

$userId = (int) $_SESSION['user_id'];


// Save dana entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmerId = (int) ($_POST['farmer_id'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? '';
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $quantity = (float) ($_POST['quantity'] ?? 0);

    $today = date('Y-m-d');


    // Check farmer
    if ($farmerId <= 0) {

        $message = 'Please select a farmer.';
        $messageType = 'error';


    // Check date
    } elseif ($entryDate === '') {

        $message = 'Please select a date.';
        $messageType = 'error';


    } elseif ($entryDate > $today) {

        $message = 'Date cannot be in the future.';
        $messageType = 'error';


    // Check item
    } elseif ($itemId <= 0) {

        $message = 'Please select an item.';
        $messageType = 'error';


    // Check quantity
    } elseif ($quantity <= 0) {

        $message = 'Quantity must be greater than 0.';
        $messageType = 'error';


    } else {

        // Get dana item
        $stmt = $conn->prepare(
            "SELECT item_name, price_per_unit
             FROM dana_price
             WHERE id = ?"
        );

        $stmt->bind_param('i', $itemId);
        $stmt->execute();

        $item = $stmt->get_result()->fetch_assoc();


        if (!$item) {

            $message = 'Item not found.';
            $messageType = 'error';

        } else {

            $itemName = $item['item_name'];
            $price = (float) $item['price_per_unit'];
            $amount = $price * $quantity;


            // Save dana entry
            $stmt = $conn->prepare(
                "INSERT INTO dana_entries
                (
                    farmer_id,
                    entry_date,
                    item_name,
                    quantity,
                    price_applied,
                    amount,
                    entered_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'issdddi',
                $farmerId,
                $entryDate,
                $itemName,
                $quantity,
                $price,
                $amount,
                $userId
            );


            if ($stmt->execute()) {

                $message = 'Dana entry saved successfully.';
                $messageType = 'success';

            } else {

                $message = 'Failed to save entry.';
                $messageType = 'error';
            }
        }
    }
}


// Farmer list
$farmerList = $conn->query(
    "SELECT
        f.id,
        u.full_name
     FROM farmers f
     JOIN users u ON f.user_id = u.id
     WHERE f.status = 'active'
     AND u.status = 'active'
     ORDER BY u.full_name ASC"
);


// Dana item list
$itemList = $conn->query(
    "SELECT id, item_name
     FROM dana_price
     ORDER BY item_name ASC"
);


// Recent entries
$stmt = $conn->prepare(
    "SELECT
        d.entry_date,
        d.item_name,
        d.quantity,
        u.full_name AS farmer_name
     FROM dana_entries d
     JOIN farmers f ON d.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     WHERE d.entered_by = ?
     ORDER BY d.id DESC
     LIMIT 5"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$recentList = $stmt->get_result();


include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page heading -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Enter Dana / Chowker
        </h1>

        <p class="text-gray-500 mt-1">
            Record feed issued to a farmer.
        </p>

    </div>


    <!-- Message -->
    <?php if ($message !== ''): ?>

        <div class="mb-4 p-4 rounded-lg
            <?php
            echo $messageType === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700';
            ?>">

            <?php echo e($message); ?>

        </div>

    <?php endif; ?>


    <!-- Dana Entry Form -->
    <div class="bg-white border rounded-lg mb-6">

        <div class="px-5 py-4 border-b">

            <h2 class="font-semibold text-gray-800">
                New Dana Entry
            </h2>

        </div>


        <form
            method="POST"
            id="danaEntryForm"
            class="p-6"
        >

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Farmer -->
                <div>

                    <label
                        for="farmer_id"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Farmer
                    </label>

                    <select
                        name="farmer_id"
                        id="farmer_id"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                        <option value="0">
                            -- Select Farmer --
                        </option>

                        <?php while ($farmer = $farmerList->fetch_assoc()): ?>

                            <option
                                value="<?php echo $farmer['id']; ?>"
                            >
                                <?php echo e($farmer['full_name']); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <span
                        id="farmer-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>


                <!-- Date -->
                <div>

                    <label
                        for="entry_date"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Date
                    </label>

                    <input
                        type="date"
                        name="entry_date"
                        id="entry_date"
                        value="<?php echo date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="date-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>


                <!-- Item -->
                <div>

                    <label
                        for="item_id"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Item
                    </label>

                    <select
                        name="item_id"
                        id="item_id"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                        <option value="0">
                            -- Select Item --
                        </option>

                        <?php while ($item = $itemList->fetch_assoc()): ?>

                            <option
                                value="<?php echo $item['id']; ?>"
                            >
                                <?php echo e($item['item_name']); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <span
                        id="item-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>


                <!-- Quantity -->
                <div>

                    <label
                        for="quantity"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Quantity (kg)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="quantity"
                        id="quantity"
                        placeholder="e.g. 10.00"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="quantity-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

            </div>


            <!-- Buttons -->
            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    name="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg"
                >
                    Save Entry
                </button>

                <button
                    type="reset"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-6 rounded-lg"
                >
                    Reset
                </button>

            </div>

        </form>

    </div>


    <!-- Recent Entries -->
    <div class="bg-white border rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                Your Recent Dana Entries
            </h2>

            <span class="text-sm text-gray-500">
                Last 5
            </span>

        </div>


        <?php if ($recentList && $recentList->num_rows > 0): ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer
                            </th>

                            <th class="px-4 py-3 text-left">
                                Item
                            </th>

                            <th class="px-4 py-3 text-right">
                                Quantity
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php while ($row = $recentList->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-gray-600">
                                    <?php echo formatDate($row['entry_date']); ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo e($row['farmer_name']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo e($row['item_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['quantity'], 2); ?>
                                    kg
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="p-6 text-center text-gray-500">
                No entries yet.
            </div>

        <?php endif; ?>

    </div>

</div>


<script src="<?php echo BASE_URL; ?>/assets/js/validate-dana.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>