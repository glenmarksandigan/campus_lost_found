<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 6) {
    header("Location: auth.php"); exit;
}
include 'db.php';

$user_id = (int)$_SESSION['user_id'];
$user_name = trim($_SESSION['user_name'] ?? '');

// Get organizer role and permissions
$roleStmt = $pdo->prepare("SELECT organizer_role, can_edit FROM users WHERE id = ?");
$roleStmt->execute([$user_id]);
$userRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
$organizerRole = $userRow['organizer_role'] ?? 'member';
$canEdit = (int)($userRow['can_edit'] ?? 1);
$isPresident = ($organizerRole === 'president');

// Effective edit permission
$hasEditAccess = ($isPresident || $canEdit);

// ── My Tasks ────────────────────────────────────────────────────────────────
$myTasks = $pdo->prepare("
    SELECT t.*, u.fname, u.lname
    FROM admin_tasks t
    JOIN users u ON t.assigned_by = u.id
    WHERE t.assigned_to = ? AND t.status != 'cancelled'
    ORDER BY 
        FIELD(t.priority, 'urgent', 'high', 'normal', 'low'),
        FIELD(t.status, 'pending', 'in_progress', 'completed'),
        t.due_date ASC
");
$myTasks->execute([$user_id]);
$tasks = $myTasks->fetchAll();

$pendingTasks    = array_filter($tasks, fn($t) => $t['status'] === 'pending');
$inProgressTasks = array_filter($tasks, fn($t) => $t['status'] === 'in_progress');
$completedTasks  = array_filter($tasks, fn($t) => $t['status'] === 'completed');

// ── Quick Stats ─────────────────────────────────────────────────────────────
$pendingItems   = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Pending'")->fetchColumn();
$publishedItems = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Published'")->fetchColumn();
$unresolvedLost = $pdo->query("SELECT COUNT(*) FROM lost_reports WHERE status = 'Lost'")->fetchColumn();
$claimingItems  = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Claiming'")->fetchColumn();

// ── ALL Found Items with Detailed Claim Info ──────────────────────────────
$allItems = $pdo->query("SELECT i.*, (SELECT COUNT(*) FROM claims WHERE item_id = i.id) as claim_count FROM items i ORDER BY i.created_at DESC LIMIT 50")->fetchAll();

// Fetch all claims with user details for all visible items
$itemIds = array_column($allItems, 'id');
$allClaims = [];
if (!empty($itemIds)) {
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $claimsStmt = $pdo->prepare("
        SELECT c.*, u.fname, u.lname, u.email, u.contact_number, u.student_id
        FROM claims c
        JOIN users u ON c.user_id = u.id
        WHERE c.item_id IN ($placeholders)
        ORDER BY c.claimed_at DESC
    ");
    $claimsStmt->execute($itemIds);
    foreach ($claimsStmt->fetchAll(PDO::FETCH_ASSOC) as $claim) {
        $allClaims[$claim['item_id']][] = $claim;
    }
}

// ── ALL Lost Reports with Contact/Matcher Info ──────────────────────────────
$allLostReports = $pdo->query("
    SELECT lr.*,
           GROUP_CONCAT(lc.finder_name SEPARATOR ', ') as contact_finder_names
    FROM lost_reports lr
    LEFT JOIN lost_contacts lc ON lr.id = lc.report_id
    GROUP BY lr.id
    ORDER BY lr.created_at DESC LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Organizer Dashboard – FoundIt!</title>
  <style>
    /* ── Section Navigation ─────────────────────────────────── */
    .section { display: none; animation: fadeUp .3s ease; }
    .section.active { display: block; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    .stat-card { border: none; border-radius: 16px; transition: transform .2s, box-shadow .2s; background: white; box-shadow: var(--admin-card-shadow); }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
    .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .stat-lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }

    .task-card { border: none; border-radius: 14px; padding: 16px; margin-bottom: 12px; background: #fff; border-left: 4px solid #e2e8f0; transition: all .2s; }
    .task-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .task-card.urgent { border-left-color: #ef4444; } .task-card.high { border-left-color: #f59e0b; }
    .task-card.normal { border-left-color: #3b82f6; } .task-card.low  { border-left-color: #94a3b8; }
    .task-title { font-weight: 700; font-size: .92rem; margin-bottom: 6px; }
    .task-meta { font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    .badge-priority { padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
    .badge-urgent { background: #fee2e2; color: #dc2626; } .badge-high { background: #fef3c7; color: #ca8a04; }
    .badge-normal { background: #dbeafe; color: #1d4ed8; } .badge-low  { background: #f1f5f9; color: #64748b; }

    .badge-status { padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
    .badge-pending { background: #fef3c7; color: #ca8a04; } .badge-in_progress { background: #dbeafe; color: #1d4ed8; }
    .badge-completed { background: #dcfce7; color: #16a34a; } .badge-published { background: #dbeafe; color: #1d4ed8; }
    .badge-claiming { background: #e0e7ff; color: #4f46e5; } .badge-returned { background: #dcfce7; color: #16a34a; }
    .badge-lost { background: #fee2e2; color: #dc2626; } .badge-resolved { background: #dcfce7; color: #16a34a; }

    .btn-teal { background: #0d9488; color: #fff; border: none; border-radius: 10px; font-weight: 600; transition: all .2s; }
    .btn-teal:hover { background: #0f766e; color: #fff; }

    .content-card { border: none; border-radius: 16px; overflow: hidden; }
    .table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    /* Claimer UI Components */
    .claimer-count-badge {
        display: inline-flex; align-items: center; gap: 5px;
        background: #fffbeb; border: 1px solid #fcd34d;
        border-radius: 20px; padding: 4px 10px;
        font-size: 0.78rem; font-weight: 700; color: #92400e;
        cursor: pointer; transition: all 0.2s;
    }
    .claimer-count-badge:hover { background: #fef3c7; border-color: #f59e0b; }
    .claimer-count-badge.none { background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; cursor: default; }

    .claimer-card {
        border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 14px 16px; margin-bottom: 10px;
        background: #fff; transition: box-shadow 0.2s;
    }
    .claimer-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .claimer-card .claimer-num {
        width: 28px; height: 28px; border-radius: 50%;
        background: #0d9488; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
    }
    .claimer-card .claim-msg {
        background: #f8fafc; border-left: 3px solid #fcd34d;
        border-radius: 0 8px 8px 0; padding: 8px 12px;
        font-size: 0.82rem; font-style: italic; color: #475569;
    }
    .claimer-card { position: relative; }
    .status-badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-badge-verified { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-badge-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .msg-input-area { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: none; margin-top: 10px; }
    .claimer-actions { display: flex; gap: 8px; margin-top: 12px; }
    .btn-claimer { font-size: .75rem; padding: 5px 12px; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 5px; }

    /* ── Topbar for organizer ─────────────────────────────── */
    .org-topbar {
      background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 28px; height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 200;
    }
    .topbar-title { font-weight: 700; font-size: 1.1rem; color: #0f172a; }
    .greeting-text { font-size: .82rem; color: #64748b; margin-top: 2px; }
    .page-body { padding: 24px 28px; }
  </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

  <header class="org-topbar">
    <div>
      <div class="topbar-title" id="topbarTitle">Overview</div>
      <?php
        $hour = (int)date('G');
        if ($hour < 12) $greet = 'Good morning';
        elseif ($hour < 18) $greet = 'Good afternoon';
        else $greet = 'Good evening';
      ?>
      <div class="greeting-text"><?= $greet ?>, <?= htmlspecialchars($user_name) ?> 👋</div>
    </div>
    <span class="badge px-3 py-2 rounded-pill" style="background:#ccfbf1;color:#0d9488;font-size:.8rem">
      <i class="bi bi-calendar3 me-1"></i><?= date('l, F d, Y') ?>
    </span>
  </header>

  <div class="page-body">

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- OVERVIEW -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="overview" class="section active">
      <div class="row g-3 mb-4">
        <?php foreach([
          ['Pending Items',   $pendingItems,   '#fef9c7','#ca8a04','bi-hourglass-split'],
          ['Published',       $publishedItems, '#dcfce7','#16a34a','bi-megaphone'],
          ['Claiming',        $claimingItems,  '#dbeafe','#1d4ed8','bi-hand-thumbs-up'],
          ['Lost Reports',    $unresolvedLost, '#fee2e2','#dc2626','bi-exclamation-circle'],
        ] as [$lbl,$val,$bg,$col,$ico]): ?>
        <div class="col-xl-3 col-md-6">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $col ?>">
                <i class="bi <?= $ico ?>"></i>
              </div>
              <div>
                <div class="stat-lbl"><?= $lbl ?></div>
                <div class="stat-num counter-val" data-target="<?= $val ?>" style="color:<?= $col ?>"><?= $val ?></div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <h5 class="fw-bold mb-3" style="font-family:'Syne',sans-serif">
        <i class="bi bi-list-task me-2" style="color:var(--teal)"></i>Active Tasks
      </h5>
      <?php if(empty($pendingTasks) && empty($inProgressTasks)): ?>
      <div class="alert alert-success border-0 rounded-3 shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>All caught up!</strong> You have no pending tasks right now.
      </div>
      <?php else: ?>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-3" style="border-left:4px solid #f59e0b !important">
            <div class="card-body">
              <h6 class="fw-bold mb-2"><i class="bi bi-clock-history me-2 text-warning"></i>Pending</h6>
              <div class="display-6 fw-bold text-warning"><?= count($pendingTasks) ?></div>
              <small class="text-muted">Tasks waiting to be started</small>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card shadow-sm border-0 rounded-3" style="border-left:4px solid #3b82f6 !important">
            <div class="card-body">
              <h6 class="fw-bold mb-2"><i class="bi bi-arrow-repeat me-2 text-primary"></i>In Progress</h6>
              <div class="display-6 fw-bold text-primary"><?= count($inProgressTasks) ?></div>
              <small class="text-muted">Tasks currently working on</small>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MY TASKS -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="my-tasks" class="section">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif">
          <i class="bi bi-list-task me-2" style="color:var(--teal)"></i>My Tasks
        </h5>
        <span class="badge bg-light text-dark border">
          <?= count($completedTasks) ?> / <?= count($tasks) ?> completed
        </span>
      </div>

      <div id="taskAlert" class="mb-3"></div>

      <?php if(!empty($pendingTasks)): ?>
      <h6 class="text-muted mb-3"><i class="bi bi-clock me-1"></i>Pending (<?= count($pendingTasks) ?>)</h6>
      <?php foreach($pendingTasks as $task): ?>
      <div class="task-card <?= $task['priority'] ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
          <button class="btn btn-sm btn-primary" onclick="startTask(<?= $task['id'] ?>)">
            <i class="bi bi-play-fill"></i> Start
          </button>
        </div>
        <?php if($task['description']): ?>
        <div class="text-muted small mb-2"><?= htmlspecialchars($task['description']) ?></div>
        <?php endif; ?>
        <div class="task-meta">
          <span class="badge-priority badge-<?= $task['priority'] ?>"><?= $task['priority'] ?></span>
          <span><i class="bi bi-person text-muted"></i> Assigned by <?= htmlspecialchars($task['fname'].' '.$task['lname']) ?></span>
          <?php if($task['due_date']): ?>
          <span><i class="bi bi-calendar text-muted"></i> Due: <?= date('M d', strtotime($task['due_date'])) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if(!empty($inProgressTasks)): ?>
      <h6 class="text-muted mb-3 mt-4"><i class="bi bi-arrow-repeat me-1"></i>In Progress (<?= count($inProgressTasks) ?>)</h6>
      <?php foreach($inProgressTasks as $task): ?>
      <div class="task-card <?= $task['priority'] ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
          <button class="btn btn-sm btn-success" onclick="completeTask(<?= $task['id'] ?>)">
            <i class="bi bi-check-lg"></i> Complete
          </button>
        </div>
        <?php if($task['description']): ?>
        <div class="text-muted small mb-2"><?= htmlspecialchars($task['description']) ?></div>
        <?php endif; ?>
        <div class="task-meta">
          <span class="badge-status badge-in_progress">In Progress</span>
          <span class="badge-priority badge-<?= $task['priority'] ?>"><?= $task['priority'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if(!empty($completedTasks)): ?>
      <h6 class="text-muted mb-3 mt-4"><i class="bi bi-check-circle me-1"></i>Completed (<?= count($completedTasks) ?>)</h6>
      <?php foreach(array_slice($completedTasks, 0, 5) as $task): ?>
      <div class="task-card low opacity-75">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
          <span class="badge-status badge-completed">Completed</span>
        </div>
        <div class="task-meta">
          <span><i class="bi bi-check-circle text-success"></i> Done <?= date('M d, g:i A', strtotime($task['completed_at'])) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <?php if(empty($tasks)): ?>
      <div class="text-center text-muted py-5">
        <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
        No tasks assigned yet
      </div>
      <?php endif; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- FOUND ITEMS -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="found-items" class="section">
      <h5 class="fw-bold mb-3" style="font-family:'Syne',sans-serif">
        <i class="bi bi-box-seam me-2 text-warning"></i>Found Items Management
      </h5>

      <?php if(!$hasEditAccess): ?>
      <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
        <i class="bi bi-shield-lock-fill me-2"></i>
        <strong>Editing Disabled:</strong> Your editing permissions have been restricted by an Administrator. You can still view items but cannot change their status.
      </div>
      <?php endif; ?>

      <div id="itemAlert" class="mb-3"></div>

      <div class="card content-card shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="fw-bold">All Found Items (<?= count($allItems) ?>)</span>
          <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:200px">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control form-control-sm border-start-0" id="itemSearchInput" placeholder="Search items..." oninput="searchItems()">
            </div>
            <select class="form-select form-select-sm" style="width:auto" onchange="filterItems(this.value)">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="Published">Published</option>
              <option value="Claiming">Claiming</option>
              <option value="Returned">Returned</option>
            </select>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="itemsTable">
            <thead>
              <tr>
                <th>Item</th>
                <th>Location</th>
                <th>Finder</th>
                <th>Claimer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($allItems)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No items found yet</td></tr>
              <?php else: ?>
                <?php foreach($allItems as $item): ?>
                <tr data-status="<?= $item['status'] ?>">
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></div>
                    <?php if($item['description']): ?>
                    <small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><small><?= htmlspecialchars($item['found_location'] ?? 'N/A') ?></small></td>
                  <td><small><?= htmlspecialchars($item['finder_name'] ?? 'N/A') ?></small></td>
                  <td>
                    <?php 
                      $itemClaims = $allClaims[$item['id']] ?? [];
                      $claimCount = count($itemClaims);
                      $claimsJson = htmlspecialchars(json_encode($itemClaims), ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php if ($claimCount > 0): ?>
                      <span class="claimer-count-badge" 
                            onclick="viewClaimers(<?= $claimsJson ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>', <?= $item['id'] ?>, '<?= $item['image_path'] ?>')">
                        <i class="bi bi-people-fill"></i>
                        <?= $claimCount ?> Claimer<?= $claimCount > 1 ? 's' : '' ?>
                        <i class="bi bi-chevron-right" style="font-size:.65rem"></i>
                      </span>
                    <?php else: ?>
                      <span class="claimer-count-badge none">
                        <i class="bi bi-clock"></i> 0 Claims
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><small class="text-muted"><?= date('M d, Y', strtotime($item['created_at'])) ?></small></td>
                  <td><span class="badge-status badge-<?= strtolower($item['status']) ?>"><?= $item['status'] ?></span></td>
                  <td>
                    <select class="form-select form-select-sm" 
                            onchange="updateItemStatus(<?= $item['id'] ?>, this.value, this)" 
                            style="min-width:120px"
                            <?= !$hasEditAccess ? 'disabled' : '' ?>>
                      <option value="<?= $item['status'] ?>" selected><?= $item['status'] ?></option>
                      <option value="Pending">Pending</option>
                      <option value="Published">Published</option>
                      <option value="Claiming">Claiming</option>
                      <option value="Returned">Returned</option>
                    </select>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- LOST REPORTS -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div id="lost-reports" class="section">
      <h5 class="fw-bold mb-3" style="font-family:'Syne',sans-serif">
        <i class="bi bi-search me-2 text-danger"></i>Lost Reports Management
      </h5>

      <div id="lostAlert" class="mb-3"></div>

      <div class="card content-card shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="fw-bold">All Lost Reports (<?= count($allLostReports) ?>)</span>
          <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width:200px">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control form-control-sm border-start-0" id="lostSearchInput" placeholder="Search reports..." oninput="searchLost()">
            </div>
            <select class="form-select form-select-sm" style="width:auto" onchange="filterLost(this.value)">
              <option value="">All Status</option>
              <option value="Lost">Lost</option>
              <option value="Matching">Matching</option>
              <option value="Resolved">Resolved</option>
            </select>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="lostTable">
            <thead>
              <tr>
                <th>Item</th>
                <th>Last Seen</th>
                <th>Owner</th>
                <th>Contact/Finder</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($allLostReports)): ?>
              <tr><td colspan="7" class="text-center text-muted py-4">No lost reports yet</td></tr>
              <?php else: ?>
                <?php foreach($allLostReports as $report): ?>
                <tr data-status="<?= $report['status'] ?? 'Lost' ?>">
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($report['item_name']) ?></div>
                    <?php if($report['description']): ?>
                    <small class="text-muted"><?= htmlspecialchars(substr($report['description'], 0, 50)) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><small><?= htmlspecialchars($report['last_seen_location'] ?? 'N/A') ?></small></td>
                  <td><small><?= htmlspecialchars($report['owner_name'] ?? 'N/A') ?></small></td>
                  <td><small><?= !empty($report['contact_finder_names']) ? htmlspecialchars($report['contact_finder_names']) : '<span class="text-muted">—</span>' ?></small></td>
                  <td><small class="text-muted"><?= date('M d, Y', strtotime($report['created_at'])) ?></small></td>
                  <td><span class="badge-status badge-<?= strtolower($report['status'] ?? 'lost') ?>"><?= $report['status'] ?? 'Lost' ?></span></td>
                  <td>
                    <select class="form-select form-select-sm" 
                            onchange="updateLostStatus(<?= $report['id'] ?>, this.value, this)" 
                            style="min-width:120px"
                            <?= !$hasEditAccess ? 'disabled' : '' ?>>
                      <option value="<?= $report['status'] ?? 'Lost' ?>" selected><?= $report['status'] ?? 'Lost' ?></option>
                      <option value="Lost">Lost</option>
                      <option value="Matching">Matching</option>
                      <option value="Resolved">Resolved</option>
                    </select>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Claimers Modal -->
<div class="modal fade" id="claimersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="modal-header border-0 bg-warning text-dark px-4 py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-people-fill me-2"></i>
                    <span id="claimersModalTitle">Claimers</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Review all claimers below. Verify their proof of ownership before returning items.
                </p>
                <div id="claimersListContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
const titles = {
  'overview': 'Overview',
  'my-tasks': 'My Tasks',
  'found-items': 'Found Items Management',
  'lost-reports': 'Lost Reports Management'
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

// ── Filters & Search ──────────────────────────────────────
function filterItems(status) {
  document.querySelectorAll('#itemsTable tbody tr[data-status]').forEach(row => {
    row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
  });
}
function filterLost(status) {
  document.querySelectorAll('#lostTable tbody tr[data-status]').forEach(row => {
    row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
  });
}
function searchItems() {
  const q = (document.getElementById('itemSearchInput')?.value || '').toLowerCase();
  document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function searchLost() {
  const q = (document.getElementById('lostSearchInput')?.value || '').toLowerCase();
  document.querySelectorAll('#lostTable tbody tr').forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Task functions ────────────────────────────────────────
async function startTask(taskId) {
  const div = document.getElementById('taskAlert');
  div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Starting task...</div>`;
  const fd = new FormData();
  fd.append('action', 'update_task_status'); fd.append('task_id', taskId); fd.append('status', 'in_progress');
  try {
    const res = await fetch('task_actions.php', {method:'POST', body:fd});
    const data = await res.json();
    div.innerHTML = data.success
      ? `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.message}</div>`
      : `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${data.message}</div>`;
    if (data.success) setTimeout(() => location.reload(), 800);
  } catch(err) { div.innerHTML = `<div class="alert alert-danger">Server error</div>`; }
}

async function completeTask(taskId) {
  const div = document.getElementById('taskAlert');
  div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Marking as complete...</div>`;
  const fd = new FormData();
  fd.append('action', 'update_task_status'); fd.append('task_id', taskId); fd.append('status', 'completed');
  try {
    const res = await fetch('task_actions.php', {method:'POST', body:fd});
    const data = await res.json();
    div.innerHTML = data.success
      ? `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.message}</div>`
      : `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${data.message}</div>`;
    if (data.success) setTimeout(() => location.reload(), 800);
  } catch(err) { div.innerHTML = `<div class="alert alert-danger">Server error</div>`; }
}

// ── Item/Lost status updates ──────────────────────────────
async function updateItemStatus(itemId, status, sel) {
  if (status === sel.dataset.original) return;
  const fd = new FormData();
  fd.append('action', 'update_item_status'); fd.append('item_id', itemId); fd.append('status', status);
  try {
    const res = await fetch('organizer_actions.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      const row = sel.closest('tr'), badge = row.querySelector('.badge-status');
      badge.className = 'badge-status badge-' + status.toLowerCase();
      badge.textContent = status; row.dataset.status = status; sel.dataset.original = status;
    } else {
      showToast('Error: ' + data.message, 'danger');
      sel.value = sel.dataset.original || sel.options[0].value;
    }
  } catch(err) {
    showToast('Server error', 'danger');
    sel.value = sel.dataset.original || sel.options[0].value;
  }
}

async function updateLostStatus(reportId, status, sel) {
  if (status === sel.dataset.original) return;
  const fd = new FormData();
  fd.append('action', 'update_lost_status'); fd.append('report_id', reportId); fd.append('status', status);
  try {
    const res = await fetch('organizer_actions.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      const row = sel.closest('tr'), badge = row.querySelector('.badge-status');
      badge.className = 'badge-status badge-' + status.toLowerCase();
      badge.textContent = status; row.dataset.status = status; sel.dataset.original = status;
    } else {
      showToast('Error: ' + data.message, 'danger');
      sel.value = sel.dataset.original || sel.options[0].value;
    }
  } catch(err) {
    showToast('Server error', 'danger');
    sel.value = sel.dataset.original || sel.options[0].value;
  }
}

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

function viewClaimers(claims, itemName, itemId, itemImg) {
    document.getElementById('claimersModalTitle').textContent = `Claimers for: ${itemName}`;
    let html = `
    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-4 border">
        <img src="uploads/${itemImg}" class="rounded-3 shadow-sm" style="width:70px;height:70px;object-fit:cover">
        <div>
            <div class="fw-bold text-primary mb-1">FOUND ITEM REFERENCE</div>
            <div class="small text-muted">${itemName} (ID: #${itemId})</div>
        </div>
    </div>`;
    
    if (!claims || claims.length === 0) {
        html = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox display-5 d-block mb-2"></i>No claims found.</div>';
    } else {
        claims.forEach((c, i) => {
            const fullName = `${c.fname || ''} ${c.lname || ''}`.trim() || 'Unknown User';
            const date = new Date(c.claimed_at).toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            const status = c.status || 'pending';
            const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);

            html += `
            <div class="claimer-card mb-3 p-3 border rounded-3 bg-white shadow-sm">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="claimer-num">${i + 1}</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold d-flex align-items-center gap-2">
                            ${fullName}
                            <span class="text-muted small fw-normal">(ID: ${c.student_id || 'N/A'})</span>
                            <span class="badge status-badge-${status} small" style="font-size:.65rem; padding: 2px 8px;">${statusLabel}</span>
                        </div>
                        <small class="text-muted">${date}</small>
                    </div>
                </div>
                <div class="row g-2 small mb-2">
                    <div class="col-sm-6">
                        <span class="text-muted"><i class="bi bi-telephone me-1"></i></span>
                        <strong>${c.contact_number || 'No contact'}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted"><i class="bi bi-envelope me-1"></i></span>
                        ${c.email || 'No email'}
                    </div>
                </div>
                ${c.claim_message ? `
                <div class="claim-msg mb-2">
                    <i class="bi bi-chat-quote me-1 text-warning"></i>"${c.claim_message}"
                </div>` : ''}
                ${c.image_path ? `
                <div class="mt-2 text-center p-2 bg-light rounded mb-2" style="border:1.5px dashed #e2e8f0;">
                    <div class="small text-muted fw-bold mb-2">PROOF OF OWNERSHIP</div>
                    <img src="uploads/${c.image_path}" alt="Claim proof"
                         style="max-height:220px; max-width:100%; border-radius:10px; cursor:zoom-in; box-shadow:0 1px 10px rgba(0,0,0,0.1);"
                         onclick="window.open('uploads/${c.image_path}','_blank')">
                </div>` : ''}

                <div class="claimer-actions">
                    <button class="btn btn-outline-primary btn-claimer" onclick="toggleMessageInput(${c.user_id}, ${i})">
                        <i class="bi bi-chat-dots"></i> Message
                    </button>
                    
                    ${status === 'pending' ? `
                    <button class="btn btn-success btn-claimer" onclick="verifyClaimant(${c.id}, '${itemName}')">
                        <i class="bi bi-patch-check"></i> Verify Owner
                    </button>
                    <button class="btn btn-outline-danger btn-claimer" onclick="rejectClaimant(${c.id})">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    ` : ''}

                    ${status === 'verified' ? `
                    <button class="btn btn-primary btn-claimer" onclick="confirmReturn(${itemId}, ${c.id})">
                        <i class="bi bi-check-all"></i> Confirm Return
                    </button>
                    ` : ''}
                </div>

                <div id="msgInputArea_${i}" class="msg-input-area">
                    <textarea class="form-control form-control-sm mb-2" id="msgText_${i}" placeholder="Type your message to ${c.fname}..."></textarea>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-sm btn-light" onclick="toggleMessageInput(${c.user_id}, ${i})">Cancel</button>
                        <button class="btn btn-sm btn-primary" onclick="sendMessageToClaimer(${c.user_id}, ${i}, '${itemName.replace(/'/g, "\\'")}')">Send Message</button>
                    </div>
                </div>
            </div>`;
        });
    }
    
    document.getElementById('claimersListContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('claimersModal')).show();
}

function toggleMessageInput(userId, index) {
    const area = document.getElementById(`msgInputArea_${index}`);
    area.style.display = area.style.display === 'block' ? 'none' : 'block';
}

async function sendMessageToClaimer(receiverId, index, itemName) {
    const textEl = document.getElementById(`msgText_${index}`);
    const message = textEl.value.trim();
    if (!message) return;

    try {
        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('receiver_id', receiverId);
        fd.append('message', message);
        fd.append('item_name', itemName);

        const res = await fetch('claimer_actions.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Message sent!', 'success');
            textEl.value = '';
            toggleMessageInput(receiverId, index);
        } else {
            showToast('Error: ' + data.message, 'danger');
        }
    } catch (err) { showToast('Server error', 'danger'); }
}

async function verifyClaimant(claimId, itemName) {
    showConfirm({
        title: 'Verify Claimant?',
        msg: `Are you sure you want to verify this claimant as the owner of <strong>"${itemName}"</strong>?<br><br>This will reject all other claims for this item.`,
        type: 'success',
        confirmText: 'Verify & Approve',
        onConfirm: async () => {
            try {
                const fd = new FormData();
                fd.append('action', 'verify_claim');
                fd.append('claim_id', claimId);
                const res = await fetch('claimer_actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'danger');
                }
            } catch (err) { showToast('Server error', 'danger'); }
        }
    });
}

async function rejectClaimant(claimId) {
    showConfirm({
        title: 'Reject Claim?',
        msg: 'Are you sure you want to reject this claim?',
        type: 'danger',
        confirmText: 'Yes, Reject',
        onConfirm: async () => {
            try {
                const fd = new FormData();
                fd.append('action', 'reject_claim');
                fd.append('claim_id', claimId);
                const res = await fetch('claimer_actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'danger');
                }
            } catch (err) { showToast('Server error', 'danger'); }
        }
    });
}

async function confirmReturn(itemId, claimId) {
    showConfirm({
        title: 'Confirm Return?',
        msg: 'Mark this item as officially <strong>Returned</strong> to the owner?',
        type: 'success',
        confirmText: 'Confirm Returned',
        onConfirm: async () => {
            try {
                const fd = new FormData();
                fd.append('action', 'complete_return');
                fd.append('item_id', itemId);
                fd.append('claim_id', claimId);
                const res = await fetch('claimer_actions.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.message, 'danger');
                }
            } catch (err) { showToast('Server error', 'danger'); }
        }
    });
}

</script>
</body>
</html>