<?php 
include 'auth.php';
include 'db.php'; 

// Fetch Resolved Lost Reports with date filtering
$resolvedLost = $pdo->query("SELECT * FROM lost_reports WHERE status = 'Resolved' ORDER BY created_at DESC")->fetchAll();

// Fetch Returned Found Items
$returnedFound = $pdo->query("SELECT * FROM items WHERE status = 'Returned' ORDER BY created_at DESC")->fetchAll();

$totalRecovered = count($resolvedLost) + count($returnedFound);

// Calculate this month's recoveries
$thisMonth = date('Y-m');
$thisMonthRecoveries = 0;

foreach ($resolvedLost as $item) {
    if (date('Y-m', strtotime($item['created_at'])) === $thisMonth) {
        $thisMonthRecoveries++;
    }
}

foreach ($returnedFound as $item) {
    if (date('Y-m', strtotime($item['created_at'])) === $thisMonth) {
        $thisMonthRecoveries++;
    }
}

// Calculate success rate
$totalItems = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$successRate = $totalItems > 0 ? round((count($returnedFound) / $totalItems) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success Log | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
    /* Shared styles in admin_header.php */
    .stat-box {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 1.25rem;
        border-radius: 16px;
        text-align: center;
        flex: 1;
        border: 1px solid rgba(255,255,255,0.1);
    }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .stat-box {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .stat-box h2 {
        font-size: 2.2rem;
        font-weight: 800;
        margin: 0;
        color: #fff;
    }
        
        .stat-box small {
            opacity: 0.95;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .success-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .success-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .success-card-found {
            border-left: 5px solid var(--success-green);
        }
        
        .success-card-lost {
            border-left: 5px solid var(--bisu-blue);
        }
        
        .section-header {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .section-header h4 {
            margin: 0;
            font-weight: 700;
        }
        
        .item-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .icon-found {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: var(--success-dark);
        }
        
        .icon-lost {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            background: white;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .filter-tab:hover {
            border-color: var(--success-green);
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        .filter-tab.active {
            background: var(--success-green);
            color: white;
            border-color: var(--success-green);
        }
        
        .timeline-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .dot-success {
            background: var(--success-green);
        }
        
        .dot-primary {
            background: var(--bisu-blue);
        }
        
        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="container-fluid px-4 mt-4">
    <!-- Enhanced Page Header -->
    <div class="admin-page-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-trophy-fill me-2 text-warning"></i>Success Log
                </h2>
                <p class="mb-0 opacity-75">Items successfully reunited with their owners</p>
            </div>
        </div>
        
        <!-- Stats Row in Header -->
        <div class="d-flex gap-3 flex-wrap">
            <div class="stat-box">
                <h2 class="counter-val" data-target="<?= $totalRecovered ?>"><?= $totalRecovered ?></h2>
                <small>Total Recovered</small>
            </div>
            <div class="stat-box">
                <h2 class="counter-val" data-target="<?= count($returnedFound) ?>"><?= count($returnedFound) ?></h2>
                <small>Found Items</small>
            </div>
            <div class="stat-box">
                <h2 class="counter-val" data-target="<?= count($resolvedLost) ?>"><?= count($resolvedLost) ?></h2>
                <small>Lost Reports</small>
            </div>
            <div class="stat-box">
                <h2 class="counter-val" data-target="<?= $thisMonthRecoveries ?>"><?= $thisMonthRecoveries ?></h2>
                <small>This Month</small>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="admin-card mb-4">
        <div class="card-body p-3">

    <?php if ($totalRecovered == 0): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="bi bi-trophy"></i>
            <h3 class="fw-bold text-dark mb-2">No Success Stories Yet</h3>
            <p class="text-muted mb-4">
                Recovered items will appear here when items are successfully returned to their owners.
            </p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="manage_found.php" class="btn btn-success">
                    <i class="bi bi-box-seam me-2"></i>Manage Found Items
                </a>
                <a href="manage_lost.php" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Lost Reports
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Content Grid -->
        <div class="row g-4">
            <!-- Found Items Column -->
            <div class="col-lg-6" data-category="found">
                <div class="section-header">
                    <h4 class="text-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Returned Items (<?php echo count($returnedFound); ?>)
                    </h4>
                    <small class="text-muted">Items from the found gallery that were claimed</small>
                </div>

                <?php if (empty($returnedFound)): ?>
                    <div class="card success-card p-4 text-center">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-0">No returned items yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach($returnedFound as $item): ?>
                        <div class="card success-card success-card-found" data-search="<?php echo strtolower($item['item_name']); ?>">
                            <div class="card-body">
                                <div class="d-flex gap-3">
                                    <div class="item-icon icon-found flex-shrink-0">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </h6>
                                            <span class="badge bg-success badge-custom">Returned</span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                                Found at: <?php echo htmlspecialchars($item['found_location']); ?>
                                            </small>
                                        </div>

                                        <?php if (!empty($item['finder_name'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-person-check text-success me-1"></i>
                                                    Found by: <?php echo htmlspecialchars($item['finder_name']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center small text-muted">
                                            <span class="timeline-dot dot-success"></span>
                                            <i class="bi bi-calendar-check me-2"></i>
                                            Returned on <?php echo date('F d, Y', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Lost Reports Column -->
            <div class="col-lg-6" data-category="lost">
                <div class="section-header">
                    <h4 class="text-primary">
                        <i class="bi bi-person-check-fill me-2"></i>
                        Resolved Reports (<?php echo count($resolvedLost); ?>)
                    </h4>
                    <small class="text-muted">Lost item reports that were successfully resolved</small>
                </div>

                <?php if (empty($resolvedLost)): ?>
                    <div class="card success-card p-4 text-center">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2 mb-0">No resolved reports yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach($resolvedLost as $report): ?>
                        <div class="card success-card success-card-lost" data-search="<?php echo strtolower($report['item_name'] . ' ' . $report['owner_name']); ?>">
                            <div class="card-body">
                                <div class="d-flex gap-3">
                                    <div class="item-icon icon-lost flex-shrink-0">
                                        <i class="bi bi-search"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <?php echo htmlspecialchars($report['item_name']); ?>
                                            </h6>
                                            <span class="badge bg-primary badge-custom">Resolved</span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-person-fill text-primary me-1"></i>
                                                Owner: <?php echo htmlspecialchars($report['owner_name']); ?>
                                            </small>
                                        </div>

                                        <?php if (!empty($report['last_seen_location'])): ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                                    Last seen: <?php echo htmlspecialchars($report['last_seen_location']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex align-items-center small text-muted">
                                            <span class="timeline-dot dot-primary"></span>
                                            <i class="bi bi-check-circle-fill text-primary me-2"></i>
                                            Resolved on <?php echo date('F d, Y', strtotime($report['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Search functionality
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.success-card[data-search]').forEach(card => {
        const text = card.dataset.search;
        card.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filter view
function filterView(category) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.closest('.filter-tab').classList.add('active');
    
    // Show/hide columns
    const foundCol = document.querySelector('[data-category="found"]');
    const lostCol = document.querySelector('[data-category="lost"]');
    
    if (category === 'all') {
        if (foundCol) foundCol.style.display = '';
        if (lostCol) lostCol.style.display = '';
    } else if (category === 'found') {
        if (foundCol) {
            foundCol.style.display = '';
            foundCol.classList.remove('col-lg-6');
            foundCol.classList.add('col-lg-12');
        }
        if (lostCol) lostCol.style.display = 'none';
    } else if (category === 'lost') {
        if (foundCol) foundCol.style.display = 'none';
        if (lostCol) {
            lostCol.style.display = '';
            lostCol.classList.remove('col-lg-6');
            lostCol.classList.add('col-lg-12');
        }
    }
    
    // Reset column classes when showing all
    if (category === 'all') {
        if (foundCol) {
            foundCol.classList.remove('col-lg-12');
            foundCol.classList.add('col-lg-6');
        }
        if (lostCol) {
            lostCol.classList.remove('col-lg-12');
            lostCol.classList.add('col-lg-6');
        }
    }
}

// ── Counter animation ────────────────────────────────────────────
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
</script>
</div><!-- /admin-main-content -->
</body>
</html>