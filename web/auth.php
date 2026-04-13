<?php
// auth.php - Session check for admin pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix: use user_id + type_id instead of admin_id
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 4) {
    header("Location: signup.php");
    exit();
}

// Session timeout (30 minutes)
$timeout_duration = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: auth.php?msg=Session expired. Please login again.");
    exit();
}

$_SESSION['last_activity'] = time();
?>