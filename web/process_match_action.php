<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';
include 'activity_logger.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$reportId = (int)($_POST['report_id'] ?? 0);

if (!$reportId || !in_array($action, ['confirm', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']); exit;
}

try {
    // Verify ownership of the report
    $stmt = $pdo->prepare("SELECT * FROM lost_reports WHERE id = ? AND user_id = ?");
    $stmt->execute([$reportId, $_SESSION['user_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found or access denied']); exit;
    }

    if ($action === 'reject') {
        // 1. Revert report status to 'Lost'
        $pdo->prepare("UPDATE lost_reports SET status = 'Lost', updated_at = NOW() WHERE id = ?")->execute([$reportId]);

        // 2. Clear any item links that point to this report
        // Revert claiming item back to Published
        $pdo->prepare("UPDATE items SET lost_report_id = NULL, status = 'Published', updated_at = NOW() WHERE lost_report_id = ?")->execute([$reportId]);

        // 3. Mark any lost_contacts as rejected (optional, but good for history)
        // For now we just revert the main state.

        logActivity($pdo, $_SESSION['user_id'], 'update', 'lost_report', $reportId, 'Rejected match, reverted to Lost');
        echo json_encode(['success' => true, 'message' => 'Match rejected. Your report is now back to "Lost" status.']);
    } else {
        // Confirm
        // 1. Update report status to 'Resolved'
        $pdo->prepare("UPDATE lost_reports SET status = 'Resolved', updated_at = NOW() WHERE id = ?")->execute([$reportId]);

        // 2. If there's a linked item, mark it as 'Returned'
        $pdo->prepare("UPDATE items SET status = 'Returned', updated_at = NOW() WHERE lost_report_id = ? AND status = 'Claiming'")->execute([$reportId]);

        logActivity($pdo, $_SESSION['user_id'], 'approve', 'lost_report', $reportId, 'Confirmed match, resolved report');
        echo json_encode(['success' => true, 'message' => 'Match confirmed! Your report is now marked as Resolved.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
