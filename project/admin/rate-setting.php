<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$success = '';
$error = '';

$fatRate = '';
$snfRate = '';


// Get current milk rates

$stmt = $conn->prepare(
    "SELECT id, fat_rate, snf_rate
     FROM milk_rate_settings
     ORDER BY id DESC
     LIMIT 1"
);

$stmt->execute();

$result = $stmt->get_result();

$currentRate = $result->fetch_assoc();

$stmt->close();


// Update milk rates

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_milk_rate'])
) {

    $fatRate = trim($_POST['fat_rate'] ?? '');
    $snfRate = trim($_POST['snf_rate'] ?? '');

    if ($fatRate === '' || $snfRate === '') {

        $error = 'Please enter both FAT and SNF rates.';

    } elseif (
        !is_numeric($fatRate) ||
        !is_numeric($snfRate)
    ) {

        $error = 'FAT and SNF rates must be valid numbers.';

    } elseif (
        (float) $fatRate <= 0 ||
        (float) $snfRate <= 0
    ) {

        $error = 'FAT and SNF rates must be greater than 0.';

    } else {

        $fatRate = (float) $fatRate;
        $snfRate = (float) $snfRate;


        if ($currentRate) {

            $stmt = $conn->prepare(
                "UPDATE milk_rate_settings
                 SET fat_rate = ?, snf_rate = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ddi',
                $fatRate,
                $snfRate,
                $currentRate['id']
            );

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO milk_rate_settings
                 (fat_rate, snf_rate)
                 VALUES (?, ?)"
            );

            $stmt->bind_param(
                'dd',
                $fatRate,
                $snfRate
            );
        }


        if ($stmt->execute()) {

            $success = 'Milk rates updated successfully.';

        } else {

            $error = 'Failed to update milk rates.';

        }

        $stmt->close();
    }
}


// Load current milk rates again

$stmt = $conn->prepare(
    "SELECT id, fat_rate, snf_rate
     FROM milk_rate_settings
     ORDER BY id DESC
     LIMIT 1"
);

$stmt->execute();

$result = $stmt->get_result();

$currentRate = $result->fetch_assoc();

$stmt->close();


if ($currentRate) {

    $fatRate = $currentRate['fat_rate'];
    $snfRate = $currentRate['snf_rate'];

}


// Add dana price

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_dana'])
) {

    $itemName = trim($_POST['item_name'] ?? '');
    $price = trim($_POST['price_per_unit'] ?? '');


    if ($itemName === '' || $price === '') {

        $error = 'Please enter item name and price.';

    } elseif (strlen($itemName) < 3) {

        $error = 'Item name must contain at least 3 characters.';

    } elseif (!is_numeric($price)) {

        $error = 'Dana price must be a valid number.';

    } elseif ((float) $price <= 0) {

        $error = 'Dana price must be greater than 0.';

    } else {

        $price = (float) $price;


        $stmt = $conn->prepare(
            "INSERT INTO dana_price
             (item_name, price_per_unit, effective_from)
             VALUES (?, ?, CURDATE())"
        );

        $stmt->bind_param(
            'sd',
            $itemName,
            $price
        );


        if ($stmt->execute()) {

            $success = 'Dana price added successfully.';

        } else {

            $error = 'Failed to add dana price.';

        }

        $stmt->close();
    }
}


// Update dana price

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_dana'])
) {

    $danaId = (int) ($_POST['dana_id'] ?? 0);
    $price = trim($_POST['price_per_unit'] ?? '');


    if ($danaId <= 0) {

        $error = 'Invalid dana item.';

    } elseif (!is_numeric($price)) {

        $error = 'Dana price must be a valid number.';

    } elseif ((float) $price <= 0) {

        $error = 'Dana price must be greater than 0.';

    } else {

        $price = (float) $price;


        $stmt = $conn->prepare(
            "UPDATE dana_price
             SET price_per_unit = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            'di',
            $price,
            $danaId
        );


        if ($stmt->execute()) {

            $success = 'Dana price updated successfully.';

        } else {

            $error = 'Failed to update dana price.';

        }

        $stmt->close();
    }
}


// Get dana prices

$danaPrices = array();

$stmt = $conn->prepare(
    "SELECT id, item_name, price_per_unit, effective_from
     FROM dana_price
     ORDER BY id DESC"
);

$stmt->execute();

$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $danaPrices[] = $row;

}

$stmt->close();


require_once __DIR__ . '/../includes/header.php';

?>

