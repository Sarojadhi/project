<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminOrStaff();

$message = '';
$messageType = '';

// Add or edit farmer

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    // Add farmer

    if ($action === 'add') {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        $phonePrefix = $_POST['phone_prefix'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';

        $address = trim($_POST['address'] ?? '');
        $joinDate = $_POST['join_date'] ?? '';

        // Server-side validation

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{2,19}$/', $username)) {

            $message = 'Username must start with a letter and contain 3 to 20 characters.';
            $messageType = 'error';

        } elseif (
            !preg_match(
                '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/',
                $password
            )
        ) {

            $message = 'Password must be at least 8 characters with uppercase, lowercase, number and symbol.';
            $messageType = 'error';

        } elseif (
            strlen($fullName) < 2 ||
            !preg_match('/^[A-Za-z ]+$/', $fullName)
        ) {

            $message = 'Full name must contain letters and spaces only.';
            $messageType = 'error';

        } elseif (
            ($phonePrefix !== '97' && $phonePrefix !== '98') ||
            !preg_match('/^\d{8}$/', $phoneNumber)
        ) {

            $message = 'Phone number must be 97 or 98 followed by exactly 8 digits.';
            $messageType = 'error';

        } elseif (
            strlen($address) < 3 ||
            strlen($address) > 200 ||
            !preg_match('/^[A-Za-z ]+$/', $address)
        ) {

            $message = 'Address must contain only letters and spaces and be at least 3 characters.';
            $messageType = 'error';

        } elseif ($joinDate !== date('Y-m-d')) {

            $message = 'Join date must be today.';
            $messageType = 'error';

        } else {

            $phone = $phonePrefix . $phoneNumber;

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user

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

                // Insert farmer profile

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

                    $deleteStmt = $conn->prepare(
                        "DELETE FROM users WHERE id = ?"
                    );

                    $deleteStmt->bind_param(
                        'i',
                        $userId
                    );

                    $deleteStmt->execute();
                    $deleteStmt->close();

                    $message = 'Failed to add farmer details.';
                    $messageType = 'error';
                }

                $stmt->close();

            } else {

                if ($stmt->errno === 1062) {
                    $message = 'Username already exists.';
                } else {
                    $message = 'Failed to create farmer.';
                }

                $messageType = 'error';

                $stmt->close();
            }
        }
    }

    // Edit farmer

    if ($action === 'edit') {

        $farmerId = (int) ($_POST['farmer_id'] ?? 0);

        $fullName = trim($_POST['full_name'] ?? '');

        $phonePrefix = $_POST['phone_prefix'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';

        $address = trim($_POST['address'] ?? '');
        $joinDate = $_POST['join_date'] ?? '';

        // Server-side validation

        if (
            strlen($fullName) < 2 ||
            !preg_match('/^[A-Za-z ]+$/', $fullName)
        ) {

            $message = 'Full name must contain letters and spaces only.';
            $messageType = 'error';

        } elseif (
            ($phonePrefix !== '97' && $phonePrefix !== '98') ||
            !preg_match('/^\d{8}$/', $phoneNumber)
        ) {

            $message = 'Phone number must be 97 or 98 followed by exactly 8 digits.';
            $messageType = 'error';

        } elseif (
            strlen($address) < 3 ||
            strlen($address) > 200 ||
            !preg_match('/^[A-Za-z ]+$/', $address)
        ) {

            $message = 'Address must contain only letters and spaces and be at least 3 characters.';
            $messageType = 'error';

        } elseif ($joinDate !== date('Y-m-d')) {

            $message = 'Join date must be today.';
            $messageType = 'error';

        } else {

            $phone = $phonePrefix . $phoneNumber;

            // Find farmer

            $stmt = $conn->prepare(
                "SELECT user_id
                 FROM farmers
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'i',
                $farmerId
            );

            $stmt->execute();

            $result = $stmt->get_result();
            $farmer = $result->fetch_assoc();

            $stmt->close();

            if ($farmer) {

                $userId = (int) $farmer['user_id'];

                // Update user

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

                $userUpdated = $stmt->execute();

                $stmt->close();

                // Update farmer

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

                $farmerUpdated = $stmt->execute();

                $stmt->close();

                if ($userUpdated && $farmerUpdated) {

                    $message = 'Farmer updated successfully.';
                    $messageType = 'success';

                } else {

                    $message = 'Failed to update farmer.';
                    $messageType = 'error';
                }

            } else {

                $message = 'Farmer not found.';
                $messageType = 'error';
            }
        }
    }
}

