<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Only allow Admins (4) or Organizers (6)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 6])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

// All administrators and organizers can perform these actions
$isAuthorized = true; 


$action = $_POST['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

try {
    if ($action === 'send_message') {
        $receiver_id = (int)$_POST['receiver_id'];
        $message = trim($_POST['message'] ?? '');
        $item_name = trim($_POST['item_name'] ?? 'Item');

        if (!$receiver_id || !$message) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $subject = "Message regarding: $item_name";
        $stmt->execute([$user_id, $receiver_id, $subject, $message]);

        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        exit;
    }

    if ($action === 'verify_claim') {
        $claim_id = (int)$_POST['claim_id'];
        
        // Get claim and item info
        $stmt = $pdo->prepare("SELECT id, item_id, user_id FROM claims WHERE id = ?");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();

        if (!$claim) {
            echo json_encode(['success' => false, 'message' => 'Claim not found']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Mark this claim as verified
        $pdo->prepare("UPDATE claims SET status = 'verified' WHERE id = ?")->execute([$claim_id]);

        // 2. Mark other claims for same item as rejected
        $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE item_id = ? AND id != ?")
            ->execute([$claim['item_id'], $claim_id]);

        // 3. Keep item in 'Claiming' status but maybe log that it's verified
        // You can add a new item status 'Verified' if you want, but sticking to the plan:
        // Item stays as 'Claiming' until physically returned.

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Claimant verified. Competitors rejected.']);
        exit;
    }

    if ($action === 'reject_claim') {
        $claim_id = (int)$_POST['claim_id'];
        $stmt = $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$claim_id]);
        echo json_encode(['success' => true, 'message' => 'Claim rejected']);
        exit;
    }

    if ($action === 'complete_return') {
        $item_id = (int)$_POST['item_id'];
        $claim_id = (int)$_POST['claim_id'];

        $pdo->beginTransaction();

        // Update item status
        $pdo->prepare("UPDATE items SET status = 'Returned' WHERE id = ?")->execute([$item_id]);

        // Set returned_at timestamp on the claim
        $pdo->prepare("UPDATE claims SET returned_at = NOW() WHERE id = ?")->execute([$claim_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Item marked as Returned and Returned timestamp updated.']);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
