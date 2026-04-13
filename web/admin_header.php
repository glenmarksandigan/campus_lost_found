<?php 
// Database connection is assumed to be included in the main page
$lostCountStmt = $pdo->query("SELECT COUNT(*) FROM lost_reports WHERE status = 'Lost'");
$unresolvedCount = $lostCountStmt->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']); 

$_headerTypeId = (int)($_SESSION['type_id'] ?? 4);

// Robust Organizer/President Role Detection
$isPresident = $isPresident ?? false;
$organizerRole = $organizerRole ?? null;

if ($_headerTypeId === 6 && $organizerRole === null) {
    $roleStmt = $pdo->prepare("SELECT organizer_role FROM users WHERE id = ?");
    $roleStmt->execute([$_SESSION['user_id']]);
    $organizerRole = $roleStmt->fetchColumn() ?: 'member';
}
if ($organizerRole === 'president') {
    $isPresident = true;
}

$_headerRole = match($_headerTypeId) {
    5 => 'superadmin',
    6 => 'organizer',
    default => 'admin'
};
$_headerRoleLabel = match($_headerTypeId) {
    5 => 'Super Admin',
    6 => isset($organizerRole) && $organizerRole === 'president' ? '👑 SSG President' : 'SSG Organizer',
    default => 'Administrator'
};
$_headerBrandName = match($_headerTypeId) {
    5 => 'Super Admin',
    6 => 'SSG Organizer',
    default => 'BISU Candijay'
};
$_headerBrandSub = match($_headerTypeId) {
    5 => 'Observer Dashboard',
    6 => 'BISU Candijay Campus',
    default => 'Admin Panel'
};

// Pending users count for sidebar badge (admin/superadmin only)
$pendingUserCount = 0;
if ($_headerTypeId == 4 || $_headerTypeId == 5) {
    $pendingUserCount = $pdo->query("SELECT COUNT(*) FROM users WHERE (type_id = 1 OR type_id = 3) AND status = 'pending'")->fetchColumn();
}

// Unread messages for admin
$adminMsgCount = 0;
if (isset($_SESSION['user_id'])) {
    $msgStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msgStmt->execute([$_SESSION['user_id']]);
    $adminMsgCount = $msgStmt->fetchColumn();
}

