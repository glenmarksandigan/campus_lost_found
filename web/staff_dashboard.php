<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Only staff (type_id = 3) allowed
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 3) {
    header('Location: auth.php'); exit;
}
// Redirect staff to the shared student dashboard (unified)
header('Location: index.php'); exit;
?>