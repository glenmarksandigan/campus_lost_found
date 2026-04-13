<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Only Admin (type_id=4) and Super Admin (type_id=5) can access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4, 5])) {
    header('Location: auth.php');
    exit;
}

$current_user_type = (int)$_SESSION['type_id'];

// ── Get all unique conversations ─────────────────────────────────────
$convStmt = $pdo->query("
    SELECT 
        LEAST(m.sender_id, m.receiver_id) AS user1_id,
        GREATEST(m.sender_id, m.receiver_id) AS user2_id,
        u1.fname AS u1_fname, u1.lname AS u1_lname, u1.type_id AS u1_type,
        u2.fname AS u2_fname, u2.lname AS u2_lname, u2.type_id AS u2_type,
        MAX(m.created_at) AS last_message_time,
        COUNT(m.id) AS message_count,
        (SELECT body FROM messages WHERE 
            (sender_id = LEAST(m.sender_id, m.receiver_id) AND receiver_id = GREATEST(m.sender_id, m.receiver_id))
            OR (sender_id = GREATEST(m.sender_id, m.receiver_id) AND receiver_id = LEAST(m.sender_id, m.receiver_id))
            ORDER BY created_at DESC LIMIT 1
        ) AS last_message
    FROM messages m
    JOIN users u1 ON u1.id = LEAST(m.sender_id, m.receiver_id)
    JOIN users u2 ON u2.id = GREATEST(m.sender_id, m.receiver_id)
    GROUP BY user1_id, user2_id, u1.fname, u1.lname, u1.type_id, u2.fname, u2.lname, u2.type_id
    ORDER BY last_message_time DESC
");
$conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Load selected conversation ───────────────────────────────────────
$activeUser1 = isset($_GET['u1']) ? (int)$_GET['u1'] : 0;
$activeUser2 = isset($_GET['u2']) ? (int)$_GET['u2'] : 0;
$convMessages = [];
$user1Info = null;
$user2Info = null;

if ($activeUser1 && $activeUser2) {
    $u1Stmt = $pdo->prepare("SELECT id, fname, lname, email, type_id FROM users WHERE id = ?");
    $u1Stmt->execute([$activeUser1]);
    $user1Info = $u1Stmt->fetch();

    $u2Stmt = $pdo->prepare("SELECT id, fname, lname, email, type_id FROM users WHERE id = ?");
    $u2Stmt->execute([$activeUser2]);
    $user2Info = $u2Stmt->fetch();

    $msgStmt = $pdo->prepare("
        SELECT m.*, u.fname, u.lname, u.type_id as sender_type
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $msgStmt->execute([$activeUser1, $activeUser2, $activeUser2, $activeUser1]);
    $convMessages = $msgStmt->fetchAll();
}

$roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'Super Admin', 6=>'Organizer'];
$roleColors = [1=>'#3b82f6', 2=>'#0d9488', 3=>'#f59e0b', 4=>'#0d6efd', 5=>'#7c3aed', 6=>'#ec4899'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Conversations | FoundIt!</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--admin-bg) !important; color: #1e293b; }

        .page-wrapper { max-width: 1100px; margin: 0 auto; padding: 0 15px 80px; }
        .admin-page-header { padding: 28px 0 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .page-header h4 { font-weight: 800; margin: 0; }
        .page-header .badge { font-size: .78rem; }

        .conv-card {
            border: none; border-radius: 20px;
            box-shadow: var(--admin-card-shadow);
            overflow: hidden; min-height: 620px; display: flex;
            background: #fff;
        }

        /* Sidebar */
        .conv-sidebar { width: 340px; flex-shrink: 0; border-right: 1px solid #f1f5f9; background: #fff; overflow-y: auto; display: flex; flex-direction: column; }
        .conv-sidebar-header { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 800; font-size: 1rem; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .conv-search { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
        .conv-search input {
            width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px;
            padding: 9px 12px 9px 36px; font-size: .82rem; outline: none;
            background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") 12px center no-repeat;
            transition: all 0.2s;
        }
        .conv-search input:focus { border-color: #0d6efd; background-color: #fff; }

        .conv-list { flex: 1; overflow-y: auto; }
        .conv-item {
            padding: 14px 20px; border-bottom: 1px solid #f8fafc;
            cursor: pointer; transition: all 0.2s; text-decoration: none;
            display: block; color: inherit;
        }
        .conv-item:hover { background: #f8fafc; color: inherit; }
        .conv-item.active { background: #eff6ff; border-left: 3px solid #0d6efd; }
        .conv-item .conv-names { font-weight: 700; font-size: .88rem; margin-bottom: 2px; }
        .conv-item .conv-preview { font-size: .78rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }
        .conv-item .conv-meta { font-size: .72rem; color: #94a3b8; display: flex; align-items: center; gap: 8px; margin-top: 4px; }

        .conv-avatar-pair { display: flex; align-items: center; }
        .conv-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .78rem; color: #fff; flex-shrink: 0;
            border: 2px solid #fff;
        }
        .conv-avatar:nth-child(2) { margin-left: -12px; }

        /* Chat panel */
        .chat-panel { flex: 1; display: flex; flex-direction: column; background: #fff; }
        .chat-panel-header {
            padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 14px; flex-shrink: 0;
        }
        .chat-panel-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; gap: 16px; padding: 40px; }

        .chat-messages { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px; max-height: 500px; }
        .chat-bubble {
            max-width: 70%; padding: 10px 14px; border-radius: 18px;
            font-size: .86rem; line-height: 1.5;
            opacity: 0; animation: bubbleIn 0.3s ease forwards;
        }
        @keyframes bubbleIn {
            from { opacity: 0; transform: translateY(8px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .chat-bubble.left { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
        .chat-bubble.right { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .bubble-name { font-size: .7rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
        .bubble-time { font-size: .68rem; opacity: .6; margin-top: 4px; text-align: right; }
        .chat-bubble.right .bubble-time { color: rgba(255,255,255,0.7); }

        .read-only-banner {
            padding: 10px 24px; background: #fffbeb; border-top: 1px solid #fef3c7;
            color: #92400e; font-size: .82rem; text-align: center; font-weight: 500;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .conv-sidebar { width: 100%; border-right: none; max-height: <?= ($activeUser1 && $activeUser2) ? '200px' : 'none' ?>; }
            .conv-card { flex-direction: column; }
            .chat-panel { display: <?= ($activeUser1 && $activeUser2) ? 'flex' : 'none' ?>; }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div style="padding: 0 28px 80px;">
    <div class="admin-page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-chat-square-text me-2 text-primary"></i>User Conversations</h2>
                <p class="mb-0 opacity-75">Read-only view of all user messaging threads</p>
            </div>
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                <i class="bi bi-eye me-1"></i>Observer Mode · <?= count($conversations) ?> threads
            </span>
        </div>
    </div>

    <div class="conv-card">
        <!-- Conversation Sidebar -->
        <div class="conv-sidebar">
            <div class="conv-sidebar-header">
                <i class="bi bi-chat-dots text-primary"></i> All Threads
            </div>

            <div class="conv-search">
                <input type="text" id="convSearch" placeholder="Search by name..." autocomplete="off">
            </div>

            <div class="conv-list">
                <?php if (empty($conversations)): ?>
                <div class="text-center text-muted py-5 px-3" style="font-size:.88rem">
                    <i class="bi bi-chat-dots display-5 d-block mb-2 opacity-30"></i>
                    No conversations found.
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv):
                        $isActive = ($activeUser1 == $conv['user1_id'] && $activeUser2 == $conv['user2_id']);
                        $u1Role = $roleMap[$conv['u1_type']] ?? 'User';
                        $u2Role = $roleMap[$conv['u2_type']] ?? 'User';
                        $u1Color = $roleColors[$conv['u1_type']] ?? '#64748b';
                        $u2Color = $roleColors[$conv['u2_type']] ?? '#64748b';
                    ?>
                    <a href="view_conversations.php?u1=<?= $conv['user1_id'] ?>&u2=<?= $conv['user2_id'] ?>"
                       class="conv-item <?= $isActive ? 'active' : '' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="conv-avatar-pair">
                                <div class="conv-avatar" style="background:<?= $u1Color ?>">
                                    <?= strtoupper(substr($conv['u1_fname'], 0, 1)) ?>
                                </div>
                                <div class="conv-avatar" style="background:<?= $u2Color ?>">
                                    <?= strtoupper(substr($conv['u2_fname'], 0, 1)) ?>
                                </div>
                            </div>
                            <div style="min-width:0; flex:1">
                                <div class="conv-names">
                                    <?= htmlspecialchars($conv['u1_fname']) ?> & <?= htmlspecialchars($conv['u2_fname']) ?>
                                </div>
                                <div class="conv-preview"><?= htmlspecialchars($conv['last_message'] ?? '') ?></div>
                                <div class="conv-meta">
                                    <span><i class="bi bi-chat-left-text me-1"></i><?= $conv['message_count'] ?> messages</span>
                                    <span>·</span>
                                    <span><?= date('M d, h:i A', strtotime($conv['last_message_time'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel">
            <?php if (!$activeUser1 || !$user1Info || !$user2Info): ?>
            <div class="chat-panel-empty">
                <div style="width:80px;height:80px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-chat-square-dots" style="font-size:2.2rem; color:#cbd5e1"></i>
                </div>
                <div style="font-size:1rem; font-weight:700; color:#64748b">Select a conversation</div>
                <div style="font-size:.85rem; color:#94a3b8">Pick a thread from the left to view the messages</div>
            </div>

            <?php else: ?>
            <!-- Chat Header -->
            <div class="chat-panel-header">
                <div class="conv-avatar-pair">
                    <div class="conv-avatar" style="background:<?= $roleColors[$user1Info['type_id']] ?? '#64748b' ?>">
                        <?= strtoupper(substr($user1Info['fname'], 0, 1)) ?>
                    </div>
                    <div class="conv-avatar" style="background:<?= $roleColors[$user2Info['type_id']] ?? '#64748b' ?>">
                        <?= strtoupper(substr($user2Info['fname'], 0, 1)) ?>
                    </div>
                </div>
                <div>
                    <div class="fw-bold">
                        <?= htmlspecialchars($user1Info['fname'] . ' ' . $user1Info['lname']) ?>
                        <span class="badge ms-1" style="background:<?= $roleColors[$user1Info['type_id']] ?? '#64748b' ?>;font-size:.6rem">
                            <?= $roleMap[$user1Info['type_id']] ?? 'User' ?>
                        </span>
                        <span class="mx-1 text-muted">↔</span>
                        <?= htmlspecialchars($user2Info['fname'] . ' ' . $user2Info['lname']) ?>
                        <span class="badge ms-1" style="background:<?= $roleColors[$user2Info['type_id']] ?? '#64748b' ?>;font-size:.6rem">
                            <?= $roleMap[$user2Info['type_id']] ?? 'User' ?>
                        </span>
                    </div>
                    <div class="text-muted small"><?= count($convMessages) ?> messages in thread</div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($convMessages)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-chat-dots display-6 d-block mb-2 opacity-50"></i>
                    No messages in this thread.
                </div>
                <?php else: ?>
                    <?php foreach ($convMessages as $i => $msg):
                        $isUser1 = ((int)$msg['sender_id'] === $activeUser1);
                        $senderRole = $roleMap[$msg['sender_type']] ?? 'User';
                    ?>
                    <div class="chat-bubble <?= $isUser1 ? 'left' : 'right' ?>" style="animation-delay:<?= ($i * 0.03) ?>s">
                        <div class="bubble-name">
                            <?= htmlspecialchars($msg['fname'] . ' ' . $msg['lname']) ?>
                            <span style="opacity:.6">(<?= $senderRole ?>)</span>
                        </div>
                        <?= nl2br(htmlspecialchars($msg['body'])) ?>
                        <div class="bubble-time">
                            <?= date('M d, h:i A', strtotime($msg['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Read-only banner -->
            <div class="read-only-banner">
                <i class="bi bi-lock me-1"></i>
                Read-only view — You are observing this conversation as an administrator.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /admin-main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Scroll chat to bottom
    const chat = document.getElementById('chatMessages');
    if (chat) chat.scrollTop = chat.scrollHeight;

    // Search conversations
    const convSearch = document.getElementById('convSearch');
    if (convSearch) {
        convSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('.conv-item').forEach(item => {
                const names = item.querySelector('.conv-names');
                if (!names) return;
                const match = names.textContent.toLowerCase().includes(term);
                item.style.display = match ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>
