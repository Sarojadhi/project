<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$messageType = '';


// Add or edit staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    // Add staff
    if ($action === 'add') {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        $phoneNumber = trim($_POST['phone_number'] ?? '');

        $address = trim($_POST['address'] ?? '');
        $joinDate = $_POST['join_date'] ?? '';


        // Validation

        if (
            !preg_match('/^[A-Za-z ]+$/', $username) ||
            strlen($username) < 3 ||
            strlen($username) > 20
        ) {

            $message = 'Username must be at least 3 characters and contain letters and spaces only.';
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
            strlen($fullName) < 3 ||
            strlen($fullName) > 100 ||
            !preg_match('/^[A-Za-z ]+$/', $fullName)
        ) {

            $message = 'Full name must be at least 3 characters and contain letters and spaces only.';
            $messageType = 'error';

        } elseif (
            !preg_match('/^\d{10}$/', $phoneNumber) ||
            !preg_match('/^(97|98)/', $phoneNumber)
        ) {

            $message = 'Phone number must contain exactly 10 digits and start with 97 or 98.';
            $messageType = 'error';

        } elseif (
            strlen($address) < 3 ||
            strlen($address) > 200 ||
            !preg_match('/^[A-Za-z0-9 ,\-]+$/', $address) ||
            !preg_match('/[A-Za-z]/', $address) ||
            substr_count($address, ',') > 1
        ) {

            $message = 'Address must be at least 3 characters, contain at least one letter, and can contain letters, numbers, spaces, - and only one comma.';
            $messageType = 'error';

        } elseif ($joinDate !== date('Y-m-d')) {

            $message = 'New staff join date must be today.';
            $messageType = 'error';

        } else {

            $phone = $phoneNumber;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();

            try {

                // Create user
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (username, password, role, full_name, phone, status)
                    VALUES (?, ?, 'staff', ?, ?, 'active')"
                );

                $stmt->bind_param(
                    'ssss',
                    $username,
                    $hashedPassword,
                    $fullName,
                    $phone
                );

                $stmt->execute();

                $userId = $conn->insert_id;

                $stmt->close();


                // Create staff profile
                $stmt = $conn->prepare(
                    "INSERT INTO staff
                    (user_id, address, join_date, status)
                    VALUES (?, ?, ?, 'active')"
                );

                $stmt->bind_param(
                    'iss',
                    $userId,
                    $address,
                    $joinDate
                );

                $stmt->execute();
                $stmt->close();


                $conn->commit();

                $message = 'Staff added successfully.';
                $messageType = 'success';

            } catch (mysqli_sql_exception $e) {

                $conn->rollback();

                if ($e->getCode() == 1062) {
                    $message = 'Username already exists.';
                } else {
                    $message = 'Failed to add staff.';
                }

                $messageType = 'error';
            }
        }
    }


    // Edit staff
    elseif ($action === 'edit') {

        $staffId = (int) ($_POST['staff_id'] ?? 0);

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        $phoneNumber = trim($_POST['phone_number'] ?? '');

        $address = trim($_POST['address'] ?? '');


        // Validation

        if ($staffId <= 0) {

            $message = 'Invalid staff.';
            $messageType = 'error';

        } elseif (
            !preg_match('/^[A-Za-z ]+$/', $username) ||
            strlen($username) < 3 ||
            strlen($username) > 20
        ) {

            $message = 'Username must be at least 3 characters and contain letters and spaces only.';
            $messageType = 'error';

        } elseif (
            $password !== '' &&
            !preg_match(
                '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^])[A-Za-z\d@$!%*?&#^]{8,}$/',
                $password
            )
        ) {

            $message = 'New password must be at least 8 characters with uppercase, lowercase, number and symbol.';
            $messageType = 'error';

        } elseif (
            strlen($fullName) < 3 ||
            strlen($fullName) > 100 ||
            !preg_match('/^[A-Za-z ]+$/', $fullName)
        ) {

            $message = 'Full name must be at least 3 characters and contain letters and spaces only.';
            $messageType = 'error';

        } elseif (
            !preg_match('/^\d{10}$/', $phoneNumber) ||
            !preg_match('/^(97|98)/', $phoneNumber)
        ) {

            $message = 'Phone number must contain exactly 10 digits and start with 97 or 98.';
            $messageType = 'error';

        } elseif (
            strlen($address) < 3 ||
            strlen($address) > 200 ||
            !preg_match('/^[A-Za-z0-9 ,\-]+$/', $address) ||
            !preg_match('/[A-Za-z]/', $address) ||
            substr_count($address, ',') > 1
        ) {

            $message = 'Address must be at least 3 characters, contain at least one letter, and can contain letters, numbers, spaces, - and only one comma.';
            $messageType = 'error';

        } else {

            $phone = $phoneNumber;


            // Get original staff record
            $stmt = $conn->prepare(
                "SELECT user_id, join_date, status
                 FROM staff
                 WHERE id = ?"
            );

            $stmt->bind_param('i', $staffId);
            $stmt->execute();

            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();

            $stmt->close();


            if (!$staff) {

                $message = 'Staff not found.';
                $messageType = 'error';

            } else {

                $userId = (int) $staff['user_id'];

                $conn->begin_transaction();

                try {

                    // Update username, name and phone
                    $stmt = $conn->prepare(
                        "UPDATE users
                         SET username = ?, full_name = ?, phone = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        'sssi',
                        $username,
                        $fullName,
                        $phone,
                        $userId
                    );

                    $stmt->execute();
                    $stmt->close();


                    // Change password only if admin entered a new one
                    if ($password !== '') {

                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $conn->prepare(
                            "UPDATE users
                             SET password = ?
                             WHERE id = ?"
                        );

                        $stmt->bind_param(
                            'si',
                            $hashedPassword,
                            $userId
                        );

                        $stmt->execute();
                        $stmt->close();
                    }


                    // Update staff information.
                    // Join date is intentionally NOT updated.
                    $stmt = $conn->prepare(
                        "UPDATE staff
                         SET address = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        'si',
                        $address,
                        $staffId
                    );

                    $stmt->execute();
                    $stmt->close();


                    $conn->commit();

                    $message = 'Staff updated successfully.';
                    $messageType = 'success';

                } catch (mysqli_sql_exception $e) {

                    $conn->rollback();

                    if ($e->getCode() == 1062) {
                        $message = 'Username already exists.';
                    } else {
                        $message = 'Failed to update staff.';
                    }

                    $messageType = 'error';
                }
            }
        }
    }


    // Change staff status
    elseif ($action === 'status') {

        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';


        if (
            $staffId <= 0 ||
            ($newStatus !== 'active' && $newStatus !== 'inactive')
        ) {

            $message = 'Invalid status request.';
            $messageType = 'error';

        } else {

            $stmt = $conn->prepare(
                "SELECT user_id, status
                 FROM staff
                 WHERE id = ?"
            );

            $stmt->bind_param('i', $staffId);
            $stmt->execute();

            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();

            $stmt->close();


            if (!$staff) {

                $message = 'Staff not found.';
                $messageType = 'error';

            } else {

                $userId = (int) $staff['user_id'];

                $conn->begin_transaction();

                try {

                    $stmt = $conn->prepare(
                        "UPDATE staff
                         SET status = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        'si',
                        $newStatus,
                        $staffId
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
                        $userId
                    );

                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();

                    $message = 'Staff status updated.';
                    $messageType = 'success';

                } catch (mysqli_sql_exception $e) {

                    $conn->rollback();

                    $message = 'Failed to update staff status.';
                    $messageType = 'error';
                }
            }
        }
    }
}


