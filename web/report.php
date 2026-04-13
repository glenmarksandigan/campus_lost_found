<?php
// Must be first — before ANY output or whitespace
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item | FoundIt!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc; color: #1e293b;
        }
        .report-container { max-width: 800px; margin: 60px auto; }
        .card {
            border: none; border-radius: 24px; background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02);
        }
        .form-control, .form-select {
            border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 16px; transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
        }
        .form-control.is-invalid { border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,0.1); }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a4ab2 100%);
            border: none; border-radius: 12px; padding: 16px;
            font-weight: 700; transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(13,110,253,0.3); }

        /* Icon box header */
        .icon-box {
            width: 50px; height: 50px; background: rgba(16,185,129,0.1); color: #10b981;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; margin-bottom: 20px;
        }

        /* User banner */
        .user-banner {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0; border-radius: 14px;
            padding: 14px 18px; margin-bottom: 24px;
            display: flex; align-items: center; gap: 12px;
        }
        .user-banner .avatar {
            width: 42px; height: 42px; background: #10b981; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 1rem; flex-shrink: 0;
        }

        /* Category grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 10px; margin-bottom: 8px;
        }
        .cat-card {
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 8px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            background: #fff; font-size: 0.8rem; font-weight: 600; color: #475569;
        }
        .cat-card:hover { border-color: #10b981; background: #ecfdf5; color: #10b981; }
        .cat-card.active { border-color: #10b981; background: #10b981; color: #fff; }
        .cat-card.invalid { border-color: #ef4444; background: #fef2f2; }
        .cat-card i { display: block; font-size: 1.5rem; margin-bottom: 4px; }

        /* Dynamic fields panel */
        .dynamic-fields {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 14px; padding: 18px; margin-top: 12px;
            display: none; animation: fadeIn 0.3s ease;
        }
        .dynamic-fields.show { display: block; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

        .combo-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
        .error-msg { color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: none; }
        .error-msg.show { display: block; }

        /* Storage location cards */
        .storage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 10px; margin-bottom: 8px;
        }
        .storage-card {
            border: 2px solid #e2e8f0; border-radius: 12px; padding: 14px 10px;
            text-align: center; cursor: pointer; transition: all 0.2s;
            background: #fff; font-size: 0.82rem; font-weight: 600; color: #475569;
        }
        .storage-card:hover { border-color: #0d6efd; background: #eff6ff; color: #0d6efd; }
        .storage-card.active { border-color: #0d6efd; background: #0d6efd; color: #fff; }
        .storage-card.invalid { border-color: #ef4444; background: #fef2f2; }
        .storage-card i { display: block; font-size: 1.6rem; margin-bottom: 6px; }

        /* Section label */
        .section-label {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: #94a3b8;
            display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

        /* Progress Stepper */
        .form-stepper {
            display: flex; align-items: center; justify-content: center; gap: 0;
            margin-bottom: 28px; padding: 0 20px;
        }
        .step { display: flex; align-items: center; gap: 0; }
        .step-circle {
            width: 32px; height: 32px; border-radius: 50%; border: 2px solid #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; color: #94a3b8;
            transition: all 0.3s;
        }
        .step-circle.active { border-color: #10b981; background: #10b981; color: white; }
        .step-circle.done { border-color: #10b981; background: #10b981; color: white; }
        .step-label { font-size: .7rem; font-weight: 600; color: #94a3b8; margin-left: 6px; margin-right: 16px; transition: color .3s; }
        .step-label.active { color: #10b981; }
        .step-line { width: 40px; height: 2px; background: #e2e8f0; transition: background .3s; }
        .step-line.active { background: #10b981; }

        /* Drag-and-drop upload zone */
        .upload-zone {
            border: 2px dashed #e2e8f0; border-radius: 16px; padding: 28px 20px;
            text-align: center; cursor: pointer; transition: all 0.3s;
            background: #f8fafc; position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: #10b981; background: #ecfdf5;
        }
        .upload-zone .upload-icon { font-size: 2.2rem; color: #94a3b8; margin-bottom: 8px; transition: color .3s; }
        .upload-zone:hover .upload-icon { color: #10b981; }
        .upload-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
        }
        .upload-preview {
            display: none; margin-top: 12px; border-radius: 10px; overflow: hidden;
            border: 2px solid #e2e8f0; max-width: 200px; margin-left: auto; margin-right: auto;
        }
        .upload-preview img { width: 100%; height: 150px; object-fit: cover; }
        .upload-preview.show { display: block; }
    </style>
</head>
<body>

<?php


$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT fname, mname, lname, contact_number, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>

<?php include 'header.php'; ?>

<?php if (isset($_GET['status'])): ?>
<div id="statusAlert" class="alert <?= $_GET['status'] == 'success' ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius:15px">
    <div class="d-flex align-items-center">
        <i class="bi <?= $_GET['status'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-4 me-3"></i>
        <div>
            <strong><?= $_GET['status'] == 'success' ? 'Submitted!' : 'Something went wrong.' ?></strong><br>
           
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<script>setTimeout(() => { const a = document.getElementById('statusAlert'); if(a) new bootstrap.Alert(a).close(); }, 5000);</script>
<?php endif; ?>

<div class="container report-container">
    <div class="card p-4 p-md-5">

        <div class="icon-box"><i class="bi bi-megaphone fs-3"></i></div>
        <h2 class="fw-bold mb-2">Report a Found Item</h2>
        <p class="text-secondary mb-4">Fill in the details below to help return this item to its owner.</p>

        <!-- Progress Stepper -->
        <div class="form-stepper" id="formStepper">
            <div class="step"><div class="step-circle active" id="step1">1</div><span class="step-label active">Item</span></div>
            <div class="step-line" id="line1"></div>
            <div class="step"><div class="step-circle" id="step2">2</div><span class="step-label">Location</span></div>
            <div class="step-line" id="line2"></div>
            <div class="step"><div class="step-circle" id="step3">3</div><span class="step-label">Details</span></div>
        </div>

        <?php if ($user): ?>
        <div class="user-banner">
            <div class="avatar"><?= strtoupper(substr($user['fname'],0,1) . substr($user['lname'],0,1)) ?></div>
            <div>
                <div class="fw-bold" style="font-size:.95rem">
                    <?= htmlspecialchars(trim($user['fname'].' '.($user['mname'] ? $user['mname'].' ' : '').$user['lname'])) ?>
                </div>
                <div style="font-size:.8rem;color:#475569">
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($user['contact_number'] ?? 'No contact') ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                </div>
            </div>
            <span class="ms-auto badge bg-success" style="font-size:.72rem">Submitting as you</span>
        </div>
        <?php else: ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You're not logged in. <a href="auth.php">Login</a> so we can link this report to your account.
        </div>
        <?php endif; ?>

        <form action="upload_logic.php" method="POST" enctype="multipart/form-data" id="foundItemForm" novalidate>

            <!-- Hidden reporter fields -->
            <input type="hidden" name="reporter_name" value="<?= htmlspecialchars(trim(
                ($user['fname'] ?? '') . ' ' .
                (!empty($user['mname']) ? $user['mname'] . ' ' : '') .
                ($user['lname'] ?? '')
            )) ?>">
            <input type="hidden" name="reporter_contact" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>">
            <input type="hidden" name="reporter_email"   value="<?= htmlspecialchars($user['email'] ?? '') ?>">

            <!-- ── SECTION 1: Item Info ── -->
            <div class="section-label">Item Information</div>

            <!-- Item Name -->
            <div class="mb-4">
                <label class="form-label fw-bold">Item Name <span class="text-danger">*</span></label>
                <input type="text" name="item_name" id="item_name" class="form-control" placeholder="e.g., Blue Wallet, Samsung Phone, Umbrella">
                <div class="error-msg" id="err_item_name"><i class="bi bi-exclamation-circle me-1"></i>Please enter the item name.</div>
            </div>

            <!-- Category -->
            <div class="mb-4">
                <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                <input type="hidden" name="category" id="categoryInput">
                <div class="category-grid" id="categoryGrid">
                    <div class="cat-card" onclick="selectCategory('Phone', this)"><i class="bi bi-phone"></i>Phone</div>
                    <div class="cat-card" onclick="selectCategory('Wallet', this)"><i class="bi bi-wallet2"></i>Wallet</div>
                    <div class="cat-card" onclick="selectCategory('ID / Card', this)"><i class="bi bi-credit-card"></i>ID / Card</div>
                    <div class="cat-card" onclick="selectCategory('Keys', this)"><i class="bi bi-key"></i>Keys</div>
                    <div class="cat-card" onclick="selectCategory('Bag', this)"><i class="bi bi-bag"></i>Bag</div>
                    <div class="cat-card" onclick="selectCategory('Laptop / Tablet', this)"><i class="bi bi-laptop"></i>Laptop</div>
                    <div class="cat-card" onclick="selectCategory('Clothing', this)"><i class="bi bi-person-bounding-box"></i>Clothing</div>
                    <div class="cat-card" onclick="selectCategory('Book / Notes', this)"><i class="bi bi-book"></i>Book</div>
                    <div class="cat-card" onclick="selectCategory('Umbrella', this)"><i class="bi bi-umbrella"></i>Umbrella</div>
                    <div class="cat-card" onclick="selectCategory('Other', this)"><i class="bi bi-three-dots"></i>Other</div>
                </div>
                <div class="error-msg" id="err_category"><i class="bi bi-exclamation-circle me-1"></i>Please select a category.</div>

                <!-- Dynamic category fields -->
                <div id="dynamicFields" class="dynamic-fields">
                    <p class="fw-bold mb-3 text-success" id="dynamicTitle"></p>
                    <div id="dynamicInputs" class="row g-3"></div>
                </div>
            </div>

            <!-- ── SECTION 2: Location ── -->
            <div class="section-label">Where &amp; When Found</div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Place Found <span class="text-danger">*</span></label>
                    <input type="text" name="found_location" id="found_location" class="form-control" placeholder="e.g., Library, Hallway, Parking Lot, Canteen">
                    <div class="error-msg" id="err_location"><i class="bi bi-exclamation-circle me-1"></i>Please enter the location where you found the item.</div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Date & Time Found <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                        <input type="datetime-local" name="date_found" id="date_found" class="form-control border-start-0">
                    </div>
                    <div class="error-msg" id="err_date"><i class="bi bi-exclamation-circle me-1"></i>Please select the date and time.</div>
                </div>
            </div>

            <!-- ── SECTION 3: Storage ── -->
            <div class="section-label">Where is it now?</div>

            <div class="mb-4">
                <label class="form-label fw-bold">Current Storage Location <span class="text-danger">*</span></label>
                <input type="hidden" name="storage_location" id="storageInput">
                <div class="storage-grid" id="storageGrid">
                    <div class="storage-card" onclick="selectStorage('SSG Office', this)">
                        <i class="bi bi-building"></i>SSG Office
                    </div>
                    <div class="storage-card" onclick="selectStorage('Guard House', this)">
                        <i class="bi bi-shield-check"></i>Guard House
                    </div>
                    <div class="storage-card" onclick="selectStorage('Finder\'s Possession', this)">
                        <i class="bi bi-person-fill"></i>With Me
                    </div>
                </div>
                <div class="error-msg" id="err_storage"><i class="bi bi-exclamation-circle me-1"></i>Please select where the item is stored.</div>
            </div>

            <!-- ── SECTION 4: Description & Photo ── -->
            <div class="section-label">Additional Details</div>

            <div class="mb-4">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"
                    placeholder="Any unique marks, color, contents inside, condition of the item..."></textarea>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted"><span id="charCount">0</span>/500</small>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Photo of the Item <span class="text-danger">*</span></label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="item_image" id="imageInput" accept="image/*" required>
                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                    <div class="fw-bold" style="color:#64748b; font-size:.9rem">Drop image here or click to upload</div>
                    <div style="color:#94a3b8; font-size:.78rem; margin-top:4px">PNG, JPG up to 5MB</div>
                    <div class="upload-preview" id="uploadPreview"><img id="previewImg" src="" alt="Preview"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fs-5">
                <i class="bi bi-send-fill me-2"></i>Submit Found Report
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Max date = now
document.getElementById('date_found').setAttribute('max', new Date().toISOString().slice(0, 16));

// Char count
document.getElementById('description').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

// ── Storage location picker ──────────────────────────────────────────────────
function selectStorage(val, el) {
    document.querySelectorAll('.storage-card').forEach(c => c.classList.remove('active', 'invalid'));
    el.classList.add('active');
    document.getElementById('storageInput').value = val;
    document.getElementById('err_storage').classList.remove('show');
}

// ── Category fields (same as lost report) ────────────────────────────────────
const categoryFields = {
    'Phone': [
        { name: 'brand',  label: 'Brand',    type: 'combo',  options: ['Samsung','iPhone','Oppo','Vivo','Xiaomi','Realme','Huawei','Nokia'] },
        { name: 'model',  label: 'Model',    type: 'text',   placeholder: 'e.g. Samsung A15, iPhone 13' },
        { name: 'color',  label: 'Color',    type: 'combo',  options: ['Black','White','Blue','Red','Green','Gold','Silver','Pink','Gray'] },
        { name: 'case',   label: 'Has Case?',type: 'select', options: ['No Case','Yes - Clear Case','Yes - Colored Case','Yes - Rugged Case'] },
    ],
    'Wallet': [
        { name: 'color',    label: 'Color',    type: 'combo',  options: ['Black','Brown','White','Red','Blue','Pink','Gray'] },
        { name: 'material', label: 'Material', type: 'select', options: ['Leather','Fabric','Synthetic/Plastic','Other'] },
        { name: 'contents', label: 'Contents inside', type: 'text', placeholder: 'e.g. IDs, cash, cards' },
    ],
    'ID / Card': [
        { name: 'id_type', label: 'Type of ID', type: 'combo', options: ['School ID','PhilSys ID','Driver\'s License','Passport','ATM Card','Other'] },
        { name: 'id_name', label: 'Name on ID',  type: 'text', placeholder: 'Name printed on the ID' },
    ],
    'Keys': [
        { name: 'key_type', label: 'Type',                   type: 'select', options: ['House Key','Motorcycle Key','Car Key','Padlock Key','Other'] },
        { name: 'keychain', label: 'Keychain / Attached item', type: 'text', placeholder: 'e.g. Blue lanyard, rubber duck keychain' },
    ],
    'Bag': [
        { name: 'brand',    label: 'Brand',    type: 'combo',  options: ['JanSport','Nike','Adidas','Converse','No Brand','Other'] },
        { name: 'color',    label: 'Color',    type: 'combo',  options: ['Black','White','Blue','Red','Green','Gray','Brown'] },
        { name: 'type',     label: 'Bag Type', type: 'select', options: ['Backpack','Sling Bag','Tote Bag','Handbag','Drawstring Bag'] },
        { name: 'contents', label: 'Contents inside', type: 'text', placeholder: 'e.g. Laptop, notebooks, charger' },
    ],
    'Laptop / Tablet': [
        { name: 'brand',  label: 'Brand',                      type: 'combo',  options: ['Acer','Asus','Lenovo','Dell','HP','Apple','Samsung','Huawei'] },
        { name: 'model',  label: 'Model',                      type: 'text',   placeholder: 'e.g. Acer Aspire 5' },
        { name: 'color',  label: 'Color',                      type: 'combo',  options: ['Black','Silver','White','Gray','Blue'] },
        { name: 'serial', label: 'Serial Number (if visible)', type: 'text',   placeholder: 'Optional but very helpful' },
    ],
    'Clothing': [
        { name: 'type',  label: 'Type',        type: 'combo',  options: ['Jacket','Hoodie','Shirt','Uniform','PE Shirt','Pants','Shorts','Cap','Shoes'] },
        { name: 'color', label: 'Color',        type: 'combo',  options: ['Black','White','Blue','Red','Green','Gray','Yellow','Brown'] },
        { name: 'size',  label: 'Size',         type: 'select', options: ['XS','S','M','L','XL','XXL','Free Size'] },
        { name: 'label', label: 'Brand/Label',  type: 'text',   placeholder: 'e.g. BISU PE Shirt, H&M' },
    ],
    'Book / Notes': [
        { name: 'title',       label: 'Title / Subject',          type: 'text',  placeholder: 'e.g. Calculus by Stewart, Math Notes' },
        { name: 'cover_color', label: 'Cover Color',              type: 'combo', options: ['Black','White','Blue','Red','Green','Yellow','Brown'] },
        { name: 'markings',    label: 'Markings / name written',  type: 'text',  placeholder: 'e.g. Name written on cover' },
    ],
    'Umbrella': [
        { name: 'color', label: 'Color', type: 'combo',  options: ['Black','Blue','Red','Green','Yellow','Pink','Multicolor'] },
        { name: 'type',  label: 'Type',  type: 'select', options: ['Foldable','Long Handle','Kids Umbrella'] },
    ],
    'Other': [
        { name: 'item_type', label: 'What kind of item?',  type: 'text', placeholder: 'Describe the item type' },
        { name: 'color',     label: 'Color',               type: 'combo', options: ['Black','White','Blue','Red','Green','Yellow','Brown','Gray'] },
        { name: 'size',      label: 'Size / Dimensions',   type: 'text', placeholder: 'e.g. Small, about the size of a hand' },
    ]
};

let listCounter = 0;
function selectCategory(cat, el) {
    document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active', 'invalid'));
    el.classList.add('active');
    document.getElementById('categoryInput').value = cat;
    document.getElementById('err_category').classList.remove('show');

    const container = document.getElementById('dynamicFields');
    const inputs    = document.getElementById('dynamicInputs');
    const title     = document.getElementById('dynamicTitle');
    const fields    = categoryFields[cat] || [];

    title.textContent = `📋 Details for: ${cat}`;
    inputs.innerHTML  = '';

    fields.forEach(f => {
        const col       = document.createElement('div');
        col.className   = 'col-md-6';
        const fieldName = `extra_${f.name}`;
        let inputHtml   = '';

        if (f.type === 'combo') {
            const listId = `list_${listCounter++}`;
            inputHtml = `
                <input type="text" name="${fieldName}" class="form-control"
                       placeholder="${f.options[0]}" list="${listId}">
                <datalist id="${listId}">
                    ${f.options.map(o => `<option value="${o}">`).join('')}
                </datalist>
                <div class="combo-hint"><i class="bi bi-lightbulb me-1"></i>Type or pick from suggestions</div>`;
        } else if (f.type === 'select') {
            inputHtml = `
                <select name="${fieldName}" class="form-select">
                    <option value="">-- Select --</option>
                    ${f.options.map(o => `<option value="${o}">${o}</option>`).join('')}
                </select>`;
        } else {
            inputHtml = `<input type="text" name="${fieldName}" class="form-control" placeholder="${f.placeholder || ''}">`;
        }

        col.innerHTML = `<label class="form-label fw-bold small">${f.label}</label>${inputHtml}`;
        inputs.appendChild(col);
    });

    container.classList.add('show');
}

// Max date = now (Local time)
const nowFound = new Date();
const localISOFound = new Date(nowFound.getTime() - (nowFound.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
if (document.getElementById('date_found')) {
    document.getElementById('date_found').setAttribute('max', localISOFound);
}

// ── Form Validation ───────────────────────────────────────────────────────────
document.getElementById('foundItemForm').addEventListener('submit', function(e) {
    let valid = true;

    // Item name
    const itemName = document.getElementById('item_name');
    const errName  = document.getElementById('err_item_name');
    if (!itemName.value.trim()) {
        itemName.classList.add('is-invalid'); errName.classList.add('show'); valid = false;
    } else {
        itemName.classList.remove('is-invalid'); errName.classList.remove('show');
    }

    // Category
    const category    = document.getElementById('categoryInput');
    const errCategory = document.getElementById('err_category');
    if (!category.value.trim()) {
        document.querySelectorAll('.cat-card').forEach(c => c.classList.add('invalid'));
        errCategory.classList.add('show'); valid = false;
    } else {
        document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('invalid'));
        errCategory.classList.remove('show');
    }

    // Location
    const loc    = document.getElementById('found_location');
    const errLoc = document.getElementById('err_location');
    if (!loc.value.trim()) {
        loc.classList.add('is-invalid'); errLoc.classList.add('show'); valid = false;
    } else {
        loc.classList.remove('is-invalid'); errLoc.classList.remove('show');
    }

    // Date
    const dateFound = document.getElementById('date_found');
    const errDate   = document.getElementById('err_date');
    if (!dateFound.value.trim()) {
        dateFound.classList.add('is-invalid'); errDate.classList.add('show'); valid = false;
    } else {
        dateFound.classList.remove('is-invalid'); errDate.classList.remove('show');
    }

    // Storage
    const storage    = document.getElementById('storageInput');
    const errStorage = document.getElementById('err_storage');
    if (!storage.value.trim()) {
        document.querySelectorAll('.storage-card').forEach(c => c.classList.add('invalid'));
        errStorage.classList.add('show'); valid = false;
    } else {
        document.querySelectorAll('.storage-card').forEach(c => c.classList.remove('invalid'));
        errStorage.classList.remove('show');
    }

    if (!valid) {
        e.preventDefault();
        document.querySelector('.is-invalid, .invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Clear errors on input
document.getElementById('item_name').addEventListener('input', function() {
    this.classList.remove('is-invalid');
    document.getElementById('err_item_name').classList.remove('show');
});
document.getElementById('date_found').addEventListener('change', function() {
    this.classList.remove('is-invalid');
    document.getElementById('err_date').classList.remove('show');
});
document.getElementById('found_location').addEventListener('change', function() {
    this.classList.remove('is-invalid');
    document.getElementById('err_location').classList.remove('show');
});

// ── Drag-and-drop + preview ──────────────────────────────────────────────────
const zone = document.getElementById('uploadZone');
const imageInput = document.getElementById('imageInput');
const preview = document.getElementById('uploadPreview');
const previewImg = document.getElementById('previewImg');

['dragover','dragenter'].forEach(evt => {
    zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.add('dragover'); });
});
['dragleave','drop'].forEach(evt => {
    zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.remove('dragover'); });
});
zone.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
        imageInput.files = e.dataTransfer.files;
        showPreview(e.dataTransfer.files[0]);
    }
});
imageInput.addEventListener('change', function() {
    if (this.files[0]) showPreview(this.files[0]);
});
function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        preview.classList.add('show');
    };
    reader.readAsDataURL(file);
    updateStepper();
}

// ── Progress Stepper Logic ───────────────────────────────────────────────────
function updateStepper() {
    const hasItem = document.getElementById('item_name').value.trim() && document.getElementById('categoryInput').value;
    const hasLoc = document.getElementById('found_location').value.trim() && document.getElementById('date_found').value && document.getElementById('storageInput').value;
    const hasDetails = document.getElementById('imageInput').files.length > 0;

    const s1 = document.getElementById('step1'), l1 = document.getElementById('line1');
    const s2 = document.getElementById('step2'), l2 = document.getElementById('line2');
    const s3 = document.getElementById('step3');

    s1.className = 'step-circle ' + (hasItem ? 'done' : 'active');
    s1.previousElementSibling ? null : null;
    s1.nextElementSibling.className = 'step-label ' + (hasItem ? 'active' : 'active');
    l1.className = 'step-line ' + (hasItem ? 'active' : '');
    s2.className = 'step-circle ' + (hasLoc ? 'done' : (hasItem ? 'active' : ''));
    s2.nextElementSibling.className = 'step-label ' + (hasItem ? 'active' : '');
    l2.className = 'step-line ' + (hasLoc ? 'active' : '');
    s3.className = 'step-circle ' + (hasDetails ? 'done' : (hasLoc ? 'active' : ''));
    s3.nextElementSibling.className = 'step-label ' + (hasLoc ? 'active' : '');
}

// Listen on all relevant fields to update stepper
['item_name','found_location','date_found'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updateStepper);
    if (el) el.addEventListener('change', updateStepper);
});
</script>
</body>
</html>