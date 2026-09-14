<?php
/**
 * Staff - Enter Milk Collection
 * 
 * Allows staff to record a milk collection entry:
 *   - Choose farmer, date, shift
 *   - Enter litres, FAT %, SNF %
 *   - Server computes rate (FAT × SNF) and amount (rate × litres)
 *   - Amount is SAVED to the database but NEVER shown to the staff
 * 
 * Duplicate prevention:
 *   The database has a UNIQUE KEY on (farmer_id, entry_date, shift).
 *   If a duplicate is submitted, MySQL refuses it and we show a message.
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
    $shift      = $_POST['shift'];
    $litre      = (float)$_POST['litre'];
    $fat        = (float)$_POST['fat'];
    $snf        = (float)$_POST['snf'];

    // Rate = FAT × SNF (see project report for pricing method)
    $rate   = $fat * $snf;
    $amount = $rate * $litre;

    // Who entered this record
    $entered_by = $_SESSION['user_id'];

    // Insert into database
    $sql = "INSERT INTO milk_entries
            (farmer_id, entry_date, shift, litre, fat, snf, rate_applied, amount, entered_by)
            VALUES
            ('$farmer_id', '$entry_date', '$shift', '$litre', '$fat', '$snf', '$rate', '$amount', '$entered_by')";

    if ($conn->query($sql) === TRUE) {
        $message = "Milk entry saved successfully.";
        $messageType = "success";
    } else {
        // Detect duplicate-key error (MySQL error code 1062)
        if ($conn->errno === 1062) {
            $message = "This farmer already has a " . $shift . " entry on " . $entry_date . ". Please check before entering again.";
        } else {
            $message = "Failed to save entry: " . $conn->error;
        }
        $messageType = "error";
    }
}

// =====================================================
// 2. LOAD ACTIVE FARMERS FOR THE DROPDOWN
// =====================================================
$sql = "SELECT f.id, u.full_name
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        WHERE f.status = 'active' AND u.status = 'active'
        ORDER BY u.full_name ASC";
$farmerList = $conn->query($sql);

// =====================================================
// 3. LOAD RECENT ENTRIES BY THIS STAFF (last 5)
// =====================================================
$staff_id = $_SESSION['user_id'];

$sql = "SELECT m.entry_date, m.shift, m.litre, m.fat, m.snf,
               u.full_name AS farmer_name
        FROM milk_entries m
        JOIN farmers f ON m.farmer_id = f.id
        JOIN users u ON f.user_id = u.id
        WHERE m.entered_by = $staff_id
        ORDER BY m.id DESC
        LIMIT 5";
$recentList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Enter Milk Collection</h1>

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

        <form method="POST" id="milkEntryForm">

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

                <!-- Shift -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Shift</label>
                    <select name="shift" id="shift"
                            class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                    </select>
                    <span id="shift-error" class="text-sm text-red-600"></span>
                </div>

                <!-- Litres -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Litres</label>
                    <input type="number" step="0.01" name="litre" id="litre"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2"
                           placeholder="e.g. 5.50">
                    <span id="litre-error" class="text-sm text-red-600"></span>
                </div>

                <!-- FAT -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">FAT %</label>
                    <input type="number" step="0.1" name="fat" id="fat"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2"
                           placeholder="e.g. 4.0">
                    <span id="fat-error" class="text-sm text-red-600"></span>
                </div>

                <!-- SNF -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">SNF %</label>
                    <input type="number" step="0.1" name="snf" id="snf"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2"
                           placeholder="e.g. 9.0">
                    <span id="snf-error" class="text-sm text-red-600"></span>
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
            <h2 class="text-lg font-semibold text-gray-700">Your Recent Milk Entries</h2>
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
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Shift</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Litres</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">FAT</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SNF</th>
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
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 text-xs rounded-full
                                <?php echo ($row['shift'] === 'morning') ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo ucfirst($row['shift']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['litre'], 2); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['fat'], 1); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <?php echo number_format($row['snf'], 1); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<script src="/assets/js/validate-milk-entry.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>