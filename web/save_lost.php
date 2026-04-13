<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';
include 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name     = trim($_POST['item_name']     ?? '');
    $last_seen     = trim($_POST['last_seen']      ?? '');
    $owner_name    = trim($_POST['owner_name']     ?? '');
    $owner_email   = trim($_POST['owner_email']    ?? '');
    $owner_contact = trim($_POST['owner_contact']  ?? '');
    $description   = trim($_POST['description']    ?? '');
    $date_lost     = !empty($_POST['date_lost']) ? $_POST['date_lost'] : null;
    $category      = trim($_POST['category']       ?? '');
    $user_id       = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // ── Server-side validation ─────────────────────────────────────────────
    if (empty($item_name)) {
        header("Location: report_lost.php?status=error&msg=Please+enter+the+item+name.");
        exit;
    }
    if (empty($category)) {
        header("Location: report_lost.php?status=error&msg=Please+select+a+category.");
        exit;
    }
    if (empty($date_lost)) {
        header("Location: report_lost.php?status=error&msg=Please+select+the+date+you+lost+the+item.");
        exit;
    }

    // ── Extra fields ───────────────────────────────────────────────────────
    $extra_brand       = trim($_POST['extra_brand']       ?? '');
    $extra_model       = trim($_POST['extra_model']       ?? '');
    $extra_color       = trim($_POST['extra_color']       ?? '');
    $extra_case        = trim($_POST['extra_case']        ?? '');
    $extra_contents    = trim($_POST['extra_contents']    ?? '');
    $extra_material    = trim($_POST['extra_material']    ?? '');
    $extra_id_type     = trim($_POST['extra_id_type']     ?? '');
    $extra_id_name     = trim($_POST['extra_id_name']     ?? '');
    $extra_key_type    = trim($_POST['extra_key_type']    ?? '');
    $extra_keychain    = trim($_POST['extra_keychain']    ?? '');
    $extra_type        = trim($_POST['extra_type']        ?? '');
    $extra_serial      = trim($_POST['extra_serial']      ?? '');
    $extra_size        = trim($_POST['extra_size']        ?? '');
    $extra_label       = trim($_POST['extra_label']       ?? '');
    $extra_title       = trim($_POST['extra_title']       ?? '');
    $extra_cover_color = trim($_POST['extra_cover_color'] ?? '');
    $extra_markings    = trim($_POST['extra_markings']    ?? '');
    $extra_item_type   = trim($_POST['extra_item_type']   ?? '');

    // ── Image Upload ───────────────────────────────────────────────────────
    $image_name = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_types)) {
            $image_name = "lost_" . uniqid() . "." . $file_ext;
            if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $target_dir . $image_name)) {
                $image_name = null;
            }
        }
    }

    // ── Insert ─────────────────────────────────────────────────────────────
    try {
        $sql = "INSERT INTO lost_reports (
                    item_name, category, last_seen_location, owner_name, owner_email, owner_contact,
                    description, date_lost, image_path, status, user_id,
                    extra_brand, extra_model, extra_color, extra_case, extra_contents,
                    extra_material, extra_id_type, extra_id_name, extra_key_type,
                    extra_keychain, extra_type, extra_serial, extra_size, extra_label,
                    extra_title, extra_cover_color, extra_markings, extra_item_type
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, 'Pending', ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $item_name, $category, $last_seen, $owner_name, $owner_email, $owner_contact,
            $description, $date_lost, $image_name, $user_id,
            $extra_brand, $extra_model, $extra_color, $extra_case, $extra_contents,
            $extra_material, $extra_id_type, $extra_id_name, $extra_key_type,
            $extra_keychain, $extra_type, $extra_serial, $extra_size, $extra_label,
            $extra_title, $extra_cover_color, $extra_markings, $extra_item_type
        ]);

        $newReportId = $pdo->lastInsertId();
        logActivity($pdo, $user_id ?? 0, 'create', 'lost_report', $newReportId, 'Reported lost: ' . $item_name);

        header("Location: report_lost.php?status=success&msg=Your+lost+report+has+been+submitted+for+approval!");
        exit;

    } catch (PDOException $e) {
        error_log("Save lost error: " . $e->getMessage());
        header("Location: report_lost.php?status=error&msg=Something+went+wrong.+Please+try+again.");
        exit;
    }

} else {
    header("Location: report_lost.php");
    exit;
}
?>