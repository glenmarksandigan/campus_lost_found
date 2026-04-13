<?php
// guard_actions.php - Handles all guard dashboard actions
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Only guards allowed
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include 'db.php';

$response = ['success' => false, 'message' => 'Invalid request'];
$action   = $_POST['action'] ?? '';
$guard_id = $_SESSION['user_id'];

// ── Log a Found Item ──────────────────────────────────────────────────────
if ($action === 'log_found') {
    $item_name      = trim($_POST['item_name']      ?? '');
    $description    = trim($_POST['description']    ?? '');
    $found_location = trim($_POST['found_location'] ?? '');
    $date_found     = $_POST['date_found']          ?? '';
    $turned_by      = trim($_POST['turned_by']      ?? '');
    $lost_report_id = intval($_POST['lost_report_id'] ?? 0) ?: null;

    if (empty($item_name) || empty($found_location) || empty($date_found)) {
        $response['message'] = 'Item name, location and date are required';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO items 
                    (item_name, description, found_location, date_found, turned_in_by, lost_report_id, logged_by_user_id, status, created_at)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, 'found', NOW())
            ");
            $stmt->execute([$item_name, $description, $found_location, $date_found, $turned_by, $lost_report_id, $guard_id]);

            if ($lost_report_id) {
                $pdo->prepare("UPDATE lost_reports SET status = 'found' WHERE id = ?")
                    ->execute([$lost_report_id]);
            }

            $response = ['success' => true, 'message' => 'Item logged successfully!'];
        } catch (PDOException $e) {
            error_log("Log found item error: " . $e->getMessage());
            $response['message'] = 'Database error occurred';
        }
    }
}

// ── Mark Item as Claimed ──────────────────────────────────────────────────
elseif ($action === 'mark_claimed') {
    $item_id = intval($_POST['item_id'] ?? 0);
    if (!$item_id) {
        $response['message'] = 'Invalid item ID';
    } else {
        try {
            $pdo->prepare("UPDATE items SET status = 'Returned', claimed_at = NOW() WHERE id = ?")
                ->execute([$item_id]);

            $pdo->prepare("
                UPDATE lost_reports SET status = 'Resolved' 
                WHERE id = (SELECT lost_report_id FROM items WHERE id = ?)
            ")->execute([$item_id]);

            $response = ['success' => true, 'message' => 'Item marked as returned!'];
        } catch (PDOException $e) {
            error_log("Mark claimed error: " . $e->getMessage());
            $response['message'] = 'Database error occurred';
        }
    }
}

// ── Mark Item as Returned (from Process Claim) ────────────────────────────
elseif ($action === 'mark_returned') {
    $item_id    = intval($_POST['item_id']    ?? 0);
    $claimer_id = intval($_POST['claimer_id'] ?? 0);

    if (!$item_id || !$claimer_id) {
        $response['message'] = 'Invalid item or claimer ID';
    } else {
        try {
            // Update item status to Returned
            $pdo->prepare("UPDATE items SET status = 'Returned' WHERE id = ?")
                ->execute([$item_id]);

            // Record who the item was returned to in the claims table
            $pdo->prepare("
                UPDATE claims SET returned_at = NOW() 
                WHERE item_id = ? AND user_id = ?
            ")->execute([$item_id, $claimer_id]);

            $response = ['success' => true, 'message' => 'Item marked as returned to owner.'];
        } catch (PDOException $e) {
            error_log("Mark returned error: " . $e->getMessage());
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// ── Send In-App Message to Admin ──────────────────────────────────────────
elseif ($action === 'send_message') {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body']    ?? '');

    if (empty($subject) || empty($body)) {
        $response['message'] = 'Subject and message are required';
    } else {
        try {
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE type_id = 4");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($admins)) {
                $response['message'] = 'No admin accounts found';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (sender_id, receiver_id, subject, body, created_at)
                    VALUES (:sender, :receiver, :sub, :body, NOW())
                ");
                foreach ($admins as $admin_id) {
                    $stmt->execute([
                        ':sender'   => $guard_id,
                        ':receiver' => $admin_id,
                        ':sub'      => $subject,
                        ':body'     => $body
                    ]);
                }
                $response = ['success' => true, 'message' => 'Message sent!'];
            }
        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            $response['message'] = 'SQL Error: ' . $e->getMessage();
        }
    }
}

ob_clean();
echo json_encode($response);
exit;