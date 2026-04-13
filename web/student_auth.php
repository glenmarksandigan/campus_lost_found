<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

require_once 'db.php'; // provides $pdo

$action = $_POST['action'] ?? $_GET['action'] ?? '';

match($action) {
    'login'            => handleLogin($pdo),
    'update_profile'   => handleUpdateProfile($pdo),
    'change_password'  => handleChangePassword($pdo),
    default            => json_exit(false, 'Invalid action.')
};

// ── LOGIN ─────────────────────────────────────────────────────────────────────
function handleLogin($pdo) {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        json_exit(false, 'Email and password are required.');
    }

    $stmt = $pdo->prepare("SELECT id, fname, lname, email, password, type_id, status, is_activated, force_password_reset FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        json_exit(false, 'No account found with that email address.');
    }

    if ($user['status'] === 'rejected') {
        json_exit(false, 'Your account has been deactivated. Please contact the admin.');
    }

    // Support plain-text default password AND hashed
    $valid = password_verify($password, $user['password']) || $password === $user['password'];

    if (!$valid) {
        json_exit(false, 'Incorrect password. Please try again.');
    }

    // Account exists and status is fine — proceed with session setup

    // Auto-hash plain-text password on first login
    if ($password === $user['password']) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user['id']]);
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['fname'] . ' ' . $user['lname'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['type_id']    = $user['type_id'];

    // ── Redirect by role (including new hierarchical roles) ──────────────────
    // type_id: 1=Student, 2=Guard, 3=Staff, 4=Admin, 5=Super Admin, 6=Organizer
    $redirect = match((int)$user['type_id']) {
        5       => 'superadmin_dashboard.php', // Super Admin (view-only observer)
        4       => 'admin.php',                // Admin (full control)
        6       => 'organizer_dashboard.php',  // Organizers (SSG members)
        2       => 'guard_dashboard.php',      // Guard
        3       => 'index.php',                // Staff/Teacher now use unified dashboard (index.php)
        default => 'index.php'                 // Students
    };

    json_exit(true, 'Login successful! Redirecting…', ['redirect' => $redirect]);
}

// ── UPDATE PROFILE ────────────────────────────────────────────────────────────
function handleUpdateProfile($pdo) {
    requireLogin();

    $userId  = $_SESSION['user_id'];
    $fname   = trim($_POST['fname']   ?? '');
    $mname   = trim($_POST['mname']   ?? '');
    $lname   = trim($_POST['lname']   ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (!$fname || !$lname) {
        json_exit(false, 'First name and last name are required.');
    }

    // Get user type
    $t = $pdo->prepare("SELECT type_id FROM users WHERE id = ?");
    $t->execute([$userId]);
    $typeId = (int)($t->fetch(PDO::FETCH_ASSOC)['type_id'] ?? 1);

    if ($typeId === 1) {
        // Student — update academic info too
        $collegeId = $_POST['college_id']    ?? null;
        $deptId    = $_POST['department_id'] ?? null;
        $courseId  = $_POST['course_id']     ?? null;
        $year      = $_POST['year']          ?? null;

        $stmt = $pdo->prepare("
            UPDATE users
            SET fname=?, mname=?, lname=?, contact_number=?,
                college_id=?, department_id=?, course_id=?, year=?, section=?
            WHERE id=?
        ");
        $ok = $stmt->execute([
            $fname, $mname, $lname, $contact,
            $collegeId ?: null, $deptId ?: null,
            $courseId  ?: null, $year   ?: null,
            $_POST['section'] ?? null,
            $userId
        ]);

    } elseif ($typeId === 3) {
        // Employee — update department and position
        $deptId   = $_POST['department_id'] ?? null;
        $position = trim($_POST['position'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE users
            SET fname=?, mname=?, lname=?, contact_number=?,
                department_id=?, position=?
            WHERE id=?
        ");
        $ok = $stmt->execute([
            $fname, $mname, $lname, $contact,
            $deptId ?: null, $position ?: null,
            $userId
        ]);

    } else {
        // Other roles — name and contact only
        $stmt = $pdo->prepare("
            UPDATE users SET fname=?, mname=?, lname=?, contact_number=? WHERE id=?
        ");
        $ok = $stmt->execute([$fname, $mname, $lname, $contact, $userId]);
    }

    if ($ok) {
        $_SESSION['user_name'] = $fname . ' ' . $lname;
        json_exit(true, 'Profile updated successfully.');
    } else {
        json_exit(false, 'Failed to update profile. Please try again.');
    }
}

// ── CHANGE PASSWORD ───────────────────────────────────────────────────────────
function handleChangePassword($pdo) {
    requireLogin();

    $userId    = $_SESSION['user_id'];
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']      ?? '';
    $confirmPw = $_POST['confirm_password']  ?? '';

    if (!$currentPw || !$newPw || !$confirmPw) {
        json_exit(false, 'All password fields are required.');
    }
    if (strlen($newPw) < 6) {
        json_exit(false, 'New password must be at least 6 characters.');
    }
    if ($newPw !== $confirmPw) {
        json_exit(false, 'New passwords do not match.');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_exit(false, 'User not found.');
    }

    $valid = password_verify($currentPw, $row['password']) || $currentPw === $row['password'];

    if (!$valid) {
        json_exit(false, 'Current password is incorrect.');
    }

    $hashed = password_hash($newPw, PASSWORD_DEFAULT);
    $upd    = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");

    if ($upd->execute([$hashed, $userId])) {
        json_exit(true, 'Password changed successfully.');
    } else {
        json_exit(false, 'Failed to change password. Please try again.');
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        json_exit(false, 'Session expired. Please log in again.');
    }
}

function json_exit(bool $success, string $message, array $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}