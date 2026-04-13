<?php
session_start();

// Return logged-in user's info
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'name' => $_SESSION['user_name'] ?? 'Unknown',
        'email' => $_SESSION['user_email'] ?? ''
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
}
?>
