<?php

require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
}

header('Location: ' . BASE_URL . '/login.php');
exit;