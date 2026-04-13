<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4,6])) {
    // only admins (4) and organizers (6)
    header("Location: auth.php"); exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include 'db.php'; 

if (isset($_POST['link_report'])) {
    $reportId = $_POST['report_id'];
    $itemId   = $_POST['item_id'];
    
    // 1. Update Lost Report Status
    $stmt = $pdo->prepare("UPDATE lost_reports SET status = 'Matching', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reportId]);
    
    // 2. Link Item to Report
    $stmt = $pdo->prepare("UPDATE items SET lost_report_id = ?, status = 'Claiming', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reportId, $itemId]);
    
    // 3. Send Notification Message
    $report = $pdo->prepare("SELECT user_id, item_name FROM lost_reports WHERE id = ?");
    $report->execute([$reportId]);
    $reportData = $report->fetch();
    
    if ($reportData && $reportData['user_id']) {
        $foundItem = $pdo->prepare("SELECT item_name FROM items WHERE id = ?");
        $foundItem->execute([$itemId]);
        $foundData = $foundItem->fetch();
        
        $msgBody = "Great news! An administrator has matched your lost report for \"" . $reportData['item_name'] . "\" with a found item in our database: \"" . ($foundData['item_name'] ?? 'Found Item') . "\". \n\nPlease check your report details for more information or contact the SSG office.";
        
        $msgStmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $msgStmt->execute([$_SESSION['user_id'], $reportData['user_id'], 'Potential Match Found', $msgBody]);
    }
    
    echo json_encode(['success' => true]);
    exit();
}

if (isset($_POST['update_status'])) {
    $allowedStatuses = ['Lost', 'Matching', 'Resolved', 'Pending'];
    if (in_array($_POST['new_status'], $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE lost_reports SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_POST['new_status'], $_POST['report_id']]);
    }
    header("Location: manage_lost.php?msg=Status Updated Successfully&type=success"); exit();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM lost_reports WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_lost.php?msg=Report Deleted&type=success"); exit();
}

$reports = $pdo->query("SELECT * FROM lost_reports ORDER BY created_at DESC")->fetchAll();

$stats = ['Pending' => 0, 'Lost' => 0, 'Matching' => 0, 'Resolved' => 0];
foreach ($reports as $report) {
    $s = ucfirst(strtolower(trim($report['status'])));
    if (isset($stats[$s])) $stats[$s]++;
}
$totalReports = count($reports);
$untracked = 0;
foreach($reports as $r) {
    $s = ucfirst(strtolower(trim($r['status'])));
    if (!isset($stats[$s])) $untracked++;
}
$resolvedRate = $totalReports > 0 ? round(($stats['Resolved'] / $totalReports) * 100) : 0;

$allFinders = [];
$findersCheck = $pdo->query("SHOW TABLES LIKE 'lost_contacts'")->fetch();
if ($findersCheck) {
    $findersStmt = $pdo->query("
        SELECT lc.*, u.fname, u.lname, u.email AS user_email, u.contact_number AS user_contact
        FROM lost_contacts lc
        LEFT JOIN users u ON lc.finder_user_id = u.id
        ORDER BY lc.created_at DESC
    ");
    foreach ($findersStmt->fetchAll(PDO::FETCH_ASSOC) as $finder) {
        $allFinders[$finder['report_id']][] = $finder;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lost Reports | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Shared styles are in admin_header.php */
        .quick-stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .quick-stat-item {
            flex: 1; background: rgba(255,255,255,0.15) !important;
            padding: 1rem; border-radius: 12px; text-align: center; color: white;
        }
        .quick-stat-item h3 { margin: 0; font-size: 2rem; font-weight: 700; }
        .quick-stat-item small { opacity: 0.9; font-size: 0.85rem; }
        
        .search-filter-bar { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: var(--admin-card-shadow); }
        .item-description { max-width: 300px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .contact-info { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .contact-info i { color: var(--admin-blue); }
        .detail-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        .detail-section h6 { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 8px; }
        .extra-field-badge { display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 8px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; margin: 3px; }

        /* Finder badge */
        .finder-count-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 20px; padding: 4px 10px;
            font-size: 0.78rem; font-weight: 700; color: #1e40af;
            cursor: pointer; transition: all 0.2s;
        }
        .finder-count-badge:hover { background: #dbeafe; border-color: #93c5fd; }
        .finder-count-badge.none { background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; cursor: default; }

        /* Finder cards */
        .finder-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; background: #fff; transition: box-shadow 0.2s; }
        .finder-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .finder-card .finder-num { width: 28px; height: 28px; border-radius: 50%; background: #dc2626; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; }
        .finder-card .finder-msg { background: #f8fafc; border-left: 3px solid #bfdbfe; border-radius: 0 8px 8px 0; padding: 8px 12px; font-size: 0.82rem; font-style: italic; color: #475569; margin-top: 8px; }

        .btn-msg-finder {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, var(--admin-blue), #0a4ab2);
            color: #fff !important; border: none; border-radius: 20px;
            padding: 5px 14px; font-size: 0.78rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s;  
        }

        /* ── Card Grid System ────────────────────────────────── */
        .report-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
        .report-card { 
            background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
            display: flex; flex-direction: column; height: 100%;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .report-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); }
        .card-img-wrapper { position: relative; height: 180px; overflow: hidden; background: #f1f5f9; }
        .card-img-wrapper .report-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
        .report-card:hover .report-img { transform: scale(1.05); }
        .card-status-badge { 
             position: absolute; top: 12px; right: 12px; z-index: 2;
             padding: 6px 12px; border-radius: 30px; font-size: 0.72rem; font-weight: 700;
             backdrop-filter: blur(8px); box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .match-glow-indicator {
            position: absolute; top: 12px; left: 12px; z-index: 2;
            background: rgba(16, 185, 129, 0.9); color: #fff;
            padding: 4px 10px; border-radius: 30px; font-size: 0.68rem; font-weight: 700;
            display: flex; align-items: center; gap: 4px;
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .report-card-body { padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column; }
        .report-card-footer { padding: 1rem 1.25rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        
        .owner-info { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .owner-av { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; }
        
        .location-tag { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; color: #64748b; margin-top: 6px; }
        .status-Lost { background: rgba(239, 68, 68, 0.9); color: #fff; }
        .status-Matching { background: rgba(245, 158, 11, 0.9); color: #fff; }
        .status-Pending { background: rgba(100, 116, 139, 0.9); color: #fff; }
        .status-Resolved { background: rgba(16, 185, 129, 0.9); color: #fff; }

        /* ── Modal Navigation & Timeline ─────────────────────── */
        .modal-navigation .nav-pills .nav-link { color: #64748b; font-size: 0.85rem; border: 1.5px solid transparent; transition: all 0.2s; }
        .modal-navigation .nav-pills .nav-link:hover { background: rgba(0,0,0,0.03); }
        .modal-navigation .nav-pills .nav-link.active { background: #fee2e2 !important; color: #dc2626 !important; border-color: #fecaca; }
        
        .timeline { position: relative; padding-left: 10px; }
        .timeline-item { position: relative; }
        .timeline-item.active .timeline-dot { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2); }
        .timeline-item.pending .timeline-dot { opacity: 0.6; }
        .timeline-item:last-child .timeline-line { display: none; }
        
        .btn-xs { padding: 2px 8px; font-size: 0.7rem; font-weight: 600; }
    </style>
</head>
<body>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<?php include 'admin_header.php'; ?>

<div class="container-fluid px-4 mt-4">
    <div class="admin-page-header">
        <div>
            <h2 class="fw-bold mb-2"><i class="bi bi-search me-2"></i>Lost Item Reports</h2>
            <p class="mb-0 opacity-75">Review and manage items reported missing by students</p>
        </div>
        <div class="quick-stats mt-4">
            <div class="quick-stat-item"><h3><?= $stats['Pending'] ?></h3><small><i class="bi bi-hourglass-split me-1"></i>New/Pending</small></div>
            <div class="quick-stat-item"><h3><?= $stats['Lost'] ?></h3><small><i class="bi bi-exclamation-circle me-1"></i>Published Lost</small></div>
            <div class="quick-stat-item"><h3><?= $stats['Matching'] ?></h3><small><i class="bi bi-link-45deg me-1"></i>Matching</small></div>
            <div class="quick-stat-item"><h3><?= $stats['Resolved'] ?></h3><small><i class="bi bi-check-circle me-1"></i>Resolved</small></div>
            <div class="quick-stat-item"><h3><?= $resolvedRate ?>%</h3><small><i class="bi bi-graph-up me-1"></i>Success Rate</small></div>
        </div>
    </div>

    <!-- The global showToast() handles notifications now, but we'll keep the PHP-to-Toast logic if needed for redirects -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_GET['msg'])): ?>
                showToast("<?= htmlspecialchars($_GET['msg']) ?>", "<?= ($_GET['type']??'success') === 'success' ? 'success' : 'danger' ?>");
            <?php endif; ?>
        });
    </script>

    <div class="search-filter-bar">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search by item name, owner, or place...">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-muted me-3"><i class="bi bi-folder me-2"></i>Total: <strong><?= $totalReports ?></strong></span>
                <div class="btn-group">
                    <button class="btn btn-outline-primary btn-sm"  onclick="filterStatus('all')"><i class="bi bi-list"></i> All</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterStatus('Pending')"><i class="bi bi-hourglass"></i> Pending</button>
                    <button class="btn btn-outline-danger btn-sm"   onclick="filterStatus('Lost')"><i class="bi bi-exclamation-circle"></i> Published</button>
                    <button class="btn btn-outline-warning btn-sm"  onclick="filterStatus('Matching')"><i class="bi bi-link-45deg"></i> Matching</button>
                    <button class="btn btn-outline-success btn-sm"  onclick="filterStatus('Resolved')"><i class="bi bi-check-circle"></i> Resolved</button>
                </div>
            </div>
        </div>
    </div>

    <div class="report-grid" id="reportsGrid">
        <?php if (empty($reports)): ?>
            <div class="col-12 text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                    <h5 class="text-muted mt-3">No Lost Reports Found</h5>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($reports as $r):
                $reportFinders = $allFinders[$r['id']] ?? [];
                $finderCount   = count($reportFinders);
                $findersJson   = htmlspecialchars(json_encode($reportFinders), ENT_QUOTES, 'UTF-8');
                $statusClass   = "status-" . $r['status'];
                $initials      = strtoupper(substr($r['owner_name'], 0, 1));
                
                // Potential Matches Check (for UI indicator)
                $keywords = array_filter(explode(' ', $r['item_name']), function($k) { return strlen($k) > 2; });
                $matchSql = "SELECT COUNT(*) FROM items WHERE status IN ('Published', 'Claiming') AND (category = ?";
                $matchParams = [$r['category']];
                foreach($keywords as $k) { $matchSql .= " OR item_name LIKE ?"; $matchParams[] = "%$k%"; }
                $matchSql .= ")";
                $matchStmt = $pdo->prepare($matchSql);
                $matchStmt->execute($matchParams);
                $potentialMatchCount = $matchStmt->fetchColumn();
            ?>
            <div class="report-card" data-status="<?= $r['status'] ?>" data-report-id="<?= $r['id'] ?>" data-item-name="<?= htmlspecialchars(strtolower($r['item_name'])) ?>">
                <div class="card-img-wrapper">
                    <span class="card-status-badge <?= $statusClass ?>"><?= $r['status'] ?></span>
                    <?php if ($potentialMatchCount > 0 && $r['status'] !== 'Resolved'): ?>
                        <div class="match-glow-indicator">
                            <i class="bi bi-lightning-fill"></i> <?= $potentialMatchCount ?> Matches
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($r['image_path']): ?>
                        <img src="uploads/<?= $r['image_path'] ?>" class="report-img" alt="<?= htmlspecialchars($r['item_name']) ?>">
                    <?php else: ?>
                        <div class="report-img d-flex align-items-center justify-content-center bg-light text-muted">
                            <i class="bi bi-box-seam" style="font-size: 3rem; opacity: 0.2;"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="report-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($r['item_name']) ?></h5>
                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?= htmlspecialchars($r['category']) ?></span>
                    </div>
                    
                    <p class="text-muted small mb-3 item-description"><?= htmlspecialchars($r['description'] ?: 'No description provided') ?></p>

                    <div class="owner-info">
                        <div class="owner-av"><?= $initials ?></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small"><?= htmlspecialchars($r['owner_name']) ?></div>
                            <div class="small text-muted"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($r['owner_contact']) ?></div>
                        </div>
                    </div>

                    <div class="location-tag">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                        <span><?= htmlspecialchars($r['last_seen_location'] ?: 'Unknown') ?></span>
                    </div>
                    <div class="location-tag mt-1">
                        <i class="bi bi-calendar3"></i>
                        <span><?= $r['date_lost'] ? date('M d, Y', strtotime($r['date_lost'])) : 'Date unknown' ?></span>
                    </div>

                    <div class="mt-3">
                        <?php if ($finderCount > 0): ?>
                        <div class="finder-count-badge w-100 justify-content-center"
                              onclick="viewFinders(<?= $findersJson ?>, '<?= htmlspecialchars(addslashes($r['item_name'])) ?>')">
                            <i class="bi bi-person-check-fill"></i>
                            <?= $finderCount ?> Potential Finder<?= $finderCount > 1 ? 's' : '' ?>
                            <i class="bi bi-chevron-right ms-auto" style="font-size:.65rem"></i>
                        </div>
                        <?php else: ?>
                        <div class="finder-count-badge none w-100 justify-content-center">
                            <i class="bi bi-clock"></i> No finders contacted yet
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="report-card-footer">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary rounded-pill border-2 px-3"
                                onclick='viewReportDetails(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>
                            <i class="bi bi-eye me-1"></i>Details
                        </button>
                        <?php if ($r['status'] === 'Pending'): ?>
                        <button class="btn btn-sm btn-success rounded-pill px-3"
                                onclick="updateReportStatus(<?= $r['id'] ?>, 'Lost')">
                            <i class="bi bi-check-circle me-1"></i>Approve
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm rounded-pill status-select" style="font-size: 0.75rem;" 
                                onchange="updateReportStatus(<?= $r['id'] ?>, this.value)">
                            <option value="Pending"  <?= $r['status']=='Pending'  ? 'selected':'' ?>>Pending</option>
                            <option value="Lost"     <?= $r['status']=='Lost'     ? 'selected':'' ?>>Published</option>
                            <option value="Matching" <?= $r['status']=='Matching' ? 'selected':'' ?>>Matching</option>
                            <option value="Resolved" <?= $r['status']=='Resolved' ? 'selected':'' ?>>Resolved</option>
                        </select>
                        <button class="btn btn-sm btn-outline-danger rounded-circle" style="width:30px;height:30px;padding:0"
                                onclick="confirmDeleteReport(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['item_name'])) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ── Finders Modal ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="findersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow-lg" style="border:none;border-radius:20px">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d6efd,#0a4ab2)">
                <h5 class="modal-title text-white">
                    <i class="bi bi-person-check-fill me-2"></i>
                    <span id="findersModalTitle">Finders</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    People who said they found this item. Click <strong>Message</strong> to chat with any finder.
                </p>
                <div id="findersListContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Admin Chat Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0 pb-2" style="background:linear-gradient(135deg,#0d6efd,#0a4ab2);">
                <div class="d-flex align-items-center gap-3">
                    <div id="chatAvatar" style="width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.25);
                         display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;color:#fff;flex-shrink:0;">
                        ?
                    </div>
                    <div>
                        <div class="fw-bold text-white lh-1" id="chatReceiverName">Loading...</div>
                        <small class="text-white opacity-75">Finder</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div id="chatMessages"
                 style="height:340px;overflow-y:auto;padding:16px;background:#f8fafc;display:flex;flex-direction:column;gap:10px;">
                <div class="text-center text-muted small py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading messages...
                </div>
            </div>
            <div class="border-top p-3 bg-white d-flex gap-2 align-items-end">
                <textarea id="chatReplyText"
                          placeholder="Type a message..."
                          rows="1"
                          style="flex:1;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px;
                                 font-size:.875rem;resize:none;outline:none;max-height:90px;overflow-y:auto;"
                          oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,90)+'px'"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChatReply();}"></textarea>
                <button onclick="sendChatReply()"
                        style="width:42px;height:42px;border-radius:50%;background:#0d6efd;border:none;
                               color:#fff;display:flex;align-items:center;justify-content:center;
                               font-size:1rem;flex-shrink:0;transition:all .2s;"
                        onmouseover="this.style.background='#0a4ab2'"
                        onmouseout="this.style.background='#0d6efd'">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content overflow-hidden" style="border-radius:24px; border:none; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header border-0 p-4" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                        <i class="bi bi-search text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="detailsModalTitle">Lost Report Details</h5>
                        <small class="text-white text-opacity-75" id="detailsModalSubline">Manage and track lost item</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-navigation bg-light border-bottom px-4">
                <ul class="nav nav-pills gap-2 py-3" id="detailsTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active rounded-pill px-4 fw-bold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-content">
                            <i class="bi bi-info-circle me-1"></i> Information
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-4 fw-bold" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-content">
                            <i class="bi bi-clock-history me-1"></i> Timeline
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link rounded-pill px-4 fw-bold position-relative" id="matching-tab" data-bs-toggle="tab" data-bs-target="#matching-content">
                            <i class="bi bi-lightning-fill me-1"></i> Potential Matches
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="matchBadgeCounter">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-0">
                <div class="tab-content" id="detailsTabsContent">
                    <!-- Info Tab -->
                    <div class="tab-pane fade show active p-4" id="info-content">
                        <div id="detailsInfoBody"></div>
                    </div>
                    <!-- Timeline Tab -->
                    <div class="tab-pane fade p-4" id="timeline-content">
                        <div class="timeline-container" id="detailsTimelineBody"></div>
                    </div>
                    <!-- Matching Tab -->
                    <div class="tab-pane fade p-4" id="matching-content">
                        <div class="matching-container" id="detailsMatchingBody">
                            <div class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm mb-2"></div>
                                <p>Finding potential matches...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer: Mark Resolved -->
            <div class="modal-footer border-top" id="detailsModalFooter" style="display:none;">
                <div class="d-flex w-100 align-items-center justify-content-between">
                    <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>Mark this report as resolved once the item is returned.</div>
                    <button class="btn btn-success rounded-pill px-4 fw-bold" id="markResolvedBtn" onclick="markResolved()">
                        <i class="bi bi-check-circle-fill me-1"></i>Mark Resolved
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script>
// Auto-hide toast
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);

// Status Update JS
function updateReportStatus(reportId, newStatus) {
    const formData = new FormData();
    formData.append('update_status', '1');
    formData.append('report_id', reportId);
    formData.append('new_status', newStatus);

    fetch('manage_lost.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        showToast(`Status updated to ${newStatus}`, 'success');
        // Optional: Update card appearance without reload
        const card = document.querySelector(`.report-card[data-item-id="${reportId}"]`) || document.querySelector(`.report-card:has(input[value="${reportId}"])`);
        // Simpler to reload for now to refresh potential match counts if needed
        setTimeout(() => window.location.reload(), 500);
    });
}

// Search
document.getElementById('searchInput').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('.report-card').forEach(card => {
        const itemName = card.dataset.itemName || '';
        const cardText = card.textContent.toLowerCase();
        card.style.display = cardText.includes(q) ? '' : 'none';
    });
});

// Filter
function filterStatus(status) {
    const grid = document.getElementById('reportsGrid');
    if (!grid) return;
    
    // Only target cards in the main grid, not in modals
    grid.querySelectorAll('.report-card').forEach(card => {
        const cardStatus = card.dataset.status || '';
        const matches = (status === 'all' || cardStatus.toLowerCase() === status.toLowerCase());
        card.style.display = matches ? '' : 'none';
    });
}

// ── View Finders Modal ────────────────────────────────────────────────────────
function viewFinders(finders, itemName) {
    document.getElementById('findersModalTitle').textContent = `Finders for: ${itemName}`;

    let html = '';
    if (!finders || finders.length === 0) {
        html = '<p class="text-muted text-center py-4">No finders yet.</p>';
    } else {
        finders.forEach((f, i) => {
            const name    = f.finder_name || `${f.fname || ''} ${f.lname || ''}`.trim() || 'Unknown';
            const contact = f.finder_contact || f.user_contact || '—';
            const email   = f.finder_email   || f.user_email   || '—';
            const userId  = f.finder_user_id || null;
            const date    = new Date(f.created_at).toLocaleDateString('en-US', {
                year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'
            });

            const msgBtn = userId
                ? `<button class="btn-msg-finder"
                           data-uid="${userId}"
                           data-name="${name.replace(/"/g, '&quot;')}"
                           onclick="openChatModal(this.dataset.uid, this.dataset.name)">
                       <i class="bi bi-chat-dots-fill"></i> Message
                   </button>`
                : '';

            html += `
            <div class="finder-card">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="finder-num">${i + 1}</div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${name}</div>
                        <small class="text-muted">${date}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        ${msgBtn}
                        <span class="badge bg-primary">#${i + 1}</span>
                    </div>
                </div>
                <div class="row g-2 small mb-1">
                    <div class="col-sm-6">
                        <i class="bi bi-telephone me-1 text-muted"></i><strong>${contact}</strong>
                    </div>
                    <div class="col-sm-6">
                        <i class="bi bi-envelope me-1 text-muted"></i>${email}
                    </div>
                    ${f.found_location ? `
                    <div class="col-12">
                        <i class="bi bi-geo-alt me-1 text-danger"></i><strong>Has it at:</strong> ${f.found_location}
                    </div>` : ''}
                </div>
                ${f.message ? `
                <div class="finder-msg">
                    <i class="bi bi-chat-quote me-1 text-primary"></i>"${f.message}"
                </div>` : ''}
                ${f.image_path ? `
                <div class="mt-2">
                    <div class="small text-muted fw-bold mb-1"><i class="bi bi-image me-1"></i>PHOTO PROOF</div>
                    <img src="uploads/${f.image_path}" alt="Finder proof"
                         style="max-height:150px;border-radius:10px;object-fit:cover;border:1.5px solid #e2e8f0;cursor:pointer"
                         onclick="window.open('uploads/${f.image_path}','_blank')">
                </div>` : ''}
            </div>`;
        });
    }

    document.getElementById('findersListContent').innerHTML = html;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('findersModal')).show();
}

// ── Admin Chat Modal ──────────────────────────────────────────────────────────
let chatReceiverId = null;

function openChatModal(userId, userName) {
    chatReceiverId = userId;

    const initials = userName.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
    document.getElementById('chatAvatar').textContent       = initials;
    document.getElementById('chatReceiverName').textContent = userName;

    document.getElementById('chatMessages').innerHTML = `
        <div class="text-center text-muted small py-5">
            <span class="spinner-border spinner-border-sm me-2"></span>Loading...
        </div>`;
    document.getElementById('chatReplyText').value = '';

    const findersEl = document.getElementById('findersModal');
    const findersModal = bootstrap.Modal.getOrCreateInstance(findersEl);
    
    // Wait for the finders modal to be FULLY hidden before showing chat
    const showChat = () => {
        findersEl.removeEventListener('hidden.bs.modal', showChat);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('chatModal')).show();
        loadChatMessages();
    };

    if (findersEl.classList.contains('show')) {
        findersEl.addEventListener('hidden.bs.modal', showChat, { once: true });
        findersModal.hide();
    } else {
        // Finders modal wasn't open, just show chat directly
        bootstrap.Modal.getOrCreateInstance(document.getElementById('chatModal')).show();
        loadChatMessages();
    }
}

function loadChatMessages() {
    if (!chatReceiverId) return;
    fetch(`admin_chat_fetch.php?with=${chatReceiverId}`)
        .then(r => r.json())
        .then(msgs => {
            const box = document.getElementById('chatMessages');
            if (!msgs.length) {
                box.innerHTML = `<div class="text-center text-muted small py-5">
                    <i class="bi bi-chat-dots" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>
                    No messages yet. Say hello!
                </div>`;
                return;
            }
            box.innerHTML = msgs.map(m => {
                const isSent = m.is_admin == 1;
                const time   = new Date(m.created_at).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
                return `
                <div style="display:flex;flex-direction:column;align-items:${isSent?'flex-end':'flex-start'};">
                    <div style="max-width:75%;background:${isSent?'#0d6efd':'#fff'};color:${isSent?'#fff':'#1e293b'};
                                border-radius:${isSent?'18px 18px 4px 18px':'18px 18px 18px 4px'};
                                padding:10px 14px;font-size:.875rem;
                                box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                        ${m.body}
                    </div>
                    <small style="font-size:.68rem;color:#94a3b8;margin-top:3px;">${time}</small>
                </div>`;
            }).join('');
            box.scrollTop = box.scrollHeight;
        })
        .catch(() => {
            document.getElementById('chatMessages').innerHTML =
                '<p class="text-danger text-center small py-4">Failed to load messages.</p>';
        });
}

function sendChatReply() {
    const text = document.getElementById('chatReplyText').value.trim();
    if (!text || !chatReceiverId) return;

    const btn = document.querySelector('#chatModal button[onclick="sendChatReply()"]');
    btn.disabled = true;

    fetch('admin_chat_send.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `receiver_id=${chatReceiverId}&message=${encodeURIComponent(text)}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('chatReplyText').value = '';
            document.getElementById('chatReplyText').style.height = 'auto';
            loadChatMessages();
        } else {
            showToast('Failed to send: ' + (d.error || 'Unknown error'), 'danger');
        }
    })
    .finally(() => { btn.disabled = false; });
}

// Field label map
const fieldLabels = {
    brand:'Brand', model:'Model', color:'Color', case:'Has Case',
    contents:'Contents', material:'Material', id_type:'ID Type',
    id_name:'Name on ID', key_type:'Key Type', keychain:'Keychain',
    type:'Type', serial:'Serial Number', size:'Size', label:'Label/Brand',
    title:'Title/Subject', cover_color:'Cover Color', markings:'Markings', item_type:'Item Type'
};

function viewReportDetails(report) {
    document.getElementById('detailsModalTitle').textContent = `Lost: ${report.item_name}`;
    
    // Store report ID for Mark Resolved action
    window._currentDetailReportId = report.id;
    
    // Show/hide Mark Resolved footer based on status
    const footer = document.getElementById('detailsModalFooter');
    if (report.status === 'Matching') {
        footer.style.display = '';
    } else {
        footer.style.display = 'none';
    }
    
    // Reset Tabs
    bootstrap.Tab.getOrCreateInstance(document.getElementById('info-tab')).show();
    
    // 1. Render Info Tab
    let extraHtml = '';
    const extraEntries = Object.entries(report).filter(([k, v]) => k.startsWith('extra_') && v);
    if (extraEntries.length > 0) {
        extraHtml = `
        <div class="mb-4">
            <div class="detail-section">
                <h6><i class="bi bi-tags me-1"></i>Item Specific Details</h6>
                <div class="d-flex flex-wrap gap-2">
                    ${extraEntries.map(([k, v]) => {
                        const label = fieldLabels[k.replace('extra_', '')] || k.replace('extra_', '').replace(/_/g,' ');
                        return `<span class="extra-field-badge"><strong>${label}:</strong> ${v}</span>`;
                    }).join('')}
                </div>
            </div>
        </div>`;
    }

    const infoContent = `
        <div class="row g-4">
            <div class="col-md-5">
                ${report.image_path ? `
                    <div class="detail-section p-0 overflow-hidden mb-3">
                        <img src="uploads/${report.image_path}" class="w-100 shadow-sm" style="max-height:400px; object-fit:contain; background:#f1f5f9">
                    </div>` : `
                    <div class="detail-section d-flex align-items-center justify-content-center bg-light text-muted" style="height:250px">
                        <i class="bi bi-image fs-1 opacity-25"></i>
                    </div>`}
                <div class="detail-section">
                    <h6><i class="bi bi-person me-1"></i>Owner Details</h6>
                    <div class="fw-bold text-dark fs-5 mb-1">${report.owner_name}</div>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-telephone-fill text-primary"></i> ${report.owner_contact}
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="detail-section mb-3">
                    <h6><i class="bi bi-info-circle-fill me-1"></i>General Information</h6>
                    <div class="mb-3">
                        <span class="badge bg-primary rounded-pill px-3 py-2 mb-2">${report.category}</span>
                        <p class="text-dark mb-0">${report.description || 'No description provided'}</p>
                    </div>
                </div>
                ${extraHtml}
                <div class="row g-2">
                    <div class="col-6">
                        <div class="detail-section">
                            <h6><i class="bi bi-geo-alt-fill me-1"></i>Last Seen</h6>
                            <div class="small fw-bold">${report.last_seen_location || 'Unknown'}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-section">
                            <h6><i class="bi bi-calendar3 me-1"></i>Date Lost</h6>
                            <div class="small fw-bold">${report.date_lost ? new Date(report.date_lost).toLocaleDateString() : 'Unknown'}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    document.getElementById('detailsInfoBody').innerHTML = infoContent;

    // 2. Render Timeline
    const timelineData = [
        { status: 'Lost', label: 'Reported Missing', date: report.created_at, icon: 'bi-megaphone', color: 'danger' },
        { status: 'Matching', label: 'Under Review', date: report.updated_at, icon: 'bi-search', color: 'warning' },
        { status: 'Resolved', label: 'Marked Resolved', date: report.status === 'Resolved' ? report.updated_at : null, icon: 'bi-check2-circle', color: 'success' }
    ];

    let timelineHtml = '<div class="timeline">';
    timelineData.forEach(step => {
        const isPast = step.date && (new Date(report.created_at) <= new Date(step.date));
        const isActive = report.status === step.status;
        
        timelineHtml += `
            <div class="d-flex gap-4 mb-4 timeline-item ${isPast ? 'active' : 'pending'}">
                <div class="timeline-visual d-flex flex-column align-items-center">
                    <div class="timeline-dot rounded-circle bg-${isPast ? step.color : 'light'} d-flex align-items-center justify-content-center" 
                         style="width:40px;height:40px; z-index:2; border: 4px solid #fff; box-shadow: 0 0 0 2px ${isPast ? '#eee' : '#f8f9fa'}">
                        <i class="bi ${step.icon} text-${isPast ? 'white' : 'muted'}"></i>
                    </div>
                    <div class="timeline-line bg-light" style="width:2px; height:100%; min-height:40px"></div>
                </div>
                <div class="timeline-content pt-1">
                    <div class="fw-bold ${isPast ? 'text-dark' : 'text-muted'}">${step.label}</div>
                    <small class="text-muted d-block">${step.date ? new Date(step.date).toLocaleString() : 'Pending action'}</small>
                </div>
            </div>`;
    });
    timelineHtml += '</div>';
    document.getElementById('detailsTimelineBody').innerHTML = timelineHtml;

    // 3. Load Potential Matches
    loadPotentialMatches(report.id);

    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function loadPotentialMatches(reportId) {
    const container = document.getElementById('detailsMatchingBody');
    const badge = document.getElementById('matchBadgeCounter');
    
    container.innerHTML = `<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm mb-2"></div><p>Scanning global database for matches...</p></div>`;
    
    fetch(`fetch_matches.php?report_id=${reportId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.matches.length > 0) {
                badge.textContent = d.matches.length;
                badge.classList.remove('d-none');
                
                let html = '<div class="row g-3">';
                d.matches.forEach(m => {
                    html += `
                    <div class="col-md-6">
                        <div class="report-card h-100 border p-2">
                             <div class="d-flex gap-3">
                                <img src="uploads/${m.image_path || 'default_item.png'}" 
                                     class="rounded" style="width:80px;height:80px;object-fit:cover"
                                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/107/107831.png'">
                                <div class="flex-grow-1">
                                    <div class="fw-bold small mb-1">${m.item_name}</div>
                                    <div class="small text-muted mb-2"><i class="bi bi-person me-1"></i>${m.finder_name || 'Anonymous'}</div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-xs btn-primary py-0 px-2 rounded-pill" style="font-size:0.7rem" onclick="window.open('manage_found.php?item_id=${m.id}','_blank')">View Item</button>
                                        <button class="btn btn-xs btn-success py-0 px-2 rounded-pill" style="font-size:0.7rem" 
                                                onclick="linkAndNotify(${reportId}, ${m.id}, '${(m.item_name || 'Item').replace(/'/g, "\\'")}')">Link & Notify</button>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                badge.classList.add('d-none');
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-search-heart fs-1 text-muted opacity-25 mb-3"></i>
                        <h6 class="text-muted">No high-confidence matches found yet</h6>
                        <p class="small text-muted px-4">Keep this report open; we'll automatically flag any new items that match your description.</p>
                    </div>`;
            }
        });
}

function linkAndNotify(reportId, itemId, itemName) {
    showConfirm({
        title: 'Link & Notify Owner?',
        msg: `This will formally link the found item <strong>"${itemName}"</strong> to this report and send an automated notification message to the owner. Proceed?`,
        type: 'success',
        confirmText: 'Link & Notify',
        onConfirm: () => {
            const formData = new FormData();
            formData.append('link_report', '1');
            formData.append('report_id', reportId);
            formData.append('item_id', itemId);

            fetch('manage_lost.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showToast('Item linked and owner notified successfully!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast('Failed to link item.', 'danger');
                }
            });
        }
    });
}

// ── Mark Resolved ────────────────────────────────────────────────────────────
function markResolved() {
    const reportId = window._currentDetailReportId;
    if (!reportId) return;
    showConfirm({
        title: 'Mark as Resolved?',
        msg: 'This will mark the lost report as <strong>Resolved</strong> — confirming the item has been returned to its owner.',
        type: 'success',
        confirmText: 'Yes, Mark Resolved',
        onConfirm: () => {
            updateReportStatus(reportId, 'Resolved');
            bootstrap.Modal.getInstance(document.getElementById('detailsModal'))?.hide();
        }
    });
}

// ── Delete Confirmation ──────────────────────────────────────────────────────
function confirmDeleteReport(id, itemName) {
    showConfirm({
        title: 'Delete Lost Report?',
        msg: `Are you sure you want to permanently delete the report for <strong>"${itemName}"</strong>? This action cannot be undone.`,
        type: 'danger',
        confirmText: 'Delete Permanently',
        onConfirm: () => {
            window.location.href = `manage_lost.php?delete=${id}`;
        }
    });
}
</script>


</div><!-- /admin-main-content -->
</body>
</html>