<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| Add Staff
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $joinDate = $_POST['join_date'] ?? date('Y-m-d');

    if ($username === '' || $password === '' || $fullName === '') {

        $message = 'Username, password and full name are required.';
        $messageType = 'error';

    } else {

        // Check username
        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );
        $check->bind_param('s', $username);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = 'Username already exists.';
            $messageType = 'error';

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $conn->begin_transaction();

            try {

                // Add user
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

                // Add staff record
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

                $conn->commit();

                $message = 'Staff added successfully.';
                $messageType = 'success';

            } catch (Exception $e) {

                $conn->rollback();

                $message = 'Could not add staff.';
                $messageType = 'error';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Edit Staff
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $staffId = (int) ($_POST['staff_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $joinDate = $_POST['join_date'] ?? date('Y-m-d');

    if ($fullName === '') {

        $message = 'Full name is required.';
        $messageType = 'error';

    } else {

        $stmt = $conn->prepare(
            "SELECT user_id
             FROM staff
             WHERE id = ?"
        );

        $stmt->bind_param('i', $staffId);
        $stmt->execute();

        $staff = $stmt->get_result()->fetch_assoc();

        if (!$staff) {

            $message = 'Staff record not found.';
            $messageType = 'error';

        } else {

            $stmt = $conn->prepare(
                "UPDATE users
                 SET full_name = ?, phone = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ssi',
                $fullName,
                $phone,
                $staff['user_id']
            );

            $stmt->execute();

            $stmt = $conn->prepare(
                "UPDATE staff
                 SET address = ?, join_date = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'ssi',
                $address,
                $joinDate,
                $staffId
            );

            $stmt->execute();

            $message = 'Staff updated successfully.';
            $messageType = 'success';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Toggle Staff Status
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {

    $staffId = (int) ($_POST['id'] ?? 0);

    $stmt = $conn->prepare(
        "SELECT user_id, status
         FROM staff
         WHERE id = ?"
    );

    $stmt->bind_param('i', $staffId);
    $stmt->execute();

    $staff = $stmt->get_result()->fetch_assoc();

    if ($staff) {

        if ($staff['status'] === 'active') {
            $newStatus = 'inactive';
        } else {
            $newStatus = 'active';
        }

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

        // Keep users.status the same as staff.status
        $stmt = $conn->prepare(
            "UPDATE users
             SET status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            'si',
            $newStatus,
            $staff['user_id']
        );

        $stmt->execute();
    }

    header(
        'Location: ' . BASE_URL . '/admin/manage-staff.php'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Staff
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $staffId = (int) ($_POST['id'] ?? 0);

    $stmt = $conn->prepare(
        "SELECT user_id
         FROM staff
         WHERE id = ?"
    );

    $stmt->bind_param('i', $staffId);
    $stmt->execute();

    $staff = $stmt->get_result()->fetch_assoc();

    if ($staff) {

        $conn->begin_transaction();

        try {

            $stmt = $conn->prepare(
                "DELETE FROM staff
                 WHERE id = ?"
            );

            $stmt->bind_param('i', $staffId);
            $stmt->execute();

            $stmt = $conn->prepare(
                "DELETE FROM users
                 WHERE id = ?"
            );

            $stmt->bind_param(
                'i',
                $staff['user_id']
            );

            $stmt->execute();

            $conn->commit();

        } catch (Exception $e) {

            $conn->rollback();
        }
    }

    header(
        'Location: ' . BASE_URL . '/admin/manage-staff.php'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| Edit Mode
|--------------------------------------------------------------------------
*/
$editStaff = null;

if (isset($_GET['edit'])) {

    $staffId = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT
            s.id,
            s.address,
            s.join_date,
            u.full_name,
            u.phone
         FROM staff s
         JOIN users u ON s.user_id = u.id
         WHERE s.id = ?"
    );

    $stmt->bind_param('i', $staffId);
    $stmt->execute();

    $editStaff = $stmt->get_result()->fetch_assoc();
}


/*
|--------------------------------------------------------------------------
| Get All Staff
|--------------------------------------------------------------------------
*/
$staffList = $conn->query(
    "SELECT
        s.id,
        s.status,
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

    <!-- Message -->
    <?php if ($message !== ''): ?>

        <?php if ($messageType === 'success'): ?>

            <div class="mb-5 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php else: ?>

            <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Manage Staff
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Add, edit and manage staff accounts.
        </p>
    </div>


    <!-- Add / Edit Form -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">

        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-800">
                <?php echo $editStaff ? 'Edit Staff' : 'Add New Staff'; ?>
            </h2>
        </div>

        <form
            method="POST"
            id="userForm"
            class="p-5"
            onsubmit="return combinePhoneFields();"
        >

            <?php if ($editStaff): ?>

                <input type="hidden" name="action" value="edit">

                <input
                    type="hidden"
                    name="staff_id"
                    value="<?php echo $editStaff['id']; ?>"
                >

            <?php else: ?>

                <input type="hidden" name="action" value="add">

            <?php endif; ?>


            <!-- Hidden phone value -->
            <input
                type="hidden"
                id="phone"
                name="phone"
                value=""
            >


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <?php if (!$editStaff): ?>

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

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

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
                        value="<?php echo $editStaff ? htmlspecialchars($editStaff['full_name']) : ''; ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="full_name-error"
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
                            class="border border-gray-300 rounded-md px-3 py-2"
                        >
                            <option value="98">98</option>
                            <option value="97">97</option>
                        </select>

                        <input
                            type="text"
                            id="phone_suffix"
                            maxlength="8"
                            placeholder="8 digits"
                            value="<?php
                                echo $editStaff
                                    ? htmlspecialchars(substr($editStaff['phone'], 2))
                                    : '';
                            ?>"
                            class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

                    </div>

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
                        value="<?php echo $editStaff ? htmlspecialchars($editStaff['address']) : ''; ?>"
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
                            echo $editStaff
                                ? htmlspecialchars($editStaff['join_date'])
                                : date('Y-m-d');
                        ?>"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                    >

                    <span
                        id="join_date-error"
                        class="text-sm text-red-600"
                    ></span>

                </div>

            </div>


            <!-- Buttons -->
            <div class="mt-6 flex gap-2">

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-md"
                >
                    <?php echo $editStaff ? 'Update Staff' : 'Add Staff'; ?>
                </button>


                <?php if ($editStaff): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/admin/manage-staff.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-5 py-2.5 rounded-md"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- Staff List -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm">

        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">

            <h2 class="font-semibold text-gray-800">
                All Staff
            </h2>

            <span class="text-sm text-gray-500">
                <?php echo $staffList->num_rows; ?> total
            </span>

        </div>


        <?php if ($staffList->num_rows === 0): ?>

            <div class="p-6 text-center text-gray-500">
                No staff found.
            </div>

        <?php else: ?>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-4 py-3 text-left text-gray-600">
                                ID
                            </th>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Username
                            </th>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Name
                            </th>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Phone
                            </th>

                            <th class="px-4 py-3 text-left text-gray-600">
                                Status
                            </th>

                            <th class="px-4 py-3 text-right text-gray-600">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php while ($row = $staffList->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo $row['id']; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo htmlspecialchars($row['phone']); ?>
                                </td>

                                <td class="px-4 py-3">

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


                                <td class="px-4 py-3 text-right">

                                    <a
                                        href="<?php echo BASE_URL; ?>/admin/manage-staff.php?edit=<?php echo $row['id']; ?>"
                                        class="text-blue-600 hover:underline mr-3"
                                    >
                                        Edit
                                    </a>


                                    <!-- Toggle -->
                                    <form
                                        method="POST"
                                        action="<?php echo BASE_URL; ?>/admin/manage-staff.php"
                                        class="inline"
                                        onsubmit="return confirm('Change staff status?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="toggle"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo $row['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="<?php echo $row['status'] === 'active'
                                                ? 'text-orange-600'
                                                : 'text-green-600'; ?> hover:underline mr-3"
                                        >
                                            <?php echo $row['status'] === 'active'
                                                ? 'Deactivate'
                                                : 'Activate'; ?>
                                        </button>

                                    </form>


                                    <!-- Delete -->
                                    <form
                                        method="POST"
                                        action="<?php echo BASE_URL; ?>/admin/manage-staff.php"
                                        class="inline"
                                        onsubmit="return confirm('Delete this staff permanently?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo $row['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="text-red-600 hover:underline"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<script>
function combinePhoneFields()
{
    const prefix = document.getElementById('phone_prefix');
    const suffix = document.getElementById('phone_suffix');
    const phone = document.getElementById('phone');

    if (prefix && suffix && phone) {
        phone.value = prefix.value + suffix.value;
    }

    return true;
}
</script>

<script src="<?php echo BASE_URL; ?>/assets/js/validate-user-form.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>