// Get staff for editing
$editStaff = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT
            s.id,
            s.address,
            s.join_date,
            s.status,
            u.username,
            u.full_name,
            u.phone
         FROM staff s
         JOIN users u ON s.user_id = u.id
         WHERE s.id = ?"
    );

    $stmt->bind_param('i', $editId);
    $stmt->execute();

    $result = $stmt->get_result();
    $editStaff = $result->fetch_assoc();

    $stmt->close();
}


// Get all staff
$staffList = $conn->query(
    "SELECT
        s.id,
        s.status,
        s.join_date,
        u.username,
        u.full_name,
        u.phone
     FROM staff s
     JOIN users u ON s.user_id = u.id
     ORDER BY s.id DESC"
);


include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        Manage Staff
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


    <!-- Add / Edit Staff -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">

        <h2 class="text-lg font-semibold text-gray-800 mb-4">

            <?php
            echo $editStaff
                ? 'Edit Staff'
                : 'Add New Staff';
            ?>

        </h2>


        <form method="POST" id="userForm">

            <?php if ($editStaff): ?>

                <input
                    type="hidden"
                    name="action"
                    value="edit"
                >

                <input
                    type="hidden"
                    name="staff_id"
                    value="<?php echo (int) $editStaff['id']; ?>"
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
                        value="<?php
                            echo $editStaff
                                ? e($editStaff['username'])
                                : '';
                        ?>"
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
                        <?php echo $editStaff ? 'New Password' : 'Password'; ?>
                    </label>

                    <div class="relative">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="<?php
                                echo $editStaff
                                    ? 'Leave blank to keep current password'
                                    : 'Enter password';
                            ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 pr-12 focus:outline-none focus:border-blue-500"
                        >

                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 cursor-pointer"
                            style="display: none;"
                            aria-label="Show password"
                        >
                            👁
                        </button>

                    </div>

                    <span
                        id="password-error"
                        class="text-sm text-red-600"
                    ></span>

                    <?php if ($editStaff): ?>

                        <p class="text-xs text-gray-500 mt-1">
                            Enter a password only if you want to change it.
                        </p>

                    <?php endif; ?>

                </div>


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
                            echo $editStaff
                                ? e($editStaff['full_name'])
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
                        for="phone_number"
                        class="block text-sm font-medium text-gray-700 mb-1"
                    >
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone_number"
                        name="phone_number"
                        value="<?php
                            echo $editStaff
                                ? e($editStaff['phone'])
                                : '';
                        ?>"
                        maxlength="10"
                        minlength="10"
                        inputmode="numeric"
                        autocomplete="tel"
                        placeholder="98XXXXXXXX"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <input
                        type="hidden"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo $editStaff
                                ? e($editStaff['phone'])
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
                            echo $editStaff
                                ? e($editStaff['address'])
                                : '';
                        ?>"
                        minlength="3"
                        maxlength="200"
                        placeholder="Enter your Address"
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

                    <?php if ($editStaff): ?>

                        <input
                            type="text"
                            value="<?php echo e($editStaff['join_date']); ?>"
                            readonly
                            class="w-full bg-gray-100 border border-gray-300 rounded-md px-3 py-2 text-gray-600"
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            Original join date cannot be changed.
                        </p>

                    <?php else: ?>

                        <input
                            type="date"
                            id="join_date"
                            name="join_date"
                            value="<?php echo date('Y-m-d'); ?>"
                            readonly
                            class="w-full bg-gray-100 border border-gray-300 rounded-md px-3 py-2 text-gray-600"
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            New staff join date is automatically set to today.
                        </p>

                    <?php endif; ?>

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
                    echo $editStaff
                        ? 'Update Staff'
                        : 'Add Staff';
                    ?>
                </button>


                <?php if ($editStaff): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/manage-staff.php"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- Staff List -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-200">

            <h2 class="font-semibold text-gray-800">
                All Staff
            </h2>

        </div>


        <?php if ($staffList->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No staff found.
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
                                Join Date
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

                        <?php while ($row = $staffList->fetch_assoc()): ?>

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
                                    <?php echo formatDate($row['join_date']); ?>
                                </td>

                                <td class="px-4 py-3 text-sm">

                                    <?php if ($row['status'] === 'active'): ?>

                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td class="px-4 py-3 text-sm">

                                    <div class="flex flex-wrap gap-3">

                                        <!-- Edit -->
                                        <a
                                            href="<?php echo BASE_URL; ?>/admin/manage-staff.php?edit=<?php echo (int) $row['id']; ?>"
                                            class="text-blue-600 hover:underline"
                                        >
                                            Edit
                                        </a>


                                        <!-- Active -->
                                        <?php if ($row['status'] === 'active'): ?>

                                            <form method="POST" class="inline">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="status"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="staff_id"
                                                    value="<?php echo (int) $row['id']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="inactive"
                                                >

                                                <button
                                                    type="submit"
                                                    class="text-yellow-600 hover:underline"
                                                    onclick="return confirm('Deactivate this staff?');"
                                                >
                                                    Deactivate
                                                </button>

                                            </form>


                                        <!-- Inactive -->
                                        <?php else: ?>

                                            <form method="POST" class="inline">

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="status"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="staff_id"
                                                    value="<?php echo (int) $row['id']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="active"
                                                >

                                                <button
                                                    type="submit"
                                                    class="text-green-600 hover:underline"
                                                    onclick="return confirm('Activate this staff?');"
                                                >
                                                    Activate
                                                </button>

                                            </form>

                                        <?php endif; ?>

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