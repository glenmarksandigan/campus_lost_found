<?php
/**
 * Activity Logger for Super Admin Visibility
 * 
 * Include this file wherever you perform actions that should be logged
 * Example: include 'activity_logger.php';
 */

function logActivity($pdo, $user_id, $action_type, $target_type, $target_id, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action_type, target_type, target_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action_type, $target_type, $target_id, $details]);
        return true;
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Usage Examples:
 * 
 * // When publishing an item:
 * logActivity($pdo, $_SESSION['user_id'], 'publish', 'item', $item_id, 'Published item: '.$item_name);
 * 
 * // When approving a user:
 * logActivity($pdo, $_SESSION['user_id'], 'approve', 'user', $user_id, 'Approved student account');
 * 
 * // When creating a lost report:
 * logActivity($pdo, $_SESSION['user_id'], 'create', 'lost_report', $report_id, 'Created lost report for: '.$item_name);
 * 
 * // When updating an item:
 * logActivity($pdo, $_SESSION['user_id'], 'update', 'item', $item_id, 'Updated status to: '.$new_status);
 * 
 * // When deleting:
 * logActivity($pdo, $_SESSION['user_id'], 'delete', 'item', $item_id, 'Deleted item: '.$item_name);
 */
?>