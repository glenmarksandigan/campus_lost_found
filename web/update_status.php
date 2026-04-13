<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Fix: check user_id and type_id instead of admin_id
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

include 'db.php';
include 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id         = $_POST['id']         ?? 0;
    $new_status = $_POST['new_status'] ?? '';

    $allowed = ['Pending', 'Published', 'Claiming', 'Returned'];
    if (!in_array($new_status, $allowed)) {
        echo "Invalid status";
        exit();
    }

    $stmt = $pdo->prepare("UPDATE items SET status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$new_status, $id])) {
        // Fetch item name for better log details
        $itemStmt = $pdo->prepare("SELECT item_name FROM items WHERE id = ?");
        $itemStmt->execute([$id]);
        $itemName = $itemStmt->fetchColumn() ?: "Item #$id";

        $actionType = match($new_status) {
            'Published' => 'publish',
            'Returned'  => 'approve',
            default     => 'update'
        };
        logActivity($pdo, $_SESSION['user_id'], $actionType, 'item', $id, "Status updated to: $new_status (Item: $itemName)");

        echo "Status updated successfully";
    } else {
        http_response_code(500);
        echo "Failed to update status";
    }
}
?>