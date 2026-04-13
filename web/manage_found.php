<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_id'], [4,6])) {
    // admins (4) and organizers (6) may use this page
    header("Location: auth.php"); exit;
}
$user_id = (int)$_SESSION['user_id'];
$user_type = (int)$_SESSION['type_id'];

$hasEditAccess = true; // All administrators and organizers have full edit access now

// Prevent back-button cache access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include 'db.php';

$statuses     = ['Pending', 'Published', 'Claiming', 'Returned'];
$statusLabels = [
    'Pending'   => 'Pending',
    'Published' => 'Published',
    'Claiming'  => 'Verifying',
    'Returned'  => 'Returned',
];

$data = [];
$statusCounts = [];

// Fetch all claimers per item from the claims table
$allClaims = [];
$claimsCheck = $pdo->query("SHOW TABLES LIKE 'claims'")->fetch();
if ($claimsCheck) {
    $claimsStmt = $pdo->query("
        SELECT c.*, u.id AS user_id, u.fname, u.lname, u.email, u.contact_number, u.student_id
        FROM claims c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.claimed_at DESC
    ");
    foreach ($claimsStmt->fetchAll(PDO::FETCH_ASSOC) as $claim) {
        $allClaims[$claim['item_id']][] = $claim;
    }
}

foreach ($statuses as $status) {
    $sql = "SELECT items.*, 
                   CONCAT(IFNULL(users.fname, ''), ' ', IFNULL(users.lname, '')) AS uploader_name, 
                   users.email AS uploader_email, 
                   users.contact_number AS uploader_contact
            FROM items 
            LEFT JOIN users ON items.user_id = users.id 
            WHERE items.status = ? 
            ORDER BY items.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status]);
    $data[$status] = $stmt->fetchAll();
    $statusCounts[$status] = count($data[$status]);
}

