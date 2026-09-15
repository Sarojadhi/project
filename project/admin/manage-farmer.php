<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminOrStaff();

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Add Farmer
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'add') {

        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $joinDate = $_POST['join_date'];

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users
            (username, password, role, full_name, phone, status)
            VALUES (?, ?, 'farmer', ?, ?, 'active')"
        );

        $stmt->bind_param(
            'ssss',
            $username,
            $hashedPassword,
            $fullName,
            $phone
        );

        if ($stmt->execute()) {

            $userId = $conn->insert_id;

            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO farmers
                (user_id, address, join_date, status)
                VALUES (?, ?, ?, 'active')"
            );

            $stmt->bind_param(
                'iss',
                $userId,
                $address,
                $joinDate
            );

            if ($stmt->execute()) {

                $message = 'Farmer added successfully.';
                $messageType = 'success';

            } else {

                $conn->query("DELETE FROM users WHERE id = $userId");

                $message = 'Failed to add farmer details.';
                $messageType = 'error';
            }

            $stmt->close();

        } else {

            if ($conn->errno == 1062) {
                $message = 'Username already exists.';
            } else {
                $message = 'Failed to create farmer.';
            }

            $messageType = 'error';

            $stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Farmer
    |--------------------------------------------------------------------------
    */

    if ($action === 'edit') {

        $farmerId = (int) $_POST['farmer_id'];

        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $joinDate = $_POST['join_date'];

        $stmt = $conn->prepare(
            "SELECT user_id
             FROM farmers
             WHERE id = ?"
        );

        $stmt->bind_param('i', $farmerId);
        $stmt->execute();

        $result = $stmt->get_result();
        $farmer = $result->fetch_assoc();

        $stmt->close();

        if ($farmer) {

            $userId = $farmer['user_id'];

            $stmt = $conn->prepare(
                "UPDATE users
                 SET full_name = ?, phone = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ssi',
                $fullName,
                $phone,
                $userId
            );

            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                "UPDATE farmers
                 SET address = ?, join_date = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ssi',
                $address,
                $joinDate,
                $farmerId
            );

            if ($stmt->execute()) {
                $message = 'Farmer updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update farmer.';
                $messageType = 'error';
            }

            $stmt->close();

        } else {

            $message = 'Farmer not found.';
            $messageType = 'error';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Activate / Deactivate Farmer
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle'])) {

    $farmerId = (int) $_GET['toggle'];

    $stmt = $conn->prepare(
        "SELECT user_id, status
         FROM farmers
         WHERE id = ?"
    );

    $stmt->bind_param('i', $farmerId);
    $stmt->execute();

    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();

    $stmt->close();

    if ($farmer) {

        if ($farmer['status'] === 'active') {
            $newStatus = 'inactive';
        } else {
            $newStatus = 'active';
        }

        $stmt = $conn->prepare(
            "UPDATE farmers
             SET status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            'si',
            $newStatus,
            $farmerId
        );

        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE users
             SET status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            'si',
            $newStatus,
            $farmer['user_id']
        );

        $stmt->execute();
        $stmt->close();
    }

    header('Location: ' . BASE_URL . '/admin/manage-farmer.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Farmer Being Edited
|--------------------------------------------------------------------------
*/

$editFarmer = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT
            f.id,
            f.address,
            f.join_date,
            u.full_name,
            u.phone
         FROM farmers f
         JOIN users u ON f.user_id = u.id
         WHERE f.id = ?"
    );

    $stmt->bind_param('i', $editId);
    $stmt->execute();

    $result = $stmt->get_result();
    $editFarmer = $result->fetch_assoc();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Get All Farmers
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            f.id,
            f.status,
            u.username,
            u.full_name,
            u.phone
        FROM farmers f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.id DESC";

$farmerList = $conn->query($sql);

include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        Manage Farmers
    </h1>

    <?php if ($message !== ''): ?>

        <div class="mb-6 p-3 rounded-md
            <?php echo $messageType === 'success'
                ? 'bg-green-100 text-green-700'
                : 'bg-red-100 text-red-700'; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- Add / Edit Farmer -->

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">

        <h2 class="text-lg font-semibold text-gray-800 mb-4">

            <?php
            echo $editFarmer
                ? 'Edit Farmer'
                : 'Add New Farmer';
            ?>

        </h2>

        <form method="POST" id="userForm">

            <?php if ($editFarmer): ?>

                <input type="hidden" name="action" value="edit">

                <input
                    type="hidden"
                    name="farmer_id"
                    value="<?php echo $editFarmer['id']; ?>"
                >

            <?php else: ?>

                <input type="hidden" name="action" value="add">

            <?php endif; ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <?php if (!$editFarmer): ?>

                    <div>
                        <label
                            for="username"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                        >

                        <span
                            id="username-error"
                            class="text-sm text-red-600"
                        ></span>
                    </div>


                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full border border-gray-300 rounded-md px-3 py-2"
                        >

                        <span
                            id="password-error"
                            class="text-sm text-red-600"
                        ></span>
                    </div>

                <?php endif; ?>


                <div>
                    <label
                        for="full_name"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php
                            echo $editFarmer
                                ? htmlspecialchars($editFarmer['full_name'])
                                : '';
                        ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >

                    <span
                        id="full_name-error"
                        class="text-sm text-red-600"
                    ></span>
                </div>


                <div>
                    <label
                        for="phone"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo $editFarmer
                                ? htmlspecialchars($editFarmer['phone'])
                                : '';
                        ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >

                    <span
                        id="phone-error"
                        class="text-sm text-red-600"
                    ></span>
                </div>


                <div>
                    <label
                        for="address"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?php
                            echo $editFarmer
                                ? htmlspecialchars($editFarmer['address'])
                                : '';
                        ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >

                    <span
                        id="address-error"
                        class="text-sm text-red-600"
                    ></span>
                </div>


                <div>
                    <label
                        for="join_date"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Join Date
                    </label>

                    <input
                        type="date"
                        id="join_date"
                        name="join_date"
                        value="<?php
                            echo $editFarmer
                                ? $editFarmer['join_date']
                                : date('Y-m-d');
                        ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >

                    <span
                        id="join_date-error"
                        class="text-sm text-red-600"
                    ></span>
                </div>

            </div>


            <div class="mt-5 flex gap-2">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md"
                >
                    <?php
                    echo $editFarmer
                        ? 'Update Farmer'
                        : 'Add Farmer';
                    ?>
                </button>


                <?php if ($editFarmer): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/manage-farmer.php"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- Farmer List -->

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200">

            <h2 class="font-semibold text-gray-800">
                All Farmers
            </h2>

        </div>


        <?php if ($farmerList->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No farmers found.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                ID
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Username
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Phone
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Status
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-200">

                        <?php while ($row = $farmerList->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 text-sm">
                                    <?php echo $row['id']; ?>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm font-medium">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm">

                                    <?php if ($row['status'] === 'active'): ?>

                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="px-4 py-3 text-sm">

                                    <div class="flex gap-3">

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/manage-farmer.php?edit=<?php echo $row['id']; ?>"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/report.php?search=<?php echo $row['id']; ?>"
                                            class="text-purple-600 hover:underline"
                                        >
                                            Ledger
                                        </a>

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/manage-farmer.php?toggle=<?php echo $row['id']; ?>"
                                            class="<?php
                                                echo $row['status'] === 'active'
                                                    ? 'text-red-600'
                                                    : 'text-green-600';
                                            ?> hover:underline"
                                            onclick="return confirm('Change status for this farmer?');"
                                        >
                                            <?php
                                            echo $row['status'] === 'active'
                                                ? 'Deactivate'
                                                : 'Activate';
                                            ?>
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<script src="<?php echo BASE_URL; ?>/assets/js/validate-user-form.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>