<?php
// Must be first — before ANY output or whitespace
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php"); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM lost_reports WHERE id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) { header("Location: index.php"); exit; }
/*  */
$isOwner    = ((int)$report['user_id'] === (int)$_SESSION['user_id']);
$isMatching = ($report['status'] === 'Matching');

// Not the owner — show warning page, don't redirect
if (!$isOwner) {
    include 'header.php';
    ?>
    <div class="d-flex align-items-center justify-content-center" style="min-height:70vh; background:#f8fafc;">
        <div class="text-center px-4" style="max-width:420px;">
            <div style="width:80px;height:80px;background:rgba(220,38,38,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-shield-lock-fill text-danger" style="font-size:2.2rem;"></i>
            </div>
            <h4 class="fw-800 mb-2">Access Restricted</h4>
            <p class="text-muted mb-4">Only the owner of this lost report can view the finder's details. If you lost this item, please make sure you're logged into the correct account.</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                <i class="bi bi-arrow-left me-2"></i>Back to gallery
            </a>
        </div>
    </div>
    <?php
    include 'footer.php';
    exit;
}

// Owner viewing their own report — check if matching
if (!$isMatching) {
    include 'header.php';
    ?>
    <div class="d-flex align-items-center justify-content-center" style="min-height:70vh; background:#f8fafc;">
        <div class="text-center px-4" style="max-width:420px;">
            <div style="width:80px;height:80px;background:rgba(13,110,253,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="bi bi-hourglass-split text-primary" style="font-size:2.2rem;"></i>
            </div>
            <h4 class="fw-800 mb-2">No Match Yet</h4>
            <p class="text-muted mb-4">Your lost report is still active. We'll notify you when someone reports finding it.</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                <i class="bi bi-arrow-left me-2"></i>Back to gallery
            </a>
        </div>
    </div>
    <?php
    include 'footer.php';
    exit;
}

// Consolidated Finder Lookup (Check both lost_contacts and linked items)
$finder = null;

// 1. Check lost_contacts (from "I Found This")
$finderStmt = $pdo->prepare("SELECT * FROM lost_contacts WHERE report_id = ? ORDER BY created_at DESC LIMIT 1");
$finderStmt->execute([$id]);
$finder = $finderStmt->fetch();

// 2. If not found in contacts, check linked items (from admin "Link & Notify")
if (!$finder) {
    $itemStmt = $pdo->prepare("
        SELECT i.image_path, i.found_location, i.description AS message, i.created_at, i.id AS item_id,
               CONCAT(u.fname, ' ', u.lname) AS finder_name, u.email AS finder_email, u.contact_number AS finder_contact, u.id AS finder_user_id
        FROM items i
        JOIN users u ON i.user_id = u.id
        WHERE i.lost_report_id = ?
        ORDER BY i.updated_at DESC LIMIT 1
    ");
    $itemStmt->execute([$id]);
    $finder = $itemStmt->fetch();
}

if (!$finder) { header("Location: index.php"); exit; }

$lostImg = (!empty($report['image_path']))
    ? 'uploads/' . $report['image_path']
    : 'https://placehold.co/400x300/e2e8f0/64748b?text=No+Image';

// Handle in-app message send
$msgSuccess = false;
$msgError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msgText = trim($_POST['message_text'] ?? '');
    if (!empty($msgText)) {
        try {
            $msgStmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $msgStmt->execute([
                $_SESSION['user_id'],
                $finder['finder_user_id'],
                'Re: Lost Item - ' . $report['item_name'],
                $msgText
            ]);
            $msgSuccess = true;
        } catch (PDOException $e) {
            $msgError = "Failed to send message. Please try again.";
        }
    } else {
        $msgError = "Message cannot be empty.";
    }
}

