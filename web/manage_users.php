<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Role hierarchy access control ─────────────────────────────────────
// SuperAdmin (5) → creates Admin (4)
// Admin (4) → creates SSG President (6/president) + manages students/staff
// SSG President (6/president) → creates Organizers (6/member)
$_mu_tid = (int)($_SESSION['type_id'] ?? 0);
$_mu_isPresident = false;
if ($_mu_tid === 6) {
    // Check if president via a quick query before db.php is loaded
    // We'll verify again after db.php
    $_mu_isPresident = true; // tentative, verified below
}
$allowed_types = [4, 5, 6];
if (!isset($_SESSION['user_id']) || !in_array($_mu_tid, $allowed_types)) {
    header("Location: auth.php"); exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include 'db.php';
require_once 'mail_helper.php';
include 'activity_logger.php';

// Verify SSG President role for type_id=6
if ($_mu_tid === 6) {
    $prChk = $pdo->prepare("SELECT organizer_role FROM users WHERE id = ?");
    $prChk->execute([$_SESSION['user_id']]);
    $_mu_isPresident = ($prChk->fetchColumn() === 'president');
    if (!$_mu_isPresident) {
        header("Location: organizer_dashboard.php"); exit;
    }
}

// ── Handle Add User (POST) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $role      = $_POST['role']      ?? 'student';
    $fname     = trim($_POST['fname']    ?? '');
    $lname     = trim($_POST['lname']    ?? '');
    $email     = trim($_POST['email']    ?? '');
    $pass      = $_POST['password']      ?? '';
    $year      = trim($_POST['year']     ?? '');

    // Default names if omitted
    if (!$fname && $role === 'ssg_president') { $fname = "SSG"; }
    if (!$lname && $role === 'ssg_president') { $lname = "President"; }
    if (!$fname) $fname = "New";
    if (!$lname) $lname = "User";

    if (!$email || !$pass) {
        header("Location: manage_users.php?msg=Email+and+password+are+required.&t=danger"); exit;
    }

    // ── Enforce role hierarchy ────────────────────────────────────────
    // SuperAdmin (5) can only create Admin (4)
    // Admin (4) can create student, staff, OR ssg_president
    // SSG President (6) can only create organizer
    if ($_mu_tid === 5 && $role !== 'admin') {
        header("Location: manage_users.php?msg=SuperAdmin+can+only+create+Admin+accounts.&t=danger"); exit;
    }
    if ($_mu_tid === 6 && $role !== 'organizer') {
        header("Location: manage_users.php?msg=SSG+President+can+only+create+Organizer+accounts.&t=danger"); exit;
    }
    if ($_mu_tid === 4 && !in_array($role, ['student', 'staff', 'ssg_president'])) {
        header("Location: manage_users.php?msg=Invalid+role+selected.&t=danger"); exit;
    }

    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        header("Location: manage_users.php?msg=Email+already+exists!&t=danger"); exit;
    }

    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    $rawPassword = $pass; // for email reference if needed
    
    // Determine type_id and organizer_role based on selected role
    if ($role === 'admin') {
        $typeId = 4;
        $orgRole = null;
        $canEditVal = null;
    } elseif ($role === 'ssg_president') {
        $typeId = 6;
        $orgRole = 'president';
        $canEditVal = 1;
    } elseif ($role === 'organizer') {
        $typeId = 6;
        $orgRole = 'member';
        $canEditVal = 0;
    } elseif ($role === 'staff') {
        $typeId = 3;
        $orgRole = null;
        $canEditVal = null;
    } else {
        $typeId = 1;
        $orgRole = null;
        $canEditVal = null;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO users (fname, lname, email, password, type_id, status, is_activated, force_password_reset, year, organizer_role, can_edit, created_at)
            VALUES (?, ?, ?, ?, ?, 'approved', 0, 0, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $fname, $lname, $email, $hashedPassword, $typeId,
            $year,
            $orgRole,
            $canEditVal
        ]);
        
        $newUserId = $pdo->lastInsertId();
        
        // Legacy table inserts for students and staff
        if ($role === 'student') {
            $pdo->prepare("INSERT INTO students (user_id, student_id) VALUES (?, ?)")->execute([$newUserId, $studentId]);
        } elseif ($role === 'staff') {
            $pdo->prepare("INSERT INTO employees (user_id, department_id, position) VALUES (?, ?, ?)")->execute([$newUserId, $deptId, $position]);
        }
        
        $newUserId = $pdo->lastInsertId();
        
        $roleLabel = match($role) {
            'admin' => 'Admin',
            'ssg_president' => 'SSG President',
            'organizer' => 'Organizer',
            'staff' => 'Staff',
            default => 'Student'
        };

        logActivity($pdo, $_SESSION['user_id'], 'create', 'user', $newUserId, "Created $roleLabel account: $fname $lname ($email)");
        
        $pdo->commit();
        header("Location: manage_users.php?msg=$roleLabel+account+created!&t=success"); exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: manage_users.php?msg=Error+adding+user:+".urlencode($e->getMessage())."&t=danger"); exit;
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $id     = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$id]);
        
        // Fetch user info for email
        $u = $pdo->prepare("SELECT fname, lname, email FROM users WHERE id = ?");
        $u->execute([$id]);
        $user = $u->fetch();
        if ($user) {
            logActivity($pdo, $_SESSION['user_id'], 'approve', 'user', $id, "Approved user account: {$user['fname']} {$user['lname']}");
            sendCredentialEmail($user['email'], $user['fname'].' '.$user['lname'], '', 'approve');
        }
        
        header("Location: manage_users.php?msg=Account+approved!+User+notified+via+email.&t=success"); exit;
    }
    if ($action === 'reject') {
        // Fetch user info for logging before rejecting/updating
        $u = $pdo->prepare("SELECT fname, lname FROM users WHERE id = ?");
        $u->execute([$id]);
        $user = $u->fetch();
        $name = $user ? $user['fname'].' '.$user['lname'] : "User #$id";

        $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$id]);
        logActivity($pdo, $_SESSION['user_id'], 'reject', 'user', $id, "Rejected user account: $name");
        header("Location: manage_users.php?msg=Account+rejected&t=warning"); exit;
    }
 if ($action === 'delete') {
    // Fetch user info for logging before deleting
    $u = $pdo->prepare("SELECT fname, lname, email FROM users WHERE id = ?");
    $u->execute([$id]);
    $user = $u->fetch();
    $name = $user ? $user['fname'].' '.$user['lname'] : "User #$id";

    $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$id, $id]);
    $pdo->prepare("UPDATE items SET claimer_id = NULL WHERE claimer_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM items     WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM students  WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM employees WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM users     WHERE id = ? AND type_id IN (1,3,4,6)")->execute([$id]);
    
    logActivity($pdo, $_SESSION['user_id'], 'delete', 'user', $id, "Deleted user account: $name");
    
    header("Location: manage_users.php?msg=Account+deleted&t=danger"); exit;
}
    if ($action === 'reset_account') {
        $rawPassword = bin2hex(random_bytes(4)); // 8 chars
        $hashed = password_hash($rawPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ?, is_activated = 0, force_password_reset = 1 WHERE id = ?")->execute([$hashed, $id]);
        
        // Fetch user info for email
        $u = $pdo->prepare("SELECT fname, lname, email FROM users WHERE id = ?");
        $u->execute([$id]);
        $user = $u->fetch();
        if ($user) {
            sendCredentialEmail($user['email'], $user['fname'].' '.$user['lname'], $rawPassword, 'reset');
        }
        
        header("Location: manage_users.php?msg=Account+reset+successfully.+New+credentials+sent+to+user.&t=success"); exit;
    }
}

