<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) { http_response_code(403); exit; }
include 'db.php';

$admin_id    = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$message     = trim($_POST['message'] ?? '');

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']); exit;
}

try {
    $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
        VALUES (?, ?, 'Re: Lost & Found', ?, 0, NOW())
    ")->execute([$admin_id, $receiver_id, $message]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}