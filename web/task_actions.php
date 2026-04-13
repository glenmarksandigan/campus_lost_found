<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');
include 'db.php';
include 'activity_logger.php';

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// ══════════════════════════════════════════════════════════════════════════════
// CREATE TASK (Admin only)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'create_task') {
    // Check if user is admin
    if ($_SESSION['type_id'] != 4) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $task_type   = $_POST['task_type']   ?? 'general';
    $priority    = $_POST['priority']    ?? 'normal';
    $due_date    = $_POST['due_date']    ?? null;
    $related_id  = $_POST['related_id']  ?? null;

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Task title is required']);
        exit;
    }

    if (!$assigned_to) {
        echo json_encode(['success' => false, 'message' => 'Please select an organizer']);
        exit;
    }

    // Validate assigned_to is actually an organizer
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND type_id = 6");
    $check->execute([$assigned_to]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid organizer selected']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_tasks 
                (assigned_by, assigned_to, task_type, related_id, title, description, 
                 priority, status, due_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([
            $user_id, $assigned_to, $task_type, $related_id ?: null,
            $title, $description, $priority, $due_date ?: null
        ]);

        $taskId = $pdo->lastInsertId();

        // Log activity
        logActivity($pdo, $user_id, 'create', 'task', $taskId, "Created task: $title");

        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully',
            'task_id' => $taskId
        ]);

    } catch (PDOException $e) {
        error_log("Create task error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// UPDATE TASK STATUS (Admin or assigned Organizer)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'update_task_status') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status  = $_POST['status'] ?? '';

    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }

    if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Get task details
    $stmt = $pdo->prepare("SELECT * FROM admin_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }

    // Check permissions:
    // - Admin (type_id=4) can change any task
    // - Organizer (type_id=6) can only change tasks assigned to them
    $isAdmin = ($_SESSION['type_id'] == 4);
    $isAssignee = ($task['assigned_to'] == $user_id);

    if (!$isAdmin && !$isAssignee) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    // Organizers can only mark as in_progress or completed
    if (!$isAdmin && in_array($status, ['pending', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'You cannot set this status']);
        exit;
    }

    try {
        $completed_at = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("
            UPDATE admin_tasks 
            SET status = ?, completed_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $completed_at, $task_id]);

        // Log activity
        $actionType = match($status) {
            'completed' => 'approve',
            'cancelled' => 'reject',
            default => 'update'
        };
        logActivity($pdo, $user_id, $actionType, 'task', $task_id, "Task status: $status");

        $messages = [
            'pending'     => 'Task marked as pending',
            'in_progress' => 'Task started',
            'completed'   => 'Task completed! Great work! 🎉',
            'cancelled'   => 'Task cancelled'
        ];

        echo json_encode([
            'success' => true,
            'message' => $messages[$status] ?? 'Status updated'
        ]);

    } catch (PDOException $e) {
        error_log("Update task error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// DELETE TASK (Admin only)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'delete_task') {
    if ($_SESSION['type_id'] != 4) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $task_id = (int)($_POST['task_id'] ?? 0);

    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM admin_tasks WHERE id = ?");
        $stmt->execute([$task_id]);

        logActivity($pdo, $user_id, 'delete', 'task', $task_id, "Deleted task");

        echo json_encode(['success' => true, 'message' => 'Task deleted']);
    } catch (PDOException $e) {
        error_log("Delete task error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>