$totalCount = array_sum($statusCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Found Items | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Shared styles are in admin_header.php */
        #viewMap { height: 500px; width: 100%; border-radius: 12px; background: #eee; }
        .item-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; transition: transform 0.3s; cursor: pointer; }
        .item-img:hover { transform: scale(1.1); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        
        .nav-tabs { border-bottom: 2px solid #e9ecef !important; background: white; border-radius: 16px 16px 0 0; }
        .nav-tabs .nav-link            { border: none; color: #1e293b !important; font-weight: 600; padding: 14px 24px; transition: all 0.3s; }
        .nav-tabs .nav-link:hover      { color: var(--admin-blue) !important; background-color: rgba(13,110,253,0.05) !important; }
        .nav-tabs .nav-link.active     { color: var(--admin-blue) !important; background: rgba(13,110,253,0.1) !important; border-bottom: 3px solid var(--admin-blue); }
        .nav-tabs .nav-link.active .badge { background-color: var(--admin-blue) !important; color: white !important; }

        .item-detail-badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; }
        .search-filter-bar { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: var(--admin-card-shadow); }
        
        /* Claimer pill */
        .claimer-count-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #fffbeb; border: 1px solid #fcd34d;
            border-radius: 20px; padding: 4px 10px;
            font-size: 0.78rem; font-weight: 700; color: #92400e;
            cursor: pointer; transition: all 0.2s;
        }
        .claimer-count-badge:hover { background: #fef3c7; border-color: #f59e0b; }
        .claimer-count-badge.none { background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; cursor: default; }

        .msg-input-area { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: none; margin-top: 10px; }
        .claimer-actions { display: flex; gap: 8px; margin-top: 12px; }
        .btn-claimer { font-size: .75rem; padding: 5px 12px; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 5px; }

        .quick-stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .quick-stat-item { flex: 1; background: rgba(255,255,255,0.15) !important; padding: 1rem; border-radius: 12px; text-align: center; color: white; }
        .quick-stat-item h3 { margin: 0; font-size: 2rem; font-weight: 700; }
        .quick-stat-item small { opacity: 0.9; font-size: 0.85rem; }
    </style>
</head>
<body>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<?php include 'admin_header.php'; ?>

<div class="container-fluid px-4 mt-4">

    <!-- Page Header -->
    <div class="admin-page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-2"><i class="bi bi-box-seam me-2"></i>Found Items Management</h2>
                <p class="mb-0 opacity-75">Track, manage, and process all found items</p>
            </div>
        </div>
        <div class="quick-stats mt-4">
            <div class="quick-stat-item"><h3><?= $statusCounts['Pending'] ?></h3><small>Pending Review</small></div>
            <div class="quick-stat-item"><h3><?= $statusCounts['Published'] ?></h3><small>Published</small></div>
            <div class="quick-stat-item"><h3><?= $statusCounts['Claiming'] ?></h3><small>Verifying</small></div>
            <div class="quick-stat-item"><h3><?= $statusCounts['Returned'] ?></h3><small>Returned</small></div>
        </div>
    </div>

    <?php if(!$hasEditAccess): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
        <i class="bi bi-shield-lock-fill me-2"></i>
        <strong>Editing Restricted:</strong> Your editing permissions have been disabled by an Administrator. Actions like status updates or deletions are restricted.
    </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="search-filter-bar admin-card p-3 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0"
                           placeholder="Search by item name, place, or finder...">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-muted me-3">
                    <i class="bi bi-folder me-2"></i>Total Items: <strong><?= $totalCount ?></strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="admin-card shadow-sm mb-5">
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3 pt-3" id="adminTab" role="tablist">
                <?php foreach ($statuses as $index => $status): ?>
                <li class="nav-item">
                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>"
                            id="tab-btn-<?= $status ?>"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-<?= $status ?>"
                            type="button">
                        <?= $statusLabels[$status] ?>
                        <span class="ms-2 badge rounded-pill bg-light text-dark border"><?= $statusCounts[$status] ?></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content p-4">
                <?php foreach ($statuses as $index => $status): ?>
                <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="tab-<?= $status ?>">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle admin-table">
                            <thead>
                                <tr>
                                    <th style="width:100px">Photo</th>
                                    <th>Item Details</th>
                                    <th style="width:180px">Claimers</th>
                                    <th style="width:180px">Storage Location</th>
                                    <th style="width:120px">Date Added</th>
                                    <th style="width:140px">Date/Time Found</th>
                                    <th class="text-end" style="width:300px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data[$status])): ?>
                                <tr><td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5 class="text-muted mb-2">No items in <?= $statusLabels[$status] ?></h5>
                                        <p class="text-muted small mb-0">Items will appear here when they have this status</p>
                                    </div>
                                </td></tr>
                                <?php else: ?>
                                <?php foreach ($data[$status] as $row):
                                    $jsonRow    = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    $itemClaims = $allClaims[$row['id']] ?? [];
                                    $claimCount = count($itemClaims);
                                    $claimsJson = htmlspecialchars(json_encode($itemClaims), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr data-item-id="<?= $row['id'] ?>">
                                    <td>
                                        <img src="uploads/<?= htmlspecialchars($row['image_path']) ?>"
                                             class="item-img border shadow-sm"
                                             onclick="viewDetails(<?= $jsonRow ?>)"
                                             alt="<?= htmlspecialchars($row['item_name']) ?>">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($row['item_name']) ?></div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-geo-alt-fill text-danger"></i>
                                            Place Found: <?= htmlspecialchars($row['found_location']) ?>
                                        </small>
                                        <?php if (!empty($row['category'])): ?>
                                        <span class="item-detail-badge bg-light border">
                                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($row['category']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Claimers Column -->
                                    <td>
                                        <?php if ($claimCount > 0): ?>
                                        <span class="claimer-count-badge"
                                              onclick="viewClaimers(<?= $claimsJson ?>, '<?= htmlspecialchars(addslashes($row['item_name'])) ?>', <?= $row['id'] ?>, '<?= $row['image_path'] ?>', '<?= $row['status'] ?>')">
                                            <i class="bi bi-people-fill"></i>
                                            <?= $claimCount ?> Claimer<?= $claimCount > 1 ? 's' : '' ?>
                                            <i class="bi bi-chevron-right" style="font-size:.65rem"></i>
                                        </span>
                                        <?php else: ?>
                                        <span class="claimer-count-badge none">
                                            <i class="bi bi-clock"></i> No claims yet
                                        </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <select class="form-select form-select-sm"
                                                onchange="updateStorageSteady(<?= $row['id'] ?>, this)"
                                                <?= !$hasEditAccess ? 'disabled' : '' ?>>
                                            <option value="SSG Office"          <?= $row['storage_location']=='SSG Office' ? 'selected' : '' ?>>SSG Office</option>
                                            <option value="Guard House"         <?= $row['storage_location']=='Guard House' ? 'selected' : '' ?>>Guard House</option>
                                            <option value="Finder's Possession" <?= $row['storage_location']=="Finder's Possession" ? 'selected' : '' ?>>Finder Has It</option>
                                        </select>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i>
                                            <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted fw-bold">
                                            <i class="bi bi-clock"></i>
                                            <?= !empty($row['date_found']) ? date('M d, Y – h:i A', strtotime($row['date_found'])) : '---' ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group action-btn-group mb-2">
                                            <button class="btn btn-sm btn-primary"
                                                    onclick="prepareFBShare(<?= $jsonRow ?>)" title="Share on Facebook">
                                                <i class="bi bi-facebook"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    onclick="viewDetails(<?= $jsonRow ?>)" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteItem(<?= $row['id'] ?>, this)" title="Delete Item"
                                                    <?= !$hasEditAccess ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <select class="form-select form-select-sm border-primary status-select"
                                                onchange="updateStatusSteady(<?= $row['id'] ?>, this)"
                                                <?= !$hasEditAccess ? 'disabled' : '' ?>>
                                            <option selected disabled>Change Status...</option>
                                            <?php foreach ($statuses as $s): ?>
                                            <?php if ($s !== $status): ?>
                                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($statusLabels[$s]) ?></option>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Claimers Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="claimersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <h5 class="modal-title text-white">
                    <i class="bi bi-people-fill me-2"></i>
                    <span id="claimersModalTitle">Claimers</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Review all claimers below. Click <strong>Message</strong> to open a direct chat with any claimer.
                </p>
                <div id="claimersListContent"></div>
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
                        <small class="text-white opacity-75">Claimer</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <!-- Messages area -->
            <div id="chatMessages"
                 style="height:340px;overflow-y:auto;padding:16px;background:#f8fafc;display:flex;flex-direction:column;gap:10px;">
                <div class="text-center text-muted small py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading messages...
                </div>
            </div>

            <!-- Reply input -->
            <div class="border-top p-3 bg-white d-flex gap-2 align-items-end">
                <textarea id="chatReplyText"
                          placeholder="Type a message..."
                          rows="1"
                          style="flex:1;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 14px;
                                 font-size:.875rem;resize:none;outline:none;max-height:90px;overflow-y:auto;"
                          oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,90)+'px'"
                          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChatReply();}"
                          ></textarea>
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

<!-- Facebook Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-facebook me-2"></i>Share to Facebook</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted"><i class="bi bi-1-circle-fill me-1"></i> POST DESCRIPTION</label>
                    <textarea id="fbText" class="form-control fb-text-area" rows="6" readonly></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted"><i class="bi bi-2-circle-fill me-1"></i> ITEM PHOTO</label>
                    <div class="text-center p-3 bg-light rounded">
                        <img id="fbImg" src="" class="img-fluid rounded shadow" style="max-height:250px">
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="copyFBTxt()">
                        <i class="bi bi-clipboard me-2"></i>Copy Text to Clipboard
                    </button>
                    <a href="https://www.facebook.com/BISUCCOfficial" target="_blank" class="btn btn-primary btn-lg">
                        <i class="bi bi-facebook me-2"></i>Open Facebook & Post
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)">
                <h5 class="modal-title text-white"><i class="bi bi-info-circle me-2"></i>Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detailsContent"></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script>

// Search
document.getElementById('searchInput').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('tbody tr[data-item-id]').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── View Claimers Modal ───────────────────────────────────────────────────────
function viewClaimers(claims, itemName, itemId, itemImg, itemStatus) {
    document.getElementById('claimersModalTitle').textContent = `Claimers for: ${itemName}`;
    let html = `
    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-4 border">
        <img src="uploads/${itemImg}" class="rounded-3 shadow-sm" style="width:70px;height:70px;object-fit:cover">
        <div>
            <div class="fw-bold text-primary mb-1">REFERENCE ITEM</div>
            <div class="small text-muted">${itemName}</div>
        </div>
    </div>`;
    
    if (!claims || claims.length === 0) {
        html += '<div class="text-center py-5 text-muted"><i class="bi bi-inbox display-5 d-block mb-2"></i>No claims found.</div>';
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
                    <div style="width:28px;height:28px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">${i + 1}</div>
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
                <div class="claim-msg mb-2 p-2 rounded bg-light border-start border-warning" style="font-size:.85rem; font-style:italic">
                    <i class="bi bi-chat-quote-fill me-1 text-warning"></i>"${c.claim_message}"
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
                    <button class="btn btn-primary btn-claimer" 
                            id="confirmBtn_${c.id}"
                            onclick="confirmReturn(${itemId}, ${c.id})"
                            ${itemStatus === 'Returned' ? 'disabled' : ''}>
                        <i class="bi bi-check-all"></i> ${itemStatus === 'Returned' ? 'Already Returned' : 'Confirm Return'}
                    </button>
                    ` : ''}
                </div>

                <div id="msgInputArea_${i}" class="msg-input-area">
                    <textarea class="form-control form-control-sm mb-2" id="msgText_${i}" placeholder="Type message..."></textarea>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-sm btn-light" onclick="toggleMessageInput(${c.user_id}, ${i})">Cancel</button>
                        <button class="btn btn-sm btn-primary" onclick="sendMessageToClaimer(${c.user_id}, ${i}, '${itemName.replace(/'/g, "\\'")}')">Send</button>
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
        msg: `Are you sure you want to verify this claimant for <strong>"${itemName}"</strong>?<br><br>This will reject all other pending claims for this item.`,
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
    const btn = document.getElementById(`confirmBtn_${claimId}`);
    showConfirm({
        title: 'Confirm Return?',
        msg: 'Mark this item as officially <strong>Returned</strong> to the owner?',
        type: 'success',
        confirmText: 'Confirm Returned',
        onConfirm: async () => {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
            }
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
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-check-all"></i> Confirm Return';
                    }
                }
            } catch (err) { 
                showToast('Server error', 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-all"></i> Confirm Return';
                }
            }
        }
    });
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

    bootstrap.Modal.getInstance(document.getElementById('claimersModal'))?.hide();
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('chatModal')).show();
        loadChatMessages();
    }, 300);
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

// Facebook
function prepareFBShare(item) {
    const postText = `📢 LOST ITEM FOUND!\n\n🔍 Item: ${item.item_name.toUpperCase()}\n📍 Found at: ${item.found_location}\n📦 Claim at: ${item.storage_location}\n\nIf this is yours, please visit us during office hours or visit the website Lost&FoundCC.com\n\n#BISUCC #SSG #LostAndFound`;
    document.getElementById('fbText').value = postText;
    document.getElementById('fbImg').src = 'uploads/' + item.image_path;
    new bootstrap.Modal(document.getElementById('shareModal')).show();
}

function copyFBTxt() {
    const textarea = document.getElementById('fbText');
    textarea.select();
    document.execCommand('copy');
    const btn = event.target.closest('button');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Copied!';
    btn.classList.replace('btn-outline-primary','btn-success');
    setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('btn-success','btn-outline-primary'); }, 2000);
}