// ── Fetch data based on current user's role ─────────────────────────────
$pendingStudents = $approvedStudents = $rejectedStudents = [];
$pendingStaff = $approvedStaff = $rejectedStaff = [];
$adminAccounts = $organizerAccounts = $presidentAccounts = [];
$colleges = [];

if ($_mu_tid === 4) {
    // Admin sees students, staff, and SSG President accounts they manage
    $baseStudentQ = "
        SELECT u.*, 'student' AS user_role,
               c.college_name, d.department_name AS dept_name, co.course_name
        FROM users u
        LEFT JOIN colleges    c  ON u.college_id     = c.id
        LEFT JOIN departments d  ON u.department_id  = d.id
        LEFT JOIN courses     co ON u.course_id      = co.id
        WHERE u.type_id = 1
    ";
    $pendingStudents  = $pdo->query("$baseStudentQ AND u.status = 'pending'  ORDER BY u.created_at DESC")->fetchAll();
    $approvedStudents = $pdo->query("$baseStudentQ AND u.status = 'approved' ORDER BY u.created_at DESC")->fetchAll();
    $rejectedStudents = $pdo->query("$baseStudentQ AND u.status = 'rejected' ORDER BY u.created_at DESC")->fetchAll();

    $baseStaffQ = "
        SELECT u.*, 'staff' AS user_role,
               d.department_name AS dept_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.type_id = 3
    ";
    $pendingStaff  = $pdo->query("$baseStaffQ AND u.status = 'pending'  ORDER BY u.created_at DESC")->fetchAll();
    $approvedStaff = $pdo->query("$baseStaffQ AND u.status = 'approved' ORDER BY u.created_at DESC")->fetchAll();
    $rejectedStaff = $pdo->query("$baseStaffQ AND u.status = 'rejected' ORDER BY u.created_at DESC")->fetchAll();

    $colleges = $pdo->query("SELECT id, college_name FROM colleges ORDER BY college_name")->fetchAll();

    // Also show SSG President accounts
    $presidentAccounts = $pdo->query("SELECT * FROM users WHERE type_id = 6 AND organizer_role = 'president' ORDER BY created_at DESC")->fetchAll();

} elseif ($_mu_tid === 5) {
    // SuperAdmin sees Admin accounts
    $adminAccounts = $pdo->query("SELECT * FROM users WHERE type_id = 4 ORDER BY created_at DESC")->fetchAll();

} elseif ($_mu_tid === 6 && $_mu_isPresident) {
    // SSG President sees Organizer (member) accounts
    $organizerAccounts = $pdo->query("SELECT * FROM users WHERE type_id = 6 AND organizer_role = 'member' ORDER BY created_at DESC")->fetchAll();
}

