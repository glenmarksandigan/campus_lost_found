<?php
/**
 * messages_panel.php  –  Reusable inbox / chat panel
 *
 * Requirements before including this file:
 *   - session already started
 *   - $pdo  (PDO connection)
 *   - $user_id (int, current user)
 *
 * Optional:
 *   - $panelTitle  (string)  – sidebar header label, default "Messages"
 *   - $panelHeight (string)  – CSS height of chat bubble area, default "420px"
 *   - $queryParam  (string)  – GET key used for ?with=, default "msg_with"
 *                              (use a unique key per page to avoid conflicts)
 */

$panelTitle  = $panelTitle  ?? 'Messages';
$panelHeight = $panelHeight ?? '420px';
$queryParam  = $queryParam  ?? 'msg_with';

/* ── Handle reply POST ─────────────────────────────────────────────────────── */
$mp_replySuccess = false;
$mp_replyError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mp_send_reply'])) {
    $mp_replyText   = trim($_POST['mp_reply_text'] ?? '');
    $mp_receiver_id = (int)($_POST['mp_receiver_id'] ?? 0);
    $mp_subject     = trim($_POST['mp_subject'] ?? 'Message');

    if (!empty($mp_replyText) && $mp_receiver_id) {
        try {
            $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ")->execute([$user_id, $mp_receiver_id, $mp_subject, $mp_replyText]);
            $mp_replySuccess = true;
        } catch (PDOException $e) {
            $mp_replyError = 'Failed to send. Please try again.';
        }
    } else {
        $mp_replyError = 'Message cannot be empty.';
    }
}

