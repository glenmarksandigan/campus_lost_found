@extends('layouts.portal')

@section('title', 'Report Found Item — FoundIt!')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    :root {
        --bisu-blue: #003366;
        --bisu-yellow: #ffcc00;
        --success-emerald: #10b981;
    }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f8fafc; color: #1e293b;
    }
    .report-container { max-width: 800px; margin: 40px auto; }
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
    .step-circle.active, .step-circle.done { border-color: #10b981; background: #10b981; color: white; }
    .step-label { font-size: .7rem; font-weight: 600; color: #94a3b8; margin-left: 6px; margin-right: 16px; }
    .step-label.active { color: #10b981; }
    .step-line { width: 40px; height: 2px; background: #e2e8f0; }
    .step-line.active { background: #10b981; }

    /* Upload zone */
    .upload-zone {
        border: 2px dashed #e2e8f0; border-radius: 16px; padding: 28px 20px;
        text-align: center; cursor: pointer; transition: all 0.3s;
        background: #f8fafc; position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover { border-color: #10b981; background: #ecfdf5; }
    .upload-preview { display: none; margin-top: 12px; border-radius: 10px; border: 2px solid #e2e8f0; max-width: 200px; margin: 12px auto 0; }
    .upload-preview img { width: 100%; height: 150px; object-fit: cover; }
    .upload-preview.show { display: block; }

    .btn-submit {
        background: linear-gradient(135deg, #0d6efd 0%, #0a4ab2 100%);
        border: none; border-radius: 12px; padding: 16px;
        font-weight: 700; color: #fff; transition: all 0.3s;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(13,110,253,0.3); color: #fff; }
</style>
@endpush

@section('content')
<div class="container report-container">
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="card p-4 p-md-5">
        <div class="icon-box"><i class="bi bi-megaphone fs-3"></i></div>
        <h2 class="fw-bold mb-2">Report a Found Item</h2>
        <p class="text-secondary mb-4">Fill in the details below to help return this item to its owner.</p>

        <!-- Progress Stepper -->
        <div class="form-stepper" id="formStepper">
            <div class="step"><div class="step-circle active" id="step1">1</div><span class="step-label active">Item</span></div>
            <div class="step-line" id="line1"></div>
            <div class="step"><div class="step-circle" id="step2">2</div><span class="step-label" id="label2">Location</span></div>
            <div class="step-line" id="line2"></div>
            <div class="step"><div class="step-circle" id="step3">3</div><span class="step-label" id="label3">Details</span></div>
        </div>

        <!-- User Banner -->
        <div class="user-banner">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->fname, 0, 1) . substr(auth()->user()->lname, 0, 1)) }}</div>
            <div>
                <div class="fw-bold" style="font-size:.95rem">
                    {{ auth()->user()->fname }} {{ auth()->user()->mname ? auth()->user()->mname.' ' : '' }}{{ auth()->user()->lname }}
                </div>
                <div style="font-size:.8rem; color:#475569">
                    <i class="bi bi-telephone me-1"></i>{{ auth()->user()->contact_number ?? 'No contact' }}
                    &nbsp;·&nbsp;
                    <i class="bi bi-envelope me-1"></i>{{ auth()->user()->email }}
                </div>
            </div>
            <span class="ms-auto badge bg-success" style="font-size:.72rem">Submitting as you</span>
        </div>

        @if($errors->any())
        <div class="alert alert-danger border-0 rounded-3 mb-4">
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" id="foundItemForm" novalidate>
            @csrf

            <!-- SECTION 1: Item Info -->
            <div class="section-label">Item Information</div>

            <!-- Item Name -->
            <div class="mb-4">
                <label class="form-label fw-bold small">Item Name <span class="text-danger">*</span></label>
                <input type="text" name="item_name" id="item_name" class="form-control" value="{{ old('item_name') }}" placeholder="e.g., Blue Wallet, Samsung Phone, Umbrella" required>
            </div>

            <!-- Category Picker -->
            <div class="mb-4">
                <label class="form-label fw-bold small">Category <span class="text-danger">*</span></label>
                <input type="hidden" name="category" id="categoryInput" value="{{ old('category') }}">
                <div class="category-grid" id="categoryGrid">
                    <div class="cat-card" data-category="Phone"><i class="bi bi-phone"></i>Phone</div>
                    <div class="cat-card" data-category="Wallet"><i class="bi bi-wallet2"></i>Wallet</div>
                    <div class="cat-card" data-category="ID / Card"><i class="bi bi-credit-card"></i>ID / Card</div>
                    <div class="cat-card" data-category="Keys"><i class="bi bi-key"></i>Keys</div>
                    <div class="cat-card" data-category="Bag"><i class="bi bi-bag"></i>Bag</div>
                    <div class="cat-card" data-category="Laptop"><i class="bi bi-laptop"></i>Laptop</div>
                    <div class="cat-card" data-category="Clothing"><i class="bi bi-person-bounding-box"></i>Clothing</div>
                    <div class="cat-card" data-category="Book"><i class="bi bi-book"></i>Book</div>
                    <div class="cat-card" data-category="Umbrella"><i class="bi bi-umbrella"></i>Umbrella</div>
                    <div class="cat-card" data-category="Other"><i class="bi bi-three-dots"></i>Other</div>
                </div>

                <!-- Dynamic fields -->
                <div id="dynamicFields" class="dynamic-fields">
                    <p class="fw-bold mb-3 text-success small" id="dynamicTitle"></p>
                    <div id="dynamicInputs" class="row g-3"></div>
                </div>
            </div>

            <!-- SECTION 2: Location -->
            <div class="section-label">Where & When Found</div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold small">Place Found <span class="text-danger">*</span></label>
                    <input type="text" name="found_location" id="found_location" class="form-control" value="{{ old('found_location') }}" placeholder="e.g., Library, Hallway, etc." required>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold small">Date & Time Found <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="found_date" id="found_date" class="form-control" value="{{ old('found_date') }}" required>
                </div>
            </div>

            <!-- SECTION 3: Storage -->
            <div class="section-label">Where is it now?</div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Current Storage Location <span class="text-danger">*</span></label>
                <input type="hidden" name="storage_location" id="storageInput" value="{{ old('storage_location') }}">
                <div class="storage-grid" id="storageGrid">
                    <div class="storage-card" data-location="SSG Office"><i class="bi bi-building"></i>SSG Office</div>
                    <div class="storage-card" data-location="Guard House"><i class="bi bi-shield-check"></i>Guard House</div>
                    <div class="storage-card" data-location="With Me"><i class="bi bi-person-fill"></i>With Me</div>
                </div>
            </div>

            <!-- SECTION 4: Details & Photo -->
            <div class="section-label">Additional Details</div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Any unique marks, color, condition...">{{ old('description') }}</textarea>
                <div class="text-end mt-1"><small class="text-muted"><span id="charCount">0</span>/500</small></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Photo of the Item <span class="text-danger">*</span></label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up display-6 text-muted"></i></div>
                    <div class="fw-bold text-muted small mt-2">Drop image here or click to upload</div>
                    <div class="text-muted" style="font-size: .75rem">PNG, JPG up to 5MB</div>
                    <div class="upload-preview" id="uploadPreview"><img id="previewImg" src=""></div>
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100 fs-5 mt-3">
                <i class="bi bi-send-fill me-2"></i>Submit Found Report
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const categoryFields = {
        'Phone': [
            { name: 'brand', label: 'Brand', options: ['Samsung','iPhone','Oppo','Vivo','Xiaomi','Realme','Huawei','Nokia'] },
            { name: 'model', label: 'Model', placeholder: 'e.g. Samsung A15, iPhone 13' },
            { name: 'color', label: 'Color', options: ['Black','White','Blue','Red','Green','Gold','Silver','Pink','Gray'] }
        ],
        'Wallet': [
            { name: 'color', label: 'Color', options: ['Black','Brown','White','Red','Blue','Pink','Gray'] },
            { name: 'material', label: 'Material', options: ['Leather','Fabric','Synthetic/Plastic','Other'] }
        ],
        'ID / Card': [
            { name: 'id_type', label: 'Type of ID', options: ['School ID','PhilSys ID','Driver\'s License','Passport','ATM Card','Other'] },
            { name: 'id_name', label: 'Name on ID', placeholder: 'Name printed on the ID' }
        ],
        // ... more categories can be added here
        'Other': [
            { name: 'item_type', label: 'What kind of item?', placeholder: 'Describe the item type' },
            { name: 'color', label: 'Color', options: ['Black','White','Blue','Red','Green','Yellow','Brown','Gray'] }
        ]
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Character count
        const desc = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        desc.addEventListener('input', () => {
            charCount.textContent = desc.value.length;
            updateStepper();
        });

        // Category Selection
        const categoryInput = document.getElementById('categoryInput');
        const catCards = document.querySelectorAll('.cat-card');
        const dynamicFields = document.getElementById('dynamicFields');
        const dynamicInputs = document.getElementById('dynamicInputs');
        const dynamicTitle = document.getElementById('dynamicTitle');

        catCards.forEach(card => {
            card.addEventListener('click', () => {
                const cat = card.dataset.category;
                categoryInput.value = cat;
                catCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                
                // Show dynamic fields
                const fields = categoryFields[cat] || [];
                if (fields.length > 0) {
                    dynamicTitle.textContent = `📋 Details for: ${cat}`;
                    dynamicInputs.innerHTML = '';
                    fields.forEach(f => {
                        const col = document.createElement('div');
                        col.className = 'col-md-6';
                        let inputHtml = '';
                        if (f.options) {
                            inputHtml = `<select name="extra_${f.name}" class="form-select form-select-sm">
                                <option value="">-- Select --</option>
                                ${f.options.map(o => `<option value="${o}">${o}</option>`).join('')}
                            </select>`;
                        } else {
                            inputHtml = `<input type="text" name="extra_${f.name}" class="form-control form-control-sm" placeholder="${f.placeholder || ''}">`;
                        }
                        col.innerHTML = `<label class="form-label fw-bold small mb-1">${f.label}</label>${inputHtml}`;
                        dynamicInputs.appendChild(col);
                    });
                    dynamicFields.classList.add('show');
                } else {
                    dynamicFields.classList.remove('show');
                }
                updateStepper();
            });

            // Handle old value
            if (categoryInput.value === card.dataset.category) card.click();
        });

        // Storage Selection
        const storageInput = document.getElementById('storageInput');
        const storageCards = document.querySelectorAll('.storage-card');
        storageCards.forEach(card => {
            card.addEventListener('click', () => {
                storageInput.value = card.dataset.location;
                storageCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                updateStepper();
            });
            if (storageInput.value === card.dataset.location) card.click();
        });

        // Upload Zone
        const zone = document.getElementById('uploadZone');
        const input = document.getElementById('imageInput');
        const preview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('previewImg');

        zone.addEventListener('click', () => input.click());
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', () => { if (input.files[0]) handleFile(input.files[0]); });

        function handleFile(file) {
            const reader = new FileReader();
            reader.onload = e => { previewImg.src = e.target.result; preview.classList.add('show'); };
            reader.readAsDataURL(file);
            updateStepper();
        }

        // Stepper Logic
        function updateStepper() {
            const hasItem = document.getElementById('item_name').value.trim() && categoryInput.value;
            const hasLoc = document.getElementById('found_location').value.trim() && document.getElementById('found_date').value && storageInput.value;
            const hasDetails = input.files.length > 0;

            const s1 = document.getElementById('step1'), l1 = document.getElementById('line1');
            const s2 = document.getElementById('step2'), l2 = document.getElementById('line2');
            const s3 = document.getElementById('step3');

            s1.className = 'step-circle ' + (hasItem ? 'done' : 'active');
            l1.className = 'step-line ' + (hasItem ? 'active' : '');
            
            s2.className = 'step-circle ' + (hasLoc ? 'done' : (hasItem ? 'active' : ''));
            l2.className = 'step-line ' + (hasLoc ? 'active' : '');
            document.getElementById('label2').className = 'step-label ' + (hasItem ? 'active' : '');

            s3.className = 'step-circle ' + (hasDetails ? 'done' : (hasLoc ? 'active' : ''));
            document.getElementById('label3').className = 'step-label ' + (hasLoc ? 'active' : '');
        }

        // Add listeners for stepper
        ['item_name','found_location','found_date'].forEach(id => {
            document.getElementById(id).addEventListener('input', updateStepper);
            document.getElementById(id).addEventListener('change', updateStepper);
        });

        // Set max date for found_date
        const dt = new Date();
        dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
        document.getElementById('found_date').setAttribute('max', dt.toISOString().slice(0, 16));
    });
</script>
@endpush