// Load message history
try {
    $historyStmt = $pdo->prepare("
        SELECT m.*, u.fname, u.lname
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $historyStmt->execute([
        $_SESSION['user_id'], $finder['finder_user_id'],
        $finder['finder_user_id'], $_SESSION['user_id']
    ]);
    $messages = $historyStmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finder Details</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; }

        .page-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a4ab2 100%);
            padding: 50px 0 80px; color: white;
            border-radius: 0 0 40px 40px; margin-bottom: -50px;
        }

        .main-card { border: none; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }

        .lost-item-panel {
            background: #f8fafc; border-radius: 16px; padding: 20px;
            text-align: center; border: 2px dashed #e2e8f0;
        }
        .lost-item-panel img {
            width: 100%; height: 200px; object-fit: cover;
            border-radius: 12px; border: 3px solid #e2e8f0; margin-bottom: 14px;
        }

        .owner-badge {
            background: linear-gradient(135deg, #fef9c3, #fef08a);
            border: 1.5px solid #fbbf24; color: #92400e;
            border-radius: 50px; padding: 5px 14px;
            font-size: .75rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 5px;
            margin-bottom: 10px;
        }

        .finder-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0a4ab2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: white; font-weight: 700; flex-shrink: 0;
        }

        .info-row {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 0; border-bottom: 1px solid #f1f5f9;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .info-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .info-label { font-size: .72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .info-value { font-weight: 600; color: #1e293b; font-size: .93rem; word-break: break-word; }

        .finder-message-box {
            background: #f8fafc; border-left: 4px solid #0d6efd;
            border-radius: 0 10px 10px 0; padding: 14px 16px;
            font-size: .9rem; color: #475569; line-height: 1.6;
        }

        .match-badge {
            background: #ecfdf5; border: 1.5px solid #6ee7b7; color: #065f46;
            border-radius: 50px; padding: 6px 16px; font-size: .8rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 6px;
        }

        .proof-img {
            width: 100%; border-radius: 12px; border: 2px solid #e2e8f0;
            object-fit: cover; max-height: 220px; cursor: pointer; transition: transform 0.2s;
        }
        .proof-img:hover { transform: scale(1.01); }

        .divider-label {
            display: flex; align-items: center; gap: 10px;
            color: #94a3b8; font-size: .78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px; margin: 20px 0 16px;
        }
        .divider-label::before, .divider-label::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        .chat-box {
            background: #f1f5f9; border-radius: 16px; padding: 16px;
            min-height: 180px; max-height: 340px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 10px;
        }
        .chat-bubble {
            max-width: 75%; padding: 10px 14px; border-radius: 18px;
            font-size: .88rem; line-height: 1.5;
        }
        .chat-bubble.sent {
            background: #0d6efd; color: white;
            align-self: flex-end; border-bottom-right-radius: 4px;
        }
        .chat-bubble.received {
            background: white; color: #1e293b;
            align-self: flex-start; border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .bubble-name { font-size: .7rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
        .bubble-time { font-size: .68rem; opacity: .6; margin-top: 4px; text-align: right; }
        .chat-bubble.sent .bubble-time { color: rgba(255,255,255,0.7); }
        .empty-chat { text-align: center; color: #94a3b8; padding: 30px 0; font-size: .88rem; margin: auto; }

        .chat-input-area { display: flex; gap: 10px; margin-top: 12px; align-items: flex-end; }
        .chat-input-area textarea {
            flex: 1; border: 1.5px solid #e2e8f0; border-radius: 14px;
            padding: 12px 16px; font-size: .9rem; resize: none;
            font-family: inherit; transition: border-color 0.2s; max-height: 120px;
        }
        .chat-input-area textarea:focus {
            outline: none; border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
        }
        .send-btn {
            width: 48px; height: 48px; border-radius: 50%;
            background: #0d6efd; border: none; color: white;
            font-size: 1.1rem; display: flex; align-items: center;
            justify-content: center; flex-shrink: 0;
            transition: background 0.2s, transform 0.2s; cursor: pointer;
        }
        .send-btn:hover { background: #0a4ab2; transform: scale(1.05); }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header text-center">
    <div class="container">
        <span class="match-badge mb-3 d-inline-flex">
            <i class="bi bi-person-check-fill"></i> Someone Found Your Item
        </span>
        <h2 class="fw-800 mb-1">Finder Details</h2>
        <p class="opacity-75 mb-0">Contact the finder below to recover your item.</p>
    </div>
</div>

<div class="container py-5" style="max-width: 960px;">
    <div class="row g-4">

        <!-- LEFT: Your Lost Item -->
        <div class="col-md-4">
            <div class="main-card card p-3 h-100">
                <div class="info-label mb-3">
                    <i class="bi bi-bookmark-fill me-1 text-warning"></i> Your Lost Item
                </div>
                <div class="lost-item-panel">
                    <!-- Owner badge on the item panel -->
                    <div class="owner-badge">
                        <i class="bi bi-person-fill"></i> Your Report
                    </div>
                    <img src="<?= $lostImg ?>" alt="Lost Item">
                    <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($report['item_name']) ?></div>
                    <?php if (!empty($report['category'])): ?>
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($report['category']) ?></span>
                    <?php endif; ?>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        <?= htmlspecialchars($report['last_seen_location']) ?>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-calendar-x text-secondary"></i>
                        Lost: <?= date('M d, Y', strtotime($report['date_lost'])) ?>
                    </div>
                    <?php if (!empty($report['description'])): ?>
                    <p class="text-muted small mt-2 mb-0">
                        <?= htmlspecialchars(substr($report['description'], 0, 100)) ?>...
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: Finder Info + Chat -->
        <div class="col-md-8">
            <div class="main-card card p-4">

                <!-- Finder Header -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="finder-avatar">
                        <?= strtoupper(substr($finder['finder_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-800 fs-5 mb-0"><?= htmlspecialchars($finder['finder_name']) ?></div>
                        <div class="text-success small fw-bold">
                            <i class="bi bi-patch-check-fill me-1"></i>Reported finding your item
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            <?= date('M d, Y h:i A', strtotime($finder['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($finder['finder_email']) ?></div>
                    </div>
                </div>

                <?php if (!empty($finder['finder_contact'])): ?>
                <div class="info-row">
                    <div class="info-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div>
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><?= htmlspecialchars($finder['finder_contact']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($finder['found_location'])): ?>
                <div class="info-row">
                    <div class="info-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <div class="info-label">Where They Found It</div>
                        <div class="info-value"><?= htmlspecialchars($finder['found_location']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($finder['message'])): ?>
                <div class="divider-label"><i class="bi bi-chat-left-text"></i> Message from Finder</div>
                <div class="finder-message-box"><?= nl2br(htmlspecialchars($finder['message'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($finder['image_path'])): ?>
                <div class="divider-label"><i class="bi bi-image-fill"></i> Proof Photo</div>
                <img src="uploads/<?= htmlspecialchars($finder['image_path']) ?>"
                     alt="Proof" class="proof-img"
                     data-bs-toggle="modal" data-bs-target="#proofModal">
                <div class="text-muted small mt-1">
                    <i class="bi bi-zoom-in me-1"></i>Click to enlarge
                </div>
                <?php endif; ?>

                <!-- IN-APP CHAT -->
                <div class="divider-label"><i class="bi bi-chat-dots-fill"></i> Send a Message</div>

                <?php if ($msgSuccess): ?>
                <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.85rem">
                    <i class="bi bi-check-circle-fill me-1"></i> Message sent successfully!
                </div>
                <?php elseif ($msgError): ?>
                <div class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.85rem">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($msgError) ?>
                </div>
                <?php endif; ?>

                <div class="chat-box" id="chatBox">
                    <?php if (empty($messages)): ?>
                        <div class="empty-chat">
                            <i class="bi bi-chat-dots display-6 d-block mb-2 opacity-50"></i>
                            No messages yet. Start the conversation!
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg):
                            $isSent = ((int)$msg['sender_id'] === (int)$_SESSION['user_id']);
                        ?>
                        <div class="chat-bubble <?= $isSent ? 'sent' : 'received' ?>">
                            <?php if (!$isSent): ?>
                            <div class="bubble-name">
                                <?= htmlspecialchars($msg['fname'] . ' ' . $msg['lname']) ?>
                            </div>
                            <?php endif; ?>
                            <?= nl2br(htmlspecialchars($msg['body'])) ?>
                            <div class="bubble-time">
                                <?= date('M d, h:i A', strtotime($msg['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <div class="chat-input-area">
                        <textarea name="message_text" rows="2"
                            placeholder="Type a message... (Enter to send, Shift+Enter for new line)"
                            required></textarea>
                        <button type="submit" name="send_message" class="send-btn">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </form>

                <!-- MATCH ACTIONS -->
                <div class="divider-label"><i class="bi bi-shield-check"></i> Match Confirmation</div>
                <div class="alert bg-light border-0 rounded-4 p-3 mb-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                            <i class="bi bi-question-circle-fill fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">Is this the correct item?</div>
                            <div class="text-muted" style="font-size:.8rem;">Confirming will resolve this report. Rejecting will return it to "Lost" status.</div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-success flex-grow-1 rounded-pill fw-bold" onclick="handleMatchAction('confirm')">
                            <i class="bi bi-check-circle-fill me-1"></i> Yes, this is mine
                        </button>
                        <button class="btn btn-outline-danger flex-grow-1 rounded-pill fw-bold" onclick="handleMatchAction('reject')">
                            <i class="bi bi-x-circle-fill me-1"></i> No, this is not it
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php if (!empty($finder['image_path'])): ?>
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 bg-dark">
            <div class="modal-header border-0 bg-dark">
                <span class="text-white fw-bold"><i class="bi bi-image me-2"></i>Proof Photo</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img src="uploads/<?= htmlspecialchars($finder['image_path']) ?>"
                     class="img-fluid rounded-3" alt="Proof Photo">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const chatBox = document.getElementById('chatBox');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

    const textarea = document.querySelector('.chat-input-area textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    }

    async function handleMatchAction(action) {
        const confirmText = action === 'confirm' 
            ? "Are you sure this is your item? This will mark your report as Resolved." 
            : "Are you sure this is not your item? This will return your report to 'Lost' status.";
            
        if (!confirm(confirmText)) return;

        const formData = new FormData();
        formData.append('action', action);
        formData.append('report_id', <?= $id ?>);

        try {
            const response = await fetch('process_match_action.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.href = 'index.php';
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
        }
    }
</script>
</body>
</html>