<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$messageType = '';

$userId = (int) $_SESSION['user_id'];


// Save milk entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmerId = (int) ($_POST['farmer_id'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $litre = (float) ($_POST['litre'] ?? 0);
    $fat = (float) ($_POST['fat'] ?? 0);
    $snf = (float) ($_POST['snf'] ?? 0);

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


    // Check shift
    } elseif ($shift !== 'morning' && $shift !== 'evening') {

        $message = 'Invalid shift.';
        $messageType = 'error';


    // Check litres
    } elseif ($litre <= 0) {

        $message = 'Litres must be greater than 0.';
        $messageType = 'error';


    // Check FAT
    } elseif ($fat <= 0 || $fat > 10) {

        $message = 'FAT must be between 0 and 10.';
        $messageType = 'error';


    // Check SNF
    } elseif ($snf <= 0 || $snf > 15) {

        $message = 'SNF must be between 0 and 15.';
        $messageType = 'error';


    } else {

        /*
         * Current project rate calculation.
         * FAT × SNF
         */
        $rate = $fat * $snf;
        $amount = $rate * $litre;


        // Save milk entry
        $stmt = $conn->prepare(
            "INSERT INTO milk_entries
            (
                farmer_id,
                entry_date,
                shift,
                litre,
                fat,
                snf,
                rate_applied,
                amount,
                entered_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'issdddddi',
            $farmerId,
            $entryDate,
            $shift,
            $litre,
            $fat,
            $snf,
            $rate,
            $amount,
            $userId
        );


        if ($stmt->execute()) {

            $message = 'Milk entry saved successfully.';
            $messageType = 'success';

        } else {

            if ($stmt->errno == 1062) {

                $message =
                    "Duplicate: this farmer already has a $shift entry on $entryDate.";

            } else {

                $message = 'Failed to save entry.';
            }

            $messageType = 'error';
        }
    }
}


// Active farmer list
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


// Recent milk entries
$stmt = $conn->prepare(
    "SELECT
        m.entry_date,
        m.shift,
        m.litre,
        m.fat,
        m.snf,
        u.full_name AS farmer_name
     FROM milk_entries m
     JOIN farmers f ON m.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     WHERE m.entered_by = ?
     ORDER BY m.id DESC
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
            Enter Milk Collection
        </h1>

        <p class="text-gray-500 mt-1">
            Record daily milk collection.
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


    <!-- Milk Entry Form -->
    <div class="bg-white border rounded-lg mb-6">

        <div class="px-5 py-4 border-b">

            <h2 class="font-semibold text-gray-800">
                New Milk Entry
            </h2>

        </div>


        <form
            method="POST"
            id="milkEntryForm"
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


                <!-- Shift -->
                <div>

                    <label
                        for="shift"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Shift
                    </label>

                    <select
                        name="shift"
                        id="shift"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                        <option value="morning">
                            Morning
                        </option>

                        <option value="evening">
                            Evening
                        </option>

                    </select>

                </div>


                <!-- Litres -->
                <div>

                    <label
                        for="litre"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Litres
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="litre"
                        id="litre"
                        placeholder="e.g. 5.50"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="litre-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>


                <!-- FAT -->
                <div>

                    <label
                        for="fat"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        FAT %
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        max="10"
                        name="fat"
                        id="fat"
                        placeholder="e.g. 4.0"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="fat-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>


                <!-- SNF -->
                <div>

                    <label
                        for="snf"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        SNF %
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        max="15"
                        name="snf"
                        id="snf"
                        placeholder="e.g. 9.0"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="snf-error"
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
                Your Recent Entries
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

                            <th class="px-4 py-3 text-center">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right">
                                Litres
                            </th>

                            <th class="px-4 py-3 text-right">
                                FAT
                            </th>

                            <th class="px-4 py-3 text-right">
                                SNF
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

                                <td class="px-4 py-3 text-center">
                                    <?php echo e(ucfirst($row['shift'])); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold">
                                    <?php echo number_format($row['litre'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['fat'], 1); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <?php echo number_format($row['snf'], 1); ?>
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


<script src="<?php echo BASE_URL; ?>/assets/js/validate-milk.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>