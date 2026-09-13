<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shree Tri Shakti Dairy</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php if (isset($_SESSION['user_id'])): ?>
<nav class="bg-white shadow">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
        <div class="font-bold text-gray-800">
            Shree Tri Shakti Dairy
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">
                <?php echo $_SESSION['full_name']; ?>
                (<?php echo $_SESSION['role']; ?>)
            </span>
            <a href="/logout.php" class="text-sm text-red-600 hover:text-red-800 font-semibold">
                Logout
            </a>
        </div>
    </div>
</nav>
<?php endif; ?>