// Admin name
$adminName = $_SESSION['fname'] ?? ($_SESSION['user_name'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminName, 0, 1));
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

    /* ── Sidebar ─────────────────────────────────────────────── */
    :root {
        --sidebar-w: 260px;
        --sidebar-collapsed-w: 78px;
        --sidebar-bg: #0f172a;
        --sidebar-hover: rgba(255,255,255,0.06);
        --sidebar-active: <?= match($_headerRole) { 'organizer' => 'rgba(13,148,136,0.2)', 'superadmin' => 'rgba(124,58,237,0.2)', default => 'rgba(59,130,246,0.15)' } ?>;
        --sidebar-accent: <?= match($_headerRole) { 'organizer' => '#0d9488', 'superadmin' => '#7c3aed', default => '#3b82f6' } ?>;
        --sidebar-text: rgba(255,255,255,0.65);
        --sidebar-text-active: #ffffff;
        --transition-speed: 0.25s;

        /* Unified Admin UI Palette */
        --admin-blue: #0d6efd;
        --admin-dark: #0f172a;
        --admin-bg: #f1f5f9;
        --admin-card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        --admin-header-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }

    body { background-color: var(--admin-bg) !important; min-height: 100vh; }

    /* ── Harmonized Admin Components ────────────────────────── */
    .admin-page-header {
        background: var(--admin-header-gradient);
        border-radius: 20px; padding: 2rem; color: white;
        margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .admin-card {
        border: none; border-radius: 16px;
        box-shadow: var(--admin-card-shadow);
        background: white; overflow: hidden;
    }

    .admin-table thead th {
        background-color: #f8fafc; font-weight: 700;
        text-transform: uppercase; font-size: 0.72rem;
        letter-spacing: 0.05em; color: #94a3b8;
        border: none; padding: 1.1rem 1rem;
    }

    .admin-table tbody td { 
        padding: 1rem; border-color: #f1f5f9; 
        vertical-align: middle; font-size: 0.875rem;
    }

    .admin-table tbody tr:hover { background-color: rgba(13,110,253,0.02); }

    .status-badge {
        font-size: 0.7rem; font-weight: 700; padding: 6px 14px;
        border-radius: 20px; display: inline-block;
        text-transform: uppercase; letter-spacing: 0.5px;
    }

    /* Standard Status Colors */
    .bg-status-pending  { background:#fef3c7; color:#d97706; border:1px solid #fde68a; }
    .bg-status-success  { background:#dcfce7; color:#16a34a; border:1px solid #86efac; }
    .bg-status-danger   { background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; }
    .bg-status-info     { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }

    /* Sidebar Base */

    .admin-sidebar {
        position: fixed; top: 0; left: 0; bottom: 0;
        width: var(--sidebar-w);
        background: var(--sidebar-bg);
        z-index: 1040;
        display: flex; flex-direction: column;
        transition: width var(--transition-speed) cubic-bezier(0.4,0,0.2,1);
        overflow: hidden;
        border-right: 1px solid rgba(255,255,255,0.06);
    }

    .admin-sidebar.collapsed { width: var(--sidebar-collapsed-w); }

    /* Brand */
    .sidebar-brand {
        padding: 20px 18px;
        display: flex; align-items: center; gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        min-height: 72px;
        flex-shrink: 0;
    }
    .sidebar-brand img { height: 38px; width: 38px; object-fit: contain; flex-shrink: 0; }
    .sidebar-brand-text { white-space: nowrap; overflow: hidden; transition: opacity var(--transition-speed); }
    .sidebar-brand-text .brand-name { color: #fff; font-weight: 800; font-size: 0.95rem; line-height: 1.15; }
    .sidebar-brand-text .brand-sub { color: var(--sidebar-accent); font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
    .admin-sidebar.collapsed .sidebar-brand-text { opacity: 0; width: 0; }
    .admin-sidebar.collapsed .sidebar-brand { justify-content: center; padding: 20px 0; }

    /* Section label */
    .sidebar-section-label {
        padding: 20px 22px 8px;
        font-size: 0.62rem; font-weight: 700;
        letter-spacing: 0.1em; text-transform: uppercase;
        color: rgba(255,255,255,0.25);
        white-space: nowrap; overflow: hidden;
        transition: opacity var(--transition-speed);
    }
    .admin-sidebar.collapsed .sidebar-section-label { opacity: 0; height: 0; padding: 0; margin: 0; }

    /* Nav items */
    .sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px 10px; }
    .sidebar-nav::-webkit-scrollbar { width: 4px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

    .sidebar-link {
        display: flex; align-items: center; gap: 14px;
        padding: 11px 14px;
        color: var(--sidebar-text);
        text-decoration: none;
        border-radius: 10px;
        font-size: 0.86rem; font-weight: 500;
        white-space: nowrap;
        transition: all 0.15s ease;
        position: relative;
        margin-bottom: 2px;
        /* Button resets for <button> nav items */
        border: none;
        background: transparent;
        cursor: pointer;
        width: 100%;
        text-align: left;
        font-family: inherit;
    }
    .sidebar-link:hover { background: var(--sidebar-hover); color: var(--sidebar-text-active); }
    .sidebar-link.active {
        background: var(--sidebar-active);
        color: var(--sidebar-text-active);
        font-weight: 600;
    }
    .sidebar-link.active::before {
        content: '';
        position: absolute; left: 0; top: 50%; transform: translateY(-50%);
        width: 3px; height: 24px;
        background: var(--sidebar-accent);
        border-radius: 0 4px 4px 0;
    }
    .sidebar-link i.nav-icon { font-size: 1.15rem; width: 22px; text-align: center; flex-shrink: 0; }
    .sidebar-link .link-text { white-space: nowrap; overflow: hidden; transition: opacity var(--transition-speed); }
    .admin-sidebar.collapsed .sidebar-link .link-text { opacity: 0; width: 0; }
    .admin-sidebar.collapsed .sidebar-link { justify-content: center; padding: 11px 0; }
    .admin-sidebar.collapsed .sidebar-link .badge { display: none; }

    .sidebar-badge {
        margin-left: auto; font-size: 0.62rem; font-weight: 700;
        padding: 2px 7px; border-radius: 20px;
        flex-shrink: 0;
    }

    /* Sidebar footer */
    .sidebar-footer {
        padding: 14px 10px;
        border-top: 1px solid rgba(255,255,255,0.06);
        flex-shrink: 0;
    }
    .sidebar-user-info {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 14px; border-radius: 10px;
        background: rgba(255,255,255,0.04);
        margin-bottom: 8px;
    }
    .sidebar-user-avatar {
        width: 34px; height: 34px; border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 0.82rem; flex-shrink: 0;
    }
    .sidebar-user-name { color: #fff; font-weight: 600; font-size: 0.82rem; white-space: nowrap; overflow: hidden; }
    .sidebar-user-role { color: var(--sidebar-text); font-size: 0.68rem; }
    .admin-sidebar.collapsed .sidebar-user-info { justify-content: center; padding: 8px 0; }
    .admin-sidebar.collapsed .sidebar-user-name,
    .admin-sidebar.collapsed .sidebar-user-role { display: none; }

    .sidebar-logout {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 9px 14px;
        background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.15);
        color: #f87171; border-radius: 10px;
        font-size: 0.82rem; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .sidebar-logout:hover { background: rgba(239,68,68,0.2); color: #fca5a5; }
    .admin-sidebar.collapsed .sidebar-logout span { display: none; }

    /* Toggle button */
    .sidebar-toggle {
        position: fixed;
        top: 20px;
        left: calc(var(--sidebar-w) - 14px);
        width: 28px; height: 28px;
        border-radius: 50%;
        background: var(--sidebar-bg);
        border: 2px solid rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.6);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 1041;
        font-size: 0.75rem;
        transition: all var(--transition-speed) cubic-bezier(0.4,0,0.2,1);
    }
    .sidebar-toggle:hover { background: #1e293b; color: #fff; border-color: var(--sidebar-accent); }
    .admin-sidebar.collapsed ~ .sidebar-toggle { left: calc(var(--sidebar-collapsed-w) - 14px); }
    .admin-sidebar.collapsed ~ .sidebar-toggle i { transform: rotate(180deg); }

    /* Main content offset */
    .admin-main-content {
        margin-left: var(--sidebar-w);
        transition: margin-left var(--transition-speed) cubic-bezier(0.4,0,0.2,1);
        min-height: 100vh;
    }
    .admin-sidebar.collapsed ~ .sidebar-toggle ~ .admin-main-content,
    .admin-sidebar.collapsed ~ .admin-main-content { margin-left: var(--sidebar-collapsed-w); }

    /* Mobile overlay */
    .sidebar-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 1039;
        backdrop-filter: blur(2px);
        opacity: 0; transition: opacity 0.25s;
    }
    .sidebar-overlay.show { display: block; opacity: 1; }

    /* Mobile hamburger */
    .mobile-topbar {
        display: none;
        position: fixed; top: 0; left: 0; right: 0;
        height: 60px; z-index: 1038;
        background: var(--sidebar-bg);
        padding: 0 16px;
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .mobile-topbar-brand { display: flex; align-items: center; gap: 10px; }
    .mobile-topbar-brand img { height: 32px; }
    .mobile-topbar-brand span { color: #fff; font-weight: 800; font-size: 0.9rem; }
    .mobile-hamburger {
        background: none; border: none; color: rgba(255,255,255,0.8);
        font-size: 1.4rem; cursor: pointer; padding: 6px;
    }

    @media (max-width: 991.98px) {
        .mobile-topbar { display: flex; justify-content: space-between; }
        .admin-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            width: var(--sidebar-w) !important;
        }
        .admin-sidebar.mobile-open { transform: translateX(0); }
        .admin-sidebar .sidebar-brand-text { opacity: 1 !important; width: auto !important; }
        .admin-sidebar .sidebar-link .link-text { opacity: 1 !important; width: auto !important; }
        .admin-sidebar .sidebar-link { justify-content: flex-start !important; padding: 11px 14px !important; }
        .admin-sidebar .sidebar-link .badge { display: inline-flex !important; }
        .admin-sidebar .sidebar-section-label { opacity: 1 !important; height: auto !important; padding: 20px 22px 8px !important; }
        .admin-sidebar .sidebar-user-name,
        .admin-sidebar .sidebar-user-role { display: block !important; }
        .admin-sidebar .sidebar-logout span { display: inline !important; }

        .sidebar-toggle { display: none !important; }
        .admin-main-content { margin-left: 0 !important; padding-top: 60px; }
    }

    /* Tooltip for collapsed sidebar */
    .admin-sidebar.collapsed .sidebar-link { position: relative; }
    .admin-sidebar.collapsed .sidebar-link::after {
        content: attr(data-tooltip);
        position: absolute; left: calc(100% + 12px); top: 50%; transform: translateY(-50%);
        background: #1e293b; color: #fff;
        padding: 5px 12px; border-radius: 6px;
        font-size: 0.78rem; font-weight: 600;
        white-space: nowrap;
        opacity: 0; pointer-events: none;
        transition: opacity 0.15s;
        z-index: 1050;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .admin-sidebar.collapsed .sidebar-link:hover::after { opacity: 1; }
    /* ── Toasts & Alerts ────────────────────────────────────── */
    .admin-toast-container { position: fixed; top: 20px; right: 20px; z-index: 1060; }
    .admin-toast { background: white; border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); min-width: 280px; }
    .toast-success { border-left: 4px solid #10b981; }
    .toast-danger { border-left: 4px solid #ef4444; }
    .toast-info { border-left: 4px solid #3b82f6; }
    .toast-warning { border-left: 4px solid #f59e0b; }

    /* Higher z-index for priority components to prevent overlapping with other modals */
    #confirmModal { z-index: 1090 !important; }
    .confirm-backdrop { z-index: 1085 !important; }
</style>

<!-- ── Mobile Top Bar ───────────────────────────────────────── -->
<div class="mobile-topbar">
    <div class="mobile-topbar-brand">
        <img src="uploads/BISU-LOGO.png" alt="BISU" onerror="this.style.display='none'">
        <span>Admin Panel</span>
    </div>
    <button class="mobile-hamburger" onclick="toggleMobileSidebar()">
        <i class="bi bi-list"></i>
    </button>
</div>

<!-- ── Sidebar ──────────────────────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="uploads/BISU-LOGO.png" alt="BISU" onerror="this.style.display='none'">
        <div class="sidebar-brand-text">
            <div class="brand-name"><?= $_headerBrandName ?></div>
            <div class="brand-sub" style="color:var(--sidebar-accent)"><?= $_headerBrandSub ?></div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

    <?php if ($_headerRole === 'admin'): ?>
        <!-- ── Admin Navigation ──────────────────────────────── -->
        <div class="sidebar-section-label">Main</div>
        <a href="admin.php" class="sidebar-link <?= $current_page == 'admin.php' ? 'active' : '' ?>" data-tooltip="Dashboard">
            <i class="bi bi-grid-1x2-fill nav-icon"></i>
            <span class="link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Management</div>
        <a href="manage_found.php" class="sidebar-link <?= $current_page == 'manage_found.php' ? 'active' : '' ?>" data-tooltip="Found Items">
            <i class="bi bi-box-seam nav-icon"></i>
            <span class="link-text">Found Items</span>
        </a>
        <a href="manage_lost.php" class="sidebar-link <?= $current_page == 'manage_lost.php' ? 'active' : '' ?>" data-tooltip="Lost Reports">
            <i class="bi bi-search nav-icon"></i>
            <span class="link-text">Lost Reports</span>
            <?php if ($unresolvedCount > 0): ?>
            <span class="sidebar-badge bg-danger"><?= $unresolvedCount ?></span>
            <?php endif; ?>
        </a>
        <a href="manage_users.php" class="sidebar-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>" data-tooltip="Manage Users">
            <i class="bi bi-people nav-icon"></i>
            <span class="link-text">Manage Users</span>
            <?php if ($pendingUserCount > 0): ?>
            <span class="sidebar-badge bg-warning text-dark"><?= $pendingUserCount ?></span>
            <?php endif; ?>
        </a>
        <a href="admin.php?section=reports" class="sidebar-link <?= ($current_page == 'admin.php' && ($_GET['section'] ?? '') == 'reports') ? 'active' : '' ?>" data-tooltip="System Reports">
            <i class="bi bi-graph-up-arrow nav-icon"></i>
            <span class="link-text">System Reports</span>
        </a>
        <a href="view_conversations.php" class="sidebar-link <?= $current_page == 'view_conversations.php' ? 'active' : '' ?>" data-tooltip="User Conversations">
            <i class="bi bi-chat-square-text nav-icon"></i>
            <span class="link-text">User Conversations</span>
        </a>

        <div class="sidebar-section-label">Log</div>
        <a href="admin.php?section=activity" class="sidebar-link <?= ($current_page == 'admin.php' && ($_GET['section'] ?? '') == 'activity') ? 'active' : '' ?>" data-tooltip="Activity Log">
            <i class="bi bi-activity nav-icon"></i>
            <span class="link-text">Activity Log</span>
        </a>

    <?php elseif ($_headerRole === 'organizer'): ?>
        <!-- ── Organizer Navigation ────────────────────────────── -->
        <div class="sidebar-section-label">Main</div>
        <a href="organizer_dashboard.php" class="sidebar-link <?= $current_page == 'organizer_dashboard.php' ? 'active' : '' ?>" data-tooltip="Overview">
            <i class="bi bi-grid-1x2 nav-icon"></i>
            <span class="link-text">Overview</span>
        </a>

        <div class="sidebar-section-label">Management</div>
        <a href="manage_found.php" class="sidebar-link <?= $current_page == 'manage_found.php' ? 'active' : '' ?>" data-tooltip="Found Items">
            <i class="bi bi-box-seam nav-icon"></i>
            <span class="link-text">Found Items</span>
        </a>
        <a href="manage_lost.php" class="sidebar-link <?= $current_page == 'manage_lost.php' ? 'active' : '' ?>" data-tooltip="Lost Reports">
            <i class="bi bi-search nav-icon"></i>
            <span class="link-text">Lost Reports</span>
            <?php if ($unresolvedCount > 0): ?>
            <span class="sidebar-badge bg-danger"><?= $unresolvedCount ?></span>
            <?php endif; ?>
        </a>

        <?php if ($isPresident): ?>
        <a href="manage_users.php" class="sidebar-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>" data-tooltip="Manage Organizers">
            <i class="bi bi-person-gear nav-icon"></i>
            <span class="link-text">Manage Organizers</span>
        </a>
        <?php endif; ?>




    <?php elseif ($_headerRole === 'superadmin'): ?>
        <!-- ── Super Admin Navigation ──────────────────────── -->
        <?php $_onSaDashboard = ($current_page === 'superadmin_dashboard.php'); ?>
        <div class="sidebar-section-label">Monitoring</div>
        <?php if ($_onSaDashboard): ?>
        <button class="sidebar-link active" onclick="goTo('dashboard',this)" data-tooltip="Dashboard">
            <i class="bi bi-grid-1x2 nav-icon"></i>
            <span class="link-text">Dashboard</span>
        </button>
        <button class="sidebar-link" onclick="goTo('activity-log',this)" data-tooltip="Activity Log">
            <i class="bi bi-activity nav-icon"></i>
            <span class="link-text">Activity Log</span>
            <?php if(!empty($recentActivity)): ?>
            <span class="sidebar-badge bg-primary"><?= count($recentActivity) ?></span>
            <?php endif; ?>
        </button>
        <button class="sidebar-link" onclick="goTo('organizer-team',this)" data-tooltip="Organizer Team">
            <i class="bi bi-people nav-icon"></i>
            <span class="link-text">Organizer Team</span>
        </button>
        <div class="sidebar-section-label">Analytics</div>
        <button class="sidebar-link" onclick="goTo('reports',this)" data-tooltip="Reports">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            <span class="link-text">Reports</span>
        </button>
        <?php else: ?>
        <a href="superadmin_dashboard.php" class="sidebar-link" data-tooltip="Dashboard">
            <i class="bi bi-grid-1x2 nav-icon"></i>
            <span class="link-text">Dashboard</span>
        </a>
        <a href="superadmin_dashboard.php" class="sidebar-link" data-tooltip="Activity Log">
            <i class="bi bi-activity nav-icon"></i>
            <span class="link-text">Activity Log</span>
        </a>
        <a href="superadmin_dashboard.php" class="sidebar-link" data-tooltip="Organizer Team">
            <i class="bi bi-people nav-icon"></i>
            <span class="link-text">Organizer Team</span>
        </a>
        <div class="sidebar-section-label">Analytics</div>
        <a href="superadmin_dashboard.php" class="sidebar-link" data-tooltip="Reports">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            <span class="link-text">Reports</span>
        </a>
        <?php endif; ?>
        <?php if ($_onSaDashboard): ?>
        <button class="sidebar-link" onclick="goTo('user-conversations',this)" data-tooltip="User Conversations">
            <i class="bi bi-chat-square-text nav-icon"></i>
            <span class="link-text">User Conversations</span>
        </button>
        <?php else: ?>
        <a href="superadmin_dashboard.php?section=user-conversations" class="sidebar-link" data-tooltip="User Conversations">
            <i class="bi bi-chat-square-text nav-icon"></i>
            <span class="link-text">User Conversations</span>
        </a>
        <?php endif; ?>
        <div class="sidebar-section-label">Management</div>
        <a href="manage_users.php" class="sidebar-link <?= $current_page == 'manage_users.php' ? 'active' : '' ?>" data-tooltip="Manage Admins">
            <i class="bi bi-people-fill nav-icon"></i>
            <span class="link-text">Manage Admins</span>
        </a>
    <?php endif; ?>

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar" style="background:linear-gradient(135deg, var(--sidebar-accent), color-mix(in srgb, var(--sidebar-accent) 70%, black))"><?= $adminInitial ?></div>
            <div>
                <div class="sidebar-user-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="sidebar-user-role"><?= $_headerRoleLabel ?></div>
            </div>
        </div>
        <button type="button" class="sidebar-logout" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="bi bi-box-arrow-left"></i>
            <span>Log Out</span>
        </button>
    </div>
</aside>

<!-- ── Toggle Button (desktop) ──────────────────────────────── -->
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="bi bi-chevron-left"></i>
</button>

<!-- ── Mobile Overlay ───────────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

<!-- ── Main Content Wrapper (opened here, closed in each page) ── -->
<div class="admin-main-content">

<!-- ── Global Toast Container ──────────────────────────────── -->
<div class="admin-toast-container">
    <div id="adminToast" class="toast admin-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-0 pb-0">
            <i id="toastIcon" class="bi me-2"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body pt-1 pb-3" id="toastMsg"></div>
    </div>
</div>

<!-- ── Professional Confirmation Modal ───────────────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div id="confirmIconContainer" style="width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:2rem;">
                    <i id="confirmIcon" class="bi"></i>
                </div>
                <h5 class="fw-bold mb-2" id="confirmTitle">Confirm Action</h5>
                <p class="text-muted mb-4" id="confirmMsg" style="font-size:.9rem;"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmActionBtn" class="btn rounded-pill px-4 fw-bold">Proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Logout Confirmation Modal ────────────────────────────── -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content border-0" style="border-radius:20px; overflow:hidden;">
            <div class="modal-body text-center p-5">
                <div style="width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.1);
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 20px;font-size:2rem;color:#dc2626;">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <h5 class="fw-bold mb-2">Logout?</h5>
                <p class="text-muted mb-4" style="font-size:.9rem;">
                    Are you sure you want to end your admin session?
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
// ── Professional Notification Helpers ───────────────────────
function showToast(msg, type = 'info') {
    const toastEl = document.getElementById('adminToast');
    const toastTitle = document.getElementById('toastTitle');
    const toastMsg = document.getElementById('toastMsg');
    const toastIcon = document.getElementById('toastIcon');
    
    // Reset classes
    toastEl.classList.remove('toast-success', 'toast-danger', 'toast-info', 'toast-warning');
    toastIcon.classList.remove('bi-check-circle-fill', 'bi-exclamation-triangle-fill', 'bi-info-circle-fill', 'bi-exclamation-circle-fill');
    
    let colorClass = 'toast-info';
    let iconClass = 'bi-info-circle-fill';
    let title = 'Notification';
    let iconColor = '#3b82f6';

    if (type === 'success') {
        colorClass = 'toast-success';
        iconClass = 'bi-check-circle-fill';
        title = 'Success';
        iconColor = '#10b981';
    } else if (type === 'danger' || type === 'error') {
        colorClass = 'toast-danger';
        iconClass = 'bi-exclamation-circle-fill';
        title = 'Error';
        iconColor = '#ef4444';
    } else if (type === 'warning') {
        colorClass = 'toast-warning';
        iconClass = 'bi-exclamation-triangle-fill';
        title = 'Warning';
        iconColor = '#f59e0b';
    }

    toastEl.classList.add(colorClass);
    toastIcon.classList.add(iconClass);
    toastIcon.style.color = iconColor;
    toastTitle.textContent = title;
    toastMsg.textContent = msg;

    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();
}

function showConfirm(options = {}) {
    const {
        title = 'Are you sure?',
        msg = 'Do you want to proceed with this action?',
        icon = 'bi-question-circle-fill',
        type = 'primary',
        confirmText = 'Proceed',
        onConfirm = () => {}
    } = options;

    const modalEl = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const msgEl = document.getElementById('confirmMsg');
    const iconEl = document.getElementById('confirmIcon');
    const iconContainer = document.getElementById('confirmIconContainer');
    const actionBtn = document.getElementById('confirmActionBtn');

    titleEl.textContent = title;
    msgEl.innerHTML = msg;
    iconEl.className = `bi ${icon}`;
    
    // Set colors based on type
    const colors = {
        primary: { bg: 'rgba(13,110,253,0.1)', text: '#0d6efd', btn: 'btn-primary' },
        danger: { bg: 'rgba(220,38,38,0.1)', text: '#dc2626', btn: 'btn-danger' },
        success: { bg: 'rgba(16,185,129,0.1)', text: '#10b981', btn: 'btn-success' },
        warning: { bg: 'rgba(245,158,11,0.1)', text: '#f59e0b', btn: 'btn-warning' }
    };
    
    const theme = colors[type] || colors.primary;
    iconContainer.style.background = theme.bg;
    iconContainer.style.color = theme.text;
    
    actionBtn.className = `btn rounded-pill px-4 fw-bold ${theme.btn}`;
    actionBtn.textContent = confirmText;

    const bsModal = new bootstrap.Modal(modalEl);
    
    const handleConfirm = () => {
        onConfirm();
        bsModal.hide();
        actionBtn.removeEventListener('click', handleConfirm);
    };

    actionBtn.onclick = handleConfirm;
    bsModal.show();

    // Fix for stacked modals: Bootstrap 5 doesn't automatically handle z-index for multiple open modals
    // We adjust the z-index of the backdrop manually if it appears
    setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            const lastBackdrop = backdrops[backdrops.length - 1];
            lastBackdrop.style.zIndex = '1085';
            lastBackdrop.classList.add('confirm-backdrop');
        }
    }, 50);
}

// ── Sidebar toggle (desktop) ────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

// Restore sidebar state
(function() {
    if (window.innerWidth > 991 && localStorage.getItem('sidebarCollapsed') === 'true') {
        document.getElementById('adminSidebar').classList.add('collapsed');
    }
})();

// ── Mobile sidebar ──────────────────────────────────────────
function toggleMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('show');
    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
}

function closeMobileSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// Close mobile sidebar on resize to desktop
window.addEventListener('resize', () => {
    if (window.innerWidth > 991) closeMobileSidebar();
});
</script>