// Storage update
function updateStorageSteady(itemId, element) {
    fetch('update_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${itemId}&new_location=${encodeURIComponent(element.value)}`
    }).then(() => {
        element.classList.add('save-success');
        setTimeout(() => element.classList.remove('save-success'), 1000);
    });
}

// Status update
function updateStatusSteady(itemId, element) {
    const currentTabId = document.querySelector('.nav-link.active').getAttribute('data-bs-target');
    fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${itemId}&new_status=${encodeURIComponent(element.value)}`
    }).then(() => { window.location.hash = currentTabId; location.reload(); });
}

// Delete
function deleteItem(itemId, btn) {
    showConfirm({
        title: 'Delete Item?',
        msg: 'Are you sure you want to permanently delete this item? This action cannot be undone.',
        type: 'danger',
        confirmText: 'Delete Permanently',
        onConfirm: () => {
            fetch(`delete_item.php?id=${itemId}`, { method: 'GET' }).then(res => {
                if (res.ok) {
                    const row = btn.closest('tr');
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.95)';
                    setTimeout(() => { row.remove(); location.reload(); }, 400);
                } else { showToast('Error deleting item. Please try again.', 'danger'); }
            });
        }
    });
}

// View Details
function viewDetails(item) {
    const content = `
        <div class="row">
            <div class="col-md-5">
                <img src="uploads/${item.image_path}" class="img-fluid rounded shadow-sm mb-3">
            </div>
            <div class="col-md-7">
                <h3 class="fw-bold text-primary mb-3">${item.item_name}</h3>
                <div class="mb-3">
                    <h6 class="text-muted small fw-bold mb-2">DESCRIPTION</h6>
                    <p class="mb-0" style="white-space:pre-line">${item.description || 'No description provided'}</p>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="text-muted small fw-bold mb-1">UPLOADED BY</h6>
                        <p class="mb-1 text-primary fw-bold">
                            <i class="bi bi-person-circle me-2"></i>${item.uploader_name?.trim() || item.uploader_email || 'Anonymous'}
                        </p>
                        <div class="small text-muted">
                            <div><i class="bi bi-envelope me-2"></i>${item.uploader_email || 'No email'}</div>
                            <div><i class="bi bi-telephone me-2"></i>${item.uploader_contact || 'No contact number'}</div>
                        </div>
                    </div>
                    <div class="col-6 border-top pt-2">
                        <h6 class="text-muted small fw-bold mb-1">FOUND AT</h6>
                        <p class="mb-0 small"><i class="bi bi-geo-alt-fill text-danger me-2"></i>${item.found_location}</p>
                    </div>
                    <div class="col-6 border-top pt-2">
                        <h6 class="text-muted small fw-bold mb-1">STORED AT</h6>
                        <p class="mb-0 small"><i class="bi bi-building me-2"></i>${item.storage_location}</p>
                    </div>
                </div>
                <div class="border-top pt-3 mt-3">
                    <small class="text-muted">
                        <i class="bi bi-calendar3 me-2"></i>
                        Added: ${new Date(item.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}
                    </small>
                </div>
            </div>
        </div>`;

    document.getElementById('detailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

// Restore tab from hash
if (window.location.hash) {
    var trigger = document.querySelector(`button[data-bs-target="${window.location.hash}"]`);
    if (trigger) new bootstrap.Tab(trigger).show();
}
</script>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
                <h5 class="modal-title text-white">
                    <i class="bi bi-trash me-2"></i>Delete Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4 px-4">
                <div style="font-size:3rem;margin-bottom:12px;">⚠️</div>
                <h6 class="fw-bold mb-2">Are you sure?</h6>
                <p class="text-muted small mb-0">This item will be permanently deleted and cannot be recovered.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger px-4" onclick="confirmDelete()">
                    <i class="bi bi-trash me-1"></i>Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>
</div><!-- /admin-main-content -->
</body>
</html>