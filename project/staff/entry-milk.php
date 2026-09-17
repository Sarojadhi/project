<?php

date_default_timezone_set('Asia/Kathmandu');

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$messageType = '';

$userId = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$currentHour = (int) date('H');


// Set default shift from Nepal local time
$defaultShift = $currentHour < 12 ? 'morning' : 'evening';


// Get current milk rates
$fatRate = 8.50;
$snfRate = 4.00;

$stmt = $conn->prepare(
    "SELECT fat_rate, snf_rate
     FROM milk_rate_settings
     ORDER BY id DESC
     LIMIT 1"
);

$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $fatRate = (float) $row['fat_rate'];
    $snfRate = (float) $row['snf_rate'];
}

$stmt->close();


// Save milk entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmerId = (int) ($_POST['farmer_id'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $litre = (float) ($_POST['litre'] ?? 0);
    $fat = (float) ($_POST['fat'] ?? 0);
    $snf = (float) ($_POST['snf'] ?? 0);


    // Validate farmer
    if ($farmerId <= 0) {

        $message = 'Please select a farmer.';
        $messageType = 'error';


    // Validate date
    } elseif ($entryDate !== $today) {

        $message = 'Only today\'s date is allowed.';
        $messageType = 'error';


    // Validate shift
    } elseif ($shift !== 'morning' && $shift !== 'evening') {

        $message = 'Invalid shift.';
        $messageType = 'error';


    // Validate litres
    } elseif ($litre <= 0) {

        $message = 'Litres must be greater than 0.';
        $messageType = 'error';


    // Validate FAT
    } elseif ($fat <= 1.6 || $fat >= 8) {

        $message = 'FAT must be greater than 1.6 and less than 8.';
        $messageType = 'error';


    // Validate SNF
    } elseif ($snf <= 3 || $snf >= 9) {

        $message = 'SNF must be greater than 3 and less than 9.';
        $messageType = 'error';


    } else {

        // Check that farmer is active
        $stmt = $conn->prepare(
            "SELECT f.id
             FROM farmers f
             JOIN users u ON f.user_id = u.id
             WHERE f.id = ?
             AND f.status = 'active'
             AND u.status = 'active'"
        );

        $stmt->bind_param('i', $farmerId);
        $stmt->execute();

        $farmerResult = $stmt->get_result();

        $farmerExists = $farmerResult->num_rows === 1;

        $stmt->close();


        if (!$farmerExists) {

            $message = 'Selected farmer is not active.';
            $messageType = 'error';

        } else {

            // Calculate milk rate
            $rate = ($fat * $fatRate) + ($snf * $snfRate);

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
                        "Duplicate: this farmer already has a $shift entry today.";

                } else {

                    $message = 'Failed to save entry.';
                }

                $messageType = 'error';
            }

            $stmt->close();
        }
    }
}


// Get active farmers
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


// Get recent milk entries
$stmt = $conn->prepare(
    "SELECT
        m.entry_date,
        m.shift,
        m.litre,
        m.fat,
        m.snf,
        m.rate_applied,
        m.amount,
        u.full_name AS farmer_name
     FROM milk_entries m
     JOIN farmers f ON m.farmer_id = f.id
     JOIN users u ON f.user_id = u.id
     WHERE m.entered_by = ?
     ORDER BY m.id DESC
     LIMIT 10"
);

$stmt->bind_param('i', $userId);
$stmt->execute();

