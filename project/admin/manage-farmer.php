<?php
/**
 * Admin - Manage Farmers
 * 
 * Allows admin AND staff to:
 *   - Add a new farmer (creates rows in both users and farmers tables)
 *   - Edit an existing farmer
 *   - Activate / deactivate a farmer account
 *   - View all farmers in a table
 * 
 * Access: Admin and staff only (farmers cannot open this page).
 * 
 * Note: $conn and session are loaded by auth.php.
 */

require_once __DIR__ . '/../includes/auth.php';
requireAdminOrStaff();

$message = "";
$messageType = "";   // 'success' or 'error' — controls the message colour

// =====================================================
// 1. HANDLE "ADD FARMER" FORM
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {

    $username  = $_POST['username'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $phone     = $_POST['phone'];
    $address   = $_POST['address'];
    $join_date = $_POST['join_date'];

    // First insert: users table
    $sql = "INSERT INTO users (username, password, role, full_name, phone, status)
            VALUES ('$username', '$password', 'farmer', '$full_name', '$phone', 'active')";

    if ($conn->query($sql) === TRUE) {

        $user_id = $conn->insert_id;

        // Second insert: farmers table
        $sql2 = "INSERT INTO farmers (user_id, address, join_date, status)
                 VALUES ('$user_id', '$address', '$join_date', 'active')";

        if ($conn->query($sql2) === TRUE) {
            $message = "Farmer added successfully!";
            $messageType = "success";
        } else {
            // Clean up the orphan users row
            $conn->query("DELETE FROM users WHERE id = $user_id");
            $message = "Failed to add farmer details: " . $conn->error;
            $messageType = "error";
        }

    } else {
        $message = "Failed to create user: " . $conn->error;
        $messageType = "error";
    }
}

// =====================================================
// 2. HANDLE "EDIT FARMER" FORM
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {

    $farmer_id = (int)$_POST['farmer_id'];
    $full_name = $_POST['full_name'];
    $phone     = $_POST['phone'];
    $address   = $_POST['address'];
    $join_date = $_POST['join_date'];

    // Update users table
    $conn->query("UPDATE users
                  SET full_name = '$full_name', phone = '$phone'
                  WHERE id = (SELECT user_id FROM farmers WHERE id = $farmer_id)");

    // Update farmers table
    $conn->query("UPDATE farmers
                  SET address = '$address', join_date = '$join_date'
                  WHERE id = $farmer_id");

    $message = "Farmer updated successfully!";
    $messageType = "success";
}

// =====================================================
// 3. HANDLE ACTIVATE / DEACTIVATE
// =====================================================
if (isset($_GET['toggle'])) {

    $id = (int)$_GET['toggle'];

    // Fetch current status
    $sql = "SELECT status FROM farmers WHERE id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if ($row) {

        $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';

        // Update farmers table
        $conn->query("UPDATE farmers SET status = '$newStatus' WHERE id = $id");

        // Update users table (same person)
        $conn->query("UPDATE users
                      SET status = '$newStatus'
                      WHERE id = (SELECT user_id FROM farmers WHERE id = $id)");

        $message = "Farmer status changed to " . $newStatus . ".";
        $messageType = "success";

    } else {
        $message = "Farmer not found.";
        $messageType = "error";
    }

    // Redirect to clean the URL
    header('Location: /admin/manage-farmer.php');
    exit;
}

// =====================================================
// 4. LOAD FARMER FOR EDIT MODE (if ?edit=ID)
// =====================================================
$editFarmer = null;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $sql = "SELECT f.id, f.address, f.join_date, u.full_name, u.phone
            FROM farmers f
            JOIN users u ON f.user_id = u.id
            WHERE f.id = $editId";
    $result = $conn->query($sql);
    $editFarmer = $result->fetch_assoc();
}

// =====================================================
// 5. LOAD ALL FARMERS FOR THE TABLE
// =====================================================
$sql = "SELECT f.id, f.status,
               u.username, u.full_name, u.phone
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.id DESC";
$farmerList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Farmers</h1>

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
    <!-- ADD / EDIT FORM                                       -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">

        <h2 class="text-xl font-semibold mb-4">
            <?php echo $editFarmer ? 'Edit Farmer' : 'Add New Farmer'; ?>
        </h2>

        <form method="POST" action="" id="userForm">

            <?php if ($editFarmer): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="farmer_id" value="<?php echo $editFarmer['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="add">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <?php if (!$editFarmer): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="username-error" class="text-sm text-red-600"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="password-error" class="text-sm text-red-600"></span>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?php echo $editFarmer ? htmlspecialchars($editFarmer['full_name']) : ''; ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="full_name-error" class="text-sm text-red-600"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" id="phone" name="phone"
                           value="<?php echo $editFarmer ? htmlspecialchars($editFarmer['phone']) : ''; ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="phone-error" class="text-sm text-red-600"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" id="address" name="address"
                           value="<?php echo $editFarmer ? htmlspecialchars($editFarmer['address']) : ''; ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="address-error" class="text-sm text-red-600"></span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Join Date</label>
                    <input type="date" id="join_date" name="join_date"
                           value="<?php echo $editFarmer ? $editFarmer['join_date'] : date('Y-m-d'); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                    <span id="join_date-error" class="text-sm text-red-600"></span>
                </div>

            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?php echo $editFarmer ? 'Update Farmer' : 'Add Farmer'; ?>
                </button>
                <?php if ($editFarmer): ?>
                    <a href="/admin/manage-farmer.php"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>

        </form>

    </div>

    <!-- ===================================================== -->
    <!-- FARMER TABLE                                          -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold text-gray-700">All Farmers</h2>
        </div>

        <?php if ($farmerList->num_rows == 0): ?>

            <div class="p-6 text-center text-gray-500">No farmers yet.</div>

        <?php else: ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $farmerList->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $row['id']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 text-xs rounded-full
                                <?php echo ($row['status'] === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm space-x-3">
                            <a href="/admin/manage-farmer.php?edit=<?php echo $row['id']; ?>"
                               class="text-blue-600 hover:underline">Edit</a>
                            <a href="/admin/report.php?search=<?php echo $row['id']; ?>"
                               class="text-purple-600 hover:underline">Ledger</a>
                            <a href="/admin/manage-farmer.php?toggle=<?php echo $row['id']; ?>"
                               class="text-<?php echo ($row['status'] === 'active') ? 'red' : 'green'; ?>-600 hover:underline"
                               onclick="return confirm('Change status for this farmer?');">
                                <?php echo ($row['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

<script src="/assets/js/validate-user-form.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>