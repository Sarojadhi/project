<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/db.php';

$message = "";

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $join_date = $_POST['join_date'];

    $sql = "INSERT INTO users (username, password, role, full_name, phone, status)
            VALUES ('$username', '$password', 'farmer', '$full_name', '$phone', 'active')";

    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;

        $sql = "INSERT INTO farmers (user_id, address, join_date, status)
                VALUES ('$user_id', '$address', '$join_date', 'active')";
        $conn->query($sql);

        $message = "Farmer added successfully!";
    } else {
        $message = "Failed: " . $conn->error;
    }
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $sql = "SELECT status FROM farmers WHERE id = $id";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';

    $conn->query("UPDATE farmers SET status = '$newStatus' WHERE id = $id");
    $conn->query("UPDATE users SET status = '$newStatus' WHERE id = (SELECT user_id FROM farmers WHERE id = $id)");

    header('Location: manage-farmers.php');
    exit;
}

// Get all farmers
$sql = "SELECT f.id, f.address, f.join_date, f.status, u.username, u.full_name, u.phone
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.id DESC";
$result = $conn->query($sql);

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Farmers</h1>

    <?php if ($message != ""): ?>
        <div class="mb-4 p-4 rounded bg-green-100 text-green-700"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Add New Farmer</h2>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="full_name" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Join Date</label>
                    <input type="date" name="join_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Farmer</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold text-gray-700">All Farmers</h2>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows == 0) {
                echo '<tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">No farmers yet.</td></tr>';
            } else {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr class="border-t">';
                    echo '<td class="px-4 py-3">' . $row['id'] . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($row['username']) . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($row['full_name']) . '</td>';
                    echo '<td class="px-4 py-3">' . htmlspecialchars($row['phone']) . '</td>';
                    echo '<td class="px-4 py-3">' . ucfirst($row['status']) . '</td>';
                    echo '<td class="px-4 py-3">';
                    echo '<a href="?toggle=' . $row['id'] . '" class="text-blue-600 hover:underline">';
                    echo ($row['status'] === 'active') ? 'Deactivate' : 'Activate';
                    echo '</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>