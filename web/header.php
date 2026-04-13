<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$current_page = basename($_SERVER['PHP_SELF']); 

// Database Connection Logic for "My Reports" & "My Claims"
$my_items = [];
$my_claims = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Reports (Found items) and Lost Reports uploaded by user
    $stmt = $pdo->prepare("
        (SELECT id, item_name, description, found_location as location, category, image_path, status, created_at, 'Found' as report_type 
         FROM items 
         WHERE user_id = ?)
        UNION ALL
        (SELECT id, item_name, description, last_seen_location as location, category, image_path, status, created_at, 'Lost' as report_type 
         FROM lost_reports 
         WHERE user_id = ?)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $my_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Claims made by user
    $stmt2 = $pdo->prepare("
        SELECT c.*, i.item_name, i.image_path as item_image, i.found_location, i.status as item_status
        FROM claims c
        JOIN items i ON c.item_id = i.id
        WHERE c.user_id = ? 
        ORDER BY c.claimed_at DESC
    ");
    $stmt2->execute([$user_id]);
    $my_claims = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}



?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    /* 1. Global & Body */
    body { 
        font-family: 'Inter', sans-serif; 
        padding-top: 80px; 
    }

    /* 2. Navbar & Positioning */
    .navbar { 
        position: fixed !important;
        top: 0; left: 0; right: 0;
        z-index: 999999 !important;
        background-color: rgba(0, 51, 102, 0.95) !important; 
        border-bottom: 3px solid transparent;
        border-image: linear-gradient(90deg, #ffcc00, #f5c842, #e8b800, #ffcc00) 1;
        pointer-events: auto;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: all 0.35s ease;
    }
    .navbar.scrolled {
        background-color: rgba(0, 35, 70, 0.98) !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    }
    .navbar-collapse { z-index: 1000000 !important; }
    .hero-banner, .search-wrapper, .modal-backdrop, .leaflet-container {
        z-index: 1 !important;
        pointer-events: auto;
    }

    /* 3. Nav Branding & Links */
    .bisu-logo { height: 44px; width: auto; margin-right: 10px; object-fit: contain; }
    .brand-logo-svg { height: 32px; width: 32px; margin-right: 10px; filter: drop-shadow(0 0 6px rgba(255,204,0,0.3)); transition: all 0.3s; }
    .navbar-brand:hover .brand-logo-svg { filter: drop-shadow(0 0 12px rgba(255,204,0,0.5)); transform: scale(1.05); }
    .brand-text { display: flex; flex-direction: column; line-height: 1.15; }
    .brand-name { font-size: 1.35rem; font-weight: 800; color: #fff; letter-spacing: 0.5px; }
    .brand-name span { color: #ffcc00; }
    .brand-sub { font-size: 0.65rem; color: rgba(255,255,255,0.55); letter-spacing: 1.8px; font-weight: 600; text-transform: uppercase; }
    
    .nav-link { 
        font-weight: 500; 
        color: rgba(255,255,255,0.75) !important; 
        position: relative;
        transition: all 0.3s ease;
        padding: 8px 16px !important;
        font-size: 0.9rem;
    }
    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0; left: 50%;
        width: 0; height: 2px;
        background: linear-gradient(90deg, #ffcc00, #f5c842);
        transition: all 0.3s ease;
        transform: translateX(-50%);
        border-radius: 2px;
    }
    .nav-link:hover { color: #ffcc00 !important; }
    .nav-link:hover::after { width: 60%; }
    .nav-link.active { color: #ffcc00 !important; font-weight: 700; }
    .nav-link.active::after { 
        width: 70%; 
        box-shadow: 0 0 8px rgba(255,204,0,0.5); 
    }
    .nav-link i { font-size: 0.85rem; }
    
    .btn-login-nav {
        background: linear-gradient(135deg, #ffcc00, #e8b800) !important;
        color: #003366 !important;
        font-weight: 700; border: none; padding: 9px 24px; border-radius: 10px; 
        transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 2px 12px rgba(255,204,0,0.25);
        text-decoration: none; font-size: 0.9rem;
    }
    .btn-login-nav:hover {
        background: linear-gradient(135deg, #ffd633, #ffcc00) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 204, 0, 0.4);
        color: #003366 !important;
    }

    /* 4. User Avatar & Dropdowns */
    .user-avatar {
        width: 37px; height: 37px; 
        background: linear-gradient(135deg, #ffcc00, #e8b800); 
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #003366; margin-right: 10px;
        box-shadow: 0 2px 8px rgba(255,204,0,0.3);
        transition: all 0.3s;
    }
    .user-avatar:hover { transform: scale(1.08); box-shadow: 0 4px 16px rgba(255,204,0,0.4); }
    .dropdown-menu {
        border: none; box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18); border-radius: 14px;
        z-index: 2147483647 !important; pointer-events: auto !important;
        padding: 8px;
        animation: dropdownFade 0.25s ease;
        backdrop-filter: blur(20px);
    }
    @keyframes dropdownFade {
        from { opacity: 0; transform: translateY(-10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .dropdown-item { 
        font-weight: 500; color: #003366; transition: all 0.2s; 
        border-radius: 8px; padding: 10px 14px; margin: 2px 0;
    }
    .dropdown-item:hover { background-color: #f0f4ff; color: #0d6efd; transform: translateX(4px); }
    .dropdown-item.logout-btn { color: #dc2626; }
    .dropdown-item.logout-btn:hover { background-color: #fee2e2; color: #991b1b; }
    .dropdown.show .dropdown-menu { display: block !important; opacity: 1 !important; }

    /* 5. Unread badge pulse */
    .badge-pulse {
        animation: badgePulse 2s ease-in-out infinite;
    }
    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
    }

    /* 6. Modal & Table Specifics */
    .status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    .custom-table thead th { letter-spacing: 0.5px; font-weight: 600; }
    .hover-row:hover { background-color: #f8f9ff; transition: 0.2s ease-in-out; }
    .modal-dialog-scrollable .modal-body { max-height: 500px; }
    .bg-warning-subtle { background-color: #fff9e6 !important; border-color: #ffeeb3 !important; }
    .bg-success-subtle { background-color: #e6ffed !important; border-color: #b3ffcc !important; }

    /* 7. Premium Modal Enhancements */
    .premium-modal .modal-content {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .modal-glass-header {
        background: linear-gradient(135deg, rgba(0, 51, 102, 0.03), rgba(0, 51, 102, 0.08));
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .image-preview-container {
        position: relative;
        width: 100%;
        height: 180px;
        border-radius: 18px;
        overflow: hidden;
        background: #f1f3f5;
        border: 2px dashed rgba(0, 51, 102, 0.1);
        transition: all 0.3s ease;
    }
    .image-preview-container:hover {
        border-color: #ffcc00;
        transform: translateY(-2px);
    }
    .image-preview-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f8f9fa;
    }
    .image-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
        color: white;
        cursor: pointer;
    }
    .image-preview-container:hover .image-overlay {
        opacity: 1;
    }
    .premium-form-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d;
        margin-bottom: 6px;
        font-weight: 700;
    }
    .premium-input {
        border: 1px solid rgba(0, 51, 102, 0.12);
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: all 0.25s ease;
        background: rgba(255, 255, 255, 0.8);
    }
    .premium-input:focus {
        border-color: #ffcc00;
        box-shadow: 0 0 0 4px rgba(255, 204, 0, 0.15);
        background: #fff;
    }
    .btn-premium-save {
        background: linear-gradient(135deg, #003366, #00509e);
        color: white;
        border: none;
        padding: 12px 32px;
        border-radius: 50px;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(0, 51, 102, 0.2);
        transition: all 0.3s ease;
    }
    .btn-premium-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 51, 102, 0.3);
        background: linear-gradient(135deg, #004080, #0066cc);
        color: white;
    }
    .btn-premium-cancel {
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.2s;
    }
    .btn-premium-cancel:hover {
        background: #f1f3f5;
        color: #343a40;
    }
    @keyframes modalScaleIn {
        from { opacity: 0; transform: scale(0.95) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .premium-modal.show .modal-dialog {
        animation: modalScaleIn 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    /* 8. Quick Add Chips */
    .attribute-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        margin-bottom: 6px;
        margin-right: 4px;
        user-select: none;
    }
    .attribute-chip:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-1px);
        border-color: rgba(0, 51, 102, 0.1);
    }
    .attribute-chip i { font-size: 0.8rem; opacity: 0.7; }
    .attribute-chip.active {
        background: #003366;
        color: #fff;
    }

    .auto-expand {
        min-height: 80px;
        overflow-y: hidden;
        resize: none;
    }

</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="uploads/BISU-LOGO.png" alt="BISU Logo" class="bisu-logo">
            <svg class="brand-logo-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="20" cy="20" r="14" stroke="#ffcc00" stroke-width="3.5"/>
                <line x1="30" y1="30" x2="42" y2="42" stroke="#ffcc00" stroke-width="3.5" stroke-linecap="round"/>
                <polyline points="13,20 18,25 27,15" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="brand-text">
                <div class="brand-name">Found<span>It!</span></div>
                <div class="brand-sub">BISU Candijay Campus</div>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>" href="report.php">
                        <i class="bi bi-check-circle"></i> Report Found
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 <?php echo ($current_page == 'report_lost.php') ? 'active' : ''; ?>" href="report_lost.php">
                        <i class="bi bi-exclamation-circle"></i> Report Lost
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 position-relative <?php echo ($current_page == 'inbox.php') ? 'active' : ''; ?>" href="inbox.php">
                        <i class="bi bi-inbox-fill"></i> Inbox
                        <?php if (isset($_SESSION['user_id'])): 
                            $unread = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
                            $unread->execute([$_SESSION['user_id']]);
                            $count = $unread->fetchColumn();
                            if ($count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-pulse" style="font-size:.6rem">
                                    <?= $count ?>
                                </span>
                            <?php endif;
                        endif; ?>
                    </a>
                </li>

                <li class="nav-item" style="border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px; margin-left: 10px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                            <div class="dropdown">
                                <button class="nav-link dropdown-toggle ps-0" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none;">
                                    <span style="font-size: 0.95rem;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#myReportsModal">
                                            <i class="bi bi-folder2-open me-2"></i>My Reports
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item logout-btn"
                                                data-bs-toggle="modal" data-bs-target="#logoutModal">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="signup.php" class="btn-login-nav">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ── My Reports Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="myReportsModal" tabindex="-1" aria-labelledby="myReportsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold fs-4 mt-2 ms-2" id="myReportsModalLabel">
                    <i class="bi bi-person-badge text-primary me-2"></i>My Submissions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr class="text-muted small">
                                <th class="border-0">ITEM</th>
                                <th class="border-0">PLACE</th>
                                <th class="border-0">STATUS</th>
                                <th class="border-0">DATE</th>
                                <th class="border-0 text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="bi bi-clipboard-x text-muted opacity-25" style="font-size: 3rem;"></i>
                                        <p class="mt-2 text-muted">No reports found under your account.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($my_items as $item): 
                                    $rowId = "item-" . $item['id'];
                                ?>
                                <tr class="hover-row" id="row-<?= $rowId ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="uploads/<?php echo htmlspecialchars($item['image_path'] ?: 'no_image.png'); ?>" 
                                                 id="img-<?= $rowId ?>"
                                                 class="rounded-3 shadow-sm me-3" style="width: 45px; height: 45px; object-fit: cover;">
                                            <span class="fw-semibold text-dark" id="name-<?= $rowId ?>"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <span id="loc-<?= $rowId ?>"><?php echo htmlspecialchars($item['location']); ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            // Handle different status types
                                            $isFound = $item['report_type'] == 'Found';
                                            $status = $item['status'];
                                            
                                            if ($isFound) {
                                                $badgeClass = ($status == 'Pending') ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success';
                                                $dotClass = ($status == 'Pending') ? 'bg-warning' : 'bg-success';
                                            } else {
                                                // Lost items usually remain "Lost" or "Found" (matched)
                                                $badgeClass = ($status == 'Lost') ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
                                                $dotClass = ($status == 'Lost') ? 'bg-danger' : 'bg-success';
                                            }
                                        ?>
                                        <div class="mb-1">
                                            <span class="badge <?= $isFound ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?> border-0 rounded-pill" style="font-size: 0.6rem;"><?= $item['report_type'] ?></span>
                                        </div>
                                        <span class="badge <?php echo $badgeClass; ?> border px-2 py-1 rounded-pill d-inline-flex align-items-center">
                                            <span class="status-dot <?php echo $dotClass; ?> me-2"></span><?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo date('M d', strtotime($item['created_at'])); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 header-edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#editSubmissionModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-type="<?= $item['report_type'] ?>"
                                                data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                                data-desc="<?= htmlspecialchars($item['description']) ?>"
                                                data-loc="<?= htmlspecialchars($item['location']) ?>"
                                                data-cat="<?= htmlspecialchars($item['category']) ?>"
                                                data-img="<?= htmlspecialchars($item['image_path'] ?: 'no_image.png') ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ── MY CLAIMS SECTION ── -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="bi bi-hand-index-thumb text-primary me-2"></i>My Claims
                        <span class="badge bg-light text-dark border ms-2 small" style="font-size: .7rem;"><?= count($my_claims) ?></span>
                    </h6>
                    
                    <?php if (empty($my_claims)): ?>
                        <div class="text-center py-4 bg-light rounded-4">
                            <p class="text-muted mb-0 small">You haven't claimed any items yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($my_claims as $claim): 
                                $status = $claim['status'] ?? 'pending';
                                $statusLabel = ucfirst($status);
                                $badgeClass = 'bg-warning-subtle text-warning';
                                $icon = 'bi-hourglass-split';
                                
                                if ($status === 'verified') {
                                    $badgeClass = 'bg-success-subtle text-success';
                                    $icon = 'bi-check-circle-fill';
                                } elseif ($status === 'rejected') {
                                    $badgeClass = 'bg-danger-subtle text-danger';
                                    $icon = 'bi-x-circle-fill';
                                }
                            ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 hover-row h-100">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="uploads/<?= htmlspecialchars($claim['item_image']) ?>" 
                                             class="rounded-3 shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-dark text-truncate" style="font-size:.9rem;"><?= htmlspecialchars($claim['item_name']) ?></div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <span class="badge <?= $badgeClass ?> border px-2 py-1 rounded-pill d-inline-flex align-items-center" style="font-size:.7rem;">
                                                    <i class="bi <?= $icon ?> me-1"></i><?= $statusLabel ?>
                                                </span>
                                                <small class="text-muted" style="font-size:.7rem;"><?= date('M d', strtotime($claim['claimed_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($status === 'verified'): ?>
                                        <div class="mt-2 p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-20">
                                            <small class="text-success fw-bold d-block" style="font-size:.75rem;">
                                                <i class="bi bi-info-circle-fill me-1"></i>Verified! 
                                            </small>
                                            <small class="text-muted d-block mt-1" style="font-size:.7rem;">
                                                Please proceed to the <b>SSG Office</b> / <b>Guard House</b> to reclaim your item.
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Submission Modal ─────────────────────────────────────────── -->
<div class="modal fade premium-modal" id="editSubmissionModal" tabindex="-1" aria-hidden="true" style="z-index: 2147483647;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden">
            <form id="headerEditForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="h_edit_id">
                <input type="hidden" name="report_type" id="h_edit_type">
                
                <div class="modal-header modal-glass-header p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-pencil-square text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Edit <span id="h_modal_type">Report</span></h5>
                            <p class="text-muted small mb-0">Update item details and status</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Image Preview Section -->
                    <div class="mb-4">
                        <label class="premium-form-label">Item Visualization</label>
                        <div class="image-preview-container shadow-sm" onclick="document.getElementById('h_edit_image').click()">
                            <img id="h_edit_preview" src="" alt="Item Preview">
                            <div class="image-overlay">
                                <i class="bi bi-camera-fill fs-2 mb-2"></i>
                                <span class="fw-bold">Change Photo</span>
                                <small class="opacity-75">Click to browse files</small>
                            </div>
                        </div>
                        <input type="file" name="image" id="h_edit_image" class="d-none" accept="image/*">
                    </div>

                    <!-- Item Basic Info -->
                    <div class="mb-3">
                        <label class="premium-form-label">Item Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: rgba(0, 51, 102, 0.12);"><i class="bi bi-tag text-muted"></i></span>
                            <input type="text" class="form-control premium-input border-start-0" name="item_name" id="h_edit_name" placeholder="e.g. Blue Backpack" required style="border-radius: 0 12px 12px 0;">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="premium-form-label">Category</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: rgba(0, 51, 102, 0.12);"><i class="bi bi-grid text-muted"></i></span>
                                <select class="form-select premium-input border-start-0" name="category" id="h_edit_cat" style="border-radius: 0 12px 12px 0;">
                                    <option value="Electronics">Electronics</option>
                                    <option value="Personal Items">Personal Items</option>
                                    <option value="School Supplies">School Supplies</option>
                                    <option value="Clothing">Clothing</option>
                                    <option value="Money or ID">Money or ID</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="premium-form-label" id="h_loc_label">Location</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: rgba(0, 51, 102, 0.12);"><i class="bi bi-geo-alt text-muted"></i></span>
                                <input type="text" class="form-control premium-input border-start-0" name="location" id="h_edit_loc" placeholder="Specific area" required style="border-radius: 0 12px 12px 0;">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="premium-form-label mb-0">Detailed Description</label>
                            <span class="text-muted small" style="font-size:0.65rem">Append common attributes:</span>
                        </div>
                        
                        <!-- Quick Add Toolbar -->
                        <div class="mb-2 d-flex flex-wrap" id="h_edit_chips">
                            <div class="attribute-chip" onclick="h_appendAttr('Color')"><i class="bi bi-palette"></i>Color</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Brand')"><i class="bi bi-award"></i>Brand</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Model')"><i class="bi bi-cpu"></i>Model</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Material')"><i class="bi bi-layers"></i>Material</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Contents')"><i class="bi bi-box-seam"></i>Contents</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Unique Marks')"><i class="bi bi-stars"></i>Unique Marks</div>
                            <div class="attribute-chip" onclick="h_appendAttr('Condition')"><i class="bi bi-shield-check"></i>Condition</div>
                        </div>

                        <textarea class="form-control premium-input auto-expand" name="description" id="h_edit_desc" rows="3" placeholder="Add unique markings, brand info, or contents..." required></textarea>
                    </div>

                    <div id="h_edit_alert" class="mt-2"></div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-premium-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium-save" id="h_edit_btn">
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Logout Confirmation Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.1);
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 20px;font-size:2rem;color:#dc2626;">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <h5 class="fw-bold mb-2">Logout?</h5>
                <p class="text-muted mb-4" style="font-size:.9rem;">
                    Are you sure you want to end your session?
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <a href="logout.php" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-box-arrow-right me-1"></i>Yes, Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Back-button prevention ──────────────────────────────────────────────
    if (<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
        history.pushState({ page: 'protected' }, '', window.location.href);
        window.addEventListener('popstate', function(e) {
            window.location.replace('login.php');
        });
    }

    // ── Navbar scroll glass effect ──────────────────────────────────────────
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        navbar.style.pointerEvents = 'auto';
        navbar.style.zIndex = '2147483647';

        window.addEventListener('scroll', function() {
            if (window.scrollY > 30) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }, { passive: true });
    }

    // ── Dropdown body-append fix ────────────────────────────────────────────
    document.querySelectorAll('.dropdown').forEach(function (dd) {
        dd.addEventListener('show.bs.dropdown', function (e) {
            const menu = dd.querySelector('.dropdown-menu');
            const toggle = dd.querySelector('[data-bs-toggle="dropdown"], .dropdown-toggle');
            if (!menu || !toggle) return;
            dd._originalMenuParent = menu.parentNode;
            dd._originalMenuNextSibling = menu.nextSibling;
            const rect = toggle.getBoundingClientRect();
            document.body.appendChild(menu);
            menu.style.position = 'absolute';
            menu.style.top = (window.scrollY + rect.bottom) + 'px';
            menu.style.left = rect.left + 'px';
            menu.style.minWidth = Math.max(rect.width, menu.offsetWidth) + 'px';
            menu.classList.add('show');
        });

        dd.addEventListener('hide.bs.dropdown', function (e) {
            const menu = dd.querySelector('.dropdown-menu');
            if (!menu) return;
            if (dd._originalMenuParent) {
                if (dd._originalMenuNextSibling) {
                    dd._originalMenuParent.insertBefore(menu, dd._originalMenuNextSibling);
                } else {
                    dd._originalMenuParent.appendChild(menu);
                }
            }
            menu.style.position = ''; menu.style.top = ''; menu.style.left = ''; menu.style.minWidth = '';
            menu.classList.remove('show');
        });
    });

    // ── Edit Submission Logic ────────────────────────────────────────────
    const hEditModal = document.getElementById('editSubmissionModal');
    const hEditForm = document.getElementById('headerEditForm');
    const h_preview = document.getElementById('h_edit_preview');

    hEditModal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        const type = btn.getAttribute('data-type');
        document.getElementById('h_edit_id').value = btn.getAttribute('data-id');
        document.getElementById('h_edit_type').value = type;
        document.getElementById('h_modal_type').textContent = type + ' Report';
        document.getElementById('h_loc_label').textContent = type === 'Found' ? 'Place Found' : 'Place Lost';
        
        document.getElementById('h_edit_name').value = btn.getAttribute('data-name');
        document.getElementById('h_edit_desc').value = btn.getAttribute('data-desc');
        document.getElementById('h_edit_loc').value = btn.getAttribute('data-loc');
        document.getElementById('h_edit_cat').value = btn.getAttribute('data-cat');
        h_preview.src = 'uploads/' + btn.getAttribute('data-img');
        document.getElementById('h_edit_image').value = '';
        document.getElementById('h_edit_alert').innerHTML = '';
        
        // Hide only if using Bootstrap's default behavior
        const myReportsModal = bootstrap.Modal.getInstance(document.getElementById('myReportsModal'));
        if (myReportsModal) myReportsModal.hide();
    });

    document.getElementById('h_edit_image').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => h_preview.src = e.target.result;
            reader.readAsDataURL(this.files[0]);
        }
    });

    hEditForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('h_edit_btn');
        const alertBox = document.getElementById('h_edit_alert');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        alertBox.innerHTML = '';

        try {
            const fd = new FormData(this);
            const type = fd.get('report_type');
            
            // Map location for backend scripts
            if(type === 'Found') {
                fd.append('item_id', fd.get('id'));
                fd.append('found_location', fd.get('location'));
            } else {
                fd.append('report_id', fd.get('id'));
                fd.append('last_seen_location', fd.get('location'));
            }

            const endpoint = type === 'Found' ? 'update_submission.php' : 'update_lost_report.php';
            const res = await fetch(endpoint, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                const id = fd.get('id');
                const rowId = 'item-' + id;
                
                // Update specific row elements
                const nameEl = document.getElementById('name-' + rowId);
                const locEl = document.getElementById('loc-' + rowId);
                const imgEl = document.getElementById('img-' + rowId);

                if(nameEl) nameEl.textContent = data.item_name;
                if(locEl) locEl.textContent = data.found_location || data.last_seen_location;
                if(imgEl && data.image_path) imgEl.src = 'uploads/' + data.image_path + '?t=' + Date.now();

                // Update data-attributes on the button
                const editBtn = document.querySelector(`[data-id="${id}"][data-type="${type}"]`);
                if(editBtn) {
                    editBtn.setAttribute('data-name', data.item_name);
                    editBtn.setAttribute('data-desc', data.description);
                    editBtn.setAttribute('data-loc', data.found_location || data.last_seen_location);
                    editBtn.setAttribute('data-cat', data.category);
                    if(data.image_path) editBtn.setAttribute('data-img', data.image_path);
                }

                alertBox.innerHTML = '<div class="alert alert-success py-2 small rounded-3">Saved successfully!</div>';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(hEditModal).hide();
                    const mrm = new bootstrap.Modal(document.getElementById('myReportsModal'));
                    mrm.show();
                }, 1000);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2 small rounded-3">${data.error || 'Failed'}</div>`;
            }
        } catch (err) {
            alertBox.innerHTML = '<div class="alert alert-danger py-2 small rounded-3">Network error.</div>';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        }
    });

    // ── Smart Description Helpers ──────────────────────────────────────────
    const descArea = document.getElementById('h_edit_desc');
    
    // Auto-expand function
    function h_syncHeight() {
        if (!descArea) return;
        descArea.style.height = 'auto';
        descArea.style.height = descArea.scrollHeight + 'px';
    }
    descArea.addEventListener('input', h_syncHeight);

    // Append attribute function
    window.h_appendAttr = function(label) {
        const text = descArea.value;
        const insertText = label + ': ';
        const cursorPos = descArea.selectionStart;
        
        // Smart spacing
        let prefix = '';
        if (text.length > 0) {
            const beforeCursor = text.substring(0, cursorPos);
            if (beforeCursor.length > 0 && !beforeCursor.endsWith('\n')) {
                prefix = '\n';
            }
        }
        
        const newValue = text.substring(0, cursorPos) + prefix + insertText + text.substring(descArea.selectionEnd);
        descArea.value = newValue;
        
        // Advance cursor
        const newPos = cursorPos + prefix.length + insertText.length;
        descArea.setSelectionRange(newPos, newPos);
        descArea.focus();
        h_syncHeight();
    };

    // Re-sync height when modal opens
    hEditModal.addEventListener('shown.bs.modal', function() {
        h_syncHeight();
    });
});
</script>