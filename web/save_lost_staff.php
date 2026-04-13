<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');
include 'db.php';
include 'activity_logger.php';

$action = $_POST['action'] ?? '';

if ($action === 'report_lost') {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date_lost = trim($_POST['date_lost'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');

    // Owner info — use POST values if provided, else fall back to session
    $owner_name = trim($_POST['owner_name'] ?? '') ?: trim($_SESSION['user_fname'] . ' ' . $_SESSION['user_lname']);
    $owner_email = trim($_POST['owner_email'] ?? '') ?: ($_SESSION['user_email'] ?? '');
    $owner_contact = $contact ?: ($_SESSION['user_contact'] ?? '');

    if (empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit;
    }
    if (empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Last seen location is required']);
        exit;
    }
    if (empty($date_lost)) {
        echo json_encode(['success' => false, 'message' => 'Date lost is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO lost_reports
                (item_name, category, last_seen_location, description,
                 date_lost, owner_name, owner_contact, owner_email, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $stmt->execute([
            $item_name, $category, $location, $description,
            $date_lost, $owner_name, $owner_contact, $owner_email
        ]);

        $newReportId = $pdo->lastInsertId();
        logActivity($pdo, $_SESSION['user_id'], 'create', 'lost_report', $newReportId, 'Staff reported lost: ' . $item_name);

        echo json_encode([
            'success' => true,
            'message' => '✅ Lost report submitted and awaiting approval!'
        ]);
    }
    catch (PDOException $e) {
        error_log("save_lost error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
exit;
?>