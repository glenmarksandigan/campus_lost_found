<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 5) {
    header("Location: auth.php"); exit;
}
include 'db.php';

$user_name = trim($_SESSION['user_name'] ?? 'Super Admin');

// ── System Stats ────────────────────────────────────────────────────────────
$totalItems     = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$totalLost      = $pdo->query("SELECT COUNT(*) FROM lost_reports")->fetchColumn();
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM users WHERE type_id IN (1,3)")->fetchColumn();
$pendingUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$returnedItems  = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Returned'")->fetchColumn();
$pendingItems   = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Pending'")->fetchColumn();
$activeTasks    = $pdo->query("SELECT COUNT(*) FROM admin_tasks WHERE status IN ('pending','in_progress')")->fetchColumn();

// ── Recent Activity (last 20) ───────────────────────────────────────────────
$recentActivity = $pdo->query("
    SELECT a.*, u.fname, u.lname, u.type_id
    FROM activity_log a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC LIMIT 20
")->fetchAll();

// ── Organizer Performance ───────────────────────────────────────────────────
$organizerStats = $pdo->query("
    SELECT u.id, u.fname, u.lname, u.organizer_role,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN t.status IN ('pending','in_progress') THEN 1 END) as active_tasks
    FROM users u
    LEFT JOIN admin_tasks t ON u.id = t.assigned_to
    WHERE u.type_id = 6
    GROUP BY u.id, u.fname, u.lname, u.organizer_role
    ORDER BY u.organizer_role DESC, u.fname
")->fetchAll();

// ── Monthly Trends ─────────────────────────────────────────────────────────
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $label = date('M Y', strtotime("-$i month"));
    $items = $pdo->query("SELECT COUNT(*) FROM items WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn();
    $lost  = $pdo->query("SELECT COUNT(*) FROM lost_reports WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetchColumn();
    $monthlyData[] = ['label' => $label, 'items' => $items, 'lost' => $lost];
}

// ── Report Filters ─────────────────────────────────────────────────────────
$timeFilter = $_GET['time'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$reportType = $_GET['report_type'] ?? 'lost';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

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
    $query = "SELECT *, (CASE WHEN status = 'Resolved' THEN updated_at ELSE NULL END) as date_resolved FROM lost_reports WHERE 1";
    if ($dateCondition) $query .= " AND $dateCondition";
    if ($categoryFilter) $query .= " AND category = " . $pdo->quote($categoryFilter);
    if ($statusFilter) $query .= " AND status = " . $pdo->quote($statusFilter);
    $query .= " ORDER BY created_at DESC";
    $reportData = $pdo->query($query)->fetchAll();
    $statsQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost, SUM(CASE WHEN status = 'Matching' THEN 1 ELSE 0 END) as matching, SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved FROM lost_reports WHERE 1";
    if ($dateCondition) $statsQuery .= " AND $dateCondition";
    if ($categoryFilter) $statsQuery .= " AND category = " . $pdo->quote($categoryFilter);
    $reportStats = $pdo->query($statsQuery)->fetch();
} else {
    $iDateCondition = str_replace('created_at', 'i.created_at', $dateCondition);
    $query = "SELECT i.*, c.returned_at as date_claimed, 
              TRIM(CONCAT(u.fname, ' ', u.lname)) as claimant_name,
              TRIM(CONCAT(f.fname, ' ', f.lname)) as finder_full_name
              FROM items i
              LEFT JOIN claims c ON i.id = c.item_id AND c.status = 'verified'
              LEFT JOIN users u ON c.user_id = u.id
              LEFT JOIN users f ON i.user_id = f.id
              WHERE 1";
    if ($iDateCondition) $query .= " AND $iDateCondition";
    if ($categoryFilter) $query .= " AND i.category = " . $pdo->quote($categoryFilter);
    if ($statusFilter) $query .= " AND i.status = " . $pdo->quote($statusFilter);
    $query .= " ORDER BY i.created_at DESC";
    $reportData = $pdo->query($query)->fetchAll();
    $statsQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) as published, SUM(CASE WHEN status = 'Claiming' THEN 1 ELSE 0 END) as claiming, SUM(CASE WHEN status = 'Returned' THEN 1 ELSE 0 END) as returned FROM items WHERE 1";
    if ($dateCondition) $statsQuery .= " AND $dateCondition";
    if ($categoryFilter) $statsQuery .= " AND category = " . $pdo->quote($categoryFilter);
    $reportStats = $pdo->query($statsQuery)->fetch();
}
$categoriesList = $pdo->query("SELECT DISTINCT category FROM lost_reports WHERE category IS NOT NULL AND category != '' UNION SELECT DISTINCT category FROM items WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// ── Conversations Logic (Ported from view_conversations.php) ──────────
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

$activeUser1 = isset($_GET['u1']) ? (int)$_GET['u1'] : 0;
$activeUser2 = isset($_GET['u2']) ? (int)$_GET['u2'] : 0;
$convMessages = [];
$user1Info = null; $user2Info = null;

if ($activeUser1 && $activeUser2) {
    $u1Stmt = $pdo->prepare("SELECT id, fname, lname, type_id FROM users WHERE id = ?");
    $u1Stmt->execute([$activeUser1]);
    $user1Info = $u1Stmt->fetch();

    $u2Stmt = $pdo->prepare("SELECT id, fname, lname, type_id FROM users WHERE id = ?");
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin Dashboard – FoundIt!</title>
  <style>
    /* ── Section Navigation ─────────────────────────────────── */
    .section { display: none; animation: fadeUp .3s ease; }
    .section.active { display: block; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    .stat-card { border: none; border-radius: 16px; transition: transform .2s, box-shadow .2s; background: white; box-shadow: var(--admin-card-shadow); }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
    .stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }

    .activity-card { border: none; border-radius: 16px; }
    .activity-item { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; transition: background .15s; }
    .activity-item:hover { background: #f8fafc; }
    .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
    .activity-text { flex: 1; min-width: 0; }
    .activity-title { font-weight: 600; font-size: .88rem; margin-bottom: 2px; }
    .activity-meta { font-size: .75rem; color: #94a3b8; }

    .organizer-card { padding: 16px; border: 2px solid #e2e8f0; border-radius: 14px; transition: all .2s; }
    .organizer-card:hover { border-color: #7c3aed; background: #faf5ff; }
    .organizer-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #5b21b6); color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .chart-container { position: relative; height: 280px; }

    .table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    /* ── Topbar for superadmin ──────────────────────────── */
    .sa-topbar {
      background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 28px; height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 200;
    }
    .topbar-title { font-weight: 700; font-size: 1.1rem; color: #0f172a; }
    .greeting-text { font-size: .82rem; color: #64748b; margin-top: 2px; }
    .page-body { padding: 24px 28px; }

    /* ── Conversation Section Styles ────────────────────────── */
    .conv-card {
        border: none; border-radius: 20px;
        box-shadow: var(--admin-card-shadow);
        overflow: hidden; min-height: 580px; display: flex;
        background: #fff;
    }
    .conv-sidebar { width: 320px; flex-shrink: 0; border-right: 1px solid #f1f5f9; background: #fff; display: flex; flex-direction: column; }
    .conv-sidebar-header { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 800; font-size: 1rem; display: flex; align-items: center; gap: 10px; }
    .conv-search { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    .conv-search input {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: 10px;
        padding: 8px 12px 8px 34px; font-size: .82rem; outline: none;
        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") 10px center no-repeat;
    }
    .conv-list { flex: 1; overflow-y: auto; }
    .conv-item { padding: 12px 18px; border-bottom: 1px solid #f8fafc; cursor: pointer; text-decoration: none; display: block; color: inherit; transition: all 0.2s; }
    .conv-item:hover { background: #f8fafc; }
    .conv-item.active { background: #eff6ff; border-left: 3px solid #7c3aed; }
    .conv-item .conv-preview { font-size: .75rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .conv-avatar-pair { display: flex; align-items: center; }
    .conv-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .7rem; color: #fff; border: 2px solid #fff; }
    .conv-avatar:nth-child(2) { margin-left: -10px; }
    
    .chat-panel { flex: 1; display: flex; flex-direction: column; background: #fff; }
    .chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; max-height: 440px; }
    .chat-bubble { max-width: 75%; padding: 10px 14px; border-radius: 16px; font-size: .84rem; line-height: 1.4; }
    .chat-bubble.left { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
    .chat-bubble.right { background: #7c3aed; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .bubble-meta { font-size: .65rem; opacity: .7; margin-top: 4px; display: flex; justify-content: space-between; gap: 10px; }
    .read-only-strip { padding: 8px; background: #fffbeb; color: #92400e; font-size: .75rem; text-align: center; border-top: 1px solid #fef3c7; font-weight: 600; }
  </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

  <header class="sa-topbar">
    <div>
      <div class="topbar-title" id="topbarTitle">Dashboard</div>
      <?php
        $hour = (int)date('G');
        if ($hour < 12) $greet = 'Good morning';
        elseif ($hour < 18) $greet = 'Good afternoon';
        else $greet = 'Good evening';
      ?>
      <div class="greeting-text"><?= $greet ?>, <?= htmlspecialchars($user_name) ?> 👋</div>
    </div>
    <span class="badge px-3 py-2 rounded-pill" style="background:#ede9fe;color:#7c3aed;font-size:.8rem">
      <i class="bi bi-eye me-1"></i>Observer Mode
    </span>
  </header>

  <div class="page-body">

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- DASHBOARD -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="dashboard" class="section active">
      <!-- Info Banner -->
      <div class="alert border-0 rounded-3 shadow-sm mb-4" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);color:#5b21b6">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Observer Mode:</strong> You have read-only access to monitor system activity. Contact the Admin for any changes.
      </div>

      <!-- Stats Row -->
      <div class="row g-3 mb-4">
        <?php foreach([
          ['Total Items',   $totalItems,    '#3b82f6','bi-box-seam'],
          ['Returned',      $returnedItems, '#22c55e','bi-check-circle'],
          ['Lost Reports',  $totalLost,     '#f59e0b','bi-search'],
          ['Active Tasks',  $activeTasks,   '#8b5cf6','bi-list-task'],
          ['Total Users',   $totalUsers,    '#06b6d4','bi-people'],
          ['Pending Items', $pendingItems,  '#ef4444','bi-hourglass'],
        ] as [$lbl,$val,$col,$ico]): ?>
        <div class="col-xl-2 col-md-4 col-6">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="stat-icon" style="background:<?= $col ?>20;color:<?= $col ?>">
                  <i class="bi <?= $ico ?>"></i>
                </div>
              </div>
              <div class="stat-lbl"><?= $lbl ?></div>
              <div class="stat-num counter-val" data-target="<?= $val ?>" style="color:<?= $col ?>"><?= $val ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Chart + Quick Activity -->
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card activity-card shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>6-Month Trends</h6>
            </div>
            <div class="card-body">
              <div class="chart-container"><canvas id="trendChart"></canvas></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card activity-card shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
              <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Quick Activity</h6>
            </div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
              <?php foreach(array_slice($recentActivity, 0, 6) as $act):
                $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'Super Admin', 6=>'Organizer'];
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
                  <div class="activity-title"><?= htmlspecialchars($act['fname'].' '.$act['lname']) ?></div>
                  <div class="activity-meta"><?= ucfirst($act['action_type']) ?> · <?= date('M d, g:i A', strtotime($act['created_at'])) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php if(empty($recentActivity)): ?>
              <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-3 mb-2 opacity-25"></i>No activity yet</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ACTIVITY LOG -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="activity-log" class="section">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif">
          <i class="bi bi-activity me-2 text-primary"></i>System Activity Log
        </h5>
        <div class="input-group input-group-sm" style="width:250px">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control form-control-sm border-start-0" id="activitySearch" placeholder="Search activity..." oninput="searchActivity()">
        </div>
      </div>

      <div class="card activity-card shadow-sm">
        <div class="card-body p-0" style="max-height:600px; overflow-y:auto">
          <?php if(empty($recentActivity)): ?>
          <div class="text-center text-muted py-5"><i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No activity yet</div>
          <?php else: ?>
            <?php foreach($recentActivity as $act):
              $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'Super Admin', 6=>'Organizer'];
              $role = $roleMap[$act['type_id']] ?? 'User';
              $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
              $bgColor = $actionColors[$act['action_type']] ?? '#64748b';
            ?>
            <div class="activity-item activity-row">
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
                  <span class="badge bg-light text-dark border ms-1" style="font-size:.65rem"><?= $role ?></span>
                  <?= ucfirst($act['action_type']) ?> <?= $act['target_type'] ?> #<?= $act['target_id'] ?>
                </div>
                <div class="activity-meta">
                  <i class="bi bi-clock me-1"></i><?= date('M d, Y g:i A', strtotime($act['created_at'])) ?>
                  <?php if($act['details']): ?>
                  <span class="mx-1">•</span><?= htmlspecialchars(substr($act['details'],0,60)) ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ORGANIZER TEAM -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="organizer-team" class="section">
      <h5 class="fw-bold mb-4" style="font-family:'Syne',sans-serif">
        <i class="bi bi-people me-2" style="color:var(--purple)"></i>Organizer Team Performance
      </h5>

      <?php if(empty($organizerStats)): ?>
      <div class="text-center text-muted py-5"><i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No organizers found</div>
      <?php else: ?>
      <div class="row g-3">
        <?php foreach($organizerStats as $org): ?>
        <div class="col-lg-4 col-md-6">
          <div class="organizer-card">
            <div class="d-flex align-items-center gap-3">
              <div class="organizer-avatar"><?= strtoupper(substr($org['fname'],0,1)) ?></div>
              <div style="flex:1">
                <div class="fw-bold"><?= htmlspecialchars($org['fname'].' '.$org['lname']) ?></div>
                <div class="small text-muted">
                  <?= $org['organizer_role'] == 'president' ? '👑 SSG President' : 'SSG Member' ?>
                </div>
                <div class="d-flex gap-2 mt-2">
                  <span class="badge bg-success" style="font-size:.72rem">✓ <?= $org['completed_tasks'] ?> done</span>
                  <span class="badge bg-warning text-dark" style="font-size:.72rem">⏳ <?= $org['active_tasks'] ?> active</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- REPORTS -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="reports" class="section">
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
          <h5 class="fw-bold mb-1" style="font-family:'Syne',sans-serif"><i class="bi bi-file-earmark-bar-graph me-2" style="color:var(--purple)"></i>Detailed Reports</h5>
          <small class="text-muted">Filter and analyze lost items and found items data</small>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()"><i class="bi bi-download me-1"></i>Export CSV</button>
        </div>
      </div>

      <!-- Report Summary Stats -->
      <?php if($reportStats): ?>
      <div class="row g-2 mb-4">
        <div class="col-lg-2 col-md-4 col-6">
          <div class="card stat-card shadow-sm border-0"><div class="card-body py-3">
            <div class="stat-lbl mb-2">Total</div>
            <div class="stat-num text-primary" style="font-size:1.8rem"><?= $reportStats['total'] ?? 0 ?></div>
          </div></div>
        </div>
        <?php if($reportType === 'lost'): ?>
          <?php foreach([['Lost','lost','text-danger'],['Matching','matching','text-info'],['Resolved','resolved','text-success']] as [$label,$key,$cls]): ?>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm border-0"><div class="card-body py-3">
              <div class="stat-lbl mb-2"><?= $label ?></div>
              <div class="stat-num <?= $cls ?>" style="font-size:1.8rem"><?= $reportStats[$key] ?? 0 ?></div>
            </div></div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <?php foreach([['Pending','pending','text-warning'],['Published','published','text-info'],['Returned','returned','text-success']] as [$label,$key,$cls]): ?>
          <div class="col-lg-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm border-0"><div class="card-body py-3">
              <div class="stat-lbl mb-2"><?= $label ?></div>
              <div class="stat-num <?= $cls ?>" style="font-size:1.8rem"><?= $reportStats[$key] ?? 0 ?></div>
            </div></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Filter Bar -->
      <div class="card activity-card shadow-sm mb-4">
        <div class="card-body p-3">
          <form method="GET" id="reportForm">
            <div class="row g-2 align-items-end">
              <div class="col-md-2">
                <label class="form-label small fw-bold mb-2">Type</label>
                <select name="report_type" class="form-select form-select-sm" onchange="document.getElementById('reportForm').submit()">
                  <option value="lost" <?= $reportType === 'lost' ? 'selected' : '' ?>>📋 Lost Reports</option>
                  <option value="found" <?= $reportType === 'found' ? 'selected' : '' ?>>📦 Found Items</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-bold mb-2">Period</label>
                <select name="time" class="form-select form-select-sm" onchange="toggleDatePicker(); document.getElementById('reportForm').submit()">
                  <option value="" <?= $timeFilter === '' ? 'selected' : '' ?>>All Time</option>
                  <option value="month" <?= $timeFilter === 'month' ? 'selected' : '' ?>>This Month</option>
                  <option value="semester" <?= $timeFilter === 'semester' ? 'selected' : '' ?>>This Semester</option>
                  <option value="year" <?= $timeFilter === 'year' ? 'selected' : '' ?>>This Year</option>
                  <option value="custom" <?= $timeFilter === 'custom' ? 'selected' : '' ?>>📅 Custom</option>
                </select>
              </div>
              <div class="col-md-2" id="dateRangeGroup" style="display:<?= $timeFilter === 'custom' ? 'block' : 'none' ?>">
                <label class="form-label small fw-bold mb-2">From</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>" onchange="document.getElementById('reportForm').submit()">
              </div>
              <div class="col-md-2" id="dateRangeGroup2" style="display:<?= $timeFilter === 'custom' ? 'block' : 'none' ?>">
                <label class="form-label small fw-bold mb-2">To</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>" onchange="document.getElementById('reportForm').submit()">
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-bold mb-2">Category</label>
                <select name="category" class="form-select form-select-sm" onchange="document.getElementById('reportForm').submit()">
                  <option value="">All</option>
                  <?php foreach($categoriesList as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-bold mb-2">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="document.getElementById('reportForm').submit()">
                  <option value="">All Status</option>
                  <?php if($reportType === 'lost'): ?>
                    <option value="Lost" <?= $statusFilter === 'Lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="Matching" <?= $statusFilter === 'Matching' ? 'selected' : '' ?>>Matching</option>
                    <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                  <?php else: ?>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Published" <?= $statusFilter === 'Published' ? 'selected' : '' ?>>Published</option>
                    <option value="Claiming" <?= $statusFilter === 'Claiming' ? 'selected' : '' ?>>Claiming</option>
                    <option value="Returned" <?= $statusFilter === 'Returned' ? 'selected' : '' ?>>Returned</option>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-auto">
                <a href="superadmin_dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Reset</a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Data Table -->
      <div class="card activity-card shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-table me-2"></i>
            <?= $reportType === 'lost' ? 'Lost Reports' : 'Found Items' ?>
            <span class="badge bg-light text-dark ms-2"><?= count($reportData) ?> records</span>
          </h6>
          <div class="input-group input-group-sm" style="width:200px">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control form-control-sm border-start-0" id="reportSearch" placeholder="Search..." oninput="searchReport()">
          </div>
        </div>
        <div class="card-body p-0">
          <?php if(empty($reportData)): ?>
          <div class="text-center text-muted py-5">
            <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
            <p>No records match your filters</p>
            <small><a href="superadmin_dashboard.php" class="text-muted text-decoration-none">Clear filters</a></small>
          </div>
          <?php else: ?>
          <div style="overflow-x:auto">
            <table class="table table-hover mb-0 small" id="reportTable">
              <thead>
                <tr>
                  <th>Item</th><th>Category</th>
                  <th><?= $reportType === 'lost' ? 'Place Lost' : 'Place Found' ?></th>
                  <th><?= $reportType === 'lost' ? 'Owner' : 'Finder' ?></th>
                   <?php if($reportType === 'found'): ?>
                  <th>Claimant</th>
                  <th>Date/Time Found</th>
                  <?php endif; ?>
                  <th>Status</th>
                  <th>Date Reported</th>
                  <th><?= $reportType === 'lost' ? 'Date Resolved' : 'Date Claimed' ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($reportData as $record): ?>
                <tr class="report-row">
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($record['item_name']) ?></td>
                  <td><span class="badge bg-light text-dark border" style="font-size:.7rem"><?= htmlspecialchars($record['category'] ?? 'N/A') ?></span></td>
                  <td><?= htmlspecialchars($reportType === 'lost' ? ($record['last_seen_location'] ?? 'N/A') : ($record['found_location'] ?? 'N/A')) ?></td>
                  <td>
                    <?php 
                      if ($reportType === 'lost') {
                        echo htmlspecialchars($record['owner_name'] ?? 'N/A');
                      } else {
                        echo htmlspecialchars(!empty($record['finder_full_name']) ? $record['finder_full_name'] : ($record['finder_name'] ?? 'N/A'));
                      }
                    ?>
                  </td>
                   <?php if($reportType === 'found'): ?>
                  <td><?= htmlspecialchars($record['claimant_name'] ?? '---') ?></td>
                  <td><small><?= !empty($record['date_found']) ? date('M d, Y h:i A', strtotime($record['date_found'])) : '---' ?></small></td>
                  <?php endif; ?>
                  <td>
                    <span class="badge <?= match($record['status']) {
                      'Lost'=>'bg-danger','Matching'=>'bg-info','Resolved'=>'bg-success',
                      'Pending'=>'bg-warning text-dark','Published'=>'bg-info','Claiming'=>'bg-primary','Returned'=>'bg-success',
                      default=>'bg-secondary'
                    } ?>" style="font-size:.7rem"><?= htmlspecialchars($record['status']) ?></span>
                  </td>
                  <td><small class="text-muted"><?= date('M d, Y', strtotime($record['created_at'])) ?></small></td>
                  <td>
                    <small class="text-muted fw-bold">
                      <?php 
                        $secDate = $reportType === 'lost' ? $record['date_resolved'] : $record['date_claimed'];
                        echo $secDate ? date('M d, Y', strtotime($secDate)) : '---';
                      ?>
                    </small>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- USER CONVERSATIONS (Integrated) -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="user-conversations" class="section">
      <div class="conv-card">
        <div class="conv-sidebar">
          <div class="conv-sidebar-header">
            <i class="bi bi-chat-dots text-primary"></i> All Threads
          </div>
          <div class="conv-search">
            <input type="text" id="convSearch" placeholder="Search by name..." autocomplete="off">
          </div>
          <div class="conv-list">
            <?php foreach ($conversations as $conv):
              $isActive = ($activeUser1 == $conv['user1_id'] && $activeUser2 == $conv['user2_id']);
              $u1Color = $roleColors[$conv['u1_type']] ?? '#64748b';
              $u2Color = $roleColors[$conv['u2_type']] ?? '#64748b';
            ?>
            <a href="superadmin_dashboard.php?u1=<?= $conv['user1_id'] ?>&u2=<?= $conv['user2_id'] ?>&section=user-conversations"
               class="conv-item <?= $isActive ? 'active' : '' ?>">
              <div class="d-flex align-items-center gap-3">
                <div class="conv-avatar-pair">
                  <div class="conv-avatar" style="background:<?= $u1Color ?>"><?= strtoupper(substr($conv['u1_fname'], 0, 1)) ?></div>
                  <div class="conv-avatar" style="background:<?= $u2Color ?>"><?= strtoupper(substr($conv['u2_fname'], 0, 1)) ?></div>
                </div>
                <div style="min-width:0; flex:1">
                  <div class="fw-bold conv-names" style="font-size:.85rem"><?= htmlspecialchars($conv['u1_fname']) ?> & <?= htmlspecialchars($conv['u2_fname']) ?></div>
                  <div class="conv-preview"><?= htmlspecialchars($conv['last_message'] ?? '') ?></div>
                </div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="chat-panel">
          <?php if (!$activeUser1 || !$user1Info || !$user2Info): ?>
          <div class="chat-panel-empty">
            <i class="bi bi-chat-square-dots" style="font-size:3rem; color:#cbd5e1"></i>
            <div class="fw-bold">Select a thread</div>
            <div class="small">Choose a conversation from the left</div>
          </div>
          <?php else: ?>
          <div class="chat-panel-header border-bottom p-3 d-flex align-items-center gap-3">
             <div class="conv-avatar-pair">
                <div class="conv-avatar" style="background:<?= $roleColors[$user1Info['type_id']] ?? '#64748b' ?>"><?= strtoupper(substr($user1Info['fname'], 0, 1)) ?></div>
                <div class="conv-avatar" style="background:<?= $roleColors[$user2Info['type_id']] ?? '#64748b' ?>"><?= strtoupper(substr($user2Info['fname'], 0, 1)) ?></div>
             </div>
             <div>
               <div class="fw-bold" style="font-size:.9rem"><?= htmlspecialchars($user1Info['fname']) ?> ↔ <?= htmlspecialchars($user2Info['fname']) ?></div>
               <div class="text-muted" style="font-size:.7rem"><?= count($convMessages) ?> messages</div>
             </div>
          </div>
          <div class="chat-messages" id="chatMessages">
            <?php foreach ($convMessages as $msg):
              $isUser1 = ((int)$msg['sender_id'] === $activeUser1);
            ?>
            <div class="chat-bubble <?= $isUser1 ? 'left' : 'right' ?>">
              <div class="bubble-content"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>
              <div class="bubble-meta">
                <span><?= htmlspecialchars($msg['fname']) ?></span>
                <span><?= date('M d, g:i A', strtotime($msg['created_at'])) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="read-only-strip">
            <i class="bi bi-lock-fill me-1"></i> Read-only mode for Super Admin
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- page-body -->
</div><!-- /admin-main-content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const titles = {
  'dashboard': 'Dashboard',
  'activity-log': 'Activity Log',
  'organizer-team': 'Organizer Team',
  'reports': 'Detailed Reports',
  'user-conversations': 'User Conversations'
};

function goTo(id, btn) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sidebar-link').forEach(b => { if(!b.closest('.sidebar-footer')) b.classList.remove('active'); });
  document.getElementById(id).classList.add('active');
  if (btn) btn.classList.add('active');
  document.getElementById('topbarTitle').textContent = titles[id] || id;
  window.scrollTo({top:0, behavior:'smooth'});
  closeMobileSidebar();
}

// ── Filters ───────────────────────────────────────────────
function toggleDatePicker() {
  const v = document.querySelector('select[name="time"]').value;
  document.getElementById('dateRangeGroup').style.display = v === 'custom' ? 'block' : 'none';
  document.getElementById('dateRangeGroup2').style.display = v === 'custom' ? 'block' : 'none';
}

// ── Search functions ──────────────────────────────────────
function searchActivity() {
  const q = (document.getElementById('activitySearch')?.value || '').toLowerCase();
  document.querySelectorAll('.activity-row').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function searchReport() {
  const q = (document.getElementById('reportSearch')?.value || '').toLowerCase();
  document.querySelectorAll('.report-row').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Export CSV ─────────────────────────────────────────────
function exportCSV() {
  const table = document.getElementById('reportTable');
  if (!table) { alert('No data to export'); return; }
  let csv = [];
  table.querySelectorAll('tr').forEach(row => {
    const cols = [];
    row.querySelectorAll('th, td').forEach(cell => {
      cols.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
    });
    csv.push(cols.join(','));
  });
  const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'report_<?= $reportType ?>_<?= date('Y-m-d') ?>.csv';
  a.click();
}

// ── Chart ─────────────────────────────────────────────────
const monthlyData = <?= json_encode($monthlyData) ?>;
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: monthlyData.map(d => d.label),
    datasets: [
      { label: 'Found Items', data: monthlyData.map(d => d.items), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.4, fill: true, borderWidth: 2.5 },
      { label: 'Lost Reports', data: monthlyData.map(d => d.lost), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.4, fill: true, borderWidth: 2.5 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: { y: { beginAtZero: true } }
  }
});

// ── Counter animation ─────────────────────────────────────
document.querySelectorAll('.counter-val').forEach(el => {
  const target = parseInt(el.dataset.target) || 0;
  if (target === 0) return;
  let current = 0;
  const step = Math.ceil(target / 40);
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current;
    if (current >= target) clearInterval(timer);
  }, 25);
});

// ── User Conversations JS ───────────────────────────
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

function scrollChatBottom() {
  const chat = document.getElementById('chatMessages');
  if (chat) chat.scrollTop = chat.scrollHeight;
}
scrollChatBottom();

// Update goTo to include scroll for conversations
const originalGoTo = goTo;
goTo = function(id, btn) {
  originalGoTo(id, btn);
  if (id === 'user-conversations') {
    setTimeout(scrollChatBottom, 100);
  }
};

// ── Auto-navigate to sections ────────────────────────────
<?php 
  $section = $_GET['section'] ?? '';
  if ($section): 
?>
const activeBtn = document.querySelector('[onclick*="<?= $section ?>"]');
goTo('<?= $section ?>', activeBtn);
<?php endif; ?>
</script>
</body>
</html>