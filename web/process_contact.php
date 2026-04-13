<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php"); exit;
}

$report_id     = (int) ($_POST['report_id'] ?? 0);
$finder_user_id = $_SESSION['user_id'];
$finder_name   = trim($_POST['finder_name']    ?? '');
$finder_contact= trim($_POST['finder_contact'] ?? '');
$finder_email  = trim($_POST['finder_email']   ?? '');
$found_location= trim($_POST['found_location'] ?? '');
$message       = trim($_POST['message']        ?? '');

// Validate report exists
$report = $pdo->prepare("SELECT id FROM lost_reports WHERE id = ?");
$report->execute([$report_id]);
if (!$report->fetch()) {
    header("Location: index.php"); exit;
}

// Prevent duplicate submissions
$existing = $pdo->prepare("SELECT id FROM lost_contacts WHERE report_id = ? AND finder_user_id = ?");
$existing->execute([$report_id, $finder_user_id]);
if ($existing->fetch()) {
    header("Location: index.php?found=duplicate"); exit;
}

// Handle optional photo upload
$image_path = null;
if (!empty($_FILES['finder_image']['name']) && $_FILES['finder_image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/contacts/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $ext = strtolower(pathinfo($_FILES['finder_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $file_name = time() . '_' . $finder_user_id . '.' . $ext;
        if (move_uploaded_file($_FILES['finder_image']['tmp_name'], $target_dir . $file_name)) {
            $image_path = 'contacts/' . $file_name;
        }
    }
}

// Insert into lost_contacts table
$pdo->prepare("
    INSERT INTO lost_contacts (report_id, finder_user_id, finder_name, finder_contact, finder_email, found_location, message, image_path, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
")->execute([$report_id, $finder_user_id, $finder_name, $finder_contact, $finder_email, $found_location, $message, $image_path]);

// Update lost report status to Matching
$pdo->prepare("UPDATE lost_reports SET status = 'Matching' WHERE id = ?")
    ->execute([$report_id]);

header("Location: index.php?found=success"); exit;