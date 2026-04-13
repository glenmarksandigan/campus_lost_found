<?php

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    $redirect = match((int)($_SESSION['type_id'] ?? 1)) {
        5       => 'superadmin_dashboard.php',
        4       => 'admin.php',
        6       => 'organizer_dashboard.php',
        2       => 'guard_dashboard.php',
        default => 'auth.php' // redirect non-admin back to main page
    };
    header("Location: $redirect"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login – FoundIt!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --navy: #0b1d3a; --purple: #7c3aed; --gold: #ffcc00; }
    html, body { height: 100%; margin: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(135deg, var(--navy) 0%, #1e3a6e 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
      position: relative; overflow: hidden;
    }
    body::before {
      content: ''; position: fixed; top: -80px; right: -80px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(124,58,237,0.08), transparent 70%);
      animation: orbFloat 10s ease-in-out infinite;
    }
    body::after {
      content: ''; position: fixed; bottom: -60px; left: 10%;
      width: 250px; height: 250px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,204,0,0.06), transparent 70%);
      animation: orbFloat 8s ease-in-out infinite reverse;
    }
    @keyframes orbFloat {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-30px) scale(1.05); }
    }
    .auth-card {
      width: 100%; max-width: 420px;
      background: rgba(255,255,255,0.98); border-radius: 22px; overflow: hidden;
      box-shadow: 0 24px 80px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
      position: relative; z-index: 2;
      animation: cardEntry 0.45s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes cardEntry {
      from { opacity: 0; transform: translateY(20px) scale(0.96); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .auth-header {
      background: linear-gradient(135deg, var(--purple), #5b21b6);
      padding: 32px 28px 24px; text-align: center;
    }
    .auth-header .icon {
      width: 64px; height: 64px; background: rgba(255,255,255,0.15);
      border-radius: 50%; display: inline-flex; align-items: center;
      justify-content: center; margin-bottom: 12px;
    }
    .auth-header .icon i { font-size: 1.8rem; color: #fff; }
    .auth-header h2 { font-family: 'Syne',sans-serif; font-weight: 800; font-size: 1.25rem; color: #fff; margin: 0 0 4px; }
    .auth-header p { color: rgba(255,255,255,.7); font-size: .82rem; margin: 0; }

    .auth-body { padding: 28px 28px 32px; }
    .form-label { font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: 5px; }
    .form-control {
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      padding: 12px 14px; font-size: .875rem; transition: all .2s;
    }
    .form-control:focus {
      border-color: var(--purple); box-shadow: 0 0 0 3px rgba(124,58,237,.1);
    }
    .btn-auth {
      width: 100%; padding: 13px; border-radius: 10px;
      font-weight: 700; font-size: .95rem; border: none; transition: all .2s;
      background: linear-gradient(135deg, var(--purple), #5b21b6); color: #fff;
    }
    .btn-auth:hover { transform: translateY(-1px); opacity: .92; }
    .alert { border-radius: 10px; font-size: .85rem; padding: 10px 14px; }
    .back-link {
      text-align: center; margin-top: 18px; padding-top: 18px;
      border-top: 1px solid #e2e8f0;
    }
    .back-link a { color: #64748b; font-size: .8rem; text-decoration: none; transition: color .2s; }
    .back-link a:hover { color: var(--navy); }

    .warning-banner {
      background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px;
      padding: 12px 14px; margin-bottom: 18px; font-size: .82rem; color: #92400e;
    }
  </style>
</head>
<body>
<div class="auth-card">

  <!-- Header -->
  <div class="auth-header">
    <img src="uploads/BISU-LOGO.png" alt="BISU Logo" style="height:48px;width:auto;object-fit:contain;margin-bottom:10px;">
    <div class="icon"><i class="bi bi-shield-lock"></i></div>
    <h2>Admin / Guard Access</h2>
    <p>FoundIt! — BISU Candijay Campus</p>
  </div>

  <div class="auth-body">

    <div class="warning-banner">
      <i class="bi bi-exclamation-triangle me-1"></i>
      <strong>Restricted Access:</strong> This page is for administrators and security guards only.
    </div>

    <div id="adminAlert" class="mb-3"></div>

    <form onsubmit="doAdminLogin(event)" novalidate>
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input id="adminEmail" type="email" class="form-control" placeholder="admin@bisu.edu.ph" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input id="adminPassword" type="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-auth">
        <i class="bi bi-shield-check me-2"></i>Sign In
      </button>
    </form>

    <div class="back-link">
      <a href="auth.php"><i class="bi bi-arrow-left me-1"></i>Back to Student/Staff Login</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  async function doAdminLogin(e) {
    e.preventDefault();
    const div = document.getElementById('adminAlert');
    div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Verifying credentials…</div>`;
    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    document.getElementById('adminEmail').value.trim());
    fd.append('password', document.getElementById('adminPassword').value);
    try {
      const r = await fetch('student_auth.php', {method:'POST', body:fd});
      const d = await r.json();
      if (d.success) {
        div.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${d.message}</div>`;
        setTimeout(() => { window.location.href = d.redirect; }, 700);
      } else {
        div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message}</div>`;
      }
    } catch(err) {
      div.innerHTML = `<div class="alert alert-danger">Error contacting server</div>`;
    }
  }
  // Prevent forward navigation to protected pages after logout
history.pushState(null, null, window.location.href);
window.addEventListener('popstate', function() {
    history.pushState(null, null, window.location.href);
});
</script>
</body>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
</html>