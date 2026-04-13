<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 2) {
    header('Location: auth.php'); exit;
}
include 'db.php';

$guard_name = trim($_SESSION['user_fname'] . ' ' . $_SESSION['user_lname']);
$initials   = strtoupper(substr($_SESSION['user_fname'],0,1) . substr($_SESSION['user_lname'],0,1));

$totalLost    = $pdo->query("SELECT COUNT(*) FROM lost_reports")->fetchColumn();
$totalFound   = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$totalClaimed = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Returned'")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Published'")->fetchColumn();
$pendingClaims= $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Claiming'")->fetchColumn();

$lostReports = $pdo->query("
    SELECT *, owner_name AS reporter_name, owner_contact AS contact_number
    FROM lost_reports ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$foundItems = $pdo->query("
    SELECT i.*, CONCAT(IFNULL(u.fname,''), ' ', IFNULL(u.lname,'')) AS logged_by
    FROM items i LEFT JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Items that are currently being claimed — for the Process Claim section
$claimingItems = $pdo->query("
    SELECT i.*,
           CONCAT(IFNULL(u.fname,''),' ',IFNULL(u.lname,'')) AS finder_name
    FROM items i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.status = 'Claiming'
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Pre-load claimers for each claiming item
$claimsByItem = [];
if (!empty($claimingItems)) {
    $ids = implode(',', array_column($claimingItems, 'id'));
    $claimers = $pdo->query("
        SELECT c.*, u.fname, u.lname, u.email, u.contact_number
        FROM claims c
        JOIN users u ON c.user_id = u.id
        WHERE c.item_id IN ($ids)
        ORDER BY c.claimed_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($claimers as $cl) {
        $claimsByItem[$cl['item_id']][] = $cl;
    }
}

$admins = $pdo->query("SELECT fname, lname, email FROM users WHERE type_id = 4")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guard Dashboard – FoundIt!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --sidebar-w:250px; --navy:#0b1d3a; --teal:#0d9488; --teal2:#14b8a6; }
    body { font-family:'DM Sans',sans-serif; background:#f1f5f9; margin:0; }

    /* Sidebar */
    .sidebar { position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-w); background:var(--navy); z-index:300; display:flex; flex-direction:column; box-shadow:4px 0 24px rgba(0,0,0,.18); transition:transform .3s; }
    .sidebar-brand { padding:20px 18px 16px; border-bottom:1px solid rgba(255,255,255,.07); }
    .sidebar-brand .brand-name { font-family:'Syne',sans-serif; font-weight:800; font-size:.98rem; color:#fff; line-height:1.2; }
    .sidebar-brand .brand-sub { font-size:.7rem; color:var(--teal2); margin-top:2px; }
    .sidebar-profile { padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; gap:10px; }
    .profile-avatar { width:38px; height:38px; border-radius:50%; background:var(--teal); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; flex-shrink:0; }
    .profile-name { font-size:.85rem; font-weight:600; color:#fff; line-height:1.3; }
    .profile-role { font-size:.68rem; color:rgba(255,255,255,.45); }
    .sidebar-menu { flex:1; padding:10px; overflow-y:auto; }
    .menu-label { font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.28); padding:10px 10px 4px; }
    .menu-btn { display:flex; align-items:center; gap:9px; width:100%; padding:9px 12px; border:none; background:transparent; color:rgba(255,255,255,.6); font-family:'DM Sans',sans-serif; font-size:.855rem; font-weight:500; border-radius:10px; cursor:pointer; transition:all .2s; text-align:left; }
    .menu-btn i { font-size:.95rem; width:18px; }
    .menu-btn .badge-pill { margin-left:auto; background:rgba(255,255,255,.12); color:#fff; font-size:.62rem; font-weight:700; padding:2px 7px; border-radius:20px; }
    .menu-btn:hover { background:rgba(255,255,255,.08); color:#fff; }
    .menu-btn.active { background:var(--teal); color:#fff; }
    .menu-btn.active .badge-pill { background:rgba(255,255,255,.25); }
    .sidebar-footer { padding:10px; border-top:1px solid rgba(255,255,255,.07); }

    /* Main */
    .main-wrap { margin-left:var(--sidebar-w); min-height:100vh; display:flex; flex-direction:column; }
    .topbar { background:#fff; border-bottom:1px solid #e2e8f0; padding:0 28px; height:62px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:200; box-shadow:0 1px 0 #e2e8f0; }
    .topbar-title { font-family:'Syne',sans-serif; font-weight:700; font-size:1.05rem; color:#0f172a; }
    .topbar-date { font-size:.8rem; color:#64748b; }
    .btn-hamburger { display:none; }
    .page-body { padding:24px 28px; flex:1; }
    .section { display:none; }
    .section.active { display:block; animation:fadeUp .3s ease; }
    @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

    /* Stat cards */
    .stat-card { border:none; border-radius:16px; transition:transform .2s,box-shadow .2s; }
    .stat-card:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,.1) !important; }
    .stat-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .stat-num { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; line-height:1; }
    .stat-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }

    /* Cards */
    .content-card { border:none; border-radius:16px; overflow:hidden; }
    .table thead th { background:#f8fafc; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; padding:11px 16px; border:none; }
    .table tbody td { padding:11px 16px; vertical-align:middle; font-size:.86rem; border-color:#f1f5f9; }
    .table tbody tr:hover { background:#f8fafc; }

    /* Status badges */
    .sb { padding:3px 10px; border-radius:20px; font-size:.68rem; font-weight:700; text-transform:uppercase; display:inline-block; }
    .sb-lost{background:#fee2e2;color:#dc2626} .sb-found{background:#dcfce7;color:#16a34a}
    .sb-pending{background:#fef9c3;color:#ca8a04} .sb-published{background:#dbeafe;color:#1d4ed8}
    .sb-claiming{background:#ede9fe;color:#7c3aed} .sb-returned{background:#dcfce7;color:#16a34a}
    .sb-resolved{background:#dcfce7;color:#16a34a} .sb-matching{background:#fef3c7;color:#d97706}

    /* Forms */
    .form-control,.form-select { border:1.5px solid #e2e8f0; border-radius:10px; padding:9px 13px; font-size:.875rem; transition:all .2s; }
    .form-control:focus,.form-select:focus { border-color:var(--teal); box-shadow:0 0 0 3px rgba(13,148,136,.12); }
    .btn-teal { background:var(--teal); color:#fff; border:none; border-radius:10px; font-weight:600; padding:9px 20px; transition:all .2s; }
    .btn-teal:hover { background:var(--teal2); color:#fff; transform:translateY(-1px); }
    .search-bar { border:none; background:transparent; }
    .search-bar:focus { box-shadow:none; border:none; }

    /* Admin contact */
    .admin-row { display:flex; align-items:center; gap:10px; padding:10px 12px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; }
    .admin-av { width:36px; height:36px; border-radius:50%; background:var(--teal); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.82rem; flex-shrink:0; }
    .tip-card { background:linear-gradient(135deg,#f0fdfa,#ccfbf1); border:none; border-radius:14px; }
    .tip-card li { font-size:.86rem; color:#0f766e; margin-bottom:6px; }
    .mini-item { display:flex; align-items:center; gap:8px; padding:8px 10px; background:#f8fafc; border-radius:9px; border:1px solid #e8f0f8; }

    /* ── Process Claim styles ── */
    .claim-item-card {
        border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;
        margin-bottom:16px; background:#fff;
    }
    .claim-item-header {
        display:flex; align-items:center; gap:14px;
        padding:14px 18px; background:#f8fafc;
        border-bottom:1px solid #e2e8f0; cursor:pointer;
    }
    .claim-item-header img { width:56px; height:56px; object-fit:cover; border-radius:10px; flex-shrink:0; }
    .claim-item-header .item-title { font-weight:700; font-size:.92rem; color:#1e293b; }
    .claim-item-header .item-meta { font-size:.75rem; color:#64748b; margin-top:2px; }
    .claimer-row-card {
        display:flex; align-items:flex-start; gap:14px;
        padding:14px 18px; border-bottom:1px solid #f1f5f9;
        transition:background .15s;
    }
    .claimer-row-card:last-child { border-bottom:none; }
    .claimer-row-card:hover { background:#f8fafc; }
    .claimer-num { width:28px; height:28px; border-radius:50%; background:#7c3aed; color:#fff; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; flex-shrink:0; margin-top:2px; }
    .claimer-info .cname { font-weight:700; font-size:.88rem; }
    .claimer-info .cmeta { font-size:.76rem; color:#64748b; margin-top:2px; }
    .claimer-info .cmsg { font-size:.78rem; font-style:italic; color:#94a3b8; background:#f8fafc; border-left:3px solid #c4b5fd; padding:6px 10px; border-radius:0 8px 8px 0; margin-top:6px; }
    .claimer-proof img { max-height:80px; border-radius:8px; object-fit:cover; cursor:pointer; border:1.5px solid #e2e8f0; margin-top:6px; }
    .btn-mark-returned { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border:none; border-radius:10px; padding:7px 16px; font-size:.8rem; font-weight:700; cursor:pointer; transition:all .2s; flex-shrink:0; }
    .btn-mark-returned:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(22,163,74,.3); }
    .empty-claims { text-align:center; padding:60px 20px; color:#94a3b8; }
    .empty-claims i { font-size:3rem; display:block; margin-bottom:12px; }

    @media(max-width:768px) {
      .sidebar{transform:translateX(-100%)} .sidebar.show{transform:translateX(0)}
      .main-wrap{margin-left:0} .btn-hamburger{display:inline-flex}
      .page-body{padding:16px}
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-2 mb-1">
      <i class="bi bi-shield-check fs-5" style="color:var(--teal2)"></i>
      <span class="brand-name">Lost &amp; Found</span>
    </div>
    <div class="brand-sub">BISU Candijay · Guard Portal</div>
  </div>
  <div class="sidebar-profile">
    <div class="profile-avatar"><?= $initials ?></div>
    <div>
      <div class="profile-name"><?= htmlspecialchars($guard_name) ?></div>
      <div class="profile-role"><i class="bi bi-shield me-1"></i>Security Guard</div>
    </div>
  </div>
  <div class="sidebar-menu">
    <div class="menu-label">Navigation</div>
    <button class="menu-btn active" onclick="goTo('overview',this)">
      <i class="bi bi-grid-1x2"></i> Overview
    </button>
    <button class="menu-btn" onclick="goTo('lost-reports',this)">
      <i class="bi bi-exclamation-circle"></i> Lost Reports
      <?php if($totalLost>0): ?><span class="badge-pill"><?= $totalLost ?></span><?php endif; ?>
    </button>
    <button class="menu-btn" onclick="goTo('found-items',this)">
      <i class="bi bi-box-seam"></i> Found Items
      <span class="badge-pill"><?= $totalFound ?></span>
    </button>
    <div class="menu-label mt-2">Actions</div>
    <button class="menu-btn" onclick="goTo('log-item',this)">
      <i class="bi bi-plus-circle"></i> Log Found Item
    </button>
    <button class="menu-btn" onclick="goTo('process-claim',this)">
      <i class="bi bi-person-check"></i> Process Claim
      <?php if($pendingClaims>0): ?><span class="badge-pill"><?= $pendingClaims ?></span><?php endif; ?>
    </button>
    <button class="menu-btn" onclick="goTo('contact-admin',this)">
      <i class="bi bi-headset"></i> Contact Admin
    </button>
  </div>
  <div class="sidebar-footer">
    <a href="logout.php" class="menu-btn text-decoration-none" style="color:#fca5a5 !important">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-wrap">
  <header class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary btn-hamburger" onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="topbar-title" id="topbarTitle">Overview</div>
        <div class="topbar-date"><i class="bi bi-calendar3 me-1"></i><?= date('l, F d, Y') ?></div>
      </div>
    </div>
    <span class="badge rounded-pill px-3 py-2" style="background:#dcfce7;color:#16a34a;font-size:.78rem">
      <i class="bi bi-circle-fill me-1" style="font-size:.45rem"></i>On Duty
    </span>
  </header>

  <div class="page-body">

    <!-- OVERVIEW -->
    <div id="overview" class="section active">
      <div class="row g-3 mb-4">
        <?php
        $stats = [
          ['Lost Reports',$totalLost,'#fee2e2','#dc2626','bi-exclamation-circle'],
          ['Found Items',$totalFound,'#fef9c3','#ca8a04','bi-box-seam'],
          ['Returned',$totalClaimed,'#dcfce7','#16a34a','bi-check-circle'],
          ['Pending Claims',$pendingClaims,'#ede9fe','#7c3aed','bi-person-check'],
        ];
        foreach($stats as [$lbl,$val,$bg,$col,$ico]): ?>
        <div class="col-xl-3 col-md-6">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="bi <?= $ico ?>"></i></div>
              <div><div class="stat-lbl"><?= $lbl ?></div><div class="stat-num"><?= $val ?></div></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Recent Lost Reports -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif"><i class="bi bi-clock-history me-2" style="color:var(--teal)"></i>Recent Lost Reports</h5>
        <button class="btn btn-sm btn-teal" onclick="goTo('lost-reports',document.querySelectorAll('.menu-btn')[1])">View All <i class="bi bi-arrow-right ms-1"></i></button>
      </div>
      <div class="card content-card shadow-sm mb-4">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Item</th><th>Reporter</th><th>Last Seen</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach(array_slice($lostReports,0,5) as $r): ?>
              <tr>
                <td><div class="fw-semibold"><?= htmlspecialchars($r['item_name']) ?></div><small class="text-muted"><?= htmlspecialchars(substr($r['description']??'',0,45)) ?></small></td>
                <td><?= htmlspecialchars($r['reporter_name']??'—') ?></td>
                <td><small><?= htmlspecialchars($r['last_seen_location']??'N/A') ?></small></td>
                <td><small class="text-muted"><?= date('M d, Y',strtotime($r['created_at'])) ?></small></td>
                <td><span class="sb sb-<?= strtolower($r['status']??'lost') ?>"><?= $r['status']??'Lost' ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($lostReports)): ?><tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No lost reports yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Found Items -->
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif"><i class="bi bi-box-seam me-2 text-warning"></i>Recent Found Items</h5>
        <button class="btn btn-sm btn-teal" onclick="goTo('found-items',document.querySelectorAll('.menu-btn')[2])">View All <i class="bi bi-arrow-right ms-1"></i></button>
      </div>
      <div class="card content-card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Item</th><th>Location Found</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach(array_slice($foundItems,0,5) as $item): ?>
              <tr>
                <td><div class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></div></td>
                <td><small><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($item['found_location']??'N/A') ?></small></td>
                <td><small class="text-muted"><?= date('M d, Y',strtotime($item['created_at'])) ?></small></td>
                <td><span class="sb sb-<?= strtolower($item['status']??'pending') ?>"><?= $item['status']??'Pending' ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($foundItems)): ?><tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No found items yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- LOST REPORTS -->
    <div id="lost-reports" class="section">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif"><i class="bi bi-exclamation-circle me-2 text-danger"></i>All Lost Reports</h5>
      </div>
      <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
        <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" class="form-control search-bar" id="lostSearch" placeholder="Search item, reporter, or location…"></div>
      </div></div>
      <div class="card content-card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="lostTable">
            <thead><tr><th>#</th><th>Item</th><th>Reporter</th><th>Contact</th><th>Last Seen</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach($lostReports as $r): ?>
              <tr>
                <td class="text-muted fw-semibold">#<?= $r['id'] ?></td>
                <td><div class="fw-semibold"><?= htmlspecialchars($r['item_name']) ?></div><small class="text-muted"><?= htmlspecialchars(substr($r['description']??'',0,50)) ?></small></td>
                <td><?= htmlspecialchars($r['reporter_name']??'—') ?></td>
                <td><small><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($r['owner_contact']??'N/A') ?></small></td>
                <td><small><?= htmlspecialchars($r['last_seen_location']??'N/A') ?></small></td>
                <td><small class="text-muted"><?= date('M d, Y',strtotime($r['created_at'])) ?></small></td>
                <td><span class="sb sb-<?= strtolower($r['status']??'lost') ?>"><?= $r['status']??'Lost' ?></span></td>
                <td><button class="btn btn-sm btn-outline-success" onclick="prefillLog('<?= htmlspecialchars(addslashes($r['item_name'])) ?>',<?= $r['id'] ?>)"><i class="bi bi-plus me-1"></i>Log Found</button></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($lostReports)): ?><tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No lost reports yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- FOUND ITEMS -->
    <div id="found-items" class="section">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif"><i class="bi bi-box-seam me-2 text-warning"></i>Found Items</h5>
        <button class="btn btn-sm btn-teal" onclick="goTo('log-item',document.querySelectorAll('.menu-btn')[3])"><i class="bi bi-plus me-1"></i>Log New Item</button>
      </div>
      <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
        <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" class="form-control search-bar" id="foundSearch" placeholder="Search items…"></div>
      </div></div>
      <div class="card content-card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0" id="foundTable">
            <thead><tr><th>#</th><th>Item</th><th>Location Found</th><th>Date</th><th>Logged By</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach($foundItems as $item): ?>
              <tr>
                <td class="text-muted fw-semibold">#<?= $item['id'] ?></td>
                <td><div class="fw-semibold"><?= htmlspecialchars($item['item_name']) ?></div><small class="text-muted"><?= htmlspecialchars(substr($item['description']??'',0,50)) ?></small></td>
                <td><small><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($item['found_location']??'N/A') ?></small></td>
                <td><small class="text-muted"><?= date('M d, Y',strtotime($item['created_at'])) ?></small></td>
                <td><small><?= htmlspecialchars(trim($item['logged_by'])?: 'Guard') ?></small></td>
                <td><span class="sb sb-<?= strtolower($item['status']??'pending') ?>"><?= $item['status']??'Pending' ?></span></td>
                <td>
                  <?php if(!in_array(strtolower($item['status']??''),['returned','claimed'])): ?>
                  <button class="btn btn-sm btn-teal" onclick="markClaimed(<?= $item['id'] ?>)"><i class="bi bi-check2 me-1"></i>Mark Claimed</button>
                  <?php else: ?><span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i>Done</span><?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($foundItems)): ?><tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No found items logged yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- LOG FOUND ITEM -->
    <div id="log-item" class="section">
      <h5 class="fw-bold mb-4" style="font-family:'Syne',sans-serif"><i class="bi bi-plus-circle me-2" style="color:var(--teal)"></i>Log a Found Item</h5>
      <div id="logAlert" class="mb-3"></div>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="card content-card shadow-sm p-4">
            <form id="logItemForm" onsubmit="submitLogItem(event)">
              <div class="mb-3"><label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label><input type="text" id="logItemName" class="form-control" placeholder="e.g. Black wallet, iPhone 13" required></div>
              <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea id="logDescription" class="form-control" rows="3" placeholder="Color, brand, distinguishing features..."></textarea></div>
              <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label fw-semibold">Location Found <span class="text-danger">*</span></label><input type="text" id="logLocation" class="form-control" placeholder="e.g. Library, Canteen" required></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Date & Time Found <span class="text-danger">*</span></label><input type="datetime-local" id="logDateFound" class="form-control" required></div>
              </div>
              <div class="mb-3"><label class="form-label fw-semibold">Turned in by <span class="text-muted fw-normal">(optional)</span></label><input type="text" id="logTurnedBy" class="form-control" placeholder="Name of student who found it"></div>
              <div class="mb-4"><label class="form-label fw-semibold">Matched Lost Report # <span class="text-muted fw-normal">(optional)</span></label><input type="number" id="logLostReportId" class="form-control" placeholder="Enter ID if it matches a lost report"><div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>Check Lost Reports tab to find a matching report.</div></div>
              <button type="submit" class="btn btn-teal w-100 py-2"><i class="bi bi-plus-circle me-2"></i>Log Found Item</button>
            </form>
          </div>
        </div>
        <div class="col-lg-5 d-flex flex-column gap-3">
          <div class="card tip-card p-4">
            <h6 class="fw-bold mb-3" style="color:var(--teal)"><i class="bi bi-lightbulb me-2"></i>Tips for Logging</h6>
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Be specific with the description</li>
              <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Note the exact location it was found</li>
              <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Check Lost Reports for a match first</li>
              <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Record who turned the item in</li>
              <li><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Store the item at the Guard House</li>
            </ul>
          </div>
          <div class="card content-card shadow-sm p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-collection me-2 text-primary"></i>Recent Logs</h6>
            <?php if(empty($foundItems)): ?>
            <p class="text-muted small mb-0">No items logged yet.</p>
            <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach(array_slice($foundItems,0,4) as $fi): ?>
              <div class="mini-item">
                <i class="bi bi-box-seam text-warning"></i>
                <div class="flex-grow-1"><div style="font-size:.82rem;font-weight:600"><?= htmlspecialchars($fi['item_name']) ?></div><div style="font-size:.7rem;color:#94a3b8"><?= date('M d, Y',strtotime($fi['created_at'])) ?></div></div>
                <span class="sb sb-<?= strtolower($fi['status']??'pending') ?>"><?= $fi['status'] ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ── PROCESS CLAIM ──────────────────────────────────────────────────── -->
    <div id="process-claim" class="section">
      <div class="d-flex align-items-center justify-content-between mb-1">
        <h5 class="fw-bold mb-0" style="font-family:'Syne',sans-serif">
          <i class="bi bi-person-check me-2" style="color:var(--teal)"></i>Process Claim
        </h5>
        <span class="badge rounded-pill px-3 py-2" style="background:#ede9fe;color:#7c3aed;font-size:.78rem">
          <?= $pendingClaims ?> item<?= $pendingClaims != 1 ? 's' : '' ?> being claimed
        </span>
      </div>
      <p class="text-muted mb-4" style="font-size:.85rem">
        <i class="bi bi-info-circle me-1"></i>
        When a student comes to the guard house to collect an item, verify their identity against the claimers listed below, then mark the correct person as <strong>Returned</strong>.
      </p>

      <?php if(empty($claimingItems)): ?>
      <div class="empty-claims">
        <i class="bi bi-inbox"></i>
        <div class="fw-bold mb-1">No items being claimed right now</div>
        <small>Items will appear here when a student submits a claim and the status is set to "Claiming".</small>
      </div>
      <?php else: ?>

      <!-- Search -->
      <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
        <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" class="form-control search-bar" id="claimSearch" placeholder="Search item name…" oninput="searchClaims(this.value)"></div>
      </div></div>

      <div id="claimItemsList">
        <?php foreach($claimingItems as $ci):
            $itemClaims = $claimsByItem[$ci['id']] ?? [];
        ?>
        <div class="claim-item-card" data-item-name="<?= strtolower(htmlspecialchars($ci['item_name'])) ?>">
          <!-- Item header -->
          <div class="claim-item-header" onclick="toggleClaimers(<?= $ci['id'] ?>)">
            <img src="uploads/<?= htmlspecialchars($ci['image_path']) ?>" alt="<?= htmlspecialchars($ci['item_name']) ?>">
            <div class="flex-grow-1">
              <div class="item-title"><?= htmlspecialchars($ci['item_name']) ?></div>
              <div class="item-meta">
                <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($ci['found_location']) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($ci['storage_location']) ?>
              </div>
              <div class="item-meta mt-1">
                <i class="bi bi-people me-1" style="color:#7c3aed"></i>
                <strong style="color:#7c3aed"><?= count($itemClaims) ?> claimer<?= count($itemClaims) != 1 ? 's' : '' ?></strong>
              </div>
            </div>
            <i class="bi bi-chevron-down text-muted" id="chevron-<?= $ci['id'] ?>"></i>
          </div>

          <!-- Claimers list (collapsed by default) -->
          <div id="claimers-<?= $ci['id'] ?>" style="display:none">
            <?php if(empty($itemClaims)): ?>
            <div class="p-4 text-center text-muted small">No claimers found for this item yet.</div>
            <?php else: ?>
            <?php foreach($itemClaims as $idx => $cl):
                $clName = trim(($cl['fname'] ?? '') . ' ' . ($cl['lname'] ?? '')) ?: 'Unknown';
            ?>
            <div class="claimer-row-card">
              <div class="claimer-num"><?= $idx + 1 ?></div>
              <div class="claimer-info flex-grow-1">
                <div class="cname"><?= htmlspecialchars($clName) ?></div>
                <div class="cmeta">
                  <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($cl['contact_number'] ?? '—') ?>
                  &nbsp;·&nbsp;
                  <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($cl['email'] ?? '—') ?>
                </div>
                <div class="cmeta"><i class="bi bi-clock me-1"></i>Claimed <?= date('M d, Y g:i A', strtotime($cl['claimed_at'])) ?></div>
                <?php if(!empty($cl['claim_message'])): ?>
                <div class="cmsg">"<?= htmlspecialchars($cl['claim_message']) ?>"</div>
                <?php endif; ?>
                <?php if(!empty($cl['image_path'])): ?>
                <div class="claimer-proof">
                  <div class="cmeta mb-1"><i class="bi bi-image me-1"></i>Photo proof:</div>
                  <img src="uploads/<?= htmlspecialchars($cl['image_path']) ?>"
                       onclick="window.open('uploads/<?= htmlspecialchars($cl['image_path']) ?>','_blank')"
                       alt="Proof photo">
                </div>
                <?php endif; ?>
              </div>
              <!-- Mark this specific claimer as the rightful owner -->
              <button class="btn-mark-returned"
                      onclick="markReturned(<?= $ci['id'] ?>, <?= $cl['user_id'] ?>, '<?= htmlspecialchars(addslashes($clName)) ?>', '<?= htmlspecialchars(addslashes($ci['item_name'])) ?>', this)">
                <i class="bi bi-check2-circle me-1"></i>Mark Returned
              </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- CONTACT ADMIN -->
    <div id="contact-admin" class="section">
      <h5 class="fw-bold mb-4" style="font-family:'Syne',sans-serif"><i class="bi bi-headset me-2" style="color:var(--teal)"></i>Contact Admin</h5>
      <div class="row g-4">
        <div class="col-lg-5">
          <div class="card content-card shadow-sm p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-people me-2" style="color:var(--teal)"></i>Admin Contacts</h6>
            <?php if(empty($admins)): ?><p class="text-muted small">No admin accounts found.</p>
            <?php else: ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach($admins as $a): ?>
              <div class="admin-row">
                <div class="admin-av"><?= strtoupper(substr($a['fname'],0,1).substr($a['lname'],0,1)) ?></div>
                <div class="flex-grow-1">
                  <div class="fw-semibold" style="font-size:.875rem"><?= htmlspecialchars($a['fname'].' '.$a['lname']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($a['email']) ?></div>
                </div>
                <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope"></i></a>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="card content-card shadow-sm p-4 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2" style="color:var(--teal)"></i>Send In-App Message</h6>
            <div id="msgAlert" class="mb-3"></div>
            <form onsubmit="sendMessage(event)">
              <div class="mb-3"><label class="form-label fw-semibold">Subject</label><input type="text" id="msgSubject" class="form-control" placeholder="e.g. Unclaimed item since last week" required></div>
              <div class="mb-3"><label class="form-label fw-semibold">Message</label><textarea id="msgBody" class="form-control" rows="5" placeholder="Describe the situation..." required></textarea></div>
              <button type="submit" class="btn btn-teal w-100 py-2"><i class="bi bi-send me-2"></i>Send Message to Admin</button>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div><!-- page-body -->
</div><!-- main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('logDateFound').value = new Date().toISOString().slice(0,16);

  const pageTitles = {
    'overview':'Overview','lost-reports':'Lost Reports','found-items':'Found Items',
    'log-item':'Log Found Item','process-claim':'Process Claim','contact-admin':'Contact Admin'
  };

  function goTo(id, btn) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    if(btn) btn.classList.add('active');
    document.getElementById('topbarTitle').textContent = pageTitles[id] || id;
    window.scrollTo({top:0,behavior:'smooth'});
    document.getElementById('sidebar').classList.remove('show');
  }

  function prefillLog(name, id) {
    document.getElementById('logItemName').value = name;
    document.getElementById('logLostReportId').value = id;
    goTo('log-item', document.querySelectorAll('.menu-btn')[3]);
  }

  // Search
  document.getElementById('lostSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#lostTable tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q)?'':'none');
  });
  document.getElementById('foundSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#foundTable tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q)?'':'none');
  });

  // Toggle claimer list per item
  function toggleClaimers(itemId) {
    const panel   = document.getElementById('claimers-' + itemId);
    const chevron = document.getElementById('chevron-' + itemId);
    const open    = panel.style.display === 'none';
    panel.style.display   = open ? 'block' : 'none';
    chevron.className = open ? 'bi bi-chevron-up text-muted' : 'bi bi-chevron-down text-muted';
  }

  // Search claim items
  function searchClaims(q) {
    document.querySelectorAll('#claimItemsList .claim-item-card').forEach(card => {
      card.style.display = card.dataset.itemName.includes(q.toLowerCase()) ? '' : 'none';
    });
  }

  // Mark returned — confirms with guard, then posts to guard_actions.php
  async function markReturned(itemId, claimerId, claimerName, itemName, btn) {
    if (!confirm(`✅ Confirm: "${claimerName}" is the rightful owner of "${itemName}"?\n\nThis will mark the item as Returned.`)) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>Saving…';

    const fd = new FormData();
    fd.append('action',     'mark_returned');
    fd.append('item_id',    itemId);
    fd.append('claimer_id', claimerId);

    try {
      const r = await fetch('guard_actions.php', {method:'POST', body:fd});
      const d = await r.json();
      if(d.success) {
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Returned!';
        btn.style.background = '#16a34a';
        // Show Print Slip button next to it
        const printBtn = document.createElement('button');
        printBtn.className = 'btn-mark-returned';
        printBtn.style.background = 'linear-gradient(135deg,#3b82f6,#1d4ed8)';
        printBtn.style.marginLeft = '8px';
        printBtn.innerHTML = '<i class="bi bi-printer me-1"></i>Print Slip';
        printBtn.onclick = () => window.open(`claim_slip.php?item_id=${itemId}`, '_blank');
        btn.parentNode.insertBefore(printBtn, btn.nextSibling);
        // Reload after a delay
        setTimeout(() => location.reload(), 3000);
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Mark Returned';
        alert('Error: ' + d.message);
      }
    } catch(err) {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Mark Returned';
      alert('Server error. Please try again.');
    }
  }

  async function submitLogItem(e) {
    e.preventDefault();
    const alertDiv = document.getElementById('logAlert');
    const fd = new FormData();
    fd.append('action',         'log_found');
    fd.append('item_name',      document.getElementById('logItemName').value);
    fd.append('description',    document.getElementById('logDescription').value);
    fd.append('found_location', document.getElementById('logLocation').value);
    fd.append('date_found',     document.getElementById('logDateFound').value);
    fd.append('turned_by',      document.getElementById('logTurnedBy').value);
    fd.append('lost_report_id', document.getElementById('logLostReportId').value);
    try {
      const r = await fetch('guard_actions.php', {method:'POST', body:fd});
      const d = await r.json();
      alertDiv.innerHTML = `<div class="alert alert-${d.success?'success':'danger'} rounded-3 border-0">${d.message}</div>`;
      if(d.success) {
        document.getElementById('logItemForm').reset();
        document.getElementById('logDateFound').value = new Date().toISOString().slice(0,16);
        setTimeout(() => location.reload(), 1400);
      }
    } catch(err) {
      alertDiv.innerHTML = `<div class="alert alert-danger rounded-3 border-0">Server error. Please try again.</div>`;
    }
  }

  async function markClaimed(id) {
    if(!confirm('Mark this item as claimed by the owner?')) return;
    const fd = new FormData();
    fd.append('action', 'mark_claimed');
    fd.append('item_id', id);
    try {
      const r = await fetch('guard_actions.php', {method:'POST', body:fd});
      const d = await r.json();
      if(d.success) location.reload();
      else alert(d.message);
    } catch(err) { alert('Server error'); }
  }

  async function sendMessage(e) {
    e.preventDefault();
    const alertDiv = document.getElementById('msgAlert');
    const fd = new FormData();
    fd.append('action',  'send_message');
    fd.append('subject', document.getElementById('msgSubject').value);
    fd.append('body',    document.getElementById('msgBody').value);
    try {
      const r = await fetch('guard_actions.php', {method:'POST', body:fd});
      const d = await r.json();
      alertDiv.innerHTML = `<div class="alert alert-${d.success?'success':'danger'} rounded-3 border-0">${d.success?'✅ Message sent to admin!':d.message}</div>`;
      if(d.success) { document.getElementById('msgSubject').value=''; document.getElementById('msgBody').value=''; }
    } catch(err) {
      alertDiv.innerHTML = `<div class="alert alert-danger rounded-3 border-0">Server error.</div>`;
    }
  }
</script>
</body>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
</html>