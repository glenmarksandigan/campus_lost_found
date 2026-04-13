<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 6) {
    echo json_encode(['success' => false, 'message' => 'Access denied: Organizers only']);
    exit;
}

header('Content-Type: application/json');

try {
    include 'db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// All organizers now have full edit access
$hasEditAccess = true;


// Optional activity logging
function logActivity($pdo, $user_id, $action_type, $target_type, $target_id, $details = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action_type, target_type, target_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $action_type, $target_type, $target_id, $details]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════════════════════════════
// UPDATE ITEM STATUS
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'update_item_status') {
    try {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $status  = $_POST['status'] ?? '';

        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
            exit;
        }

        // Validate status
        $validStatuses = ['Pending', 'Published', 'Claiming', 'Returned'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        // Check if item exists
        $check = $pdo->prepare("SELECT id, item_name FROM items WHERE id = ?");
        $check->execute([$item_id]);
        $item = $check->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit;
        }

        // Update status
        $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $item_id]);

        if (!$success) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            exit;
        }

        // Log activity
        $actionType = match($status) {
            'Published' => 'publish',
            'Returned'  => 'approve',
            default     => 'update'
        };
        logActivity($pdo, $user_id, $actionType, 'item', $item_id, "Item status: $status");

        $messages = [
            'Published' => 'Item published successfully',
            'Claiming'  => 'Item marked as claiming',
            'Returned'  => 'Item marked as returned',
            'Pending'   => 'Item status updated'
        ];

        echo json_encode([
            'success' => true,
            'message' => $messages[$status] ?? 'Status updated'
        ]);

    } catch (PDOException $e) {
        error_log("Update item error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Update item error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// UPDATE LOST REPORT STATUS
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'update_lost_status') {
    try {
        $report_id = (int)($_POST['report_id'] ?? 0);
        $status    = $_POST['status'] ?? '';

        if (!$report_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
            exit;
        }

        // Validate status
        $validStatuses = ['Lost', 'Matching', 'Resolved', 'Pending'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        // Check if report exists
        $check = $pdo->prepare("SELECT id, item_name FROM lost_reports WHERE id = ?");
        $check->execute([$report_id]);
        $report = $check->fetch();

        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            exit;
        }

        // Update status
        $stmt = $pdo->prepare("UPDATE lost_reports SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $report_id]);

        if (!$success) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            exit;
        }

        // Log activity
        $actionType = match($status) {
            'Resolved' => 'approve',
            default    => 'update'
        };
        logActivity($pdo, $user_id, $actionType, 'lost_report', $report_id, "Report status: $status");

        $messages = [
            'Matching' => 'Report marked as matching',
            'Resolved' => 'Report marked as resolved',
            'Lost'     => 'Report status updated'
        ];

        echo json_encode([
            'success' => true,
            'message' => $messages[$status] ?? 'Status updated'
        ]);

    } catch (PDOException $e) {
        error_log("Update lost report error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Update lost report error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
?>