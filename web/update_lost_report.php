<?php
// update_lost_report.php — Allows users to edit their own lost-item reports
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$report_id = (int)($_POST['report_id'] ?? 0);

if (!$report_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid report ID']);
    exit;
}

// Verify the user owns this report
$check = $pdo->prepare("SELECT id, image_path FROM lost_reports WHERE id = ? AND user_id = ?");
$check->execute([$report_id, $user_id]);
$report = $check->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(['success' => false, 'error' => 'Report not found or access denied']);
    exit;
}

// Gather updated fields
$item_name          = trim($_POST['item_name'] ?? '');
$description        = trim($_POST['description'] ?? '');
$last_seen_location = trim($_POST['last_seen_location'] ?? '');
$category           = trim($_POST['category'] ?? '');

if (!$item_name || !$description || !$last_seen_location) {
    echo json_encode(['success' => false, 'error' => 'Item name, description, and location are required']);
    exit;
}

$image_path = $report['image_path']; // keep existing by default

// Handle optional image replacement
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode(['success' => false, 'error' => 'File is not an image']);
        exit;
    }
    $mime = $check['mime'];
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid image type']);
        exit;
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg'
    };

    $newFilename = 'lost_' . $report_id . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/uploads/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
        // Delete old image if it exists and is different
        if ($image_path && file_exists($uploadDir . $image_path) && $image_path !== $newFilename) {
            @unlink($uploadDir . $image_path);
        }
        $image_path = $newFilename;
    }
}

try {
    $stmt = $pdo->prepare("
        UPDATE lost_reports 
        SET item_name = ?, description = ?, last_seen_location = ?, category = ?, image_path = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$item_name, $description, $last_seen_location, $category, $image_path, $report_id, $user_id]);

    echo json_encode([
        'success'            => true,
        'item_name'          => htmlspecialchars($item_name),
        'description'        => htmlspecialchars($description),
        'last_seen_location' => htmlspecialchars($last_seen_location),
        'category'           => htmlspecialchars($category),
        'image_path'         => $image_path
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
