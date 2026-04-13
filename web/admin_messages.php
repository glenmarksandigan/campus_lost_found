<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Only allow Admins (type_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 4) {
    header('Location: auth.php');
    exit;
}

// Count unread messages
$unreadCount = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadCount->execute([$_SESSION['user_id']]);
$totalUnread = $unreadCount->fetchColumn();

$admin_id = $_SESSION['user_id'];

// Fetch messages sent to this admin
$stmt = $pdo->prepare("
    SELECT m.*, u.fname, u.lname, u.email as sender_email
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$admin_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Inbox - FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        .msg-card { border-left: 4px solid var(--admin-blue); transition: transform 0.2s; border-radius: 16px; box-shadow: var(--admin-card-shadow); border-top: none; border-right: none; border-bottom: none; }
        .msg-card:hover { transform: translateX(5px); }
        .unread { background-color: #fffbeb; border-left-color: #f59e0b; }
        body { background: var(--admin-bg) !important; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-inbox-fill me-2"></i>Guard Messages</h2>
            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
        </div>

        <?php if (empty($messages)): ?>
            <div class="alert alert-info">No messages from guards yet.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($messages as $msg): ?>
                    <div class="col-12 mb-3">
                        <div class="card msg-card shadow-sm <?= $msg['is_read'] ? '' : 'unread' ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($msg['subject']) ?></h5>
                                    <small class="text-muted"><?= date('M d, g:i a', strtotime($msg['created_at'])) ?></small>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    From: <?= htmlspecialchars($msg['fname'] . ' ' . $msg['lname']) ?> 
                                    <small>(<?= htmlspecialchars($msg['sender_email']) ?>)</small>
                                </h6>
                                <p class="card-text mt-3"><?= nl2br(htmlspecialchars($msg['body'])) ?></p>
                                
                                <div class="mt-3">
                                    <a href="mailto:<?= $msg['sender_email'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-reply"></i> Reply via Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>