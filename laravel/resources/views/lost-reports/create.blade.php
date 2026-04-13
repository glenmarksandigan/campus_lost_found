@extends('layouts.portal')

@section('title', 'Report Lost Item — FoundIt!')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    :root {
        --bisu-blue: #003366;
        --lost-orange: #f59e0b;
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
        border-color: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245,158,11,0.1);
    }

    /* Icon box header */
    .icon-box {
        width: 50px; height: 50px; background: rgba(245,158,11,0.1); color: #f59e0b;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px; margin-bottom: 20px;
    }

    /* User banner */
    .user-banner {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border: 1px solid #fde68a; border-radius: 14px;
        padding: 14px 18px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 12px;
    }
    .user-banner .avatar {
        width: 42px; height: 42px; background: #f59e0b; border-radius: 50%;
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
    .cat-card:hover { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
    .cat-card.active { border-color: #f59e0b; background: #f59e0b; color: #fff; }
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
    .step-circle.active, .step-circle.done { border-color: #f59e0b; background: #f59e0b; color: white; }
    .step-label { font-size: .7rem; font-weight: 600; color: #94a3b8; margin-left: 6px; margin-right: 16px; }
    .step-label.active { color: #f59e0b; }
    .step-line { width: 40px; height: 2px; background: #e2e8f0; }
    .step-line.active { background: #f59e0b; }

    /* Upload zone */
    .upload-zone {
        border: 2px dashed #e2e8f0; border-radius: 16px; padding: 28px 20px;
        text-align: center; cursor: pointer; transition: all 0.3s;
        background: #f8fafc; position: relative;
    }
    .upload-zone:hover, .upload-zone.dragover { border-color: #f59e0b; background: #fffbeb; }
    .upload-preview { display: none; margin-top: 12px; border-radius: 10px; border: 2px solid #e2e8f0; max-width: 200px; margin: 12px auto 0; }
    .upload-preview img { width: 100%; height: 150px; object-fit: cover; }
    .upload-preview.show { display: block; }

    .btn-submit {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: none; border-radius: 12px; padding: 16px;
        font-weight: 700; color: #fff; transition: all 0.3s;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(245,158,11,0.3); color: #fff; }
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
        <div class="icon-box"><i class="bi bi-search fs-3"></i></div>
        <h2 class="fw-bold mb-2">Report a Lost Item</h2>
        <p class="text-secondary mb-4">Provide details about your lost item to help others find it.</p>

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
            <span class="ms-auto badge bg-warning text-dark" style="font-size:.72rem">Submitting as you</span>
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

        <form action="{{ route('lost-reports.store') }}" method="POST" enctype="multipart/form-data" id="lostItemForm" novalidate>
            @csrf

            <!-- Hidden user fields -->
            <input type="hidden" name="owner_name" value="{{ auth()->user()->fname }} {{ auth()->user()->mname ? auth()->user()->mname.' ' : '' }}{{ auth()->user()->lname }}">
            <input type="hidden" name="owner_contact" value="{{ auth()->user()->contact_number }}">

            <!-- SECTION 1: Item Info -->
            <div class="section-label">Item Information</div>

            <!-- Item Name -->
            <div class="mb-4">
                <label class="form-label fw-bold small">What did you lose? <span class="text-danger">*</span></label>
                <input type="text" name="item_name" id="item_name" class="form-control" value="{{ old('item_name') }}" placeholder="e.g., Blue Hydroflask, Samsung Phone" required>
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
                    <p class="fw-bold mb-3 text-warning small" id="dynamicTitle"></p>
                    <div id="dynamicInputs" class="row g-3"></div>
                </div>
            </div>

            <!-- SECTION 2: Location -->
            <div class="section-label">Where & When Lost</div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold small">Place Lost <span class="text-danger">*</span></label>
                    <input type="text" name="last_seen_location" id="last_seen_location" class="form-control" value="{{ old('last_seen_location') }}" placeholder="e.g., Computer Lab, Library" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold small">Date & Time Lost <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="date_lost" id="date_lost" class="form-control" value="{{ old('date_lost') }}">
                </div>
            </div>

            <!-- SECTION 3: Details & Photo -->
            <div class="section-label">Additional Details</div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Any stickers, scratches, contents inside...">{{ old('description') }}</textarea>
                <div class="text-end mt-1"><small class="text-muted"><span id="charCount">0</span>/500</small></div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small">Photo of the Item (Optional)</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="image_path" id="imageInput" accept="image/*" class="d-none">
                    <div class="upload-icon"><i class="bi bi-cloud-arrow-up display-6 text-muted"></i></div>
                    <div class="fw-bold text-muted small mt-2">Drop image here or click to upload</div>
                    <div class="text-muted" style="font-size: .75rem">PNG, JPG up to 5MB</div>
                    <div class="upload-preview" id="uploadPreview"><img id="previewImg" src=""></div>
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100 fs-5 mt-3">
                <i class="bi bi-send-fill me-2"></i>Submit Lost Report
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
            { name: 'color', label: 'Color', options: ['Black','White','Blue','Red','Green','Gold','Silver','Pink','Gray'] },
            { name: 'case', label: 'Has Case?', options: ['No Case','Yes - Clear Case','Yes - Colored Case','Yes - Rugged Case'] }
        ],
        'Wallet': [
            { name: 'color', label: 'Color', options: ['Black', 'Brown', 'White', 'Red', 'Blue', 'Pink', 'Gray'] },
            { name: 'material', label: 'Material', options: ['Leather', 'Fabric', 'Synthetic/Plastic', 'Other'] },
            { name: 'contents', label: 'Contents inside', placeholder: 'e.g. IDs, cash, cards' }
        ],
        'ID / Card': [
            { name: 'id_type', label: 'Type of ID', options: ['School ID', 'PhilSys ID', 'Driver\'s License', 'Passport', 'ATM Card', 'Other'] },
            { name: 'id_name', label: 'Name on ID', placeholder: 'Name printed on the ID' }
        ],
        'Keys': [
            { name: 'key_type', label: 'Type', options: ['House Key', 'Motorcycle Key', 'Car Key', 'Padlock Key', 'Other'] },
            { name: 'keychain', label: 'Keychain/Attached item', placeholder: 'e.g. Blue lanyard, rubber duck keychain' }
        ],
        'Bag': [
            { name: 'brand', label: 'Brand', options: ['JanSport', 'Nike', 'Adidas', 'Converse', 'No Brand', 'Other'] },
            { name: 'color', label: 'Color', options: ['Black', 'White', 'Blue', 'Red', 'Green', 'Gray', 'Brown'] },
            { name: 'type', label: 'Bag Type', options: ['Backpack', 'Sling Bag', 'Tote Bag', 'Handbag', 'Drawstring Bag'] },
            { name: 'contents', label: 'Contents inside', placeholder: 'e.g. Laptop, notebooks, charger' }
        ],
        'Laptop': [
            { name: 'brand', label: 'Brand', options: ['Acer', 'Asus', 'Lenovo', 'Dell', 'HP', 'Apple', 'Samsung', 'Huawei'] },
            { name: 'model', label: 'Model', placeholder: 'e.g. Acer Aspire 5' },
            { name: 'color', label: 'Color', options: ['Black', 'Silver', 'White', 'Gray', 'Blue'] },
            { name: 'serial', label: 'Serial Number', placeholder: 'Optional but helpful' }
        ],
        'Clothing': [
            { name: 'type', label: 'Type', options: ['Jacket', 'Hoodie', 'Shirt', 'Uniform', 'PE Shirt', 'Pants', 'Shorts', 'Cap', 'Shoes'] },
            { name: 'color', label: 'Color', options: ['Black', 'White', 'Blue', 'Red', 'Green', 'Gray', 'Yellow', 'Brown'] },
            { name: 'size', label: 'Size', options: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size'] },
            { name: 'label', label: 'Brand/Label', placeholder: 'e.g. BISU PE Shirt, H&M' }
        ],
        'Book': [
            { name: 'title', label: 'Title / Subject', placeholder: 'e.g. Calculus by Stewart, Math Notes' },
            { name: 'cover_color', label: 'Cover Color', options: ['Black', 'White', 'Blue', 'Red', 'Green', 'Yellow', 'Brown'] },
            { name: 'markings', label: 'Any markings / name written', placeholder: 'e.g. Name written on cover' }
        ],
        'Umbrella': [
            { name: 'color', label: 'Color', options: ['Black', 'Blue', 'Red', 'Green', 'Yellow', 'Pink', 'Multicolor'] },
            { name: 'type', label: 'Type', options: ['Foldable', 'Long Handle', 'Kids Umbrella'] }
        ],
        'Other': [
            { name: 'item_type', label: 'What kind of item?', placeholder: 'Describe the item type' },
            { name: 'color', label: 'Color', options: ['Black', 'White', 'Blue', 'Red', 'Green', 'Yellow', 'Brown', 'Gray'] },
            { name: 'size', label: 'Size / Dimensions', placeholder: 'e.g. Small, about the size of a hand' }
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

            if (categoryInput.value === card.dataset.category) card.click();
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
            const hasLoc = document.getElementById('last_seen_location').value.trim() && document.getElementById('date_lost').value;
            const hasDetails = desc.value.trim().length > 0;

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

        ['item_name','last_seen_location','date_lost'].forEach(id => {
            document.getElementById(id).addEventListener('input', updateStepper);
            document.getElementById(id).addEventListener('change', updateStepper);
        });

        // Set max date for date_lost
        const dt = new Date();
        dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
        document.getElementById('date_lost').setAttribute('max', dt.toISOString().slice(0, 16));
    });
</script>
@endpush
