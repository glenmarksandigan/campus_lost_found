<?php
// update_submission.php — Handle report updates from header modal
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$item_name = trim($_POST['item_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$found_location = trim($_POST['location'] ?? '');
$category = trim($_POST['category'] ?? '');

if (!$id || !$item_name || !$description || !$found_location || !$category) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// 1. Verify ownership
$stmt = $pdo->prepare("SELECT user_id, image_path FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || (int)$item['user_id'] !== $user_id) {
    echo json_encode(['success' => false, 'error' => 'Item not found or access denied']);
    exit;
}

$image_path = $item['image_path'];

// 2. Handle Image Upload (Optional)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Check if it's an image
    $check = getimagesize($_FILES['image']['tmp_name']);
    if ($check === false) {
        echo json_encode(['success' => false, 'error' => 'File is not a valid image']);
        exit;
    }
    
    $mime = $check['mime'];
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid image type (JPG, PNG, WEBP only)']);
        exit;
    }

    $ext = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg'
    };

    $new_filename = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $upload_path = 'uploads/' . $new_filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        // Delete old image if it exists and isn't the dummy
        if ($image_path && $image_path !== 'no_image.png' && file_exists('uploads/' . $image_path)) {
            @unlink('uploads/' . $image_path);
        }
        $image_path = $new_filename;
    }
}

// 3. Update Database
$update = $pdo->prepare("
    UPDATE items 
    SET item_name = ?, description = ?, found_location = ?, category = ?, image_path = ? 
    WHERE id = ?
");

if ($update->execute([$item_name, $description, $found_location, $category, $image_path, $id])) {
    echo json_encode([
        'success' => true,
        'item_name' => $item_name,
        'description' => $description,
        'found_location' => $found_location,
        'category' => $category,
        'image_path' => $image_path
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}
