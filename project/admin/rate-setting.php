<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/db.php';

$message = '';
$messageType = '';

// ------------------------------------
// ADD RATE
// ------------------------------------

if (isset($_POST['add_rate'])) {

    $fat = $_POST['fat'];
    $snf = $_POST['snf'];
    $rate = $_POST['rate'];

    $stmt = $conn->prepare("
        INSERT INTO rate_chart
        (fat_value, snf_value, rate_per_litre, effective_from)
        VALUES (?, ?, ?, CURDATE())
    ");

    $stmt->bind_param("ddd", $fat, $snf, $rate);

    if ($stmt->execute()) {
        $message = "Rate added successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to add rate.";
        $messageType = "error";
    }
}


// ------------------------------------
// UPDATE RATE
// ------------------------------------

elseif (isset($_POST['update_rate'])) {

    $id = (int) $_POST['rate_id'];
    $fat = $_POST['fat'];
    $snf = $_POST['snf'];
    $rate = $_POST['rate'];

    $stmt = $conn->prepare("
        UPDATE rate_chart
        SET fat_value = ?, snf_value = ?, rate_per_litre = ?
        WHERE id = ?
    ");

    $stmt->bind_param("dddi", $fat, $snf, $rate, $id);

    if ($stmt->execute()) {
        $message = "Rate updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update rate.";
        $messageType = "error";
    }
}


// ------------------------------------
// DELETE RATE
// ------------------------------------

elseif (isset($_POST['delete_rate'])) {

    $id = (int) $_POST['rate_id'];

    $stmt = $conn->prepare("
        DELETE FROM rate_chart
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $message = "Rate deleted.";
    $messageType = "success";
}


// ------------------------------------
// ADD DANA PRICE
// ------------------------------------

elseif (isset($_POST['add_dana'])) {

    $item = trim($_POST['item_name']);
    $price = $_POST['price'];

    $stmt = $conn->prepare("
        INSERT INTO dana_price
        (item_name, price_per_unit, effective_from)
        VALUES (?, ?, CURDATE())
    ");

    $stmt->bind_param("sd", $item, $price);

    if ($stmt->execute()) {
        $message = "Dana price added successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to add dana price.";
        $messageType = "error";
    }
}


// ------------------------------------
// UPDATE DANA PRICE
// ------------------------------------

elseif (isset($_POST['update_dana'])) {

    $id = (int) $_POST['dana_id'];
    $item = trim($_POST['item_name']);
    $price = $_POST['price'];

    $stmt = $conn->prepare("
        UPDATE dana_price
        SET item_name = ?, price_per_unit = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sdi", $item, $price, $id);

    if ($stmt->execute()) {
        $message = "Dana price updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update dana price.";
        $messageType = "error";
    }
}


// ------------------------------------
// DELETE DANA PRICE
// ------------------------------------

elseif (isset($_POST['delete_dana'])) {

    $id = (int) $_POST['dana_id'];

    $stmt = $conn->prepare("
        DELETE FROM dana_price
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $message = "Dana price deleted.";
    $messageType = "success";
}


// ------------------------------------
// GET EDIT RATE
// ------------------------------------

$editRate = null;

if (isset($_GET['edit_rate'])) {

    $id = (int) $_GET['edit_rate'];

    $stmt = $conn->prepare("
        SELECT *
        FROM rate_chart
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editRate = $stmt->get_result()->fetch_assoc();
}


// ------------------------------------
// GET EDIT DANA
// ------------------------------------

$editDana = null;

if (isset($_GET['edit_dana'])) {

    $id = (int) $_GET['edit_dana'];

    $stmt = $conn->prepare("
        SELECT *
        FROM dana_price
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editDana = $stmt->get_result()->fetch_assoc();
}


// ------------------------------------
// GET ALL RATES
// ------------------------------------

$rates = $conn->query("
    SELECT *
    FROM rate_chart
    ORDER BY fat_value, snf_value
");


// ------------------------------------
// GET ALL DANA PRICES
// ------------------------------------

$danaPrices = $conn->query("
    SELECT *
    FROM dana_price
    ORDER BY item_name
");

include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-6xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-bold text-gray-800 mb-1">
        Rate Settings
    </h1>

    <p class="text-gray-500 text-sm mb-6">
        Manage milk rates and dana prices.
    </p>


    <!-- MESSAGE -->

    <?php if ($message !== ''): ?>

        <div class="mb-6 p-3 rounded border
            <?php echo $messageType === 'success'
                ? 'bg-green-50 border-green-200 text-green-700'
                : 'bg-red-50 border-red-200 text-red-700';
            ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- MILK RATE -->

    <div class="bg-white border rounded-lg shadow-sm mb-6">

        <div class="px-5 py-4 border-b">

            <h2 class="font-semibold text-gray-800">
                <?php echo $editRate ? 'Edit Milk Rate' : 'Add Milk Rate'; ?>
            </h2>

        </div>


        <form method="POST" class="p-5">

            <?php if ($editRate): ?>

                <input
                    type="hidden"
                    name="rate_id"
                    value="<?php echo $editRate['id']; ?>"
                >

            <?php endif; ?>


            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>

                    <label class="block text-sm mb-1">
                        FAT %
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        name="fat"
                        required
                        value="<?php
                            echo $editRate
                                ? htmlspecialchars($editRate['fat_value'])
                                : '';
                        ?>"
                        class="w-full border rounded px-3 py-2"
                    >

                </div>


                <div>

                    <label class="block text-sm mb-1">
                        SNF %
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        name="snf"
                        required
                        value="<?php
                            echo $editRate
                                ? htmlspecialchars($editRate['snf_value'])
                                : '';
                        ?>"
                        class="w-full border rounded px-3 py-2"
                    >

                </div>


                <div>

                    <label class="block text-sm mb-1">
                        Rate per Litre
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="rate"
                        required
                        value="<?php
                            echo $editRate
                                ? htmlspecialchars($editRate['rate_per_litre'])
                                : '';
                        ?>"
                        class="w-full border rounded px-3 py-2"
                    >

                </div>


                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        name="<?php echo $editRate ? 'update_rate' : 'add_rate'; ?>"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                    >
                        <?php echo $editRate ? 'Update' : 'Add'; ?>
                    </button>


                    <?php if ($editRate): ?>

                        <a
                            href="rate-setting.php"
                            class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>


        <!-- RATE TABLE -->

        <div class="overflow-x-auto px-5 pb-5">

            <?php if ($rates->num_rows > 0): ?>

                <table class="w-full text-sm">

                    <thead>

                        <tr class="border-b text-left">

                            <th class="py-3">ID</th>
                            <th class="py-3">FAT</th>
                            <th class="py-3">SNF</th>
                            <th class="py-3">Rate</th>
                            <th class="py-3">Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while ($row = $rates->fetch_assoc()): ?>

                            <tr class="border-b">

                                <td class="py-3">
                                    <?php echo $row['id']; ?>
                                </td>

                                <td class="py-3">
                                    <?php echo $row['fat_value']; ?>%
                                </td>

                                <td class="py-3">
                                    <?php echo $row['snf_value']; ?>%
                                </td>

                                <td class="py-3">
                                    Rs. <?php echo number_format($row['rate_per_litre'], 2); ?>
                                </td>

                                <td class="py-3">

                                    <a
                                        href="rate-setting.php?edit_rate=<?php echo $row['id']; ?>"
                                        class="text-blue-600 mr-3"
                                    >
                                        Edit
                                    </a>


                                    <form method="POST" class="inline">

                                        <input
                                            type="hidden"
                                            name="rate_id"
                                            value="<?php echo $row['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_rate"
                                            class="text-red-600"
                                            onclick="return confirm('Delete this rate?');"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p class="text-gray-500 text-sm py-4">
                    No milk rates found.
                </p>

            <?php endif; ?>

        </div>

    </div>


    <!-- DANA PRICE -->

    <div class="bg-white border rounded-lg shadow-sm">

        <div class="px-5 py-4 border-b">

            <h2 class="font-semibold text-gray-800">
                <?php echo $editDana ? 'Edit Dana Price' : 'Add Dana Price'; ?>
            </h2>

        </div>


        <form method="POST" class="p-5">

            <?php if ($editDana): ?>

                <input
                    type="hidden"
                    name="dana_id"
                    value="<?php echo $editDana['id']; ?>"
                >

            <?php endif; ?>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>

                    <label class="block text-sm mb-1">
                        Item Name
                    </label>

                    <input
                        type="text"
                        name="item_name"
                        required
                        value="<?php
                            echo $editDana
                                ? htmlspecialchars($editDana['item_name'])
                                : '';
                        ?>"
                        class="w-full border rounded px-3 py-2"
                    >

                </div>


                <div>

                    <label class="block text-sm mb-1">
                        Price per Kg
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="price"
                        required
                        value="<?php
                            echo $editDana
                                ? htmlspecialchars($editDana['price_per_unit'])
                                : '';
                        ?>"
                        class="w-full border rounded px-3 py-2"
                    >

                </div>


                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        name="<?php echo $editDana ? 'update_dana' : 'add_dana'; ?>"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
                    >
                        <?php echo $editDana ? 'Update' : 'Add'; ?>
                    </button>


                    <?php if ($editDana): ?>

                        <a
                            href="rate-setting.php"
                            class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded"
                        >
                            Cancel
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>


        <!-- DANA TABLE -->

        <div class="overflow-x-auto px-5 pb-5">

            <?php if ($danaPrices->num_rows > 0): ?>

                <table class="w-full text-sm">

                    <thead>

                        <tr class="border-b text-left">

                            <th class="py-3">ID</th>
                            <th class="py-3">Item</th>
                            <th class="py-3">Price</th>
                            <th class="py-3">Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while ($row = $danaPrices->fetch_assoc()): ?>

                            <tr class="border-b">

                                <td class="py-3">
                                    <?php echo $row['id']; ?>
                                </td>

                                <td class="py-3">
                                    <?php echo htmlspecialchars($row['item_name']); ?>
                                </td>

                                <td class="py-3">
                                    Rs. <?php echo number_format($row['price_per_unit'], 2); ?>
                                </td>

                                <td class="py-3">

                                    <a
                                        href="rate-setting.php?edit_dana=<?php echo $row['id']; ?>"
                                        class="text-blue-600 mr-3"
                                    >
                                        Edit
                                    </a>


                                    <form method="POST" class="inline">

                                        <input
                                            type="hidden"
                                            name="dana_id"
                                            value="<?php echo $row['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_dana"
                                            class="text-red-600"
                                            onclick="return confirm('Delete this dana price?');"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <p class="text-gray-500 text-sm py-4">
                    No dana prices found.
                </p>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>