$totalPending = count($pendingStudents) + count($pendingStaff);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Shared styles are in admin_header.php */
        .u-avatar {
            width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .82rem; color: #fff;
        }
        .badge-student  { background: #dbeafe; color: #1e40af; font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
        .badge-staff    { background: #ccfbf1; color: #134e4a; font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
        .tr-pending { background: #fffbeb !important; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
        .pulse { animation: pulse 2s infinite; }
        .empty-state { padding: 48px; text-align: center; color: #94a3b8; }
        .empty-state i { font-size: 2.8rem; display: block; margin-bottom: 10px; opacity: .35; }
        .subtab-row { display: flex; gap: 6px; padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .subtab-btn {
            padding: 6px 14px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #fff; font-size: .8rem; font-weight: 600; color: #64748b;
            cursor: pointer; transition: all .2s;
        }
        .subtab-btn.active { background: var(--admin-blue); color: #fff; border-color: var(--admin-blue); }
        .subtab-btn.teal.active { background: #0d9488; border-color: #0d9488; }

        .quick-stats-flex { display: flex; gap: 1rem; }
        .quick-stat-item {
            flex: 1; background: rgba(255,255,255,0.15) !important;
            padding: 1rem; border-radius: 12px; text-align: center; color: white;
        }
        .quick-stat-item h3 { margin: 0; font-size: 1.9rem; font-weight: 800; }
        .quick-stat-item small { opacity: .8; font-size: .8rem; }
        
        .nav-tabs .nav-link { font-weight: 600; color: #64748b; border: none; padding: 12px 20px; }
        .nav-tabs .nav-link.active { color: var(--admin-blue); border-bottom: 3px solid var(--admin-blue); background: rgba(13,110,253,.05); }
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <?php if ($_mu_tid === 5): ?>
                <h2 class="fw-bold mb-1"><i class="bi bi-shield-check me-2"></i>Manage Admin Accounts</h2>
                <p class="mb-0 opacity-75 small">Create and manage administrator accounts</p>
                <?php elseif ($_mu_tid === 6): ?>
                <h2 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Manage Organizers</h2>
                <p class="mb-0 opacity-75 small">Create and manage SSG organizer accounts</p>
                <?php else: ?>
                <h2 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Manage Accounts</h2>
                <p class="mb-0 opacity-75 small">Review and approve student & staff registrations</p>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i>
                    <?php
                    echo match($_mu_tid) {
                        5 => 'Add New Admin',
                        4 => 'Add New SSG President',
                        6 => 'Add New Organizer',
                        default => 'Add New User'
                    };
                    ?>
                </button>
                <?php if ($totalPending > 0): ?>
                <span class="badge bg-warning text-dark px-3 py-2 pulse" style="font-size:.9rem">
                    <i class="bi bi-hourglass me-1"></i><?= $totalPending ?> pending
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="quick-stats-flex">
            <?php if ($_mu_tid === 5): ?>
            <div class="quick-stat-item">
                <h3 class="text-info"><?= count($adminAccounts) ?></h3>
                <small><i class="bi bi-shield-check me-1"></i>Admin Accounts</small>
            </div>
            <?php elseif ($_mu_tid === 6): ?>
            <div class="quick-stat-item">
                <h3 class="text-info"><?= count($organizerAccounts) ?></h3>
                <small><i class="bi bi-people me-1"></i>Organizers</small>
            </div>
            <?php else: ?>
            <div class="quick-stat-item">
                <h3 class="text-warning"><?= $totalPending ?></h3>
                <small><i class="bi bi-hourglass me-1"></i>Pending</small>
            </div>
            <div class="quick-stat-item">
                <h3 class="text-success"><?= count($approvedStudents) + count($approvedStaff) ?></h3>
                <small><i class="bi bi-check-circle me-1"></i>Approved</small>
            </div>
            <div class="quick-stat-item">
                <h3 class="text-info"><?= count($pendingStudents) + count($approvedStudents) + count($rejectedStudents) ?></h3>
                <small><i class="bi bi-mortarboard me-1"></i>Students</small>
            </div>
            <div class="quick-stat-item">
                <h3 class="text-info"><?= count($pendingStaff) + count($approvedStaff) + count($rejectedStaff) ?></h3>
                <small><i class="bi bi-person-badge me-1"></i>Staff</small>
            </div>
            <div class="quick-stat-item">
                <h3 class="text-info"><?= count($presidentAccounts) ?></h3>
                <small><i class="bi bi-award me-1"></i>SSG Presidents</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_GET['msg'])): ?>
                showToast("<?= htmlspecialchars($_GET['msg']) ?>", "<?= ($_GET['t']??'success') === 'success' ? 'success' : (($_GET['t']??'success') === 'warning' ? 'warning' : 'danger') ?>");
            <?php endif; ?>
        });
    </script>

    <!-- Search Filter -->
    <div class="admin-card mb-3" style="border-radius:12px">
        <div class="card-body py-3 px-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="userSearchInput" class="form-control border-start-0"
                       placeholder="Search by name or email...">
            </div>
        </div>
    </div>

    <?php if ($_mu_tid === 5): ?>
    <!-- ═══════ SUPERADMIN VIEW: Admin Accounts ═══════ -->
    <div class="admin-card mb-5">
        <div class="card-body p-0">
            <div class="px-4 py-3 border-bottom" style="background:#f8fafc">
                <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-primary"></i>Administrator Accounts</h6>
            </div>
            <?php if (empty($adminAccounts)): ?>
            <div class="empty-state">
                <i class="bi bi-shield-check"></i>
                <p class="mb-0">No admin accounts created yet.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 admin-table">
                    <thead><tr>
                        <th class="ps-4">Name</th><th>Email</th><th>Contact</th><th>Created</th><th class="pe-4">Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($adminAccounts as $u):
                        $name = htmlspecialchars(trim($u['fname'].' '.$u['lname']));
                        $initials = strtoupper(substr($u['fname'],0,1).substr($u['lname'],0,1));
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="u-avatar" style="background:#6366f1"><?= $initials ?></div>
                                <div>
                                    <div class="fw-semibold"><?= $name ?> <span class="badge" style="background:#e0e7ff;color:#4338ca;font-size:.65rem;padding:2px 8px;border-radius:20px">Admin</span></div>
                                </div>
                            </div>
                        </td>
                        <td><small><?= htmlspecialchars($u['email']) ?></small></td>
                        <td><small><?= htmlspecialchars($u['contact_number'] ?? 'N/A') ?></small></td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                        <td class="pe-4">
                            <div class="d-flex gap-1">
                                <button onclick="confirmResetModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-info" title="Reset Account">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                </button>
                                <button onclick="confirmDeleteModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($_mu_tid === 6 && $_mu_isPresident): ?>
    <!-- ═══════ SSG PRESIDENT VIEW: Organizer Accounts ═══════ -->
    <div class="admin-card mb-5">
        <div class="card-body p-0">
            <div class="px-4 py-3 border-bottom" style="background:#f8fafc">
                <h6 class="fw-bold mb-0"><i class="bi bi-people-fill me-2" style="color:#0d9488"></i>Organizer Members</h6>
            </div>
            <?php if (empty($organizerAccounts)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <p class="mb-0">No organizer accounts created yet.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 admin-table">
                    <thead><tr>
                        <th class="ps-4">Name</th><th>Email</th><th>Contact</th><th>Can Edit</th><th>Created</th><th class="pe-4">Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($organizerAccounts as $u):
                        $name = htmlspecialchars(trim($u['fname'].' '.$u['lname']));
                        $initials = strtoupper(substr($u['fname'],0,1).substr($u['lname'],0,1));
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="u-avatar" style="background:#0d9488"><?= $initials ?></div>
                                <div>
                                    <div class="fw-semibold"><?= $name ?> <span class="badge" style="background:#ccfbf1;color:#134e4a;font-size:.65rem;padding:2px 8px;border-radius:20px">Organizer</span></div>
                                </div>
                            </div>
                        </td>
                        <td><small><?= htmlspecialchars($u['email']) ?></small></td>
                        <td><small><?= htmlspecialchars($u['contact_number'] ?? 'N/A') ?></small></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    <?= $u['can_edit'] ? 'checked' : '' ?>
                                    onchange="togglePermission(<?= $u['id'] ?>, this.checked ? 1 : 0)">
                            </div>
                        </td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                        <td class="pe-4">
                            <div class="d-flex gap-1">
                                <button onclick="confirmResetModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-info" title="Reset">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                </button>
                                <button onclick="confirmDeleteModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    async function togglePermission(userId, canEdit) {
        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('can_edit', canEdit);
        try {
            const r = await fetch('update_organizer_permission.php', {method:'POST', body:fd});
            const d = await r.json();
            if (d.success) {
                showToast('Permission updated successfully!', 'success');
            } else {
                showToast(d.error || 'Failed to update permission', 'danger');
                location.reload();
            }
        } catch(e) {
            showToast('Error updating permission', 'danger');
            location.reload();
        }
    }
    </script>

    <?php else: ?>
    <!-- ═══════ ADMIN VIEW: Students, Staff, SSG Presidents ═══════ -->

    <!-- Main Tabs -->
    <div class="admin-card mb-5">
        <ul class="nav nav-tabs px-3 pt-2" id="mainTab">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pending">
                    <i class="bi bi-hourglass me-1"></i>Pending
                    <?php if ($totalPending > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $totalPending ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-approved">
                    <i class="bi bi-check-circle me-1"></i>Approved
                    <span class="badge bg-light text-dark border ms-1"><?= count($approvedStudents) + count($approvedStaff) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rejected">
                    <i class="bi bi-x-circle me-1"></i>Rejected
                    <span class="badge bg-light text-dark border ms-1"><?= count($rejectedStudents) + count($rejectedStaff) ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-presidents">
                    <i class="bi bi-award me-1"></i>SSG Presidents
                    <span class="badge bg-light text-dark border ms-1"><?= count($presidentAccounts) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- PENDING -->
            <div class="tab-pane fade show active" id="tab-pending">
                <?php if ($totalPending > 0): ?>
                <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-4" style="font-size:.84rem">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong><?= $totalPending ?> account(s)</strong> waiting for your approval!
                </div>
                <?php endif; ?>
                <div class="subtab-row">
                    <button class="subtab-btn active" id="ps-btn" onclick="pendingTab('students')">
                        <i class="bi bi-mortarboard me-1"></i>Students
                        <?php if(count($pendingStudents)>0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= count($pendingStudents) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="subtab-btn teal" id="pt-btn" onclick="pendingTab('staff')">
                        <i class="bi bi-person-badge me-1"></i>Staff / Teachers
                        <?php if(count($pendingStaff)>0): ?>
                        <span class="badge ms-1" style="background:#0d9488;color:#fff"><?= count($pendingStaff) ?></span>
                        <?php endif; ?>
                    </button>
                </div>
                <div id="p-students"><?php echo renderTable($pendingStudents, 'pending', 'student'); ?></div>
                <div id="p-staff" style="display:none"><?php echo renderTable($pendingStaff, 'pending', 'staff'); ?></div>
            </div>

            <!-- APPROVED -->
            <div class="tab-pane fade" id="tab-approved">
                <div class="subtab-row">
                    <button class="subtab-btn active" id="as-btn" onclick="approvedTab('students')">
                        <i class="bi bi-mortarboard me-1"></i>Students (<?= count($approvedStudents) ?>)
                    </button>
                    <button class="subtab-btn teal" id="at-btn" onclick="approvedTab('staff')">
                        <i class="bi bi-person-badge me-1"></i>Staff (<?= count($approvedStaff) ?>)
                    </button>
                </div>
                <div id="a-students"><?php echo renderTable($approvedStudents, 'approved', 'student'); ?></div>
                <div id="a-staff" style="display:none"><?php echo renderTable($approvedStaff, 'approved', 'staff'); ?></div>
            </div>

            <!-- REJECTED -->
            <div class="tab-pane fade" id="tab-rejected">
                <div class="subtab-row">
                    <button class="subtab-btn active" id="rs-btn" onclick="rejectedTab('students')">
                        <i class="bi bi-mortarboard me-1"></i>Students (<?= count($rejectedStudents) ?>)
                    </button>
                    <button class="subtab-btn teal" id="rt-btn" onclick="rejectedTab('staff')">
                        <i class="bi bi-person-badge me-1"></i>Staff (<?= count($rejectedStaff) ?>)
                    </button>
                </div>
                <div id="r-students"><?php echo renderTable($rejectedStudents, 'rejected', 'student'); ?></div>
                <div id="r-staff" style="display:none"><?php echo renderTable($rejectedStaff, 'rejected', 'staff'); ?></div>
            </div>

            <!-- SSG PRESIDENTS (Admin only) -->
            <div class="tab-pane fade" id="tab-presidents">
                <?php if (empty($presidentAccounts)): ?>
                <div class="empty-state">
                    <i class="bi bi-award"></i>
                    <p class="mb-0">No SSG President accounts created yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead><tr>
                            <th class="ps-4">Name</th><th>Email</th><th>Contact</th><th>Created</th><th class="pe-4">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($presidentAccounts as $u):
                            $name = htmlspecialchars(trim($u['fname'].' '.$u['lname']));
                            $initials = strtoupper(substr($u['fname'],0,1).substr($u['lname'],0,1));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="u-avatar" style="background:#d97706"><?= $initials ?></div>
                                    <div>
                                        <div class="fw-semibold"><?= $name ?> <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 8px;border-radius:20px">👑 SSG President</span></div>
                                    </div>
                                </div>
                            </td>
                            <td><small><?= htmlspecialchars($u['email']) ?></small></td>
                            <td><small><?= htmlspecialchars($u['contact_number'] ?? 'N/A') ?></small></td>
                            <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                            <td class="pe-4">
                                <div class="d-flex gap-1">
                                    <button onclick="confirmResetModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-info"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
                                    <button onclick="confirmDeleteModal(<?= $u['id'] ?>, '<?= $name ?>')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
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
    <?php endif; ?>
</div>

<script>
    function confirmDeleteModal(userId, userName) {
        showConfirm({
            title: 'Delete Account?',
            msg: `Permanently remove <strong>"${userName}"</strong> and all their data? This cannot be undone.`,
            type: 'danger',
            confirmText: 'Yes, Delete',
            onConfirm: () => { window.location.href = `manage_users.php?action=delete&id=${userId}`; }
        });
    }

    function confirmRejectModal(userId, userName) {
        showConfirm({
            title: 'Reject/Revoke Account?',
            msg: `Are you sure you want to reject or revoke access for <strong>"${userName}"</strong>?`,
            type: 'warning',
            confirmText: 'Confirm Reject',
            onConfirm: () => { window.location.href = `manage_users.php?action=reject&id=${userId}`; }
        });
    }

    function confirmResetModal(userId, userName) {
        showConfirm({
            title: 'Reset Account?',
            msg: `Reset <strong>"${userName}"</strong>'s password to default? They will need to re-verify their ID.`,
            type: 'primary',
            confirmText: 'Reset Now',
            onConfirm: () => { window.location.href = `manage_users.php?action=reset_account&id=${userId}`; }
        });
    }

    function pendingTab(t) {
        document.getElementById('p-students').style.display = t==='students' ? '' : 'none';
        document.getElementById('p-staff').style.display    = t==='staff'    ? '' : 'none';
        document.getElementById('ps-btn').classList.toggle('active', t==='students');
        document.getElementById('pt-btn').classList.toggle('active', t==='staff');
    }
    function approvedTab(t) {
        document.getElementById('a-students').style.display = t==='students' ? '' : 'none';
        document.getElementById('a-staff').style.display    = t==='staff'    ? '' : 'none';
        document.getElementById('as-btn').classList.toggle('active', t==='students');
        document.getElementById('at-btn').classList.toggle('active', t==='staff');
    }
    function rejectedTab(t) {
        document.getElementById('r-students').style.display = t==='students' ? '' : 'none';
        document.getElementById('r-staff').style.display    = t==='staff'    ? '' : 'none';
        document.getElementById('rs-btn').classList.toggle('active', t==='students');
        document.getElementById('rt-btn').classList.toggle('active', t==='staff');
    }

    // ── User search filter ──────────────────────────────────────
    document.getElementById('userSearchInput')?.addEventListener('input', function(e) {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
</script>

<!-- ── Add User Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2 text-primary"></i>
                        <?php
                        echo match($_mu_tid) {
                            5 => 'Create New Administrator',
                            6 => 'Create New Organizer',
                            4 => 'Create New SSG President',
                            default => 'Register New Account'
                        };
                        ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px; height:64px">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <p class="text-muted small">Fill in the details below to create a new authenticated account.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Account Type</label>
                            <select name="role" id="newRole" class="form-select" required>
                                <?php if ($_mu_tid === 5): ?>
                                <option value="admin">Administrator</option>
                                <?php elseif ($_mu_tid === 6): ?>
                                <option value="organizer" selected>Organizer Member</option>
                                <?php elseif ($_mu_tid === 4): ?>
                                <option value="ssg_president" selected>SSG President</option>
                                <?php else: ?>
                                <option value="student" selected>Student / Staff</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Year (e.g. 2026-2027)</label>
                            <input type="text" name="year" class="form-control" placeholder="2026-2027" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key-fill text-muted"></i></span>
                                <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

</script>
</div><!-- /admin-main-content -->
</body>
</html>

<?php
function renderTable(array $users, string $statusFilter, string $role): string {
    $isStudent   = $role === 'student';
    $isPending   = $statusFilter === 'pending';

    if (empty($users)) {
        $icon = $statusFilter === 'pending' ? 'bi-check-circle text-success' : 'bi-inbox';
        $msg  = $statusFilter === 'pending' ? "No pending $role accounts — you're all caught up!" : "No $role accounts here.";
        return "<div class='empty-state'><i class='bi $icon'></i><p class='mb-0'>$msg</p></div>";
    }

    $rows = '';
    foreach ($users as $u) {
        $name     = htmlspecialchars(trim($u['fname'].' '.($u['mname']?$u['mname'].' ':'').$u['lname']));
        $email    = htmlspecialchars($u['email']);
        $contact  = htmlspecialchars($u['contact_number'] ?? 'N/A');
        $date     = date('M d, Y', strtotime($u['created_at']));
        $initials = strtoupper(substr($u['fname'],0,1).substr($u['lname'],0,1));
        $bgColor  = $isStudent ? '#2563eb' : '#0d9488';
        $roleTag  = $isStudent ? "<span class='badge-student'>Student</span>" : "<span class='badge-staff'>Staff</span>";

        if ($isStudent) {
            $info  = "<div class='small'>
                        <div class='fw-semibold'>".htmlspecialchars($u['college_name']??'N/A')."</div>
                        <div class='text-muted'>".htmlspecialchars($u['course_name']??'N/A')."</div>
                        <div class='text-muted'>Year {$u['year']}".($u['section'] ? " - " . htmlspecialchars($u['section']) : "")."</div>
                      </div>";
            $extra = "<span class='fw-bold text-primary'>".htmlspecialchars($u['student_id']??'N/A')."</span>";
        } else {
            $info  = "<div class='small'>
                        <div class='fw-semibold'>".htmlspecialchars($u['dept_name']??'N/A')."</div>
                        <div class='text-muted'>".htmlspecialchars($u['position']??'N/A')."</div>
                      </div>";
            $extra = "<span class='text-muted small'>Staff</span>";
        }

        // ── Action buttons — NO confirm() dialogs ──
        if ($statusFilter === 'pending') {
            $actions = "<div class='d-flex gap-1'>
                <a href='manage_users.php?action=approve&id={$u['id']}' class='btn btn-sm btn-success'>
                    <i class='bi bi-check-lg me-1'></i>Approve
                </a>
                <button onclick=\"confirmRejectModal({$u['id']}, '{$name}')\" class='btn btn-sm btn-outline-danger'>
                    <i class='bi bi-x-lg me-1'></i>Reject
                </button>
            </div>";
        } elseif ($statusFilter === 'approved') {
            $actions = "<div class='d-flex gap-1'>
                <button onclick=\"confirmRejectModal({$u['id']}, '{$name}')\" class='btn btn-sm btn-outline-warning'>
                    <i class='bi bi-slash-circle me-1'></i>Revoke
                </button>
                <button onclick=\"confirmResetModal({$u['id']}, '{$name}')\" class='btn btn-sm btn-outline-info' title='Reset Account'>
                    <i class='bi bi-arrow-counterclockwise me-1'></i>Reset
                </button>
                <button onclick=\"confirmDeleteModal({$u['id']}, '{$name}')\" class='btn btn-sm btn-outline-danger' title='Delete Account'>
                    <i class='bi bi-trash'></i>
                </button>
            </div>";
        } else {
            $actions = "<div class='d-flex gap-1'>
                <a href='manage_users.php?action=approve&id={$u['id']}' class='btn btn-sm btn-outline-success'>
                    <i class='bi bi-check-lg me-1'></i>Approve
                </a>
                <button onclick=\"confirmDeleteModal({$u['id']}, '{$name}')\" class='btn btn-sm btn-outline-danger' title='Delete Account'>
                    <i class='bi bi-trash'></i>
                </button>
            </div>";
        }

        $trClass = $isPending ? 'tr-pending' : '';
        $rows .= "
        <tr class='$trClass'>
            <td class='ps-4'>
                <div class='d-flex align-items-center gap-2'>
                    <div class='u-avatar' style='background:$bgColor'>$initials</div>
                    <div>
                        <div class='fw-semibold'>$name $roleTag</div>
                        <small class='text-muted'>$email</small>
                    </div>
                </div>
            </td>
            <td>$extra</td>
            <td>$info</td>
            <td><small><i class='bi bi-telephone text-primary me-1'></i>$contact</small></td>
            <td><small class='text-muted'>$date</small></td>
            <td class='pe-4'>$actions</td>
        </tr>";
    }

    return "<div class='table-responsive'>
        <table class='table table-hover mb-0 admin-table'>
            <thead>
                <tr>
                    <th class='ps-4'>Name</th>
                    <th>".($isStudent ? 'Student ID' : 'Role')."</th>
                    <th>".($isStudent ? 'Academic Info' : 'Department & Position')."</th>
                    <th>Contact</th>
                    <th>Registered</th>
                    <th class='pe-4'>Actions</th>
                </tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
    </div>";
}
?>