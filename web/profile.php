<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

require_once 'db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.*,
           c.college_name, d.department_name, co.course_name
    FROM users u
    LEFT JOIN colleges    c  ON c.id  = u.college_id
    LEFT JOIN departments d  ON d.id  = u.department_id
    LEFT JOIN courses     co ON co.id = u.course_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { session_destroy(); header('Location: login.php'); exit; }

$isStudent  = (int)$user['type_id'] === 1;
$isEmployee = (int)$user['type_id'] === 3;

$colleges   = $pdo->query("SELECT id, college_name FROM colleges ORDER BY college_name")->fetchAll(PDO::FETCH_ASSOC);
$allDepts   = $pdo->query("SELECT id, department_name, college_id FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$allCourses = $pdo->query("SELECT id, course_name, department_id FROM courses ORDER BY course_name")->fetchAll(PDO::FETCH_ASSOC);

// Activity stats
$stFound  = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ?"); $stFound->execute([$userId]); $countFound = $stFound->fetchColumn();
$stClaimed = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE user_id = ?"); $stClaimed->execute([$userId]); $countClaimed = $stClaimed->fetchColumn();
$stMsgs   = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ?"); $stMsgs->execute([$userId]); $countMsgs = $stMsgs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile – FoundIt!</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root { --navy: #0b1d3a; --blue: #0d6efd; --teal: #0d9488; }
    body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; min-height: 100vh; }


    .page-wrap { max-width: 860px; margin: 20px auto; padding: 0 16px 60px; }

    /* Animated avatar ring */
    .profile-hero {
      background: linear-gradient(135deg, var(--navy), #1e3a6e);
      border-radius: 18px; padding: 28px 30px; display: flex; align-items: center; gap: 22px;
      margin-bottom: 24px; color: #fff; position: relative; overflow: hidden;
    }
    .profile-hero::before {
      content: ''; position: absolute; top: -30px; right: -30px;
      width: 200px; height: 200px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,204,0,0.1), transparent 70%);
    }
    .avatar {
      width: 76px; height: 76px; border-radius: 50%;
      background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
      font-size: 2rem; flex-shrink: 0;
      border: 3px solid transparent;
      background-image: linear-gradient(rgba(255,255,255,.18), rgba(255,255,255,.18)), linear-gradient(135deg, #ffcc00, #0d6efd, #ffcc00);
      background-origin: border-box;
      background-clip: padding-box, border-box;
      animation: avatarRing 4s linear infinite;
    }
    @keyframes avatarRing {
      0% { filter: hue-rotate(0deg); }
      100% { filter: hue-rotate(360deg); }
    }
    .profile-hero h4 { font-family: 'Syne',sans-serif; font-weight: 800; font-size: 1.25rem; margin: 0 0 3px; position: relative; }
    .profile-hero p  { color: rgba(255,255,255,.7); font-size: .85rem; margin: 0; position: relative; }
    .type-badge { display: inline-block; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); border-radius: 20px; padding: 3px 12px; font-size: .75rem; font-weight: 600; margin-top: 6px; }

    /* Activity stat cards */
    .stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
    .stat-card {
      background: white; border-radius: 14px; padding: 18px; text-align: center;
      box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f1f5f9;
      transition: all 0.3s; opacity: 0; transform: translateY(16px);
      animation: statReveal 0.5s ease forwards;
    }
    .stat-card:nth-child(1) { animation-delay: 0s; }
    .stat-card:nth-child(2) { animation-delay: 0.1s; }
    .stat-card:nth-child(3) { animation-delay: 0.2s; }
    @keyframes statReveal { to { opacity: 1; transform: translateY(0); } }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
    .stat-num { font-family: 'Syne',sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .stat-label { font-size: .78rem; color: #94a3b8; font-weight: 500; margin-top: 4px; }
    .stat-icon { font-size: 1.3rem; margin-bottom: 8px; }

    .card { border: none; border-radius: 16px; box-shadow: 0 2px 16px rgba(0,0,0,.07); margin-bottom: 20px; opacity: 0; transform: translateY(16px); animation: statReveal 0.5s 0.3s ease forwards; }
    .card:nth-of-type(2) { animation-delay: 0.4s; }
    .card-header { background: transparent; border-bottom: 1px solid #e2e8f0; padding: 16px 22px 12px; font-family: 'Syne',sans-serif; font-weight: 700; font-size: .95rem; color: var(--navy); display: flex; align-items: center; gap: 8px; }
    .card-body { padding: 22px; }

    .form-label { font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: 5px; }
    .form-control, .form-select { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 13px; font-size: .875rem; transition: all .2s; }
    .form-control:focus, .form-select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(13,110,253,.1); }

    .locked-field { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 13px; font-size: .875rem; color: #64748b; display: flex; align-items: center; gap: 8px; }
    .locked-field i { color: #94a3b8; }

    .input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
    .input-group .btn-eye { border: 1.5px solid #e2e8f0; border-left: none; border-radius: 0 10px 10px 0; background: #f8fafc; color: #64748b; padding: 0 13px; cursor: pointer; }

    .btn-save { background: var(--blue); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-weight: 700; font-size: .9rem; transition: all .2s; cursor: pointer; }
    .btn-save:hover { background: #0b5ed7; transform: translateY(-1px); }
    .btn-save-teal { background: var(--teal); }
    .btn-save-teal:hover { background: #0f766e; }

    .alert { border-radius: 10px; font-size: .85rem; }
    .section-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin: 16px 0 10px; display: flex; align-items: center; gap: 8px; }
    .section-label::after { content:''; flex:1; height:1px; background:#e5e7eb; }

    /* Confetti overlay */
    .confetti-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      pointer-events: none; z-index: 99999; display: none;
    }
    .confetti-overlay.active { display: block; }
    .confetti-piece {
      position: absolute; width: 8px; height: 8px; border-radius: 2px;
      animation: confettiFall 1.5s ease forwards;
    }
    @keyframes confettiFall {
      0% { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
      100% { opacity: 0; transform: translateY(100vh) rotate(720deg) scale(0.5); }
    }

    @media (max-width: 600px) {
      .profile-hero { flex-direction: column; text-align: center; }
      .page-wrap { margin-top: 20px; }
      .stat-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-wrap">

  <!-- Hero -->
  <div class="profile-hero">
    <div class="avatar"><i class="bi bi-person-fill"></i></div>
    <div style="position:relative">
      <h4><?= htmlspecialchars($user['fname'].' '.($user['mname'] ? $user['mname'].' ' : '').$user['lname']) ?></h4>
      <p><?= htmlspecialchars($user['email']) ?></p>
      <span class="type-badge">
        <i class="bi bi-<?= $isStudent ? 'mortarboard' : 'person-badge' ?> me-1"></i>
        <?= $isStudent ? 'Student' : 'Staff / Employee' ?>
      </span>
    </div>
  </div>

  <!-- Activity Stats -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon" style="color:#0d6efd"><i class="bi bi-box-seam-fill"></i></div>
      <div class="stat-num" data-count="<?= $countFound ?>">0</div>
      <div class="stat-label">Items Reported</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="color:#10b981"><i class="bi bi-hand-index-thumb-fill"></i></div>
      <div class="stat-num" data-count="<?= $countClaimed ?>">0</div>
      <div class="stat-label">Claims Made</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="color:#f59e0b"><i class="bi bi-chat-dots-fill"></i></div>
      <div class="stat-num" data-count="<?= $countMsgs ?>">0</div>
      <div class="stat-label">Messages Sent</div>
    </div>
  </div>

  <!-- Personal + Academic/Work Details -->
  <div class="card">
    <div class="card-header"><i class="bi bi-pencil-square"></i> Edit My Profile</div>
    <div class="card-body">
      <div id="profileAlert" class="mb-3"></div>

      <!-- Locked -->
      <div class="section-label">Cannot be changed — contact admin</div>
      <div class="row g-3 mb-2">
        <div class="col-sm-6">
          <label class="form-label"><i class="bi bi-envelope me-1"></i>School Email</label>
          <div class="locked-field"><i class="bi bi-lock-fill"></i><?= htmlspecialchars($user['email']) ?></div>
        </div>
        <div class="col-sm-6">
          <label class="form-label"><i class="bi bi-card-text me-1"></i><?= $isStudent ? 'Student ID' : 'Employee ID' ?></label>
          <div class="locked-field"><i class="bi bi-lock-fill"></i><?= htmlspecialchars($user['student_id'] ?? 'N/A') ?></div>
        </div>
      </div>

      <form onsubmit="saveProfile(event)" novalidate>

        <!-- Name -->
        <div class="section-label">Name</div>
        <div class="row g-3 mb-3">
          <div class="col-sm-4">
            <label class="form-label">First Name <span class="text-danger">*</span></label>
            <input id="pFname" type="text" class="form-control" value="<?= htmlspecialchars($user['fname']) ?>" required>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Middle Name</label>
            <input id="pMname" type="text" class="form-control" value="<?= htmlspecialchars($user['mname'] ?? '') ?>">
          </div>
          <div class="col-sm-4">
            <label class="form-label">Last Name <span class="text-danger">*</span></label>
            <input id="pLname" type="text" class="form-control" value="<?= htmlspecialchars($user['lname']) ?>" required>
          </div>
        </div>

        <!-- Contact -->
        <div class="row g-3 mb-3">
          <div class="col-sm-5">
            <label class="form-label">Contact Number</label>
            <input id="pContact" type="tel" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" placeholder="09xx-xxx-xxxx">
          </div>
        </div>

        <?php if ($isStudent): ?>
        <!-- Student Academic Info -->
        <div class="section-label">Academic Info</div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label class="form-label">College <span class="text-danger">*</span></label>
            <select id="pCollege" class="form-select" onchange="filterDepts()" required>
              <option value="">-- Select College --</option>
              <?php foreach($colleges as $col): ?>
              <option value="<?= $col['id'] ?>" <?= $user['college_id'] == $col['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($col['college_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select id="pDept" class="form-select" onchange="filterCourses()" required>
              <option value="">-- Select Department --</option>
              <?php foreach($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>" data-college="<?= $d['college_id'] ?>"
                <?= $user['department_id'] == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['department_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Course <span class="text-danger">*</span></label>
            <select id="pCourse" class="form-select" required>
              <option value="">-- Select Course --</option>
              <?php foreach($allCourses as $c): ?>
              <option value="<?= $c['id'] ?>" data-dept="<?= $c['department_id'] ?>"
                <?= $user['course_id'] == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Year Level <span class="text-danger">*</span></label>
            <select id="pYear" class="form-select" required>
              <option value="">-- Select Year --</option>
              <?php foreach([1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year',5=>'5th Year'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= (int)($user['year'] ?? 0) === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Section</label>
            <input id="pSection" type="text" class="form-control" value="<?= htmlspecialchars($user['section'] ?? '') ?>" placeholder="e.g. 3B, A">
          </div>
        </div>

        <?php elseif ($isEmployee): ?>
        <!-- Employee Work Info -->
        <div class="section-label">Work Info</div>
        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label class="form-label">Department</label>
            <select id="pDept" class="form-select">
              <option value="">-- Select Department --</option>
              <?php foreach($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $user['department_id'] == $d['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['department_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6">
            <label class="form-label">Position</label>
            <select id="pPosition" class="form-select">
              <option value="">-- Select Position --</option>
              <?php foreach(['Instructor','Assistant Professor','Associate Professor','Professor','Dean','Department Chair','Administrative Staff','Registrar Staff','Librarian','Guidance Counselor','Nurse / Clinic Staff','Other'] as $pos): ?>
              <option value="<?= $pos ?>" <?= ($user['position'] ?? '') === $pos ? 'selected' : '' ?>><?= $pos ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn-save mt-2">
          <i class="bi bi-check-lg me-2"></i>Save Changes
        </button>
      </form>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header"><i class="bi bi-shield-lock"></i> Change Password</div>
    <div class="card-body">
      <div id="pwAlert" class="mb-3"></div>
      <form onsubmit="changePassword(event)" novalidate style="max-width:400px">
        <div class="mb-3">
          <label class="form-label">Current Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input id="pwCurrent" type="password" class="form-control" placeholder="••••••••" required>
            <button type="button" class="btn-eye" onclick="togglePw('pwCurrent','eye1')"><i id="eye1" class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input id="pwNew" type="password" class="form-control" placeholder="At least 6 characters" required>
            <button type="button" class="btn-eye" onclick="togglePw('pwNew','eye2')"><i id="eye2" class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
          <div class="input-group">
            <input id="pwConfirm" type="password" class="form-control" placeholder="Repeat new password" required>
            <button type="button" class="btn-eye" onclick="togglePw('pwConfirm','eye3')"><i id="eye3" class="bi bi-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn-save btn-save-teal">
          <i class="bi bi-key me-2"></i>Update Password
        </button>
      </form>
    </div>
  </div>

</div>

<!-- Confetti overlay -->
<div class="confetti-overlay" id="confettiOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Count-up animation for stat numbers
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-num[data-count]').forEach(el => {
      const target = parseInt(el.dataset.count) || 0;
      if (target === 0) { el.textContent = '0'; return; }
      let current = 0;
      const step = Math.ceil(target / 40);
      const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current >= target) clearInterval(timer);
      }, 25);
    });
  });

  function showConfetti() {
    const overlay = document.getElementById('confettiOverlay');
    overlay.innerHTML = '';
    overlay.classList.add('active');
    const colors = ['#0d6efd','#ffcc00','#10b981','#f59e0b','#ef4444','#8b5cf6'];
    for (let i = 0; i < 40; i++) {
      const piece = document.createElement('div');
      piece.className = 'confetti-piece';
      piece.style.left = Math.random() * 100 + '%';
      piece.style.top = '-10px';
      piece.style.background = colors[Math.floor(Math.random() * colors.length)];
      piece.style.animationDelay = (Math.random() * 0.5) + 's';
      piece.style.animationDuration = (1 + Math.random()) + 's';
      overlay.appendChild(piece);
    }
    setTimeout(() => overlay.classList.remove('active'), 2500);
  }
  const allDepts   = <?= json_encode(array_map(fn($d) => ['id'=>(string)$d['id'],'college_id'=>(string)$d['college_id'],'name'=>$d['department_name']], $allDepts)) ?>;
  const allCourses = <?= json_encode(array_map(fn($c) => ['id'=>(string)$c['id'],'dept_id'=>(string)$c['department_id'],'name'=>$c['course_name']], $allCourses)) ?>;

  function filterDepts() {
    const collegeId = document.getElementById('pCollege')?.value;
    const deptSel   = document.getElementById('pDept');
    const prevDept  = deptSel.value;
    deptSel.innerHTML = '<option value="">-- Select Department --</option>';
    allDepts.filter(d => !collegeId || d.college_id === collegeId)
            .forEach(d => {
              const o = new Option(d.name, d.id);
              if (d.id === prevDept) o.selected = true;
              deptSel.add(o);
            });
    filterCourses();
  }

  function filterCourses() {
    const deptId    = document.getElementById('pDept')?.value;
    const courseSel = document.getElementById('pCourse');
    if (!courseSel) return;
    const prevCourse = courseSel.value;
    courseSel.innerHTML = '<option value="">-- Select Course --</option>';
    allCourses.filter(c => !deptId || c.dept_id === deptId)
              .forEach(c => {
                const o = new Option(c.name, c.id);
                if (c.id === prevCourse) o.selected = true;
                courseSel.add(o);
              });
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('pCollege')) filterDepts();
  });

  function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    ico.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  }

  async function saveProfile(e) {
    e.preventDefault();
    const div = document.getElementById('profileAlert');
    div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Saving…</div>`;
    const fd = new FormData();
    fd.append('action',  'update_profile');
    fd.append('fname',   document.getElementById('pFname').value.trim());
    fd.append('mname',   document.getElementById('pMname').value.trim());
    fd.append('lname',   document.getElementById('pLname').value.trim());
    fd.append('contact', document.getElementById('pContact').value.trim());
    if (document.getElementById('pCollege')) {
      fd.append('college_id',    document.getElementById('pCollege').value);
      fd.append('department_id', document.getElementById('pDept').value);
      fd.append('course_id',     document.getElementById('pCourse').value);
      fd.append('year',          document.getElementById('pYear').value);
      fd.append('section',       document.getElementById('pSection').value.trim());
    }
    if (document.getElementById('pPosition')) {
      fd.append('department_id', document.getElementById('pDept').value);
      fd.append('position',      document.getElementById('pPosition').value);
    }
    try {
      const r = await fetch('student_auth.php', {method:'POST', body:fd});
      const d = await r.json();
      if (d.success) {
        div.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${d.message}</div>`;
        showConfetti();
      } else {
        div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message}</div>`;
      }
    } catch(err) {
      div.innerHTML = `<div class="alert alert-danger">Error contacting server.</div>`;
    }
  }

  async function changePassword(e) {
    e.preventDefault();
    const div = document.getElementById('pwAlert');
    div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Updating…</div>`;
    const fd = new FormData();
    fd.append('action',           'change_password');
    fd.append('current_password', document.getElementById('pwCurrent').value);
    fd.append('new_password',     document.getElementById('pwNew').value);
    fd.append('confirm_password', document.getElementById('pwConfirm').value);
    try {
      const r = await fetch('student_auth.php', {method:'POST', body:fd});
      const d = await r.json();
      if (d.success) {
        div.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${d.message}</div>`;
        document.getElementById('pwCurrent').value = '';
        document.getElementById('pwNew').value     = '';
        document.getElementById('pwConfirm').value = '';
      } else {
        div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message}</div>`;
      }
    } catch(err) {
      div.innerHTML = `<div class="alert alert-danger">Error contacting server.</div>`;
    }
  }
</script>
<?php include 'footer.php'; ?>
</body>
</html>