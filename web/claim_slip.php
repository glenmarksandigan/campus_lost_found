<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Must have a claim ID
if (empty($_GET['claim_id']) && empty($_GET['item_id'])) {
    header("Location: index.php"); exit;
}

// Fetch by claim_id or item_id
if (!empty($_GET['claim_id'])) {
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS claim_id, c.claim_message, c.claimed_at, c.returned_at,
            i.id AS item_id, i.item_name, i.category, i.found_location, i.storage_location,
            i.description, i.date_found, i.turned_in_by, i.created_at AS date_reported,
            i.claimed_at AS item_claimed_at,
            finder.fname AS finder_fname, finder.lname AS finder_lname,
            finder.student_id AS finder_student_id, finder.type_id AS finder_type_id,
            fc.course_name AS finder_course, finder.year AS finder_year, finder.section AS finder_section,
            claimer.fname AS claimer_fname, claimer.lname AS claimer_lname,
            claimer.student_id AS claimer_student_id, claimer.type_id AS claimer_type_id,
            cc.course_name AS claimer_course, claimer.year AS claimer_year, claimer.section AS claimer_section
        FROM claims c
        JOIN items i ON c.item_id = i.id
        LEFT JOIN users finder  ON i.user_id      = finder.id
        LEFT JOIN users claimer ON c.user_id       = claimer.id
        LEFT JOIN courses fc ON finder.course_id   = fc.id
        LEFT JOIN courses cc ON claimer.course_id  = cc.id
        WHERE c.id = ?
    ");
    $stmt->execute([(int)$_GET['claim_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS claim_id, c.claim_message, c.claimed_at, c.returned_at,
            i.id AS item_id, i.item_name, i.category, i.found_location, i.storage_location,
            i.description, i.date_found, i.turned_in_by, i.created_at AS date_reported,
            i.claimed_at AS item_claimed_at,
            finder.fname AS finder_fname, finder.lname AS finder_lname,
            finder.student_id AS finder_student_id, finder.type_id AS finder_type_id,
            fc.course_name AS finder_course, finder.year AS finder_year, finder.section AS finder_section,
            claimer.fname AS claimer_fname, claimer.lname AS claimer_lname,
            claimer.student_id AS claimer_student_id, claimer.type_id AS claimer_type_id,
            cc.course_name AS claimer_course, claimer.year AS claimer_year, claimer.section AS claimer_section
        FROM claims c
        JOIN items i ON c.item_id = i.id
        LEFT JOIN users finder  ON i.user_id      = finder.id
        LEFT JOIN users claimer ON c.user_id       = claimer.id
        LEFT JOIN courses fc ON finder.course_id   = fc.id
        LEFT JOIN courses cc ON claimer.course_id  = cc.id
        WHERE c.item_id = ?
        ORDER BY c.claimed_at DESC LIMIT 1
    ");
    $stmt->execute([(int)$_GET['item_id']]);
}

$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    echo "<p style='text-align:center;padding:40px'>Claim record not found.</p>";
    exit;
}

// Prepare display values
$dateReported  = $data['date_reported']    ? date('F d, Y', strtotime($data['date_reported'])) : '';
$finderName    = trim(($data['finder_fname'] ?? '') . ' ' . ($data['finder_lname'] ?? ''));
$finderName    = $finderName ?: ($data['turned_in_by'] ?? '');
$finderProgram = trim(($data['finder_course'] ?? '') . ($data['finder_year'] ? ', Year ' . $data['finder_year'] : '') . ($data['finder_section'] ? ' - ' . $data['finder_section'] : ''));
$placeFound    = trim(($data['found_location'] ?? '') ?: ($data['location_found'] ?? ''));
$dateTimeFound = $data['date_found']       ? date('F d, Y – h:i A', strtotime($data['date_found'])) : '';
$itemDesc      = $data['item_name']        ?? '';
$content       = $data['description']      ?? '';
$dateClaimed   = $data['item_claimed_at']  ? date('F d, Y', strtotime($data['item_claimed_at'])) : ($data['claimed_at'] ? date('F d, Y', strtotime($data['claimed_at'])) : '');
$ownerName     = trim(($data['claimer_fname'] ?? '') . ' ' . ($data['claimer_lname'] ?? ''));
$ownerProgram  = trim(($data['claimer_course'] ?? '') . ($data['claimer_year'] ? ', Year ' . $data['claimer_year'] : '') . ($data['claimer_section'] ? ' - ' . $data['claimer_section'] : ''));
$formCode      = 'F-SAS-SDS-005';
$formRev       = 'Rev 2';
$formDate      = '07/01/24';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lost & Found Slip – <?= htmlspecialchars($itemDesc) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', 'Segoe UI', sans-serif;
      background: #e2e8f0;
      padding: 20px;
    }

    .toolbar {
      max-width: 820px; margin: 0 auto 16px;
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; background: #0f172a;
      border-radius: 12px; color: #fff;
    }
    .toolbar h3 { font-size: .9rem; font-weight: 700; flex: 1; }
    .toolbar .btn {
      padding: 8px 20px; border-radius: 8px; font-weight: 700;
      font-size: .82rem; border: none; cursor: pointer; transition: all .2s;
    }
    .btn-print { background: #3b82f6; color: #fff; }
    .btn-print:hover { background: #2563eb; }
    .btn-download { background: #10b981; color: #fff; }
    .btn-download:hover { background: #059669; }
    .btn-back { background: #334155; color: #fff; text-decoration: none; }
    .btn-back:hover { background: #475569; }

    /* ── The Slip ─────────────────────────────────────── */
    .slip-page {
      max-width: 1000px; margin: 0 auto;
      background: #fff; border: 2px solid #1e293b;
      padding: 0; min-height: none;
    }

    .slip-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 24px 8px;
      border-bottom: 2px solid #1e293b;
    }
    .slip-header .left { display: flex; align-items: center; gap: 12px; }
    .slip-header .logo { height: 56px; width: auto; }
    .slip-header .org-text { font-size: .65rem; line-height: 1.4; color: #374151; }
    .slip-header .org-text .rep { font-style: italic; font-size: .6rem; color: #6b7280; }
    .slip-header .org-text .bisu { font-weight: 800; font-size: .78rem; color: #111; }
    .slip-header .org-text .motto { font-style: italic; font-size: .58rem; color: #6b7280; }
    .slip-header .right { display: flex; align-items: center; gap: 10px; }
    .slip-header .cert-logos img { height: 36px; }

    .slip-title {
      text-align: center; padding: 10px 24px;
      font-weight: 800; font-size: 1rem;
      text-transform: uppercase; letter-spacing: 0.04em;
      border-bottom: 2px solid #1e293b;
    }

    .slip-body { padding: 12px 24px 4px; }

    .field-row {
      display: flex; align-items: baseline;
      margin-bottom: 10px; gap: 8px;
    }
    .field-label {
      font-weight: 700; font-size: .8rem;
      white-space: nowrap; color: #111; min-width: 160px;
    }
    .field-value {
      flex: 1; border-bottom: 1.5px solid #333;
      font-size: .83rem; color: #1e293b; padding-bottom: 1px;
      min-height: 16px; font-weight: 500;
    }

    .section-divider {
      border: none; border-top: 1px dashed #94a3b8;
      margin: 12px 0;
    }

    .signatures {
      display: flex; gap: 32px; margin-top: 20px; padding-top: 4px;
    }
    .sig-block {
      flex: 1; text-align: center;
    }
    .sig-line {
      border-bottom: 1.5px solid #333;
      margin: 10px 12px 2px;
    }
    .sig-name-display {
      font-size: .85rem;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: -5px;
    }
    .sig-label { font-size: .72rem; font-weight: 600; color: #6b7280; }
    .sig-title { font-size: .65rem; color: #94a3b8; font-style: italic; }

    .slip-footer {
      border-top: 2px solid #1e293b;
      padding: 6px 28px;
      font-size: .6rem; color: #94a3b8;
      display: flex; justify-content: space-between;
    }

    /* ── Print styles ─────────────────────────────────── */
    @media print {
      @page { 
        margin: 0; 
        size: A4 landscape; 
      }
      html, body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      /* Highly aggressive header/footer removal */
      div.toolbar, header, footer, nav, aside { display: none !important; }
      
      .slip-page {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        padding: 40px !important; /* Visual padding for the print */
        overflow: visible !important;
      }
    }

    @media (max-width: 600px) {
      .slip-header { flex-direction: column; gap: 8px; text-align: center; }
      .field-row { flex-direction: column; gap: 2px; }
      .field-label { min-width: 0; }
      .signatures { flex-direction: column; gap: 16px; }
    }
  </style>
</head>
<body>

<!-- Toolbar (hidden on print) -->
<div class="toolbar">
  <h3><i>📋</i> Lost & Found Slip</h3>
  <a href="javascript:history.back()" class="btn btn-back">← Back</a>
  <button class="btn btn-download" onclick="downloadDOC()">📥 Download DOC</button>
  <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
</div>





<!-- The Actual Slip -->
<div class="slip-page">

  <!-- Header with logos -->
  <div class="slip-header">
    <div class="left">
      <img src="uploads/BISU-LOGO.png" class="logo" alt="BISU Logo" onerror="this.style.display='none'">
      <div class="org-text">
        <div class="rep">Republic of the Philippines</div>
        <div class="bisu">BOHOL ISLAND STATE UNIVERSITY</div>
        <div>Cogtong, Candijay, Bohol, 6312 Philippines</div>
        <div>Office of the</div>
        <div class="motto">Balance | Integrity | Stewardship | Uprightness</div>
      </div>
    </div>
    <div class="right">
      <!-- Certification logos placeholder -->
    </div>
  </div>

  <!-- Title -->
  <div class="slip-title">Lost and Found Slip</div>

  <!-- Body -->
  <div class="slip-body">

    <!-- Finder / Report Info -->
    <div class="field-row">
      <span class="field-label">Date Reported:</span>
      <span class="field-value"><?= htmlspecialchars($dateReported) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Name of Finder:</span>
      <span class="field-value"><?= htmlspecialchars($finderName) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Program, Yr, Section:</span>
      <span class="field-value"><?= htmlspecialchars($finderProgram) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Place Found:</span>
      <span class="field-value"><?= htmlspecialchars($placeFound) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Date & Time Found:</span>
      <span class="field-value"><?= htmlspecialchars($dateTimeFound) ?></span>
    </div>

    <hr class="section-divider">

    <!-- Item Info -->
    <div class="field-row">
      <span class="field-label">Item Description:</span>
      <span class="field-value"><?= htmlspecialchars($itemDesc) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Content:</span>
      <span class="field-value"><?= htmlspecialchars($content) ?></span>
    </div>

    <hr class="section-divider">

    <!-- Claim Info -->
    <div class="field-row">
      <span class="field-label">Date Claimed:</span>
      <span class="field-value"><?= htmlspecialchars($dateClaimed) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Owner/Recipient Name:</span>
      <span class="field-value"><?= htmlspecialchars($ownerName) ?></span>
    </div>
    <div class="field-row">
      <span class="field-label">Program, Yr, Section:</span>
      <span class="field-value"><?= htmlspecialchars($ownerProgram) ?></span>
    </div>

    <!-- Signatures -->
    <div class="signatures">
      <div class="sig-block">
        <div class="sig-name-display"><?= htmlspecialchars($ownerName) ?></div>
        <div class="sig-line"></div>
        <div class="sig-label">Received by:</div>
      </div>
      <div class="sig-block">
        <div class="sig-name-display">GOATMARK</div>
        <div class="sig-line"></div>
        <div class="sig-label">Approved by:</div>
        <div class="sig-title">Director, Student Affairs and Services</div>
      </div>
    </div>

    <div class="signatures" style="margin-top:16px">
      <div class="sig-block">
        <div class="sig-name-display"><?= htmlspecialchars($ownerName) ?></div>
        <div class="sig-line"></div>
        <div class="sig-label">Claimant</div>
      </div>
      <div class="sig-block"></div>
    </div>

  </div>


</div>

<script>
function downloadDOC() {
    // Grab the slip HTML content
    var slipEl = document.querySelector('.slip-page');
    var slipHTML = slipEl.outerHTML;

    // Build a Word-compatible HTML document
    var docContent = '<!DOCTYPE html>' +
        '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' +
        'xmlns:w="urn:schemas-microsoft-com:office:word" ' +
        'xmlns="http://www.w3.org/TR/REC-html40">' +
        '<head>' +
        '<meta charset="utf-8">' +
        '<!--[if gte mso 9]>' +
        '<xml><w:WordDocument><w:View>Print</w:View>' +
        '<w:Zoom>100</w:Zoom>' +
        '<w:DoNotOptimizeForBrowser/>' +
        '</w:WordDocument></xml>' +
        '<![endif]-->' +
        '<style>' +
        '@page { size: A4 landscape; margin: 1cm; }' +
        'body { font-family: "Segoe UI", Arial, sans-serif; font-size: 11pt; }' +
        'table { border-collapse: collapse; width: 100%; }' +
        'td, th { padding: 4px 8px; vertical-align: top; }' +
        '.field-label { font-weight: bold; width: 180px; }' +
        '.sig-line { border-bottom: 1px solid #000; width: 200px; margin: 30px auto 4px; }' +
        '.sig-label { text-align: center; font-size: 9pt; color: #555; }' +
        '.sig-name { text-align: center; font-weight: bold; font-size: 11pt; }' +
        'h2 { text-align: center; margin: 10px 0; }' +
        '.header-text { text-align: center; }' +
        'hr { border: none; border-top: 2px solid #000; margin: 8px 0; }' +
        '.dashed-hr { border: none; border-top: 1px dashed #999; margin: 12px 0; }' +
        '</style>' +
        '</head><body>' +
        '<div class="header-text">' +
        '<p style="font-size:8pt;font-style:italic;color:#666;margin:0">Republic of the Philippines</p>' +
        '<p style="font-size:14pt;font-weight:bold;margin:2px 0">BOHOL ISLAND STATE UNIVERSITY</p>' +
        '<p style="font-size:9pt;color:#333;margin:0">Cogtong, Candijay, Bohol, 6312 Philippines</p>' +
        '<p style="font-size:9pt;color:#333;margin:0">Office of the</p>' +
        '<p style="font-size:8pt;font-style:italic;color:#888;margin:0 0 8px">Balance | Integrity | Stewardship | Uprightness</p>' +
        '</div>' +
        '<hr>' +
        '<h2>LOST AND FOUND SLIP</h2>' +
        '<table>' +
        '<tr><td class="field-label">Date Reported:</td><td>' + <?= json_encode($dateReported) ?> + '</td></tr>' +
        '<tr><td class="field-label">Name of Finder:</td><td>' + <?= json_encode($finderName) ?> + '</td></tr>' +
        '<tr><td class="field-label">Program, Yr, Section:</td><td>' + <?= json_encode($finderProgram) ?> + '</td></tr>' +
        '<tr><td class="field-label">Place Found:</td><td>' + <?= json_encode($placeFound) ?> + '</td></tr>' +
        '<tr><td class="field-label">Date & Time Found:</td><td>' + <?= json_encode($dateTimeFound) ?> + '</td></tr>' +
        '</table>' +
        '<div class="dashed-hr"></div>' +
        '<table>' +
        '<tr><td class="field-label">Item Description:</td><td>' + <?= json_encode($itemDesc) ?> + '</td></tr>' +
        '<tr><td class="field-label">Content:</td><td>' + <?= json_encode(str_replace("\n", "<br>", $content)) ?> + '</td></tr>' +
        '</table>' +
        '<div class="dashed-hr"></div>' +
        '<table>' +
        '<tr><td class="field-label">Date Claimed:</td><td>' + <?= json_encode($dateClaimed) ?> + '</td></tr>' +
        '<tr><td class="field-label">Owner/Recipient Name:</td><td>' + <?= json_encode($ownerName) ?> + '</td></tr>' +
        '<tr><td class="field-label">Program, Yr, Section:</td><td>' + <?= json_encode($ownerProgram) ?> + '</td></tr>' +
        '</table>' +
        '<br><br>' +
        '<table style="width:100%"><tr>' +
        '<td style="width:50%;text-align:center">' +
        '<p class="sig-name">' + <?= json_encode(strtoupper($ownerName)) ?> + '</p>' +
        '<div class="sig-line"></div>' +
        '<p class="sig-label">Received by:</p>' +
        '</td>' +
        '<td style="width:50%;text-align:center">' +
        '<p class="sig-name">GOATMARK</p>' +
        '<div class="sig-line"></div>' +
        '<p class="sig-label">Approved by:</p>' +
        '<p style="font-size:8pt;font-style:italic;color:#888;text-align:center">Director, Student Affairs and Services</p>' +
        '</td>' +
        '</tr></table>' +
        '<br>' +
        '<table><tr><td style="width:220px;text-align:center">' +
        '<p class="sig-name">' + <?= json_encode(strtoupper($ownerName)) ?> + '</p>' +
        '<div class="sig-line"></div>' +
        '<p class="sig-label">Claimant</p>' +
        '</td></tr></table>' +
        '</body></html>';

    var safeName = <?= json_encode($itemDesc) ?>.replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_') || 'Item';
    var fileName = 'Claim_Slip_' + safeName + '.doc';

    var blob = new Blob(['\ufeff' + docContent], { type: 'application/msword' });
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = fileName;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    setTimeout(function() {
        window.URL.revokeObjectURL(link.href);
        document.body.removeChild(link);
    }, 100);
}
</script>
</body>
</html>
