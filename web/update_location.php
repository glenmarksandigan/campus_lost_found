<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

include 'db.php';
include 'activity_logger.php';

if (isset($_POST['id']) && isset($_POST['new_location'])) {
    $id  = $_POST['id'];
    $loc = $_POST['new_location'];

    $allowed = ['SSG Office', 'Guard House', "Finder's Possession"];
    if (!in_array($loc, $allowed)) {
        http_response_code(400);
        echo "Invalid location";
        exit();
    }

    $stmt = $pdo->prepare("UPDATE items SET storage_location = ? WHERE id = ?");
    if ($stmt->execute([$loc, $id])) {
        // Fetch item name for better log details
        $itemStmt = $pdo->prepare("SELECT item_name FROM items WHERE id = ?");
        $itemStmt->execute([$id]);
        $itemName = $itemStmt->fetchColumn() ?: "Item #$id";

        logActivity($pdo, $_SESSION['user_id'], 'update', 'item', $id, "Storage location updated to: $loc (Item: $itemName)");
        echo "Success";
    } else {
        http_response_code(500);
        echo "Error";
    }
}
?>