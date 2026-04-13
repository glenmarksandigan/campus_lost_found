<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) { http_response_code(403); exit; }
include 'db.php';

$admin_id = (int)$_SESSION['user_id'];
$with_id  = (int)($_GET['with'] ?? 0);
if (!$with_id) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END AS is_admin
    FROM messages m
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$admin_id, $admin_id, $with_id, $with_id, $admin_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));