// Activate or deactivate farmer

if (isset($_GET['toggle'])) {

    $farmerId = (int) $_GET['toggle'];

    $stmt = $conn->prepare(
        "SELECT user_id, status
         FROM farmers
         WHERE id = ?"
    );

    $stmt->bind_param(
        'i',
        $farmerId
    );

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

        // Update farmer status

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

        // Update user status

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

    header(
        'Location: ' .
        BASE_URL .
        '/admin/manage-farmer.php'
    );

    exit;
}

// Get farmer being edited

$editFarmer = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT
            f.id,
            f.address,
            f.join_date,
            u.username,
            u.full_name,
            u.phone
         FROM farmers f
         JOIN users u ON f.user_id = u.id
         WHERE f.id = ?"
    );

    $stmt->bind_param(
        'i',
        $editId
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $editFarmer = $result->fetch_assoc();

    $stmt->close();
}

// Prepare phone values for edit

$editPhonePrefix = '';
$editPhoneNumber = '';

if ($editFarmer && !empty($editFarmer['phone'])) {

    $editPhone = $editFarmer['phone'];

    if (substr($editPhone, 0, 2) === '97') {

        $editPhonePrefix = '97';
        $editPhoneNumber = substr($editPhone, 2);

    } elseif (substr($editPhone, 0, 2) === '98') {

        $editPhonePrefix = '98';
        $editPhoneNumber = substr($editPhone, 2);
    }
}

// Get all farmers

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

        <div
            id="message"
            class="mb-6 p-3 rounded-md
                <?php
                echo $messageType === 'success'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700';
                ?>"
        >
            <?php echo e($message); ?>
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

        <form
            method="POST"
            id="userForm"
        >

            <?php if ($editFarmer): ?>

                <input
                    type="hidden"
                    name="action"
                    value="edit"
                >

                <input
                    type="hidden"
                    name="farmer_id"
                    value="<?php echo (int) $editFarmer['id']; ?>"
                >

            <?php else: ?>

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Username -->

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
                            placeholder="Enter username"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

                        <span
                            id="username-error"
                            class="text-sm text-red-600"
                        ></span>

                    </div>

                    <!-- Password -->

                    <div>

                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Password
                        </label>

                        <div class="relative">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter password"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 focus:outline-none focus:border-blue-500"
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"
                            >
                                👁
                            </button>

                        </div>

                        <span
                            id="password-error"
                            class="text-sm text-red-600"
                        ></span>

                    </div>

                <?php endif; ?>

                <!-- Full Name -->

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
                                ? e($editFarmer['full_name'])
                                : '';
                        ?>"
                        placeholder="Enter full name"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="full-name-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

                <!-- Phone -->

                <div>

                    <label
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Phone
                    </label>

                    <div class="flex gap-2">

                        <select
                            id="phone_prefix"
                            name="phone_prefix"
                            class="w-20 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

                      
                            <option
                                value="98"
                                <?php
                                echo $editPhonePrefix === '98'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                98
                            </option>

                            <option
                                value="97"
                                <?php
                                echo $editPhonePrefix === '97'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                97
                            </option>

                        </select>

                        <input
                            type="text"
                            id="phone_number"
                            name="phone_number"
                            value="<?php echo e($editPhoneNumber); ?>"
                            maxlength="8"
                            inputmode="numeric"
                            autocomplete="tel"
                            placeholder="12345678"
                            class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

                    </div>

                    <input
                        type="hidden"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo $editFarmer
                                ? e($editFarmer['phone'])
                                : '';
                        ?>"
                    >

                    <span
                        id="phone-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

                <!-- Address -->

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
                                ? e($editFarmer['address'])
                                : '';
                        ?>"
                        minlength="3"
                        maxlength="200"
                        placeholder="Enter address"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="address-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

                <!-- Join Date -->

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
                                ? e($editFarmer['join_date'])
                                : date('Y-m-d');
                        ?>"
                        min="<?php echo date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="join-date-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

            </div>

            <!-- Buttons -->

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
                                    <?php echo (int) $row['id']; ?>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <?php echo e($row['username']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm font-medium">
                                    <?php echo e($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <?php echo e($row['phone']); ?>
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
                                            href="<?php echo BASE_URL; ?>/admin/manage-farmer.php?edit=<?php echo (int) $row['id']; ?>"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/manage-farmer.php?toggle=<?php echo (int) $row['id']; ?>"
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