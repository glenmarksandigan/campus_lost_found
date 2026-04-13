<?php
// Must be first — before ANY output or whitespace
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM lost_reports WHERE id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    header("Location: index.php"); exit;
}

// Check if already contacted by this user/session
$alreadyContacted = false;
if (isset($_SESSION['user_id'])) {
    $check = $pdo->prepare("SELECT id FROM lost_contacts WHERE report_id = ? AND finder_user_id = ?");
    $check->execute([$id, $_SESSION['user_id']]);
    $alreadyContacted = (bool) $check->fetch();
}

$lostImg = (!empty($report['image_path']))
    ? 'uploads/' . $report['image_path']
    : 'https://placehold.co/400x300/e2e8f0/64748b?text=No+Image';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Found This – <?= htmlspecialchars($report['item_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; }
        .contact-container { max-width: 560px; margin: 60px auto; padding-bottom: 80px; }
        .card { border: none; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.07); }
        .form-control, .form-select {
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 12px 16px; transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd; box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
        }
        .btn-submit {
            background: linear-gradient(135deg, #0d6efd, #0a4ab2);
            border: none; border-radius: 12px; padding: 14px;
            font-weight: 700; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13,110,253,0.3); }
        .item-preview {
            display: flex; align-items: center; gap: 14px;
            background: #f1f5f9; border-radius: 14px; padding: 14px 16px;
            margin-bottom: 24px;
        }
        .item-preview img {
            width: 70px; height: 70px; object-fit: cover;
            border-radius: 10px; flex-shrink: 0;
        }
        .tip-box {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 12px; padding: 12px 16px;
            font-size: 0.82rem; color: #1e40af; margin-bottom: 20px;
        }
        .section-label {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #94a3b8;
            display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .combo-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container contact-container">
    <div class="card p-4 p-md-5">

        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Not logged in -->
        <div class="text-center py-4">
            <i class="bi bi-person-lock" style="font-size:3rem;color:#94a3b8"></i>
            <h4 class="fw-bold mt-3 mb-2">Login Required</h4>
            <p class="text-muted mb-4">You need to be logged in to contact the owner.</p>
            <a href="auth.php" class="btn btn-primary px-5" style="border-radius:12px;font-weight:700">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
        </div>

        <?php elseif ($alreadyContacted): ?>
        <!-- Already submitted -->
        <div class="text-center py-4">
            <div style="width:72px;height:72px;background:rgba(13,110,253,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <i class="bi bi-clock-history text-primary" style="font-size:2rem"></i>
            </div>
            <h4 class="fw-bold mb-2">Already Submitted</h4>
            <p class="text-muted mb-4">You've already sent a message about this item. The owner will be notified to contact you.</p>
            <a href="index.php" class="btn btn-outline-secondary px-5" style="border-radius:12px;font-weight:700">
                <i class="bi bi-house me-2"></i>Go Home
            </a>
        </div>

        <?php else: ?>
        <!-- Contact form -->
        <div style="width:48px;height:48px;background:rgba(220,38,38,0.1);color:#dc2626;display:flex;align-items:center;justify-content:center;border-radius:12px;margin-bottom:16px">
            <i class="bi bi-hand-thumbs-up fs-4"></i>
        </div>
        <h4 class="fw-bold mb-1">I Found This Item</h4>
        <p class="text-secondary mb-4" style="font-size:.9rem">Tell the owner where you found it and how to reach you.</p>

        <!-- Lost item preview -->
        <div class="item-preview">
            <img src="<?= $lostImg ?>" alt="<?= htmlspecialchars($report['item_name']) ?>">
            <div>
                <div class="fw-bold"><?= htmlspecialchars($report['item_name']) ?></div>
                <small class="text-muted d-block">
                    <i class="bi bi-geo-alt me-1"></i>Last seen: <?= htmlspecialchars($report['last_seen_location'] ?? 'Unknown') ?>
                </small>
                <small class="text-muted">
                    <i class="bi bi-calendar-x me-1"></i>Lost: <?= date('M d, Y', strtotime($report['date_lost'])) ?>
                </small>
            </div>
        </div>

        <div class="tip-box">
            <i class="bi bi-info-circle-fill me-2"></i>
            Your contact info will be shared with the item owner so they can reach you to retrieve it.
        </div>

        <form action="process_contact.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="report_id" value="<?= $id ?>">

            <!-- Finder Info (pre-filled if logged in) -->
            <?php
            $finderName    = '';
            $finderContact = '';
            $finderEmail   = '';
            if (isset($_SESSION['user_id'])) {
                $me = $pdo->prepare("SELECT fname, lname, contact_number, email FROM users WHERE id = ?");
                $me->execute([$_SESSION['user_id']]);
                $me = $me->fetch();
                $finderName    = trim(($me['fname'] ?? '') . ' ' . ($me['lname'] ?? ''));
                $finderContact = $me['contact_number'] ?? '';
                $finderEmail   = $me['email'] ?? '';
            }
            ?>

            <div class="section-label">Your Contact Info</div>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="finder_name" class="form-control"
                           value="<?= htmlspecialchars($finderName) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Contact Number <span class="text-danger">*</span></label>
                    <input type="text" name="finder_contact" class="form-control"
                           placeholder="09xx-xxx-xxxx"
                           value="<?= htmlspecialchars($finderContact) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="finder_email" class="form-control"
                           placeholder="Optional"
                           value="<?= htmlspecialchars($finderEmail) ?>">
                </div>
            </div>

            <div class="section-label">Where You Found It</div>
            <div class="mb-4">
                <label class="form-label fw-bold">Current Location of Item <span class="text-danger">*</span></label>
                <input type="text" name="found_location" class="form-control"
                       placeholder="e.g. I left it at the Guard House / I still have it" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Message to Owner <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="4"
                    placeholder="Describe how you found it, where it currently is, and the best way to contact you..." required></textarea>
            </div>

            <!-- Optional photo -->
            <div class="mb-4">
                <label class="form-label fw-bold">
                    Photo of Item <span class="text-muted fw-normal">(Optional)</span>
                </label>
                <div class="input-group">
                    <input type="file" name="finder_image" class="form-control" id="finderImageInput" accept="image/*">
                    <label class="input-group-text" for="finderImageInput"><i class="bi bi-camera"></i></label>
                </div>
                <div class="combo-hint">Upload a photo to confirm you have the item.</div>
                <div id="imgPreviewWrap" class="mt-2 d-none">
                    <img id="imgPreview" src="" style="max-height:160px;border-radius:10px;border:2px solid #e2e8f0;object-fit:cover">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit w-100">
                <i class="bi bi-send-fill me-2"></i>Send to Owner
            </button>
        </form>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('finderImageInput')?.addEventListener('change', function() {
    const file = this.files[0];
    const wrap = document.getElementById('imgPreviewWrap');
    const preview = document.getElementById('imgPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; wrap.classList.remove('d-none'); };
        reader.readAsDataURL(file);
    } else {
        wrap.classList.add('d-none');
    }
});
</script>
</body>
</html>