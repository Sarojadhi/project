<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');
require_once __DIR__ . '/../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $farmer_id = (int)$_POST['farmer_id'];
    $entry_date = $_POST['entry_date'];
    $shift = $_POST['shift'];
    $litre = (float)$_POST['litre'];
    $fat = (float)$_POST['fat'];
    $snf = (float)$_POST['snf'];

    $rate = $fat * $snf;
    $amount = $rate * $litre;
    $entered_by = $_SESSION['user_id'];

    $sql = "INSERT INTO milk_entries (farmer_id, entry_date, shift, litre, fat, snf, rate_applied, amount, entered_by)
            VALUES ('$farmer_id', '$entry_date', '$shift', '$litre', '$fat', '$snf', '$rate', '$amount', '$entered_by')";

    if ($conn->query($sql)) {
        $message = "Entry saved.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Get farmers
$farmers = $conn->query("SELECT f.id, u.full_name FROM farmers f JOIN users u ON f.user_id = u.id WHERE u.status = 'active' ORDER BY u.full_name");

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Enter Milk Collection</h1>

    <?php if ($message != ""): ?>
        <div class="mb-4 p-4 rounded bg-green-100 text-green-700"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" id="milkEntryForm">
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
                    <span id="date-error" class="text-sm text-red-600"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Shift</label>
                    <select name="shift" id="shift" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Litres</label>
                    <input type="number" step="0.01" name="litre" id="litre" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="litre-error" class="text-sm text-red-600"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">FAT %</label>
                    <input type="number" step="0.1" name="fat" id="fat" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="fat-error" class="text-sm text-red-600"></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">SNF %</label>
                    <input type="number" step="0.1" name="snf" id="snf" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="snf-error" class="text-sm text-red-600"></span>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" name="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">Save Entry</button>
            </div>
        </form>
    </div>

</div>

<script src="/assets/js/validate-milk-entry.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>