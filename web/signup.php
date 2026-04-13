<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    $redirect = match((int)($_SESSION['type_id'] ?? 1)) {
        4       => 'admin.php',
        2       => 'guard_dashboard.php',
        3       => 'staff_dashboard.php',
        default => 'index.php'
    };
    header("Location: $redirect"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login – FoundIt! | BISU Candijay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <script>
    history.pushState({ page: 'login' }, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState({ page: 'login' }, '', window.location.href);
    });
  </script>

  <style>
    :root { --navy: #0b1d3a; --blue: #0d6efd; --teal: #0d9488; --gold: #ffcc00; }
    html, body { height: 100%; margin: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(135deg, var(--navy) 0%, #1e3a6e 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
      position: relative; overflow: hidden;
    }
    /* Floating orbs */
    body::before {
      content: ''; position: fixed; top: -80px; right: -80px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,204,0,0.08), transparent 70%);
      animation: orbFloat 10s ease-in-out infinite;
    }
    body::after {
      content: ''; position: fixed; bottom: -60px; left: 10%;
      width: 250px; height: 250px; border-radius: 50%;
      background: radial-gradient(circle, rgba(13,110,253,0.06), transparent 70%);
      animation: orbFloat 8s ease-in-out infinite reverse;
    }
    @keyframes orbFloat {
      0%, 100% { transform: translateY(0) scale(1); }
      50% { transform: translateY(-30px) scale(1.05); }
    }

    .auth-card {
      width: 100%; max-width: 480px;
      background: rgba(255,255,255,0.98); border-radius: 22px; overflow: hidden;
      box-shadow: 0 24px 80px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
      animation: cardEntry 0.45s cubic-bezier(0.34,1.56,0.64,1);
      position: relative; z-index: 2;
      backdrop-filter: blur(20px);
    }
    @keyframes cardEntry {
      from { opacity: 0; transform: translateY(20px) scale(0.96); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .auth-header {
      background: linear-gradient(135deg, var(--navy), #1e3a6e);
      padding: 28px 28px 22px; text-align: center;
      position: relative; overflow: hidden;
    }
    .auth-header::before {
      content: ''; position: absolute; top: -40%; right: -20%;
      width: 180px; height: 180px; border-radius: 50%;
      background: rgba(255,204,0,0.06);
    }
    .auth-header::after {
      content: ''; position: absolute; bottom: -30%; left: -10%;
      width: 140px; height: 140px; border-radius: 50%;
      background: rgba(13,110,253,0.05);
    }
    .auth-logo-svg { height: 48px; width: 48px; margin-bottom: 10px; position: relative; filter: drop-shadow(0 0 8px rgba(255,204,0,0.3)); }
    .auth-header h2 { font-family: 'Syne',sans-serif; font-weight: 800; font-size: 1.35rem; color: #fff; margin: 0 0 4px; position: relative; }
    .auth-header h2 span { color: var(--gold); }
    .auth-header p  { color: rgba(255,255,255,.55); font-size: .82rem; margin: 0; position: relative; }

    .auth-body { padding: 28px 30px 32px; }

    .form-label { font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: 5px; }
    .form-control {
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      padding: 11px 14px; font-size: .875rem; transition: all .2s;
    }
    .form-control:focus {
      border-color: var(--blue); box-shadow: 0 0 0 3px rgba(13,110,253,.1);
    }
    .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
    .input-group .btn-toggle-pw {
      border: 1.5px solid #e2e8f0; border-left: none;
      border-radius: 0 10px 10px 0; background: #f8fafc;
      color: #64748b; padding: 0 14px; cursor: pointer; transition: all .2s;
    }
    .input-group .btn-toggle-pw:hover { background: #e2e8f0; }

    .btn-auth {
      width: 100%; padding: 12px; border-radius: 10px;
      font-weight: 700; font-size: .95rem; border: none;
      background: var(--blue); color: #fff; transition: all .2s;
      cursor: pointer;
    }
    .btn-auth:hover { transform: translateY(-1px); background: #0b5ed7; }
    .btn-auth:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .alert { border-radius: 10px; font-size: .85rem; padding: 10px 14px; }

    .hint-box {
      background: #f0f9ff; border: 1px solid #bae6fd;
      border-radius: 10px; padding: 10px 14px;
      font-size: .8rem; color: #0369a1; margin-bottom: 18px;
    }

    .admin-link {
      text-align: center; margin-top: 20px; padding-top: 18px;
      border-top: 1px solid #e2e8f0;
    }
    .admin-link a { color: #64748b; font-size: .8rem; text-decoration: none; transition: color .2s; }
    .admin-link a:hover { color: var(--navy); }

    /* Password step (hidden initially) */
    #passwordStep {
      display: none;
      animation: slideDown 0.35s ease;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .welcome-msg {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      border: 1px solid #bfdbfe; border-radius: 10px;
      padding: 12px 16px; margin-bottom: 16px; font-size: .84rem;
      display: flex; align-items: center; gap: 10px;
    }
    .welcome-msg .emoji { font-size: 1.4rem; }
    .welcome-msg strong { color: #1e40af; }

    .change-email {
      font-size: .78rem; color: #64748b; cursor: pointer;
      text-decoration: none; transition: color .2s;
    }
    .change-email:hover { color: var(--blue); }

    @media (max-width: 520px) {
      body { padding: 12px; }
      .auth-card { border-radius: 16px; }
      .auth-body { padding: 20px 18px 24px; }
    }
  </style>
</head>
<body>
<div class="auth-card">

  <div class="auth-header">
    <img src="uploads/BISU-LOGO.png" alt="BISU Logo" style="height:48px;width:auto;object-fit:contain;margin-bottom:10px;position:relative;">
    <svg class="auth-logo-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="20" cy="20" r="14" stroke="#ffcc00" stroke-width="3.5"/>
      <line x1="30" y1="30" x2="42" y2="42" stroke="#ffcc00" stroke-width="3.5" stroke-linecap="round"/>
      <polyline points="13,20 18,25 27,15" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h2>Found<span>It!</span></h2>
    <p>BISU Candijay Campus</p>
  </div>

  <div class="auth-body">

    <div class="hint-box" id="hintBox">
      <i class="bi bi-info-circle me-1"></i>
      Please log in with your <strong>school email</strong> and the password provided to you.
    </div>

    <div id="loginAlert" class="mb-3"></div>

    <div id="loginSection">
      <form onsubmit="doLogin(event)" novalidate>
        <div class="mb-4">
          <label class="form-label"><i class="bi bi-envelope me-1"></i>School Email</label>
          <input id="loginEmail" type="email" class="form-control" placeholder="you@school.edu.ph" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
          <div class="input-group">
            <input id="loginPassword" type="password" class="form-control" placeholder="••••••••" required>
            <button type="button" class="btn-toggle-pw" onclick="togglePw()" tabindex="-1">
              <i class="bi bi-eye" id="pwEyeIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn-auth" id="signInBtn">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>
    </div>

    <div class="admin-link">
      <a href="landing.php"><i class="bi bi-arrow-left me-1"></i>Back to Home</a>
      <span style="color:#cbd5e1;margin:0 8px;">•</span>
      <a href="login.php"><i class="bi bi-shield-lock me-1"></i>Admin / Guard Access</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePw() {
    const inp  = document.getElementById('loginPassword');
    const icon = document.getElementById('pwEyeIcon');
    const show = inp.type === 'password';
    inp.type   = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  }

  async function doLogin(e) {
    e.preventDefault();
    const div      = document.getElementById('loginAlert');
    const btn      = document.getElementById('signInBtn');
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!email || !password) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Email and password are required.</div>`;
      return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Signing in…`;
    div.innerHTML = '';

    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    email);
    fd.append('password', password);

    try {
      const r = await fetch('student_auth.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.success) {
        div.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${d.message}</div>`;
        btn.innerHTML = `<i class="bi bi-check-circle me-2"></i>Success!`;
        setTimeout(() => { window.location.replace(d.redirect); }, 700);
      } else {
        div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message}</div>`;
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-box-arrow-in-right me-2"></i>Sign In`;
      }
    } catch(err) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Error contacting server. Please try again.</div>`;
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-box-arrow-in-right me-2"></i>Sign In`;
    }
  }
</script>
</body>
</html>