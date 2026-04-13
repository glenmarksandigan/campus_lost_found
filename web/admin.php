<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 4) {
    header("Location: auth.php"); exit;
}
include 'db.php';

// ── Handle reply from inbox modal ───────────────────────────────────────────
$modalReplySuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modal_send_reply'])) {
    $replyText   = trim($_POST['modal_reply_text'] ?? '');
    $receiver_id = (int)($_POST['modal_receiver_id'] ?? 0);
    $subject     = trim($_POST['modal_subject'] ?? 'Re: Lost & Found');
    if (!empty($replyText) && $receiver_id) {
        try {
            $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ")->execute([$_SESSION['user_id'], $receiver_id, $subject, $replyText]);
            $modalReplySuccess = true;
        } catch (PDOException $e) { /* silently fail */ }
    }
}

// ── Inbox: load conversations ───────────────────────────────────────────────
$adminId = (int)$_SESSION['user_id'];

// Get all unique conversations grouped by the other user
$convStmt = $pdo->prepare("
    SELECT
        u.id AS other_user_id,
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
$convStmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId, $adminId]);
$adminConversations = $convStmt->fetchAll();

// Total unread
$totalUnread = 0;
foreach ($adminConversations as $c) $totalUnread += (int)$c['unread_count'];

// Active conversation from POST (after reply) or GET param
$activeWith = null;
$convMessages = [];
$otherUser = null;
$convSubject = '';

$openWith = isset($_POST['modal_receiver_id']) ? (int)$_POST['modal_receiver_id']
          : (isset($_GET['msg_with'])          ? (int)$_GET['msg_with'] : null);