$recentList = $stmt->get_result();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page heading -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Enter Milk Collection
        </h1>

        <p class="text-gray-500 mt-1">
            Record today's milk collection.
        </p>

    </div>


    <!-- Message -->
    <?php if ($message !== ''): ?>

        <div
            id="message"
            class="mb-4 p-4 rounded-lg
            <?php
            echo $messageType === 'success'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700';
            ?>"
        >
            <?php echo e($message); ?>
        </div>

    <?php endif; ?>


    <!-- Milk entry form -->
    <div class="bg-white border border-gray-200 rounded-lg mb-6">

        <div class="px-5 py-4 border-b border-gray-200">

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
                <div class="relative">

                    <label
                        for="farmer_search"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Farmer
                    </label>

                    <input
                        type="text"
                        id="farmer_search"
                        autocomplete="off"
                        placeholder="Type farmer name..."
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <input
                        type="hidden"
                        name="farmer_id"
                        id="farmer_id"
                        value=""
                    >

                    <div
                        id="farmer-suggestions"
                        class="absolute z-20 left-0 right-0 bg-white border border-gray-300 rounded-lg mt-1 hidden max-h-52 overflow-y-auto shadow"
                    ></div>

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
                        type="text"
                        id="entry_date"
                        value="<?php echo date('d-M-Y'); ?>"
                        readonly
                        class="w-full border border-gray-300 rounded-lg p-3 bg-gray-100 text-gray-700 cursor-not-allowed"
                    >

                    <input
                        type="hidden"
                        name="entry_date"
                        value="<?php echo $today; ?>"
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

                        <option
                            value="morning"
                            <?php echo $defaultShift === 'morning' ? 'selected' : ''; ?>
                        >
                            Morning
                        </option>

                        <option
                            value="evening"
                            <?php echo $defaultShift === 'evening' ? 'selected' : ''; ?>
                        >
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
                        step="0.1"
                        min="0.1"
                        name="litre"
                        id="litre"
                        placeholder="e.g. 5.5"
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
                        min="1.7"
                        max="7.9"
                        name="fat"
                        id="fat"
                        value="4.0"
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
                        min="3.1"
                        max="8.9"
                        name="snf"
                        id="snf"
                        value="9.0"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="snf-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

            </div>


            <!-- Rate preview -->
            <div class="mt-5 bg-gray-50 border border-gray-200 rounded-lg p-5">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>

                        <p class="text-xs text-gray-500">
                            Rate Per Litre
                        </p>

                        <p
                            id="rate-preview"
                            class="text-xl font-bold text-gray-800 mt-1"
                        >
                            Rs. 70.00
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-500">
                            Litres
                        </p>

                        <p
                            id="litre-preview"
                            class="text-xl font-bold text-gray-800 mt-1"
                        >
                            0.0 L
                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-gray-500">
                            Total Amount
                        </p>

                        <p
                            id="amount-preview"
                            class="text-xl font-bold text-green-600 mt-1"
                        >
                            Rs. 0.00
                        </p>

                    </div>

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
                    id="resetButton"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 px-6 rounded-lg"
                >
                    Reset
                </button>

            </div>

        </form>

    </div>


    <!-- Recent entries -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">

            <div>

                <h2 class="font-semibold text-gray-800">
                    Your Recent Entries
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Your latest 10 milk entries.
                </p>

            </div>

            <span class="text-sm text-gray-500">
                Last 10
            </span>

        </div>


        <?php if ($recentList && $recentList->num_rows > 0): ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                S.N.
                            </th>

                            <th class="px-4 py-3 text-left">
                                Farmer
                            </th>

                            <th class="px-4 py-3 text-center">
                                Shift
                            </th>

                            <th class="px-4 py-3 text-right">
                                Ltr
                            </th>

                            <th class="px-4 py-3 text-right">
                                Rate/Ltr
                            </th>

                            <th class="px-4 py-3 text-right">
                                Total Rs.
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y">

                        <?php $serialNumber = 1; ?>

                        <?php while ($row = $recentList->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-gray-600">
                                    <?php echo $serialNumber; ?>
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <?php echo e($row['farmer_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-center text-gray-600">
                                    <?php echo e(ucfirst($row['shift'])); ?>
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700">
                                    <?php echo number_format((float) $row['litre'], 1); ?>
                                </td>

                                <td class="px-4 py-3 text-right text-gray-700">
                                    Rs.
                                    <?php echo number_format((float) $row['rate_applied'], 2); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    Rs.
                                    <?php echo number_format((float) $row['amount'], 2); ?>
                                </td>

                            </tr>

                            <?php $serialNumber++; ?>

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


<script>

    const milkFatRate = <?php echo json_encode($fatRate); ?>;
    const milkSnfRate = <?php echo json_encode($snfRate); ?>;

</script>

<script src="<?php echo BASE_URL; ?>/assets/js/validate-milk-entry.js"></script>


<script>

    const message = document.getElementById('message');

    if (message) {

        setTimeout(function () {
            message.style.display = 'none';
        }, 3000);

    }

</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>