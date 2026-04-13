<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include 'db.php';
include 'activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id     = $_SESSION['user_id'];
    $item_name   = trim($_POST['item_name']   ?? '');
    $found_loc   = trim($_POST['found_location']   ?? '');
    $storage_loc = trim($_POST['storage_location'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    $date_found  = !empty($_POST['date_found']) ? $_POST['date_found'] : date('Y-m-d H:i:s');
    $category    = trim($_POST['category'] ?? '');

    // Collect extra dynamic fields into description appendix
    $extras = [];
    foreach ($_POST as $key => $val) {
        if (str_starts_with($key, 'extra_') && !empty($val)) {
            $label = ucwords(str_replace('_', ' ', substr($key, 6)));
            $extras[] = "$label: $val";
        }
    }
    if (!empty($extras)) {
        $desc .= ($desc ? "\n\n" : '') . "Details:\n" . implode("\n", $extras);
    }

    // Image upload
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (empty($_FILES['item_image']['name'])) {
        header("Location: report.php?status=error&msg=Please+upload+a+photo."); exit;
    }

    $file_name   = time() . "_" . basename($_FILES["item_image"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
        try {
            $sql = "INSERT INTO items 
                        (user_id, item_name, category, found_location, storage_location, description, image_path, date_found, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id,
                $item_name,
                $category,
                $found_loc,
                $storage_loc,
                $desc,
                $file_name,
                $date_found
            ]);

            $newItemId = $pdo->lastInsertId();
            logActivity($pdo, $user_id, 'create', 'item', $newItemId, 'Reported found: ' . $item_name);

            header("Location: report.php?status=success"); exit;

        } catch (PDOException $e) {
            header("Location: report.php?status=error&msg=" . urlencode("Database error: " . $e->getMessage())); exit;
        }
    } else {
        header("Location: report.php?status=error&msg=Error+uploading+photo.+Please+try+again."); exit;
    }
}

// If accessed directly without POST
header("Location: report.php"); exit;
?>