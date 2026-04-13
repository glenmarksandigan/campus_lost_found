<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';
include 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php"); exit;
}

$item_id = (int) $_POST['id'];
$msg     = trim($_POST['claimer_message'] ?? '');
$section = trim($_POST['section'] ?? '');
$user_id = $_SESSION['user_id'];

// Prepend section info to the message if provided
if (!empty($section)) {
    $msg = "[Section: $section]\n$msg";
}

// Make sure item exists
$itemStmt = $pdo->prepare("SELECT id, status FROM items WHERE id = ?");
$itemStmt->execute([$item_id]);
$item = $itemStmt->fetch();

if (!$item) {
    header("Location: index.php"); exit;
}

// Check if this user already claimed this item
$existing = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND user_id = ?");
$existing->execute([$item_id, $user_id]);
if ($existing->fetch()) {
    header("Location: index.php?claim=duplicate"); exit;
}

// Handle optional image upload
$image_path = null;
if (!empty($_FILES['claim_image']['name'])) {
    $target_dir = "uploads/claims/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower(pathinfo($_FILES['claim_image']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $_FILES['claim_image']['error'] === UPLOAD_ERR_OK) {
        $file_name  = time() . '_' . $user_id . '.' . $ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['claim_image']['tmp_name'], $target_file)) {
            $image_path = 'claims/' . $file_name;
        }
    }
}

// Insert into claims table
$pdo->prepare("INSERT INTO claims (item_id, user_id, claim_message, image_path, claimed_at) VALUES (?, ?, ?, ?, NOW())")
    ->execute([$item_id, $user_id, $msg, $image_path]);

// Update item status to Claiming
$pdo->prepare("UPDATE items SET status = 'Claiming' WHERE id = ?")
    ->execute([$item_id]);

// Log activity
logActivity($pdo, $user_id, 'create', 'claim', $item_id, 'Submitted claim for item #' . $item_id);

header("Location: index.php?claim=success"); exit;