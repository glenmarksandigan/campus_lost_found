<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include 'db.php';

// Allow Admin (type_id=4) OR SSG President (type_id=6, organizer_role='president')
$allowed = false;
if (isset($_SESSION['user_id'])) {
    $tid = (int)($_SESSION['type_id'] ?? 0);
    if ($tid === 4) {
        $allowed = true;
    } elseif ($tid === 6) {
        $chk = $pdo->prepare("SELECT organizer_role FROM users WHERE id = ?");
        $chk->execute([$_SESSION['user_id']]);
        if ($chk->fetchColumn() === 'president') $allowed = true;
    }
}
if (!$allowed) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$user_id  = intval($_POST['user_id'] ?? 0);
$can_edit = intval($_POST['can_edit'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid User ID']);
    exit;
}

// Ensure we are not toggling the president (extra safety)
$check = $pdo->prepare("SELECT organizer_role FROM users WHERE id = ?");
$check->execute([$user_id]);
$role = $check->fetchColumn();

if ($role === 'president') {
    echo json_encode(['success' => false, 'error' => 'Cannot modify President permissions']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET can_edit = ? WHERE id = ? AND type_id = 6");
    $stmt->execute([$can_edit, $user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
