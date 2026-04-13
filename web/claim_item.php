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
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: index.php"); exit;
}

// Check if user already claimed
$alreadyClaimed = false;
if (isset($_SESSION['user_id'])) {
    $check = $pdo->prepare("SELECT id FROM claims WHERE item_id = ? AND user_id = ?");
    $check->execute([$id, $_SESSION['user_id']]);
    $alreadyClaimed = (bool) $check->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Item – <?= htmlspecialchars($item['item_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .claim-card {
            max-width: 600px; margin: 60px auto;
            border: none; border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.07);
        }
        .item-preview {
            display: flex; align-items: center; gap: 14px;
            background: #f1f5f9; border-radius: 14px; padding: 14px 16px;
            margin-bottom: 24px;
        }
        .item-preview img {
            width: 64px; height: 64px; object-fit: cover;
            border-radius: 10px; flex-shrink: 0;
        }
        .form-control {
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 12px 16px; transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
        }
        .btn-submit {
            background: linear-gradient(135deg, #0d6efd, #0a4ab2);
            border: none; border-radius: 12px; padding: 14px;
            font-weight: 700; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13,110,253,0.3); }
        .tip-box {
            background: #fffbeb; border: 1px solid #fde68a;
            border-radius: 12px; padding: 12px 16px;
            font-size: 0.82rem; color: #92400e; margin-bottom: 20px;
        }

        /* ── Slip-style form sections ── */
        .slip-section {
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 14px; padding: 16px 18px; margin-bottom: 16px;
        }
        .slip-section-title {
            font-weight: 700; font-size: .82rem; color: #334155;
            text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px;
        }
        .slip-field {
            display: flex; align-items: baseline; gap: 8px;
            margin-bottom: 10px; padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .slip-field:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .slip-label {
            font-size: .76rem; font-weight: 700; color: #64748b;
            min-width: 140px; white-space: nowrap;
        }
        .slip-value {
            font-size: .85rem; color: #1e293b; font-weight: 500;
            flex: 1; word-break: break-word;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <div class="card claim-card p-4 p-md-5">

        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Not logged in -->
        <div class="text-center py-4">
            <i class="bi bi-person-lock" style="font-size:3rem;color:#94a3b8"></i>
            <h4 class="fw-bold mt-3 mb-2">Login Required</h4>
            <p class="text-muted mb-4">You need to be logged in to submit a claim.</p>
            <a href="auth.php" class="btn btn-primary px-5" style="border-radius:12px;font-weight:700">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
        </div>

        <?php elseif ($alreadyClaimed): ?>
        <!-- User's own claim status -->
        <div class="text-center py-4">
            <div style="width:72px;height:72px;background:rgba(16,185,129,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <i class="bi bi-check-circle text-success" style="font-size:2rem"></i>
            </div>
            <h4 class="fw-bold mb-2">Your Claim Has Been Submitted!</h4>
            <p class="text-muted mb-2">Your claim for <strong><?= htmlspecialchars($item['item_name']) ?></strong> is currently being reviewed.</p>
            <p class="text-muted mb-4" style="font-size:.85rem">
                <i class="bi bi-info-circle me-1"></i>
                The admin or guard will verify ownership and contact you once approved. Please wait for further instructions.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="index.php" class="btn btn-outline-secondary px-4" style="border-radius:12px;font-weight:700">
                    <i class="bi bi-house me-2"></i>Browse Items
                </a>
                <a href="index.php" class="btn btn-primary px-4" style="border-radius:12px;font-weight:700" data-bs-toggle="modal" data-bs-target="#myReportsModal">
                    <i class="bi bi-list-check me-2"></i>My Submissions
                </a>
            </div>
        </div>

        <?php else:
            // Fetch current user info to pre-fill
            $userStmt = $pdo->prepare("SELECT u.fname, u.lname, u.student_id, u.year, c.course_name 
                FROM users u LEFT JOIN courses c ON u.course_id = c.id WHERE u.id = ?");
            $userStmt->execute([$_SESSION['user_id']]);
            $claimUser = $userStmt->fetch(PDO::FETCH_ASSOC);

            // Fetch finder info
            $finderStmt = $pdo->prepare("SELECT u.fname, u.lname, c.course_name, u.year
                FROM users u LEFT JOIN courses c ON u.course_id = c.id WHERE u.id = ?");
            $finderStmt->execute([$item['user_id'] ?? 0]);
            $finderInfo = $finderStmt->fetch(PDO::FETCH_ASSOC);

            $finderFullName = $finderInfo ? trim($finderInfo['fname'] . ' ' . $finderInfo['lname']) : ($item['turned_in_by'] ?? '');
            $finderProgram  = $finderInfo ? trim(($finderInfo['course_name'] ?? '') . ($finderInfo['year'] ? ', Year ' . $finderInfo['year'] : '')) : '';
            $claimantName   = $claimUser ? trim($claimUser['fname'] . ' ' . $claimUser['lname']) : '';
            $claimantProgram= $claimUser ? trim(($claimUser['course_name'] ?? '') . ($claimUser['year'] ? ', Year ' . $claimUser['year'] : '')) : '';
        ?>

        <!-- Slip-style Claim Form -->
        <div class="text-center mb-3">
            <div style="width:52px;height:52px;background:rgba(13,110,253,0.1);color:#0d6efd;display:flex;align-items:center;justify-content:center;border-radius:14px;margin:0 auto 12px">
                <i class="bi bi-clipboard-check fs-4"></i>
            </div>
            <h4 class="fw-bold mb-1">Lost and Found Slip</h4>
            <p class="text-muted mb-0" style="font-size:.82rem">F-SAS-SDS-005 · Fill out this form to claim the item</p>
        </div>

        <form action="process_claim.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Item / Finder Section -->
            <div class="slip-section">
                <div class="slip-section-title"><i class="bi bi-box-seam me-2"></i>Item Information</div>
                
                <div class="slip-field">
                    <label class="slip-label">Date Reported</label>
                    <div class="slip-value"><?= date('F d, Y', strtotime($item['created_at'])) ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Name of Finder</label>
                    <div class="slip-value"><?= htmlspecialchars($finderFullName ?: '—') ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Program, Yr, Section</label>
                    <div class="slip-value"><?= htmlspecialchars($finderProgram ?: '—') ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Place Found</label>
                    <div class="slip-value"><?= htmlspecialchars($item['found_location'] ?? '—') ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Date & Time Found</label>
                    <div class="slip-value"><?= $item['date_found'] ? date('F d, Y – h:i A', strtotime($item['date_found'])) : '—' ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Item Description</label>
                    <div class="slip-value"><?= htmlspecialchars($item['item_name'] ?? '') ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Content / Details</label>
                    <div class="slip-value" style="font-size:.8rem"><?= htmlspecialchars($item['description'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Claimant Section -->
            <div class="slip-section">
                <div class="slip-section-title"><i class="bi bi-person-fill me-2"></i>Claimant Information</div>

                <div class="slip-field">
                    <label class="slip-label">Date of Claim</label>
                    <div class="slip-value"><?= date('F d, Y') ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Owner/Recipient Name</label>
                    <div class="slip-value fw-bold"><?= htmlspecialchars($claimantName) ?></div>
                </div>
                <div class="slip-field">
                    <label class="slip-label">Program, Yr, Section</label>
                    <div class="slip-value"><?= htmlspecialchars($claimantProgram ?: '—') ?></div>
                </div>
            </div>

            <!-- Proof Section -->
            <div class="slip-section">
                <div class="slip-section-title"><i class="bi bi-shield-check me-2"></i>Proof of Ownership</div>

                <div class="tip-box mb-3">
                    <i class="bi bi-lightbulb-fill me-2"></i>
                    <strong>Tip:</strong> Mention specific details like the color, brand, contents inside, unique marks, stickers, or the lock screen wallpaper.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.85rem">Describe why this is yours <span class="text-danger">*</span></label>
                    <textarea name="claimer_message" class="form-control" rows="4"
                        placeholder="e.g. The wallet is brown leather, contains 2 ATM cards, a school ID with my name Juan Dela Cruz, and about ₱500 cash..." required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:.85rem">
                        Supporting Photo
                        <span class="text-muted fw-normal">(Optional)</span>
                    </label>
                    <div class="input-group">
                        <input type="file" name="claim_image" class="form-control" id="claimImageInput" accept="image/*">
                        <label class="input-group-text" for="claimImageInput"><i class="bi bi-camera"></i></label>
                    </div>
                    <small class="text-muted">Upload a photo proving ownership — e.g. a previous photo, receipt, or screenshot.</small>
                    <div id="imgPreviewWrap" class="mt-3 d-none">
                        <img id="imgPreview" src="" alt="Preview"
                             style="max-height:180px;border-radius:12px;object-fit:cover;border:2px solid #e2e8f0">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit w-100">
                <i class="bi bi-send-fill me-2"></i>Submit Claim
            </button>
        </form>

        <script>
        document.getElementById('claimImageInput').addEventListener('change', function() {
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
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>