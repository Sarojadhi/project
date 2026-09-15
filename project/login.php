<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$username = '';


// Redirect logged-in users
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirectToDashboard();
}


// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {

        $message = 'Please enter username and password.';

    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password, role, full_name, status
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (!password_verify($password, $user['password'])) {

                $message = 'Invalid username or password.';

            } elseif ($user['status'] !== 'active') {

                $message = 'Your account is not active. Contact admin.';

            } else {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                redirectToDashboard();
            }

        } else {

            $message = 'Invalid username or password.';
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Shree Tri Shakti Dairy</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100 min-h-screen">

<div class="min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        <!-- Title -->
        <div class="text-center mb-6">

            <h1 class="text-2xl font-bold text-gray-800">
                Shree Tri Shakti Dairy
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Milk Collection Management System
            </p>

        </div>


        <!-- Login card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">

            <div class="px-6 py-4 border-b border-gray-200">

                <h2 class="text-lg font-semibold text-gray-800">
                    Sign In
                </h2>

            </div>


            <div class="p-6">

                <?php if ($message !== ''): ?>

                    <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200">

                        <p class="text-sm text-red-700">
                            <?php echo e($message); ?>
                        </p>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    id="loginForm"
                    novalidate
                >

                    <!-- Username -->
                    <div class="mb-4">

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
                            value="<?php echo e($username); ?>"
                            placeholder="Enter your username"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
                        >

                        <span
                            id="username-error"
                            class="text-sm text-red-600"
                        ></span>

                    </div>


                    <!-- Password -->
                    <div class="mb-6">

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
                                placeholder="Enter your password"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 focus:outline-none focus:border-blue-500"
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                👁
                            </button>

                        </div>

                        <span
                            id="password-error"
                            class="text-sm text-red-600"
                        ></span>

                    </div>


                    <!-- Submit -->
                    <button
                        type="submit"
                        name="login"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-md"
                    >
                        Sign In
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- JavaScript validation -->
<script src="<?php echo BASE_URL; ?>/assets/js/validators.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/validate-login.js"></script>

</body>

</html>