<div class="max-w-6xl mx-auto px-4 py-8">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        Rate Settings
    </h1>


    <!-- Success Message -->

    <?php if ($success): ?>

        <div
            id="successMessage"
            class="mb-6 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded"
        >
            <?php echo e($success); ?>
        </div>

    <?php endif; ?>


    <!-- Error Message -->

    <?php if ($error): ?>

        <div
            class="mb-6 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded"
        >
            <?php echo e($error); ?>
        </div>

    <?php endif; ?>


    <!-- Milk Rate Section -->

    <div class="bg-white rounded-lg shadow p-6 mb-8">

        <h2 class="text-lg font-semibold text-gray-800 mb-6">
            Milk Rate
        </h2>


        <form
            method="POST"
            id="rateForm"
        >

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                <!-- FAT Rate -->

                <div>

                    <label
                        for="fat_rate"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        FAT Rate
                    </label>

                    <input
                        type="number"
                        name="fat_rate"
                        id="fat_rate"
                        step="0.5"
                        min="0.5"
                        value="<?php echo e($fatRate); ?>"
                        placeholder="8.50"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <p
                        id="fat-rate-error"
                        class="text-red-600 text-sm mt-1"
                    ></p>

                </div>


                <!-- SNF Rate -->

                <div>

                    <label
                        for="snf_rate"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        SNF Rate
                    </label>

                    <input
                        type="number"
                        name="snf_rate"
                        id="snf_rate"
                        step="0.5"
                        min="0.5"
                        value="<?php echo e($snfRate); ?>"
                        placeholder="4.00"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >

                    <p
                        id="snf-rate-error"
                        class="text-red-600 text-sm mt-1"
                    ></p>

                </div>

            </div>




            <!-- Update Button -->

            <div class="mt-6">

                <button
                    type="submit"
                    name="update_milk_rate"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"
                >
                    Update Milk Rate
                </button>

            </div>

        </form>

    </div>


    <!-- Dana Price Section -->

    <div class="bg-white rounded-lg shadow p-6">

        <h2 class="text-lg font-semibold text-gray-800">
            Dana Price
        </h2>

        <p class="text-sm text-gray-500 mt-1 mb-6">
            Manage feed prices.
        </p>


        <!-- Add Dana -->

        <form
            method="POST"
            id="danaForm"
            class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8"
        >


            <!-- Item Name -->

            <div>

                <label
                    for="item_name"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Item Name
                </label>

                <input
                    type="text"
                    name="item_name"
                    id="item_name"
                    placeholder="Example: Cow Feed"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                <p
                    id="item-name-error"
                    class="text-red-600 text-sm mt-1"
                ></p>

            </div>


            <!-- Price -->

            <div>

                <label
                    for="price_per_unit"
                    class="block text-sm font-medium text-gray-700 mb-1"
                >
                    Price Per Unit
                </label>

                <input
                    type="number"
                    name="price_per_unit"
                    id="price_per_unit"
                    step="0.01"
                    min="0.01"
                    placeholder="Example: 45.00"
                    class="w-full border border-gray-300 rounded px-3 py-2"
                >

                <p
                    id="price-error"
                    class="text-red-600 text-sm mt-1"
                ></p>

            </div>


            <!-- Add Button -->

            <div class="flex items-end">

                <button
                    type="submit"
                    name="add_dana"
                    class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                >
                    Add Dana Price
                </button>

            </div>

        </form>


        <!-- Dana List -->

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>

                    <tr class="border-b bg-gray-50">

                        <th class="text-left px-4 py-3">
                            Item
                        </th>

                        <th class="text-left px-4 py-3">
                            Price
                        </th>

                        <th class="text-left px-4 py-3">
                            Effective From
                        </th>

                        <th class="text-left px-4 py-3">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($danaPrices)): ?>

                        <tr>

                            <td
                                colspan="4"
                                class="text-center text-gray-500 py-6"
                            >
                                No dana prices found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($danaPrices as $dana): ?>

                            <tr class="border-b">

                                <td class="px-4 py-3">
                                    <?php echo e($dana['item_name']); ?>
                                </td>


                                <td class="px-4 py-3">
                                    Rs.
                                    <?php echo number_format(
                                        $dana['price_per_unit'],
                                        2
                                    ); ?>
                                </td>


                                <td class="px-4 py-3">
                                    <?php echo e(
                                        formatDate($dana['effective_from'])
                                    ); ?>
                                </td>


                                <td class="px-4 py-3">

                                    <form
                                        method="POST"
                                        class="flex items-center gap-2"
                                    >

                                        <input
                                            type="hidden"
                                            name="dana_id"
                                            value="<?php echo (int) $dana['id']; ?>"
                                        >


                                        <input
                                            type="number"
                                            name="price_per_unit"
                                            step="0.01"
                                            min="0.01"
                                            value="<?php echo e(
                                                $dana['price_per_unit']
                                            ); ?>"
                                            class="w-28 border border-gray-300 rounded px-2 py-1"
                                        >


                                        <button
                                            type="submit"
                                            name="update_dana"
                                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<script src="<?php echo BASE_URL; ?>/assets/js/validate-rate-settings.js"></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const successMessage =
        document.getElementById('successMessage');

    if (successMessage) {

        setTimeout(function () {

            successMessage.remove();

        }, 3000);

    }

});

</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>