if ($openWith) {
    $activeWith = $openWith;

    // Mark as read
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")
        ->execute([$openWith, $adminId]);

    // Other user info
    $ouStmt = $pdo->prepare("SELECT id, fname, lname, email FROM users WHERE id = ?");
    $ouStmt->execute([$openWith]);
    $otherUser = $ouStmt->fetch();

    // Subject
    $subStmt = $pdo->prepare("
        SELECT subject FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC LIMIT 1
    ");
    $subStmt->execute([$adminId, $openWith, $openWith, $adminId]);
    $subRow = $subStmt->fetch();
    $convSubject = $subRow['subject'] ?? 'Re: Lost & Found';

    // All messages in conversation
    $msgStmt = $pdo->prepare("
        SELECT m.*, u.fname, u.lname
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $msgStmt->execute([$adminId, $openWith, $openWith, $adminId]);
    $convMessages = $msgStmt->fetchAll();

    // Recount after mark-read
    $totalUnread = 0;
    foreach ($adminConversations as &$c) {
        if ((int)$c['other_user_id'] === $openWith) $c['unread_count'] = 0;
        $totalUnread += (int)$c['unread_count'];
    }
    unset($c);
}

// ── Found Items Stats ───────────────────────────────────────────────────────
$statsQuery     = $pdo->query("SELECT status, COUNT(*) as count FROM items GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$pendingCount   = $statsQuery['Pending']   ?? 0;
$publishedCount = $statsQuery['Published'] ?? 0;
$claimingCount  = $statsQuery['Claiming']  ?? 0;
$returnedCount  = $statsQuery['Returned']  ?? 0;
$totalItems     = array_sum($statsQuery);

// ── Lost Items Stats ────────────────────────────────────────────────────────
$lostStats      = $pdo->query("SELECT status, COUNT(*) as count FROM lost_reports GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$unresolvedLost = $lostStats['Lost']     ?? 0;
$matchingLost   = $lostStats['Matching'] ?? 0;
$resolvedLost   = $lostStats['Resolved'] ?? 0;
$totalLost      = array_sum($lostStats);

// ── User Stats ──────────────────────────────────────────────────────────────
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM users WHERE type_id = 1")->fetchColumn();
$pendingUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE type_id = 1 AND status = 'pending'")->fetchColumn();
$approvedUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE type_id = 1 AND status = 'approved'")->fetchColumn();
$newUsersMonth  = $pdo->query("SELECT COUNT(*) FROM users WHERE type_id = 1 AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();

// ── This Month ──────────────────────────────────────────────────────────────
$thisMonth          = $pdo->query("SELECT COUNT(*) FROM items WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$thisMonthReturned  = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Returned' AND MONTH(updated_at) = MONTH(CURDATE()) AND YEAR(updated_at) = YEAR(CURDATE())")->fetchColumn();
$successRate        = $totalItems > 0 ? round(($returnedCount / $totalItems) * 100) : 0;

// ── Chart Data: Monthly (current year) ─────────────────────────────────────
$monthlyFound = $pdo->query("SELECT MONTH(created_at) as m, COUNT(*) as c FROM items WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyLost  = $pdo->query("SELECT MONTH(created_at) as m, COUNT(*) as c FROM lost_reports WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlyFoundData = $monthlyLostData = [];
for ($i = 1; $i <= 12; $i++) {
    $monthlyFoundData[] = $monthlyFound[$i] ?? 0;
    $monthlyLostData[]  = $monthlyLost[$i]  ?? 0;
}

// ── Chart Data: Weekly (last 8 weeks) ──────────────────────────────────────
$weeklyFound = $pdo->query("SELECT WEEK(created_at, 1) as w, COUNT(*) as c FROM items WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) GROUP BY w ORDER BY w")->fetchAll();
$weeklyLost  = $pdo->query("SELECT WEEK(created_at, 1) as w, COUNT(*) as c FROM lost_reports WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) GROUP BY w ORDER BY w")->fetchAll();
$weekLabels = [];
$weekMap    = [];
for ($i = 7; $i >= 0; $i--) {
    $weekStart = date('M d', strtotime("-$i week", strtotime('monday this week')));
    $weekLabels[] = "Wk of $weekStart";
    $weekNum = date('W', strtotime("-$i week"));
    $weekMap[$weekNum] = count($weekLabels) - 1;
}
$wFoundData = array_fill(0, 8, 0);
$wLostData  = array_fill(0, 8, 0);
foreach ($weeklyFound as $r) { if (isset($weekMap[$r['w']])) $wFoundData[$weekMap[$r['w']]] = (int)$r['c']; }
foreach ($weeklyLost  as $r) { if (isset($weekMap[$r['w']])) $wLostData[$weekMap[$r['w']]]  = (int)$r['c']; }

// ── Chart Data: Yearly (last 5 years) ──────────────────────────────────────
$yearlyFound = $pdo->query("SELECT YEAR(created_at) as y, COUNT(*) as c FROM items WHERE YEAR(created_at) >= YEAR(CURDATE())-4 GROUP BY y ORDER BY y")->fetchAll(PDO::FETCH_KEY_PAIR);
$yearlyLost  = $pdo->query("SELECT YEAR(created_at) as y, COUNT(*) as c FROM lost_reports WHERE YEAR(created_at) >= YEAR(CURDATE())-4 GROUP BY y ORDER BY y")->fetchAll(PDO::FETCH_KEY_PAIR);
$yearLabels  = $yearFoundData = $yearLostData = [];
for ($y = date('Y')-4; $y <= date('Y'); $y++) {
    $yearLabels[]    = $y;
    $yearFoundData[] = $yearlyFound[$y] ?? 0;
    $yearLostData[]  = $yearlyLost[$y]  ?? 0;
}

// ── Activity Log (last 50) ───────────────────────────────────────────────────
$recentActivity = $pdo->query("
    SELECT a.*, u.fname, u.lname, u.type_id
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC LIMIT 50
")->fetchAll();

// ── Report Filters (Replicated from SuperAdmin) ─────────────────────────────
$timeFilter     = $_GET['time'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter   = $_GET['status'] ?? '';
$reportType     = $_GET['report_type'] ?? 'lost';
$startDate      = $_GET['start_date'] ?? '';
$endDate        = $_GET['end_date'] ?? '';

$dateCondition = '';
if ($timeFilter === 'month') {
    $dateCondition = "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
} elseif ($timeFilter === 'semester') {
    $month = (int)date('m'); $year = date('Y');
    $semesterStart = ($month <= 6) ? "$year-01-01" : "$year-07-01";
    $semesterEnd = ($month <= 6) ? "$year-06-30" : "$year-12-31";
    $dateCondition = "created_at BETWEEN '$semesterStart' AND '$semesterEnd'";
} elseif ($timeFilter === 'year') {
    $dateCondition = "YEAR(created_at) = YEAR(NOW())";
} elseif ($timeFilter === 'custom' && $startDate && $endDate) {
    $dateCondition = "created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
}

if ($reportType === 'lost') {
    $q = "SELECT * FROM lost_reports WHERE 1";
    if ($dateCondition) $q .= " AND $dateCondition";
    if ($categoryFilter) $q .= " AND category = " . $pdo->quote($categoryFilter);
    if ($statusFilter) $q .= " AND status = " . $pdo->quote($statusFilter);
    $q .= " ORDER BY created_at DESC";
    $reportData = $pdo->query($q)->fetchAll();
} else {
    $q = "SELECT * FROM items WHERE 1";
    if ($dateCondition) $q .= " AND $dateCondition";
    if ($categoryFilter) $q .= " AND category = " . $pdo->quote($categoryFilter);
    if ($statusFilter) $q .= " AND status = " . $pdo->quote($statusFilter);
    $q .= " ORDER BY created_at DESC";
    $reportData = $pdo->query($q)->fetchAll();
}

$categoriesList = $pdo->query("SELECT DISTINCT category FROM lost_reports WHERE category IS NOT NULL AND category != '' UNION SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --blue: #0d6efd; --navy: #0f172a; }
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; margin: 0; }

        /* ── Entrance animations ── */
        @keyframes fadeInUp { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
        .fade-in-up { animation: fadeInUp 0.5s ease both; }
        .fade-in-up:nth-child(1) { animation-delay: 0s; }
        .fade-in-up:nth-child(2) { animation-delay: 0.07s; }
        .fade-in-up:nth-child(3) { animation-delay: 0.14s; }
        .fade-in-up:nth-child(4) { animation-delay: 0.21s; }

        .greeting-text { font-size: 1.35rem; font-weight: 800; margin-bottom: 2px; }
        .greeting-sub { font-size: 0.88rem; opacity: 0.8; }

        .page-header {
            background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%);
            border-radius: 20px; padding: 1.75rem 2rem;
            color: white; margin-bottom: 1.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stat-card {
            border: none; border-radius: 16px;
            transition: transform 0.2s, box-shadow 0.2s; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.1) !important; }

        .stat-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
        }

        .chart-wrapper  { position: relative; height: 300px; width: 100%; }
        .donut-wrapper  { position: relative; height: 220px; width: 100%; }

        .section-label {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #94a3b8; margin-bottom: 12px;
        }

        .period-btn { font-size: 0.8rem; font-weight: 600; border-radius: 8px; padding: 5px 14px; }
        .period-btn.active { background: var(--blue); color: white; border-color: var(--blue); }

        .dist-bar-wrap { margin-bottom: 12px; }
        .dist-bar-label { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px; }
        .dist-bar { height: 8px; border-radius: 100px; background: #e2e8f0; overflow: hidden; }
        .dist-bar-fill { height: 100%; border-radius: 100px; transition: width 0.8s ease; }

        .quick-action-btn {
            border-radius: 14px; padding: 18px; text-align: center;
            transition: all 0.3s; border: 2px solid #e2e8f0; background: white;
        }
        .quick-action-btn:hover { border-color: var(--blue); background: #eff6ff; transform: translateY(-3px); }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
        .pulse { animation: pulse 2s infinite; }

        .btn-messages {
            background: #ffffff; border: 1.5px solid #ffffff;
            color: #3f5486 !important; border-radius: 10px;
            padding: 7px 16px; font-size: .875rem; font-weight: 600; transition: background .2s;
        }
        .btn-messages:hover { background: #e2e8f0; color: #ffffff !important; }
        .btn-messages .badge { font-size: .7rem; }

        /* ── Inbox Modal Chat Styles (mirrors inbox.php) ── */
        .inbox-modal-wrap {
            display: flex; height: 560px; overflow: hidden;
        }

        /* Sidebar */
        .inbox-modal-sidebar {
            width: 260px; flex-shrink: 0;
            border-right: 1px solid #f1f5f9;
            overflow-y: auto; background: #fff;
        }
        .inbox-modal-sidebar-header {
            padding: 14px 18px; border-bottom: 1px solid #f1f5f9;
            font-weight: 800; font-size: .95rem;
            display: flex; align-items: center; gap: 8px;
            position: sticky; top: 0; background: #fff; z-index: 1;
        }
        .inbox-conv-item {
            padding: 13px 18px; border-bottom: 1px solid #f8fafc;
            cursor: pointer; text-decoration: none; display: block; color: inherit;
            transition: background 0.15s;
        }
        .inbox-conv-item:hover { background: #f8fafc; }
        .inbox-conv-item.active { background: #eff6ff; border-left: 3px solid #0d6efd; }
        .inbox-conv-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #0a4ab2);
            color: white; font-weight: 700; font-size: .9rem;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .inbox-conv-name { font-weight: 700; font-size: .85rem; }
        .inbox-conv-subject { font-size: .73rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .inbox-conv-time { font-size: .68rem; color: #94a3b8; }

        /* Chat panel */
        .inbox-modal-chat {
            flex: 1; display: flex; flex-direction: column; background: #fff; overflow: hidden;
        }
        .inbox-modal-chat-header {
            padding: 14px 20px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }
        .inbox-modal-messages {
            flex: 1; overflow-y: auto; padding: 18px 20px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .inbox-modal-empty {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; color: #94a3b8; gap: 10px;
        }
        .chat-bubble {
            max-width: 72%; padding: 10px 14px; border-radius: 18px;
            font-size: .86rem; line-height: 1.5;
        }
        /* ── Section Navigation ─────────────────────────────────── */
        .section { display: none; animation: fadeUp .3s ease; }
        .section.active { display: block; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        .activity-item { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; transition: background .15s; }
        .activity-item:hover { background: #f8fafc; }
        .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
        .activity-text { flex: 1; min-width: 0; }
        .activity-title { font-weight: 600; font-size: .88rem; margin-bottom: 2px; }
        .activity-meta { font-size: .75rem; color: #94a3b8; }
        .activity-card { border: none; border-radius: 16px; overflow: hidden; }

        .filter-card { background: white; border-radius: 16px; border: none; box-shadow: var(--admin-card-shadow); }
        .report-table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
        .report-table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }

        .chat-bubble.sent {
            background: #0d6efd; color: white;
            align-self: flex-end; border-bottom-right-radius: 4px;
        }
        .chat-bubble.received {
            background: #f1f5f9; color: #1e293b;
            align-self: flex-start; border-bottom-left-radius: 4px;
        }
        .bubble-name { font-size: .68rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
        .bubble-time { font-size: .65rem; opacity: .6; margin-top: 4px; text-align: right; }
        .chat-bubble.sent .bubble-time { color: rgba(255,255,255,0.7); }

        .inbox-modal-input {
            padding: 14px 20px; border-top: 1px solid #f1f5f9;
            display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0;
        }
        .inbox-modal-input textarea {
            flex: 1; border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 10px 14px; font-size: .88rem; resize: none;
            font-family: inherit; max-height: 90px; transition: border-color 0.2s;
            outline: none;
        }
        .inbox-modal-input textarea:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.08); }
        .inbox-send-btn {
            width: 42px; height: 42px; border-radius: 50%;
            background: #0d6efd; border: none; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; flex-shrink: 0; cursor: pointer; transition: background 0.2s, transform 0.2s;
        }
        .inbox-send-btn:hover { background: #0a4ab2; transform: scale(1.06); }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="container-fluid px-4 mt-4">

    <!-- Header -->
    <div class="page-header fade-in-up mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <?php
                $hour = (int)date('G');
                if ($hour < 12) $greet = 'Good morning';
                elseif ($hour < 18) $greet = 'Good afternoon';
                else $greet = 'Good evening';
                $adminGreetName = $_SESSION['fname'] ?? 'Admin';
                ?>
                <div class="greeting-text"><?= $greet ?>, <?= htmlspecialchars($adminGreetName) ?> 👋</div>
                <div id="pageSubTitle" class="greeting-sub mb-0">Here's what's happening with Lost & Found today.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-messages position-relative"
                        data-bs-toggle="modal" data-bs-target="#inboxModal">
                    <i class="bi bi-envelope-fill me-1"></i> Messages
                    <?php if ($totalUnread > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $totalUnread ?>
                        </span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- DASHBOARD VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="dashboard-view" class="section active">

    <!-- ── ROW 1: Main Stats ─────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Total Found Items</p>
                            <h2 class="fw-bold mb-0 counter-value" data-target="<?= $totalItems ?>"><?= $totalItems ?></h2>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> <?= $thisMonth ?> this month</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Returned</p>
                            <h2 class="fw-bold mb-0 text-success counter-value" data-target="<?= $returnedCount ?>"><?= $returnedCount ?></h2>
                            <small class="text-success"><i class="bi bi-check-circle"></i> <?= $thisMonthReturned ?> this month</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Lost Reports</p>
                            <h2 class="fw-bold mb-0 text-warning counter-value" data-target="<?= $totalLost ?>"><?= $totalLost ?></h2>
                            <small class="text-danger"><i class="bi bi-exclamation-circle"></i> <?= $unresolvedLost ?> unresolved</small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-search"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Success Rate</p>
                            <h2 class="fw-bold mb-0 text-info counter-value" data-target="<?= $successRate ?>"><?= $successRate ?>%</h2>
                            <small class="text-muted"><i class="bi bi-trophy"></i> Items returned</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ROW 2: User + Lost Stats ─────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Student Accounts</h6>
                        <?php if ($pendingUsers > 0): ?>
                        <a href="manage_users.php" class="badge bg-warning text-dark text-decoration-none pulse">
                            <?= $pendingUsers ?> Pending
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-primary"><?= $totalStudents ?></div>
                            <div style="font-size:.72rem;color:#94a3b8">Total</div>
                        </div>
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-success"><?= $approvedUsers ?></div>
                            <div style="font-size:.72rem;color:#94a3b8">Approved</div>
                        </div>
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-warning"><?= $pendingUsers ?></div>
                            <div style="font-size:.72rem;color:#94a3b8">Pending</div>
                        </div>
                    </div>
                    <div class="dist-bar-wrap">
                        <div class="dist-bar-label">
                            <span>Approval Rate</span>
                            <span class="fw-bold"><?= $totalStudents > 0 ? round($approvedUsers/$totalStudents*100) : 0 ?>%</span>
                        </div>
                        <div class="dist-bar">
                            <div class="dist-bar-fill bg-success" style="width:<?= $totalStudents > 0 ? round($approvedUsers/$totalStudents*100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <small class="text-muted"><i class="bi bi-person-plus me-1"></i><?= $newUsersMonth ?> new students this month</small>
                    <div class="mt-3">
                        <a href="manage_users.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-people me-1"></i>Manage Accounts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-search me-2 text-warning"></i>Lost Reports Distribution</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="donut-wrapper mb-3"><canvas id="lostChart"></canvas></div>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:#fef3c7">
                                <div class="fw-bold text-warning"><?= $unresolvedLost ?></div>
                                <div style="font-size:.7rem;color:#92400e">Lost</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:#eff6ff">
                                <div class="fw-bold text-primary"><?= $matchingLost ?></div>
                                <div style="font-size:.7rem;color:#1e40af">Matching</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded" style="background:#f0fdf4">
                                <div class="fw-bold text-success"><?= $resolvedLost ?></div>
                                <div style="font-size:.7rem;color:#166534">Resolved</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Found Items Distribution</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="donut-wrapper mb-3"><canvas id="foundChart"></canvas></div>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light">
                                <div class="fw-bold text-warning"><?= $pendingCount ?></div>
                                <div style="font-size:.7rem;color:#94a3b8">Pending</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light">
                                <div class="fw-bold text-info"><?= $publishedCount ?></div>
                                <div style="font-size:.7rem;color:#94a3b8">Published</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light">
                                <div class="fw-bold text-primary"><?= $claimingCount ?></div>
                                <div style="font-size:.7rem;color:#94a3b8">Claiming</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light">
                                <div class="fw-bold text-success"><?= $returnedCount ?></div>
                                <div style="font-size:.7rem;color:#94a3b8">Returned</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ROW 3: Main Chart ─────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0">Reporting Trends</h6>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary period-btn active" onclick="setPeriod('weekly',this)">Weekly</button>
                            <button class="btn btn-outline-secondary period-btn" onclick="setPeriod('monthly',this)">Monthly</button>
                            <button class="btn btn-outline-secondary period-btn" onclick="setPeriod('yearly',this)">Yearly</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="manage_found.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-box-seam text-primary fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Found Items</div>
                            <small class="text-muted"><?= $totalItems - $returnedCount ?> active</small>
                        </div>
                    </a>
                    <a href="manage_lost.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-search text-warning fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Lost Reports</div>
                            <small class="text-muted"><?= $unresolvedLost ?> need attention</small>
                        </div>
                    </a>
                    <a href="manage_users.php" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-people text-success fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Manage Users</div>
                            <small class="text-muted"><?= $pendingUsers ?> pending approval</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ROW 4: Automatic Activity Log ── -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card activity-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Recent System Activity</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold" onclick="showTab('activity-log')">View All</button>
                </div>
                <div class="card-body p-0" style="max-height:480px; overflow-y:auto">
                    <?php if(empty($recentActivity)): ?>
                    <div class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 opacity-25 mb-2"></i>No activity logged yet</div>
                    <?php else: ?>
                        <?php foreach(array_slice($recentActivity, 0, 10) as $act):
                            $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'SuperAdmin', 6=>'Organizer'];
                            $role = $roleMap[$act['type_id']] ?? 'User';
                            $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                            $bgColor = $actionColors[$act['action_type']] ?? '#64748b';
                        ?>
                        <div class="activity-item">
                          <div class="activity-icon" style="background:<?= $bgColor ?>20;color:<?= $bgColor ?>">
                            <i class="bi bi-<?= match($act['action_type']){
                              'create'=>'plus-circle','update'=>'pencil','delete'=>'trash',
                              'approve'=>'check-circle','reject'=>'x-circle','publish'=>'send',
                              default=>'dot'
                            } ?>"></i>
                          </div>
                          <div class="activity-text">
                            <div class="activity-title">
                                <strong><?= htmlspecialchars($act['fname'].' '.$act['lname']) ?></strong> 
                                <span class="badge border bg-light text-dark fw-normal ms-1" style="font-size:.65rem"><?= $role ?></span>
                            </div>
                            <div class="activity-meta">
                                <?= ucfirst($act['action_type']) ?> · <?= date('M d, g:i A', strtotime($act['created_at'])) ?>
                                <?php if($act['details']): ?>
                                · <span class="text-dark opacity-75"><?= htmlspecialchars($act['details']) ?></span>
                                <?php endif; ?>
                            </div>
                          </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-info"></i>System Notice</h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="bi bi-robot display-4 text-primary opacity-25 mb-3 d-block"></i>
                        <h5>Automated Tracking</h5>
                        <p class="text-muted small">Manual tasks have been replaced with the Automatic Activity Log. Every status change, approval, and system update is now recorded automatically with full actor details.</p>
                        <hr class="opacity-10">
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm" onclick="showTab('activity-log')">View Detailed Logs</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- End Dashboard View -->

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ACTIVITY LOG VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="activity-log-view" class="section">
        <div class="card activity-card shadow-sm border-0 mb-5">
            <div class="card-header bg-white border-0 py-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Complete System Logs</h5>
                <div class="input-group input-group-sm w-auto">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" id="activitySearch" placeholder="Filter logs..." onkeyup="searchLogs()">
                </div>
            </div>
            <div class="card-body p-0" style="max-height:700px; overflow-y:auto;">
                <?php foreach($recentActivity as $act):
                    $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'SuperAdmin', 6=>'Organizer'];
                    $role = $roleMap[$act['type_id']] ?? 'User';
                    $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                    $bgColor = $actionColors[$act['action_type']] ?? '#64748b';
                ?>
                <div class="activity-item log-row">
                    <div class="activity-icon" style="background:<?= $bgColor ?>20;color:<?= $bgColor ?>">
                        <i class="bi bi-<?= match($act['action_type']){
                          'create'=>'plus-circle','update'=>'pencil','delete'=>'trash',
                          'approve'=>'check-circle','reject'=>'x-circle','publish'=>'send',
                          default=>'dot'
                        } ?>"></i>
                    </div>
                    <div class="activity-text">
                        <div class="activity-title">
                            <strong><?= htmlspecialchars($act['fname'].' '.$act['lname']) ?></strong>
                            <span class="badge border bg-light text-dark fw-normal ms-1" style="font-size:.65rem"><?= $role ?></span>
                            <span class="ms-1 text-muted fw-normal">performed</span> <strong><?= $act['action_type'] ?></strong>
                        </div>
                        <div class="activity-meta">
                            <i class="bi bi-clock me-1"></i><?= date('M d, Y - g:i A', strtotime($act['created_at'])) ?>
                            <span class="mx-2 text-muted">|</span>
                            <span>Target: <span class="text-dark fw-bold"><?= ucfirst($act['target_type']) ?> #<?= $act['target_id'] ?></span></span>
                            <?php if($act['details']): ?>
                            <br><span class="text-muted mt-1 d-inline-block">Details: <span class="opacity-75"><?= htmlspecialchars($act['details']) ?></span></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- REPORTS VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="reports-view" class="section">
        <div class="filter-card shadow-sm p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-filter me-2"></i>Filter Reports</h6>
            <form action="admin.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="section" value="reports">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">REPORT TYPE</label>
                    <select name="report_type" class="form-select border-0 bg-light rounded-3">
                        <option value="lost" <?= $reportType=='lost'?'selected':'' ?>>Lost Item Reports</option>
                        <option value="found" <?= $reportType=='found'?'selected':'' ?>>Found Item Database</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">TIME PERIOD</label>
                    <select name="time" class="form-select border-0 bg-light rounded-3">
                        <option value="">All Time</option>
                        <option value="month" <?= $timeFilter=='month'?'selected':'' ?>>This Month</option>
                        <option value="semester" <?= $timeFilter=='semester'?'selected':'' ?>>This Semester</option>
                        <option value="year" <?= $timeFilter=='year'?'selected':'' ?>>This Year</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">CATEGORY</label>
                    <select name="category" class="form-select border-0 bg-light rounded-3">
                        <option value="">All Categories</option>
                        <?php foreach($categoriesList as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter==$cat?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="bg-light p-2 rounded-3">
                        <label class="form-label small fw-bold text-muted ms-2 mb-1">CUSTOM RANGE</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-transparent" value="<?= $startDate ?>">
                            <span class="text-muted">–</span>
                            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-transparent" value="<?= $endDate ?>">
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-3"><i class="bi bi-search me-2"></i>Apply</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Filtered Report Result</h6>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-2"></i>Print Report</button>
            </div>
            <div class="table-responsive">
                <table class="table report-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Posted Date</th>
                            <?php if($reportType == 'found'): ?>
                                <th>Storage</th>
                            <?php else: ?>
                                <th>Location</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($reportData)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No data found for the selected filters.</td></tr>
                        <?php else: ?>
                            <?php foreach($reportData as $row): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['item_name']) ?></td>
                                <td><span class="badge border bg-light text-dark fw-normal"><?= htmlspecialchars($row['category'] ?: 'Uncategorized') ?></span></td>
                                <td>
                                    <?php
                                    $s = $row['status'];
                                    $badge = match($s){
                                        'Returned','Resolved' => 'bg-success',
                                        'Pending','Lost' => 'bg-warning text-dark',
                                        'Matching','Published' => 'bg-info text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill" style="font-size:.65rem"><?= $s ?></span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <?php if($reportType == 'found'): ?>
                                    <td><?= htmlspecialchars($row['storage_location']) ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($row['location'] ?? $row['found_location'] ?? 'N/A') ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = {
    weekly:  { labels: <?= json_encode(array_values($weekLabels)) ?>, found: <?= json_encode(array_values($wFoundData)) ?>, lost: <?= json_encode(array_values($wLostData)) ?> },
    monthly: { labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], found: <?= json_encode($monthlyFoundData) ?>, lost: <?= json_encode($monthlyLostData) ?> },
    yearly:  { labels: <?= json_encode($yearLabels) ?>, found: <?= json_encode($yearFoundData) ?>, lost: <?= json_encode($yearLostData) ?> }
};

const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: chartData.weekly.labels,
        datasets: [
            { label: 'Found Items', data: chartData.weekly.found, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.08)', tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#0d6efd', pointBorderColor: '#fff', pointBorderWidth: 2 },
            { label: 'Lost Reports', data: chartData.weekly.lost, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#f59e0b', pointBorderColor: '#fff', pointBorderWidth: 2 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { font: { size: 12, weight: '600' }, padding: 16 } }, tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 10 } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { precision: 0 } }, x: { grid: { display: false } } }
    }
});

    // Charts
    function createTrendChart() {
        // chartData is defined at the top of the script block
        if (trendChart) trendChart.destroy();
        const ctx = document.getElementById('trendChart').getContext('2d');
        const period = document.querySelector('.period-btn.active').textContent.toLowerCase();
        const d = chartData[period];
        
        // Note: trendChart is already globally declared/initialized выше, 
        // but here we ensure the labels and data match the active button
        trendChart.data.labels = d.labels;
        trendChart.data.datasets[0].data = d.found;
        trendChart.data.datasets[1].data = d.lost;
        trendChart.update();
    }

    // Section Switching logic
    function showTab(sectionId) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(sectionId + '-view');
        if (target) {
            target.classList.add('active');
            window.scrollTo(0, 0);
            
            // Update Title
            const titles = {
                'dashboard': 'Dashboard Overview',
                'activity-log': 'System Activity Log',
                'reports': 'System Reports & Analytics'
            };
            const subTitle = document.getElementById('pageSubTitle');
            if (subTitle) subTitle.textContent = titles[sectionId] || 'Admin Dashboard';
        }
    }

    // Handle URL parameters for direct section access
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        if (section === 'reports') showTab('reports');
        else if (section === 'activity') showTab('activity-log');

        // Auto-open inbox modal when a conversation is selected (via ?msg_with=)
        const msgWith = urlParams.get('msg_with');
        if (msgWith) {
            const inboxEl = document.getElementById('inboxModal');
            if (inboxEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(inboxEl);
                modal.show();
            }
        }

        const msgs = document.getElementById('inboxModalMessages');
        if (msgs) msgs.scrollTop = msgs.scrollHeight;

        // Auto-grow textarea
        const ta = document.querySelector('.inbox-modal-input textarea');
        if (ta) {
            ta.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 90) + 'px';
            });
        }
    });

    // Quick search for logs
    window.searchLogs = function() {
        const q = document.getElementById('activitySearch').value.toLowerCase();
        document.querySelectorAll('.log-row').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(q) ? 'flex' : 'none';
        });
    };
</script>

<!-- ══════════════════════════════════════════════════════════
     INBOX MODAL — full inbox.php-style chat UI
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="inboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:860px;">
        <div class="modal-content border-0" style="border-radius:20px; overflow:hidden;">

            <!-- Modal header -->
            <div class="modal-header border-0 bg-primary text-white px-4 py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-inbox-fill me-2"></i>Messages
                    <?php if ($totalUnread > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:.7rem"><?= $totalUnread ?></span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body: sidebar + chat panel -->
            <div class="modal-body p-0">
                <div class="inbox-modal-wrap">

                    <!-- ── Conversation Sidebar ── -->
                    <div class="inbox-modal-sidebar">
                        <div class="inbox-modal-sidebar-header">
                            <i class="bi bi-chat-dots text-primary"></i> Conversations
                        </div>

                        <?php if (empty($adminConversations)): ?>
                        <div class="text-center text-muted py-5 px-3" style="font-size:.85rem">
                            <i class="bi bi-chat-dots display-5 d-block mb-2 opacity-25"></i>
                            No conversations yet.
                        </div>
                        <?php else: ?>
                            <?php foreach ($adminConversations as $conv): ?>
                            <a href="?msg_with=<?= $conv['other_user_id'] ?>#inboxModal"
                               class="inbox-conv-item <?= $activeWith == $conv['other_user_id'] ? 'active' : '' ?>"
                               onclick="document.getElementById('inboxModal').scrollTop=0">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="inbox-conv-avatar">
                                        <?= strtoupper(substr($conv['fname'], 0, 1)) ?>
                                    </div>
                                    <div style="min-width:0; flex:1;">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="inbox-conv-name">
                                                <?= htmlspecialchars($conv['fname'] . ' ' . $conv['lname']) ?>
                                            </div>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill" style="font-size:.62rem">
                                                <?= $conv['unread_count'] ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="inbox-conv-subject"><?= htmlspecialchars($conv['subject'] ?? '') ?></div>
                                        <div class="inbox-conv-time">
                                            <?= date('M d, h:i A', strtotime($conv['last_message_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ── Chat Panel ── -->
                    <div class="inbox-modal-chat">

                        <?php if (!$activeWith || !$otherUser): ?>
                        <!-- No conversation selected -->
                        <div class="inbox-modal-empty">
                            <i class="bi bi-chat-square-dots" style="font-size:2.5rem; opacity:.25"></i>
                            <div style="font-size:.88rem;">Select a conversation to view messages</div>
                        </div>

                        <?php else: ?>
                        <!-- Chat header -->
                        <div class="inbox-modal-chat-header">
                            <div class="inbox-conv-avatar">
                                <?= strtoupper(substr($otherUser['fname'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:.95rem">
                                    <?= htmlspecialchars($otherUser['fname'] . ' ' . $otherUser['lname']) ?>
                                </div>
                                <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($convSubject) ?></div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="inbox-modal-messages" id="inboxModalMessages">
                            <?php if (empty($convMessages)): ?>
                            <div class="text-center text-muted py-4" style="font-size:.85rem; margin:auto;">
                                <i class="bi bi-chat-dots display-6 d-block mb-2 opacity-30"></i>
                                No messages yet.
                            </div>
                            <?php else: ?>
                                <?php foreach ($convMessages as $msg):
                                    $isSent = ((int)$msg['sender_id'] === $adminId);
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

                        <!-- Reply success/error -->
                        <?php if ($modalReplySuccess): ?>
                        <div class="px-4 pt-2">
                            <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-0" style="font-size:.82rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Sent!
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Reply form -->
                        <form method="POST">
                            <input type="hidden" name="modal_receiver_id" value="<?= $activeWith ?>">
                            <input type="hidden" name="modal_subject" value="<?= htmlspecialchars($convSubject) ?>">
                            <div class="inbox-modal-input">
                                <textarea name="modal_reply_text" rows="2"
                                    placeholder="Type a reply... (Enter to send, Shift+Enter for new line)"
                                    required></textarea>
                                <button type="submit" name="modal_send_reply" class="inbox-send-btn">
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                    </div><!-- /chat panel -->
                </div><!-- /inbox-modal-wrap -->
            </div><!-- /modal-body -->

        </div>
    </div>
</div>

</div><!-- /admin-main-content -->

<script>
// ── Counter animation ──────────────────────────────────────────
document.querySelectorAll('.counter-value').forEach(el => {
    const target = parseInt(el.dataset.target) || 0;
    if (target === 0) return;
    let current = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        const suffix = el.textContent.includes('%') ? '%' : '';
        el.textContent = current + suffix;
        if (current >= target) clearInterval(timer);
    }, 25);
});
</script>

</body>
</html>