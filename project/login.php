<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'staff') {
        header('Location: staff/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'farmer') {
        header('Location: farmer/dashboard.php');
        exit;
    }
}

include 'config/db.php';

$message = "";
$username = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            if ($user['status'] !== 'active') {
                $message = "Your account is not active. Please contact admin.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit;
                } elseif ($user['role'] === 'staff') {
                    header('Location: staff/dashboard.php');
                    exit;
                } elseif ($user['role'] === 'farmer') {
                    header('Location: farmer/dashboard.php');
                    exit;
                }
            }

        } else {
            $message = "Invalid username or password.";
        }

    } else {
        $message = "Invalid username or password.";
    }
}

include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-10">
    <div class="w-full max-w-md">

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Shree Tri Shakti Dairy</h1>
            <p class="text-gray-600 mt-1">Milk Collection Management System</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Login</h2>

            <?php if ($message != ""): ?>
                <div class="mb-4 p-3 rounded text-sm bg-red-100 text-red-700">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">

                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($username); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span id="username-error" class="text-sm text-red-600"></span>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <span id="password-error" class="text-sm text-red-600"></span>
                </div>

                <button type="submit" name="login"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Login
                </button>
            </form>
        </div>

    </div>
</div>

<script src="assets/js/validate-login.js"></script>

<?php include 'includes/footer.php'; ?>