/* ── Conversations list ────────────────────────────────────────────────────── */
$mp_convStmt = $pdo->prepare("
    SELECT
        u.id   AS other_user_id,
        u.fname, u.lname,
        (SELECT sub.subject FROM messages sub
         WHERE (sub.sender_id = ? AND sub.receiver_id = u.id)
            OR (sub.sender_id = u.id AND sub.receiver_id = ?)
         ORDER BY sub.created_at DESC LIMIT 1) AS subject,
        MAX(m.created_at) AS last_message_time,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN users u ON u.id = CASE
        WHEN m.sender_id = ? THEN m.receiver_id
        ELSE m.sender_id
    END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY u.id, u.fname, u.lname
    ORDER BY last_message_time DESC
");
$mp_convStmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$mp_conversations = $mp_convStmt->fetchAll();

/* ── Load selected conversation ────────────────────────────────────────────── */
$mp_activeConv   = null;
$mp_convMessages = [];
$mp_otherUser    = null;
$mp_convSubject  = '';

if (isset($_GET[$queryParam])) {
    $mp_withId = (int)$_GET[$queryParam];

    // Mark messages as read
    $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ?
    ")->execute([$mp_withId, $user_id]);

    // Other user info
    $mp_ouStmt = $pdo->prepare("SELECT id, fname, lname, email FROM users WHERE id = ?");
    $mp_ouStmt->execute([$mp_withId]);
    $mp_otherUser = $mp_ouStmt->fetch();

    // Subject from first message
    $mp_subStmt = $pdo->prepare("
        SELECT subject FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC LIMIT 1
    ");
    $mp_subStmt->execute([$user_id, $mp_withId, $mp_withId, $user_id]);
    $mp_subRow      = $mp_subStmt->fetch();
    $mp_convSubject = $mp_subRow['subject'] ?? 'Re: Lost & Found';

    // All messages
    $mp_msgStmt = $pdo->prepare("
        SELECT m.*, u.fname, u.lname
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $mp_msgStmt->execute([$user_id, $mp_withId, $mp_withId, $user_id]);
    $mp_convMessages = $mp_msgStmt->fetchAll();
    $mp_activeConv   = $mp_withId;
}

/* ── Total unread ──────────────────────────────────────────────────────────── */
$mp_unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$mp_unreadStmt->execute([$user_id]);
$mp_totalUnread = (int)$mp_unreadStmt->fetchColumn();

/* ── Unique suffix so multiple panels on same page don't clash ─────────────── */
$mp_uid = 'mp_' . md5($queryParam);
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MESSAGES PANEL  (self-contained styles + markup)
════════════════════════════════════════════════════════════════════════════ -->
<style>
/* Scoped with .mp-wrap to avoid leaking into host page */
.mp-wrap { font-family: 'Plus Jakarta Sans', 'DM Sans', sans-serif; }

.mp-inbox-card {
    border: none; border-radius: 20px;
    box-shadow: 0 6px 30px rgba(0,0,0,.08);
    overflow: hidden; display: flex; min-height: 540px;
}

/* Sidebar */
.mp-sidebar {
    width: 260px; flex-shrink: 0;
    border-right: 1px solid #f1f5f9;
    background: #fff; overflow-y: auto;
}
.mp-sidebar-header {
    padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
    font-weight: 800; font-size: 1rem;
    display: flex; align-items: center; gap: 8px;
}
.mp-conv-item {
    padding: 14px 18px; border-bottom: 1px solid #f8fafc;
    cursor: pointer; transition: background .15s;
    text-decoration: none; display: block; color: inherit;
}
.mp-conv-item:hover { background: #f8fafc; }
.mp-conv-item.mp-active {
    background: #eff6ff;
    border-left: 3px solid #0d6efd;
}
.mp-conv-name    { font-weight: 700; font-size: .88rem; }
.mp-conv-subject { font-size: .75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mp-conv-time    { font-size: .70rem; color: #94a3b8; }
.mp-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #0a4ab2);
    color: #fff; font-weight: 700; font-size: .9rem;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* Chat panel */
.mp-chat-panel { flex: 1; display: flex; flex-direction: column; background: #fff; }
.mp-chat-header {
    padding: 16px 22px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 12px;
}
.mp-chat-empty {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: #94a3b8; gap: 10px;
}
.mp-messages {
    flex: 1; overflow-y: auto; padding: 18px 22px;
    display: flex; flex-direction: column; gap: 10px;
    max-height: <?= $panelHeight ?>;
}
.mp-bubble {
    max-width: 72%; padding: 10px 14px;
    border-radius: 18px; font-size: .875rem; line-height: 1.5;
}
.mp-bubble.mp-sent {
    background: #0d6efd; color: #fff;
    align-self: flex-end; border-bottom-right-radius: 4px;
}
.mp-bubble.mp-received {
    background: #f1f5f9; color: #1e293b;
    align-self: flex-start; border-bottom-left-radius: 4px;
}
.mp-bubble-name { font-size: .68rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
.mp-bubble-time { font-size: .66rem; opacity: .6; margin-top: 4px; text-align: right; }
.mp-bubble.mp-sent .mp-bubble-time { color: rgba(255,255,255,.7); }

.mp-input-area {
    padding: 14px 22px; border-top: 1px solid #f1f5f9;
    display: flex; gap: 10px; align-items: flex-end;
}
.mp-input-area textarea {
    flex: 1; border: 1.5px solid #e2e8f0; border-radius: 14px;
    padding: 11px 15px; font-size: .875rem; resize: none;
    font-family: inherit; max-height: 100px; transition: border-color .2s;
}
.mp-input-area textarea:focus {
    outline: none; border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.1);
}
.mp-send-btn {
    width: 44px; height: 44px; border-radius: 50%;
    background: #0d6efd; border: none; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0; cursor: pointer;
    transition: background .2s, transform .2s;
}
.mp-send-btn:hover { background: #0a4ab2; transform: scale(1.06); }

@media (max-width: 600px) {
    .mp-sidebar { width: 100%; border-right: none; }
    .mp-inbox-card { flex-direction: column; }
    .mp-chat-panel { display: <?= $mp_activeConv ? 'flex' : 'none' ?>; }
}
</style>

<div class="mp-wrap">
    <!-- Header row -->
    <div class="d-flex align-items-center gap-3 mb-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-chat-dots-fill me-2 text-primary"></i><?= htmlspecialchars($panelTitle) ?>
        </h5>
        <?php if ($mp_totalUnread > 0): ?>
        <span class="badge bg-primary rounded-pill"><?= $mp_totalUnread ?> unread</span>
        <?php endif; ?>
    </div>

    <div class="mp-inbox-card">

        <!-- ── Sidebar ────────────────────────────────────────────────────── -->
        <div class="mp-sidebar">
            <div class="mp-sidebar-header">
                <i class="bi bi-chat-dots text-primary"></i>Conversations
            </div>

            <?php if (empty($mp_conversations)): ?>
            <div class="text-center text-muted py-5 px-3" style="font-size:.84rem">
                <i class="bi bi-chat-dots d-block mb-2" style="font-size:2rem;opacity:.25"></i>
                No conversations yet.
            </div>
            <?php else: ?>
                <?php foreach ($mp_conversations as $mp_conv): ?>
                <?php
                    // Build URL preserving current page params but setting msg_with
                    $mp_currentUrl  = strtok($_SERVER['REQUEST_URI'], '?');
                    $mp_currentQs   = $_GET;
                    $mp_currentQs[$queryParam] = $mp_conv['other_user_id'];
                    $mp_convHref    = $mp_currentUrl . '?' . http_build_query($mp_currentQs);
                ?>
                <a href="<?= $mp_convHref ?>"
                   class="mp-conv-item <?= $mp_activeConv == $mp_conv['other_user_id'] ? 'mp-active' : '' ?>">
                    <div class="d-flex align-items-center gap-3">
                        <div class="mp-avatar">
                            <?= strtoupper(substr($mp_conv['fname'], 0, 1)) ?>
                        </div>
                        <div style="min-width:0;flex:1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="mp-conv-name">
                                    <?= htmlspecialchars($mp_conv['fname'] . ' ' . $mp_conv['lname']) ?>
                                </div>
                                <?php if ($mp_conv['unread_count'] > 0): ?>
                                <span class="badge bg-primary rounded-pill" style="font-size:.62rem">
                                    <?= $mp_conv['unread_count'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="mp-conv-subject"><?= htmlspecialchars($mp_conv['subject'] ?? '') ?></div>
                            <div class="mp-conv-time">
                                <?= date('M d, h:i A', strtotime($mp_conv['last_message_time'])) ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ── Chat Panel ─────────────────────────────────────────────────── -->
        <div class="mp-chat-panel">

            <?php if (!$mp_activeConv || !$mp_otherUser): ?>
            <!-- Empty state -->
            <div class="mp-chat-empty">
                <i class="bi bi-chat-square-dots" style="font-size:2.8rem;opacity:.25"></i>
                <div style="font-size:.88rem">Select a conversation to start chatting</div>
            </div>

            <?php else: ?>
            <!-- Chat header -->
            <div class="mp-chat-header">
                <div class="mp-avatar">
                    <?= strtoupper(substr($mp_otherUser['fname'], 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:.93rem">
                        <?= htmlspecialchars($mp_otherUser['fname'] . ' ' . $mp_otherUser['lname']) ?>
                    </div>
                    <div class="text-muted" style="font-size:.76rem">
                        <?= htmlspecialchars($mp_convSubject) ?>
                    </div>
                </div>
            </div>

            <!-- Bubbles -->
            <div class="mp-messages" id="<?= $mp_uid ?>_messages">
                <?php if (empty($mp_convMessages)): ?>
                <div class="text-center text-muted py-5" style="font-size:.84rem;margin:auto">
                    <i class="bi bi-chat-dots d-block mb-2" style="font-size:2rem;opacity:.35"></i>
                    No messages yet.
                </div>
                <?php else: ?>
                    <?php foreach ($mp_convMessages as $mp_msg):
                        $mp_isSent = ((int)$mp_msg['sender_id'] === $user_id);
                    ?>
                    <div class="mp-bubble <?= $mp_isSent ? 'mp-sent' : 'mp-received' ?>">
                        <?php if (!$mp_isSent): ?>
                        <div class="mp-bubble-name">
                            <?= htmlspecialchars($mp_msg['fname'] . ' ' . $mp_msg['lname']) ?>
                        </div>
                        <?php endif; ?>
                        <?= nl2br(htmlspecialchars($mp_msg['body'])) ?>
                        <div class="mp-bubble-time">
                            <?= date('M d, h:i A', strtotime($mp_msg['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php if ($mp_replySuccess): ?>
            <div class="px-4 pt-2">
                <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-0" style="font-size:.82rem">
                    <i class="bi bi-check-circle-fill me-1"></i>Sent!
                </div>
            </div>
            <?php elseif ($mp_replyError): ?>
            <div class="px-4 pt-2">
                <div class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-0" style="font-size:.82rem">
                    <i class="bi bi-exclamation-circle-fill me-1"></i><?= htmlspecialchars($mp_replyError) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reply form -->
            <form method="POST">
                <input type="hidden" name="mp_receiver_id" value="<?= $mp_activeConv ?>">
                <input type="hidden" name="mp_subject"     value="<?= htmlspecialchars($mp_convSubject) ?>">
                <div class="mp-input-area">
                    <textarea name="mp_reply_text" rows="2"
                        id="<?= $mp_uid ?>_textarea"
                        placeholder="Type a reply… (Enter to send, Shift+Enter for new line)"
                        required></textarea>
                    <button type="submit" name="mp_send_reply" class="mp-send-btn" title="Send">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div><!-- /mp-chat-panel -->

    </div><!-- /mp-inbox-card -->
</div><!-- /mp-wrap -->

<script>
(function () {
    // Auto-scroll to bottom
    const chat = document.getElementById('<?= $mp_uid ?>_messages');
    if (chat) chat.scrollTop = chat.scrollHeight;

    // Auto-grow textarea + Enter-to-send
    const ta = document.getElementById('<?= $mp_uid ?>_textarea');
    if (ta) {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
        ta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.closest('form').submit();
            }
        });
    }
})();
</script>