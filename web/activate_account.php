<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Must have a pending activation — otherwise redirect to login
if (empty($_SESSION['pending_activation_user_id'])) {
    header('Location: signup.php'); exit;
}

require_once 'db.php';

$userId = (int)$_SESSION['pending_activation_user_id'];
$stmt = $pdo->prepare("SELECT fname, lname, email, type_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    unset($_SESSION['pending_activation_user_id']);
    header('Location: signup.php'); exit;
}

$isStudent = ((int)$user['type_id'] === 1);
$idLabel   = $isStudent ? 'Student ID' : 'Employee ID';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify Your Account – FoundIt!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --navy: #0b1d3a; --blue: #0d6efd; --teal: #0d9488; --green: #10b981; }
    html, body { height: 100%; margin: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: linear-gradient(135deg, var(--navy) 0%, #1e3a6e 100%);
      min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
    }

    .auth-card {
      width: 100%; max-width: 520px;
      background: #fff; border-radius: 22px; overflow: hidden;
      box-shadow: 0 24px 60px rgba(0,0,0,0.3);
      animation: cardEntry 0.5s ease;
    }
    @keyframes cardEntry {
      from { opacity: 0; transform: translateY(20px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .auth-header {
      background: linear-gradient(135deg, var(--green), #059669);
      padding: 32px 28px 26px; text-align: center; position: relative; overflow: hidden;
    }
    .auth-header::before {
      content: ''; position: absolute; top: -50%; right: -30%;
      width: 200px; height: 200px; border-radius: 50%;
      background: rgba(255,255,255,0.08);
    }
    .auth-header::after {
      content: ''; position: absolute; bottom: -40%; left: -20%;
      width: 160px; height: 160px; border-radius: 50%;
      background: rgba(255,255,255,0.06);
    }
    .auth-header .icon {
      width: 68px; height: 68px; background: rgba(255,255,255,0.18);
      border-radius: 50%; display: inline-flex; align-items: center;
      justify-content: center; margin-bottom: 14px; position: relative;
    }
    .auth-header .icon i { font-size: 1.8rem; color: #fff; }
    .auth-header h2 {
      font-family: 'Syne',sans-serif; font-weight: 800; font-size: 1.3rem;
      color: #fff; margin: 0 0 6px; position: relative;
    }
    .auth-header p {
      color: rgba(255,255,255,.75); font-size: .84rem; margin: 0;
      position: relative; line-height: 1.5;
    }

    .auth-body { padding: 28px 30px 32px; }

    .welcome-banner {
      background: linear-gradient(135deg, #ecfdf5, #d1fae5);
      border: 1px solid #a7f3d0; border-radius: 14px;
      padding: 16px 18px; margin-bottom: 22px;
      display: flex; align-items: center; gap: 14px;
    }
    .welcome-avatar {
      width: 46px; height: 46px; border-radius: 50%;
      background: linear-gradient(135deg, var(--green), #059669);
      color: #fff; font-weight: 700; font-size: 1rem;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .welcome-banner .name { font-weight: 700; font-size: .92rem; color: #064e3b; }
    .welcome-banner .email { font-size: .78rem; color: #6b7280; }

    .step-indicator {
      display: flex; align-items: center; justify-content: center;
      gap: 6px; margin-bottom: 22px;
    }
    .step-dot {
      width: 10px; height: 10px; border-radius: 50%;
      background: #e2e8f0; transition: all 0.3s;
    }
    .step-dot.active { background: var(--green); width: 28px; border-radius: 20px; }
    .step-dot.done { background: var(--green); }

    .section-label {
      font-size: .7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 12px;
      display: flex; align-items: center; gap: 8px;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

    .form-label { font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: 5px; }
    .form-control {
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      padding: 11px 14px; font-size: .875rem; transition: all .2s;
    }
    .form-control:focus {
      border-color: var(--green); box-shadow: 0 0 0 3px rgba(16,185,129,.12);
    }
    .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
    .input-group .btn-toggle-pw {
      border: 1.5px solid #e2e8f0; border-left: none;
      border-radius: 0 10px 10px 0; background: #f8fafc;
      color: #64748b; padding: 0 14px; cursor: pointer; transition: all .2s;
    }
    .input-group .btn-toggle-pw:hover { background: #e2e8f0; }

    .btn-activate {
      width: 100%; padding: 13px; border-radius: 12px;
      font-weight: 700; font-size: .95rem; border: none;
      background: linear-gradient(135deg, var(--green), #059669); color: #fff;
      transition: all .2s; cursor: pointer;
    }
    .btn-activate:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
    .btn-activate:disabled { opacity: .6; cursor: not-allowed; transform: none; box-shadow: none; }

    .hint-box {
      background: #fefce8; border: 1px solid #fde68a;
      border-radius: 10px; padding: 10px 14px;
      font-size: .78rem; color: #92400e; margin-bottom: 18px;
    }

    .alert { border-radius: 10px; font-size: .85rem; padding: 10px 14px; }

    .pw-strength {
      height: 4px; border-radius: 4px; background: #e2e8f0;
      margin-top: 6px; overflow: hidden; transition: all 0.3s;
    }
    .pw-strength-bar {
      height: 100%; width: 0; border-radius: 4px;
      transition: all 0.3s;
    }

    .back-link {
      text-align: center; margin-top: 16px; padding-top: 16px;
      border-top: 1px solid #e2e8f0;
    }
    .back-link a { color: #64748b; font-size: .8rem; text-decoration: none; transition: color .2s; }
    .back-link a:hover { color: var(--navy); }

    @media (max-width: 520px) {
      body { padding: 12px; }
      .auth-card { border-radius: 16px; }
      .auth-body { padding: 20px 18px 24px; }
    }
  </style>
</head>
<body>
<div class="auth-card">

  <!-- Header -->
  <div class="auth-header">
    <div class="icon"><i class="bi bi-shield-check"></i></div>
    <h2>Verify Your Identity</h2>
    <p>Confirm your <?= $idLabel ?> and set a new password<br>to activate your account.</p>
  </div>

  <div class="auth-body">

    <!-- Welcome banner -->
    <div class="welcome-banner">
      <div class="welcome-avatar">
        <?= strtoupper(substr($user['fname'],0,1) . substr($user['lname'],0,1)) ?>
      </div>
      <div>
        <div class="name"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></div>
        <div class="email"><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>

    <!-- Step indicator -->
    <div class="step-indicator">
      <div class="step-dot done"></div>
      <div class="step-dot active"></div>
      <div class="step-dot"></div>
    </div>

    <div class="hint-box">
      <i class="bi bi-info-circle me-1"></i>
      <strong>First-time login:</strong> To protect your account, please enter your
      <strong><?= $idLabel ?></strong> exactly as registered by the admin, then choose a new password.
    </div>

    <div id="activateAlert" class="mb-3"></div>

    <form onsubmit="doActivate(event)" novalidate>

      <!-- Step 1: Verify ID -->
      <div class="section-label"><i class="bi bi-1-circle-fill text-success"></i> Verify Identity</div>

      <div class="mb-4">
        <label class="form-label"><i class="bi bi-person-vcard me-1"></i><?= $idLabel ?></label>
        <input id="studentId" type="text" class="form-control" placeholder="Enter your <?= $idLabel ?>" required autofocus>
      </div>

      <!-- Step 2: New Password -->
      <div class="section-label"><i class="bi bi-2-circle-fill text-success"></i> Set New Password</div>

      <div class="mb-3">
        <label class="form-label"><i class="bi bi-lock me-1"></i>New Password</label>
        <div class="input-group">
          <input id="newPassword" type="password" class="form-control" placeholder="Min. 6 characters" required oninput="checkStrength()">
          <button type="button" class="btn-toggle-pw" onclick="togglePw('newPassword','pwEye1')" tabindex="-1">
            <i class="bi bi-eye" id="pwEye1"></i>
          </button>
        </div>
        <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
      </div>

      <div class="mb-4">
        <label class="form-label"><i class="bi bi-lock-fill me-1"></i>Confirm Password</label>
        <div class="input-group">
          <input id="confirmPassword" type="password" class="form-control" placeholder="Re-enter password" required>
          <button type="button" class="btn-toggle-pw" onclick="togglePw('confirmPassword','pwEye2')" tabindex="-1">
            <i class="bi bi-eye" id="pwEye2"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-activate" id="activateBtn">
        <i class="bi bi-check-circle me-2"></i>Activate My Account
      </button>
    </form>

    <div class="back-link">
      <a href="signup.php" onclick="sessionStorage.clear()"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePw(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    const show = inp.type === 'password';
    inp.type   = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  }

  function checkStrength() {
    const pw  = document.getElementById('newPassword').value;
    const bar = document.getElementById('pwBar');
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const pct    = Math.min(score / 4 * 100, 100);
    const colors = ['#ef4444','#f59e0b','#eab308','#22c55e','#10b981'];
    bar.style.width = pct + '%';
    bar.style.background = colors[Math.min(score, 4)];
  }

  async function doActivate(e) {
    e.preventDefault();
    const div = document.getElementById('activateAlert');
    const btn = document.getElementById('activateBtn');

    const studentId  = document.getElementById('studentId').value.trim();
    const newPw      = document.getElementById('newPassword').value;
    const confirmPw  = document.getElementById('confirmPassword').value;

    if (!studentId) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Please enter your ID.</div>`;
      return;
    }
    if (newPw.length < 6) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Password must be at least 6 characters.</div>`;
      return;
    }
    if (newPw !== confirmPw) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Passwords do not match.</div>`;
      return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Verifying…`;
    div.innerHTML = '';

    const fd = new FormData();
    fd.append('action',           'activate_account');
    fd.append('student_id',       studentId);
    fd.append('new_password',     newPw);
    fd.append('confirm_password', confirmPw);

    try {
      const r = await fetch('student_auth.php', { method: 'POST', body: fd });
      const d = await r.json();
      if (d.success) {
        div.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>${d.message}</div>`;
        btn.innerHTML = `<i class="bi bi-check-circle me-2"></i>Success!`;
        setTimeout(() => { window.location.replace(d.redirect); }, 1000);
      } else {
        div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message}</div>`;
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-check-circle me-2"></i>Activate My Account`;
      }
    } catch(err) {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>Error contacting server. Please try again.</div>`;
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-check-circle me-2"></i>Activate My Account`;
    }
  }
</script>
</body>
</html>
