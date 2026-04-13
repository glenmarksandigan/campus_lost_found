@extends('layouts.portal')

@section('title', 'FoundIt! — Browse Items')

@push('styles')
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }

    /* ── Hero with floating orbs ── */
    .hero-banner {
        background: linear-gradient(135deg, #003366 0%, #0d6efd 50%, #0a4ab2 100%);
        padding: 80px 0 120px 0; color: white; border-radius: 0 0 40px 40px;
        position: relative; overflow: hidden;
    }
    .hero-banner::before {
        content: ''; position: absolute; top: -60px; right: -60px;
        width: 300px; height: 300px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,204,0,0.15), transparent 70%);
        animation: floatOrb 8s ease-in-out infinite;
    }
    .hero-banner::after {
        content: ''; position: absolute; bottom: -40px; left: 10%;
        width: 200px; height: 200px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.08), transparent 70%);
        animation: floatOrb 6s ease-in-out infinite reverse;
    }
    @keyframes floatOrb {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-20px) scale(1.05); }
    }
    .hero-banner .container { position: relative; z-index: 2; }
    .hero-banner h1 { animation: heroFadeUp 0.6s ease both; }
    .hero-banner p { animation: heroFadeUp 0.6s 0.1s ease both; }
    .hero-banner .d-flex { animation: heroFadeUp 0.6s 0.2s ease both; }
    @keyframes heroFadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .search-wrapper {
        max-width: 700px; margin: -40px auto 0 auto;
        position: relative; z-index: 100; padding: 0 15px;
    }
    .search-box {
        background: white; border-radius: 20px; padding: 10px;
        display: flex; align-items: center;
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.05);
        transition: box-shadow 0.3s;
    }
    .search-box:focus-within { box-shadow: 0 15px 40px rgba(13,110,253,0.15); }
    .search-box i { font-size: 1.5rem; color: #0d6efd; padding: 0 15px; }
    .search-box input { border: none; outline: none; width: 100%; padding: 12px 5px; font-size: 1.1rem; font-weight: 500; }

    /* ── Category Filter Pills ── */
    .category-filters {
        display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;
        margin-bottom: 24px; animation: heroFadeUp 0.6s 0.3s ease both;
    }
    .cat-pill {
        padding: 7px 16px; border-radius: 100px; font-size: .82rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; background: white; color: #64748b;
        cursor: pointer; transition: all 0.25s; display: inline-flex; align-items: center; gap: 5px;
    }
    .cat-pill:hover { border-color: #0d6efd; color: #0d6efd; background: #eff6ff; }
    .cat-pill.active { background: #0d6efd; color: white; border-color: #0d6efd; box-shadow: 0 3px 12px rgba(13,110,253,0.25); }
    .cat-pill i { font-size: .9rem; }

    .nav-pills .nav-link { color: #64748b !important; border-radius: 12px; padding: 12px 25px; transition: all 0.3s ease; }
    .nav-pills .nav-link.active { background-color: #0d6efd !important; box-shadow: 0 4px 12px rgba(13,110,253,0.2); }

    /* ── Glassmorphism Cards ── */
    .item-card {
        border: 1px solid rgba(255,255,255,0.2); border-radius: 18px;
        transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(4px);
        opacity: 0; transform: translateY(20px);
        animation: cardReveal 0.5s ease forwards;
    }
    .item-card:hover {
        transform: translateY(-10px) scale(1.01);
        box-shadow: 0 24px 48px -12px rgba(13,110,253,0.12), 0 0 0 1px rgba(13,110,253,0.08);
    }
    @keyframes cardReveal {
        to { opacity: 1; transform: translateY(0); }
    }
    .item-card img { height: 220px; object-fit: cover; transition: transform 0.4s; }
    .item-card:hover img { transform: scale(1.04); }
    .status-badge { position: absolute; top: 15px; right: 15px; z-index: 10; font-weight: 600; padding: 6px 12px; border-radius: 8px; }
    .storage-box { background-color: #f1f5f9; border-left: 4px solid #0d6efd; padding: 10px; border-radius: 6px; }
    .hidden-item { display: none; }

    /* ── Show More Button ── */
    .show-more-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 28px; border-radius: 100px; font-weight: 700;
        font-size: .9rem; background: white; color: #0d6efd;
        border: 2px solid #0d6efd; cursor: pointer; transition: all 0.3s;
    }
    .show-more-btn:hover { background: #0d6efd; color: white; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13,110,253,0.2); }
    .show-more-btn i { transition: transform 0.3s; }
    .show-more-btn:hover i { transform: translateY(2px); }

    /* Toast */
    .toast-notify {
        position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
        z-index: 9999; min-width: 340px; border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        animation: slideUp 0.4s ease; padding: 16px 20px;
        display: flex; align-items: center; gap: 14px;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateX(-50%) translateY(30px); }
        to   { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
    .toast-notify.success { background: #ecfdf5; border: 1.5px solid #6ee7b7; color: #065f46; }
    .toast-notify.warning { background: #fffbeb; border: 1.5px solid #fcd34d; color: #92400e; }
    .toast-notify .toast-icon { font-size: 1.6rem; flex-shrink: 0; }
    .toast-notify .toast-close { margin-left: auto; background: none; border: none; font-size: 1.1rem; cursor: pointer; opacity: 0.5; flex-shrink: 0; }
    .toast-notify .toast-close:hover { opacity: 1; }

    /* Finder / Contact Modal Shared */
    .finder-avatar-sm {
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #0d6efd, #0a4ab2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; color: white; font-weight: 700; flex-shrink: 0;
    }
    .finder-info-row {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 0; border-bottom: 1px solid #f1f5f9;
    }
    .finder-info-row:last-child { border-bottom: none; }
    .finder-info-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; flex-shrink: 0;
    }
    .finder-info-label { font-size: .68rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .finder-info-value { font-weight: 600; color: #1e293b; font-size: .88rem; word-break: break-word; }
    .finder-msg-box {
        background: #f8fafc; border-left: 3px solid #0d6efd;
        border-radius: 0 8px 8px 0; padding: 12px 14px;
        font-size: .85rem; color: #475569; line-height: 1.6;
    }
    .proof-thumb {
        width: 100%; border-radius: 10px; border: 2px solid #e2e8f0;
        object-fit: cover; max-height: 180px; cursor: pointer;
    }
    .modal-divider {
        display: flex; align-items: center; gap: 8px;
        color: #94a3b8; font-size: .7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px; margin: 16px 0 12px;
    }
    .modal-divider::before, .modal-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }

    .access-denied-panel { text-align: center; padding: 30px 20px; }
    .access-denied-panel .lock-icon {
        width: 64px; height: 64px; border-radius: 50%;
        background: rgba(220,38,38,0.1);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px; font-size: 1.8rem; color: #dc2626;
    }

    /* ── Claim Slip Modal ── */
    .cs-section { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:14px 16px; margin-bottom:14px; }
    .cs-section-title { font-weight:700; font-size:.78rem; color:#334155; text-transform:uppercase; letter-spacing:.04em; border-bottom:1.5px solid #e2e8f0; padding-bottom:8px; margin-bottom:10px; }
    .cs-field { display:flex; align-items:baseline; gap:8px; margin-bottom:8px; padding-bottom:6px; border-bottom:1px dashed #e2e8f0; }
    .cs-field:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
    .cs-label { font-size:.73rem; font-weight:700; color:#64748b; min-width:130px; white-space:nowrap; }
    .cs-val { font-size:.83rem; color:#1e293b; font-weight:500; flex:1; word-break:break-word; }
    .cs-input { border:none; border-bottom:1.5px solid #94a3b8; border-radius:0; padding:2px 4px; font-size:.83rem; font-weight:600; color:#1e293b; background:transparent; width:100%; outline:none; transition:border-color .2s; }
    .cs-input:focus { border-color:#0d6efd; }
    .cs-input::placeholder { color:#94a3b8; font-weight:400; }
</style>
@endpush

@section('content')

{{-- ── TOASTS ── --}}
@if(session('claim_success'))
<div class="toast-notify success" id="claimToast">
    <i class="bi bi-check-circle-fill toast-icon"></i>
    <div><div class="fw-bold">Claim Submitted!</div><div style="font-size:.85rem">The admin will review your proof and contact you shortly.</div></div>
    <button class="toast-close" onclick="this.closest('.toast-notify').remove()">&times;</button>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('claimToast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);} },5000);</script>
@endif

@if(session('claim_duplicate'))
<div class="toast-notify warning" id="claimToast">
    <i class="bi bi-clock-history toast-icon"></i>
    <div><div class="fw-bold">Already Submitted</div><div style="font-size:.85rem">You've already claimed this item. Please wait for the admin to contact you.</div></div>
    <button class="toast-close" onclick="this.closest('.toast-notify').remove()">&times;</button>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('claimToast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);} },5000);</script>
@endif

@if(session('contact_success'))
<div class="toast-notify success" id="foundToast">
    <i class="bi bi-check-circle-fill toast-icon"></i>
    <div><div class="fw-bold">Message Sent!</div><div style="font-size:.85rem">The owner has been notified. They will contact you soon.</div></div>
    <button class="toast-close" onclick="this.closest('.toast-notify').remove()">&times;</button>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('foundToast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);} },5000);</script>
@endif

{{-- ── HERO ── --}}
<div class="hero-banner text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">Lost something?</h1>
        <p class="lead opacity-75 mb-4">Browse our campus gallery to reconnect with your belongings.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('items.create') }}" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow-sm">
                <i class="bi bi-plus-circle me-2"></i>I Found Something
            </a>
            <a href="{{ route('lost-reports.create') }}" class="btn btn-outline-light btn-lg px-4 fw-bold">
                <i class="bi bi-search me-2"></i>I Lost Something
            </a>
        </div>
    </div>
</div>

<div class="search-wrapper">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="gallerySearch" placeholder="Search items in both tabs..." autocomplete="off">
    </div>
</div>

<div class="container" id="gallery" style="margin-top: 60px; padding-bottom: 80px;">

    <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold me-2" id="pills-found-tab" data-bs-toggle="pill" data-bs-target="#pills-found" type="button" role="tab">
                <i class="bi bi-box-seam me-2"></i>Found Items
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="pills-lost-tab" data-bs-toggle="pill" data-bs-target="#pills-lost" type="button" role="tab">
                <i class="bi bi-megaphone me-2"></i>Lost Reports
            </button>
        </li>
    </ul>

    <!-- Category filter pills -->
    <div class="category-filters" id="categoryFilters">
        <span class="cat-pill active" data-cat="all"><i class="bi bi-grid-3x3-gap"></i> All</span>
        <span class="cat-pill" data-cat="phone"><i class="bi bi-phone"></i> Phone</span>
        <span class="cat-pill" data-cat="wallet"><i class="bi bi-wallet2"></i> Wallet</span>
        <span class="cat-pill" data-cat="id"><i class="bi bi-credit-card"></i> ID / Card</span>
        <span class="cat-pill" data-cat="keys"><i class="bi bi-key"></i> Keys</span>
        <span class="cat-pill" data-cat="bag"><i class="bi bi-bag"></i> Bag</span>
        <span class="cat-pill" data-cat="laptop"><i class="bi bi-laptop"></i> Laptop</span>
        <span class="cat-pill" data-cat="clothing"><i class="bi bi-person-bounding-box"></i> Clothing</span>
        <span class="cat-pill" data-cat="book"><i class="bi bi-book"></i> Book</span>
        <span class="cat-pill" data-cat="umbrella"><i class="bi bi-umbrella"></i> Umbrella</span>
    </div>

    <div id="noResults" class="text-center py-5 d-none">
        <i class="bi bi-emoji-frown display-1 text-muted"></i>
        <h4 class="mt-3 text-secondary">No matching items found.</h4>
    </div>

    <div class="tab-content" id="pills-tabContent">

        {{-- ════════════════════════════════════════
             FOUND ITEMS TAB
             ════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="pills-found" role="tabpanel">
            <div class="row">
            @forelse($foundItems as $item)
                @php
                    $isClaiming         = ($item->status === 'Claiming');
                    $isUploader         = $item->is_uploader;
                    $inFinderPossession = (trim($item->storage_location ?? '') === "Finder's Possession");

                    $myStatus        = optional($item->my_claim)->status;
                    $isVerifiedOwner = ($myStatus === 'verified');
                    $isOtherVerified = ($item->verified_count > 0 && !$isVerifiedOwner);
                    $hasClaimed      = $item->has_my_claim;

                    $badgeText  = $isClaiming ? 'Under Interview' : 'Available';
                    $badgeClass = $isClaiming ? 'bg-danger' : 'bg-success';

                    if ($isVerifiedOwner) {
                        $badgeText = 'Verified / Ready for Pickup';
                        $badgeClass = 'bg-success';
                    } elseif ($isOtherVerified) {
                        $badgeText = 'Owner Found';
                        $badgeClass = 'bg-secondary';
                    } elseif ($hasClaimed && $myStatus === 'pending') {
                        $badgeText = 'Claim Pending';
                        $badgeClass = 'bg-warning text-dark';
                    }
                @endphp
                <div class="col-lg-4 col-md-6 mb-4 item-card-wrapper" data-name="{{ strtolower($item->item_name) }}">
                    <div class="card h-100 item-card shadow-sm border-0">
                        <div class="status-badge {{ $badgeClass }} {{ $badgeText === 'Claim Pending' ? 'text-dark' : 'text-white' }}">
                            {{ $badgeText }}
                        </div>
                        <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : 'https://placehold.co/400x300?text=No+Image' }}" class="card-img-top" alt="{{ $item->item_name }}">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-bold mb-1">{{ $item->item_name }}</h5>
                            <div class="text-primary small fw-bold mb-3">
                                <i class="bi bi-geo-alt-fill"></i> {{ $item->found_location }}
                            </div>
                            <p class="small text-secondary flex-grow-1">
                                {{ Str::limit($item->description, 90) }}
                            </p>

                            @if($isClaiming && !empty($item->claimer_names))
                            <div class="mb-2 small text-danger">
                                <i class="bi bi-person-fill-check me-1"></i>
                                Claimed by: {{ $item->claimer_names }}
                            </div>
                            @endif

                            <!-- Storage box -->
                            <div class="storage-box mb-3 small"
                                 style="{{ $inFinderPossession ? 'border-left-color:#f59e0b; background:#fffbeb;' : '' }}">
                                @if($inFinderPossession)
                                    <i class="bi bi-person-fill text-warning me-1"></i>
                                    Storage: <b class="text-warning">Finder's Possession</b>
                                    <div class="text-muted mt-1" style="font-size:.75rem;">
                                        Contact the finder who turned this in to arrange pickup.
                                    </div>
                                @else
                                    Storage: <b>{{ $item->storage_location }}</b>
                                @endif
                            </div>

                            {{-- Action buttons --}}
                            @if($isUploader)
                                <button class="btn btn-outline-secondary btn-sm w-100 mb-2" disabled>
                                    <i class="bi bi-person-check me-1"></i>Your Report
                                </button>

                            @elseif($isVerifiedOwner)
                                <div class="alert alert-success p-2 mb-2 text-center rounded-3" style="font-size: .8rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> You are verified!
                                    <div class="fw-bold mt-1">Ready for pickup at {{ $item->storage_location }}</div>
                                </div>
                                <a href="{{ route('claim-slip', $item->id) }}" target="_blank"
                                   class="btn btn-warning btn-sm w-100 fw-bold mb-2 shadow-sm"
                                   style="background: linear-gradient(135deg, #ffc107, #ff9800); border: none; color: #021d3a;">
                                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download Claim Slip
                                </a>

                            @elseif($isOtherVerified)
                                <button class="btn btn-secondary btn-sm w-100" disabled>
                                    <i class="bi bi-lock-fill me-1"></i> Item belongs to another owner
                                </button>

                            @elseif($hasClaimed)
                                <div class="btn-group w-100">
                                    <button class="btn btn-outline-warning btn-sm fw-bold w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#claimModal{{ $item->id }}">
                                        <i class="bi bi-clock-history me-1"></i> Claim Pending
                                    </button>
                                    @if($inFinderPossession)
                                    <button class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#contactFinderModal{{ $item->id }}">
                                        <i class="bi bi-chat"></i>
                                    </button>
                                    @endif
                                </div>

                            @elseif($isClaiming)
                                <button class="btn btn-primary btn-sm w-100 fw-bold btn-claim-slip"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->item_name }}"
                                    data-location="{{ $item->found_location }}"
                                    data-datefound="{{ $item->date_found ? \Carbon\Carbon::parse($item->date_found)->format('F d, Y – h:i A') : '' }}"
                                    data-desc="{{ $item->description }}"
                                    data-reported="{{ $item->created_at->format('F d, Y') }}">
                                    <i class="bi bi-hand-index-thumb me-1"></i>Claim This Item
                                </button>
                                @if($inFinderPossession)
                                <button class="btn btn-warning btn-sm w-100 fw-bold mt-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#contactFinderModal{{ $item->id }}">
                                    <i class="bi bi-person-lines-fill me-1"></i>Contact Finder
                                </button>
                                @endif

                            @elseif($inFinderPossession)
                                <div class="d-flex flex-column gap-2">
                                    <button class="btn btn-warning btn-sm w-100 fw-bold"
                                        data-bs-toggle="modal"
                                        data-bs-target="#contactFinderModal{{ $item->id }}">
                                        <i class="bi bi-person-lines-fill me-1"></i>Contact Finder
                                    </button>
                                    <button class="btn btn-primary btn-sm w-100 fw-bold btn-claim-slip"
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->item_name }}"
                                        data-location="{{ $item->found_location }}"
                                        data-datefound="{{ $item->date_found ? \Carbon\Carbon::parse($item->date_found)->format('F d, Y – h:i A') : '' }}"
                                        data-desc="{{ $item->description }}"
                                        data-reported="{{ $item->created_at->format('F d, Y') }}">
                                        <i class="bi bi-hand-index-thumb me-1"></i>Claim This Item
                                    </button>
                                </div>

                            @else
                                <button class="btn btn-primary btn-sm w-100 btn-claim-slip"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->item_name }}"
                                    data-location="{{ $item->found_location }}"
                                    data-datefound="{{ $item->date_found ? \Carbon\Carbon::parse($item->date_found)->format('F d, Y – h:i A') : '' }}"
                                    data-desc="{{ $item->description }}"
                                    data-reported="{{ $item->created_at->format('F d, Y') }}">
                                    <i class="bi bi-hand-index-thumb me-1"></i>Claim
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Contact Finder Modal --}}
                @if($inFinderPossession && !$isUploader && isset($item->finder_user))
                <div class="modal fade" id="contactFinderModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0" style="border-radius:20px; overflow:hidden;">
                            <div class="modal-header border-0"
                                 style="background:linear-gradient(135deg,#f59e0b,#d97706); color:white; padding:20px 24px;">
                                <div>
                                    <div class="fw-bold fs-6">
                                        <i class="bi bi-person-lines-fill me-2"></i>Contact the Finder
                                    </div>
                                    <div style="font-size:.8rem; opacity:.85;">
                                        This item is currently with the person who found it.
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div style="width:52px;height:52px;border-radius:50%;
                                                background:linear-gradient(135deg,#f59e0b,#d97706);
                                                display:flex;align-items:center;justify-content:center;
                                                font-size:1.4rem;color:white;font-weight:700;flex-shrink:0;">
                                        {{ strtoupper(substr($item->finder_user->fname, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $item->finder_user->fname }} {{ $item->finder_user->lname }}</div>
                                        <div class="text-muted small">Found and is holding this item</div>
                                    </div>
                                </div>

                                <div class="finder-info-row">
                                    <div class="finder-info-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-envelope-fill"></i></div>
                                    <div>
                                        <div class="finder-info-label">Email</div>
                                        <div class="finder-info-value">{{ $item->finder_user->email }}</div>
                                    </div>
                                </div>

                                @if($item->finder_user->contact_number)
                                <div class="finder-info-row">
                                    <div class="finder-info-icon bg-success bg-opacity-10 text-success"><i class="bi bi-telephone-fill"></i></div>
                                    <div>
                                        <div class="finder-info-label">Contact Number</div>
                                        <div class="finder-info-value">{{ $item->finder_user->contact_number }}</div>
                                    </div>
                                </div>
                                @endif

                                <div class="modal-divider"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Contact</div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('messages.show', $item->finder_user->id) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-chat-dots-fill me-1"></i>Open Full Chat
                                    </a>
                                    <a href="mailto:{{ $item->finder_user->email }}?subject=Re: Found Item - {{ urlencode($item->item_name) }}"
                                       class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-envelope me-1"></i>Send Email
                                    </a>
                                </div>

                                {{-- Quick in-modal message form --}}
                                <div class="modal-divider"><i class="bi bi-chat-dots-fill"></i> Send a Message</div>
                                <form action="{{ route('messages.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="receiver_id" value="{{ $item->finder_user->id }}">
                                    <input type="hidden" name="subject" value="Re: Found Item - {{ $item->item_name }}">
                                    <div style="display:flex; gap:8px; align-items:flex-end;">
                                        <textarea name="body" rows="2" required
                                            placeholder="Type a quick message to the finder..."
                                            style="flex:1; border:1.5px solid #e2e8f0; border-radius:12px;
                                                   padding:10px 14px; font-size:.85rem; resize:none;
                                                   font-family:inherit; outline:none; transition:border-color 0.2s;"
                                            onfocus="this.style.borderColor='#f59e0b'"
                                            onblur="this.style.borderColor='#e2e8f0'"></textarea>
                                        <button type="submit"
                                            title="Send message"
                                            style="width:44px;height:44px;border-radius:50%;
                                                   background:linear-gradient(135deg,#f59e0b,#d97706);
                                                   border:none;color:white;font-size:1rem;
                                                   display:flex;align-items:center;justify-content:center;
                                                   flex-shrink:0;cursor:pointer;transition:transform 0.2s;"
                                            onmouseover="this.style.transform='scale(1.08)'"
                                            onmouseout="this.style.transform='scale(1)'">
                                            <i class="bi bi-send-fill"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">No found items yet.</p>
                </div>
            @endforelse
            </div>
        </div><!-- /found tab -->


        {{-- ════════════════════════════════════════
             LOST REPORTS TAB
             ════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="pills-lost" role="tabpanel">
            <div class="row">
            @forelse($lostReports as $report)
                @php
                    $isMatching  = ($report->status === 'Matching');
                    $isResolved  = ($report->status === 'Resolved');
                    $isLostOwner = $report->is_owner;
                    $lostImg     = $report->image_path
                        ? asset('storage/' . $report->image_path)
                        : 'https://placehold.co/400x300/e2e8f0/64748b?text=No+Image+Provided';

                    $badgeClass = 'bg-warning text-dark';
                    $badgeText  = 'LOST';
                    if ($isMatching) { $badgeClass = 'bg-info text-white'; $badgeText = 'Match Found'; }
                    elseif ($isResolved) { $badgeClass = 'bg-success text-white'; $badgeText = 'Returned'; }

                    $fd = $report->finder_detail ?? null;
                    $hasFinderData = !empty($fd);
                @endphp

                <div class="col-lg-4 col-md-6 mb-4 item-card-wrapper" data-name="{{ strtolower($report->item_name) }}">
                    <div class="card h-100 item-card shadow-sm border-0">
                        <div class="status-badge {{ $badgeClass }}">
                            @if($isMatching)
                                <i class="bi bi-person-check-fill me-1"></i>
                            @elseif($isResolved)
                                <i class="bi bi-check-circle-fill me-1"></i>
                            @endif
                            {!! $badgeText !!}
                        </div>
                        <img src="{{ $lostImg }}" class="card-img-top" alt="Lost Item">
                        <div class="card-body d-flex flex-column">
                            <h5 class="fw-bold mb-1">{{ $report->item_name }}</h5>
                            <div class="text-danger small fw-bold mb-3">
                                <i class="bi bi-geo-alt-fill"></i> Last seen: {{ $report->last_seen_location }}
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-calendar-x"></i> Lost on: {{ \Carbon\Carbon::parse($report->date_lost)->format('M d, Y') }}
                            </div>
                            <p class="small text-secondary flex-grow-1">
                                {{ Str::limit($report->description, 90) }}
                            </p>

                            @if($isLostOwner)
                                <div class="mb-2">
                                    <span class="badge bg-warning text-dark" style="font-size:.7rem;">
                                        <i class="bi bi-person-fill me-1"></i>Your Report
                                    </span>
                                </div>

                                @if($isResolved)
                                    <div class="alert alert-success p-2 mb-0 text-center rounded-3" style="font-size:.8rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Item Returned
                                        <div class="text-muted mt-1" style="font-size:.72rem;">This report has been resolved.</div>
                                    </div>
                                @elseif($isMatching && $hasFinderData)
                                    <button class="btn btn-info btn-sm w-100 text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#finderModal{{ $report->id }}">
                                        <i class="bi bi-person-lines-fill me-1"></i>View Finder Details
                                    </button>
                                @elseif($isMatching && !$hasFinderData)
                                    <a href="{{ route('messages.index') }}" class="btn btn-success btn-sm w-100 text-white">
                                        <i class="bi bi-envelope-check-fill me-1"></i>Match Found — Check Inbox
                                    </a>
                                @else
                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                        <i class="bi bi-hourglass-split me-1"></i>Waiting for Match
                                    </button>
                                @endif

                            @elseif($isMatching || $isResolved)
                                <button class="btn btn-outline-info btn-sm w-100" disabled>
                                    <i class="bi bi-link-45deg me-1"></i>{{ $isResolved ? 'Resolved' : 'Match in Progress' }}
                                </button>

                            @else
                                <a href="{{ route('lost-reports.show', $report->id) }}" class="btn btn-dark btn-sm w-100">
                                    <i class="bi bi-hand-index-thumb me-1"></i>I Found This
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Finder Details Modal --}}
                @if($isMatching && $hasFinderData)
                <div class="modal fade" id="finderModal{{ $report->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0" style="border-radius:20px; overflow:hidden;">
                            <div class="modal-header border-0"
                                 style="background:linear-gradient(135deg,#0d6efd,#0a4ab2); color:white; padding:20px 24px;">
                                <div>
                                    <div class="fw-bold fs-5">
                                        <i class="bi bi-person-check-fill me-2"></i>Finder Details
                                    </div>
                                    <div style="font-size:.82rem; opacity:.8;">
                                        Someone reported finding: <b>{{ $report->item_name }}</b>
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                @if(!$isLostOwner)
                                    <div class="access-denied-panel">
                                        <div class="lock-icon"><i class="bi bi-shield-lock-fill"></i></div>
                                        <h5 class="fw-bold mb-2">Access Restricted</h5>
                                        <p class="text-muted mb-4">Only the owner of this lost report can view the finder's contact details.</p>
                                        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <div class="finder-avatar-sm">
                                            {{ strtoupper(substr($fd->finder_name ?? ($fd->fname ?? '?'), 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6">{{ $fd->finder_name ?? ($fd->fname . ' ' . $fd->lname) }}</div>
                                            <div class="text-success small fw-bold">
                                                <i class="bi bi-patch-check-fill me-1"></i>Reported finding your item
                                            </div>
                                            @if(isset($fd->created_at))
                                            <div class="text-muted small">
                                                <i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($fd->created_at)->format('M d, Y h:i A') }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="finder-info-row">
                                        <div class="finder-info-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-envelope-fill"></i></div>
                                        <div>
                                            <div class="finder-info-label">Email</div>
                                            <div class="finder-info-value">{{ $fd->finder_email ?? '' }}</div>
                                        </div>
                                    </div>

                                    @if(!empty($fd->finder_contact))
                                    <div class="finder-info-row">
                                        <div class="finder-info-icon bg-success bg-opacity-10 text-success"><i class="bi bi-telephone-fill"></i></div>
                                        <div>
                                            <div class="finder-info-label">Contact Number</div>
                                            <div class="finder-info-value">{{ $fd->finder_contact }}</div>
                                        </div>
                                    </div>
                                    @endif

                                    @if(!empty($fd->found_location))
                                    <div class="finder-info-row">
                                        <div class="finder-info-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-geo-alt-fill"></i></div>
                                        <div>
                                            <div class="finder-info-label">Where They Found It</div>
                                            <div class="finder-info-value">{{ $fd->found_location }}</div>
                                        </div>
                                    </div>
                                    @endif

                                    @if(!empty($fd->message))
                                    <div class="modal-divider"><i class="bi bi-chat-left-text"></i> Message</div>
                                    <div class="finder-msg-box">{!! nl2br(e($fd->message)) !!}</div>
                                    @endif

                                    @if(!empty($fd->image_path))
                                    <div class="modal-divider"><i class="bi bi-image-fill"></i> Proof Photo</div>
                                    <img src="{{ asset('storage/' . $fd->image_path) }}"
                                         class="proof-thumb" alt="Proof">
                                    <div class="text-muted small mt-1"><i class="bi bi-zoom-in me-1"></i>Click to enlarge</div>
                                    @endif

                                    <div class="modal-divider"><i class="bi bi-lightning-charge-fill text-warning"></i> Contact & Chat</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if(!empty($fd->finder_user_id))
                                        <a href="{{ route('messages.show', $fd->finder_user_id) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-chat-dots-fill me-1"></i>Open Full Chat
                                        </a>
                                        @endif
                                        <a href="mailto:{{ $fd->finder_email ?? '' }}?subject=Re: Found Item - {{ urlencode($report->item_name) }}"
                                           class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            @empty
                <div class="col-12 text-center py-5">
                    <i class="bi bi-megaphone fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">No lost reports yet.</p>
                </div>
            @endforelse
            </div>
        </div><!-- /lost tab -->

    </div><!-- /tab-content -->
</div><!-- /container -->

{{-- ════════ CLAIM SLIP MODAL ════════ --}}
<div class="modal fade" id="claimSlipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:560px">
        <div class="modal-content border-0" style="border-radius:20px; overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#0b1d3a,#1e3a6e); color:white; padding:18px 22px;">
                <div>
                    <div class="fw-bold fs-6"><i class="bi bi-clipboard-check me-2"></i>Lost and Found Slip</div>
                    <div style="font-size:.78rem; opacity:.7;">F-SAS-SDS-005 · Fill out to claim this item</div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" style="max-height:70vh; overflow-y:auto;">
                <form id="claimSlipForm" onsubmit="submitClaimSlip(event)" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="item_id" id="csItemId">

                    <div class="cs-section">
                        <div class="cs-section-title"><i class="bi bi-box-seam me-2"></i>Item Information</div>
                        <div class="cs-field">
                            <span class="cs-label">Date Reported:</span>
                            <span class="cs-val" id="csDateReported"></span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Place Found:</span>
                            <span class="cs-val" id="csPlaceFound"></span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Date & Time Found:</span>
                            <span class="cs-val" id="csDateFound"></span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Item Description:</span>
                            <span class="cs-val fw-bold" id="csItemDesc"></span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Content:</span>
                            <span class="cs-val" id="csContent" style="font-size:.78rem"></span>
                        </div>
                    </div>

                    <div class="cs-section">
                        <div class="cs-section-title"><i class="bi bi-person-fill me-2"></i>Claimant Information</div>
                        <div class="cs-field">
                            <span class="cs-label">Date of Claim:</span>
                            <span class="cs-val" id="csDateClaim"></span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Owner/Recipient:</span>
                            <span class="cs-val fw-bold">{{ auth()->user()->fname }} {{ auth()->user()->lname }}</span>
                        </div>
                        <div class="cs-field">
                            <span class="cs-label">Program, Yr, Section:</span>
                            <input type="text" class="cs-input" name="section" id="csSection" placeholder="e.g. BSCS-3B" required>
                        </div>
                    </div>

                    <div class="cs-section">
                        <div class="cs-section-title"><i class="bi bi-shield-check me-2"></i>Proof of Ownership</div>
                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:10px 14px; font-size:.78rem; color:#92400e; margin-bottom:12px;">
                            <i class="bi bi-lightbulb-fill me-1"></i>
                            <strong>Tip:</strong> Mention color, brand, contents, unique marks, stickers, or lock screen wallpaper.
                        </div>
                        <div class="mb-3">
                            <textarea name="claimer_message" class="form-control" id="csProof" rows="3" style="font-size:.85rem; border-radius:10px;"
                                placeholder="Describe why this item is yours…" required></textarea>
                        </div>
                        <div>
                            <label class="form-label fw-bold" style="font-size:.8rem">
                                <i class="bi bi-camera me-1"></i>Supporting Photo <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <input type="file" name="claim_image" class="form-control" id="csPhoto" accept="image/*" style="font-size:.82rem; border-radius:10px;">
                        </div>
                    </div>

                    <div id="csSubmitAlert" class="mb-2"></div>

                    <button type="submit" id="csSubmitBtn" class="btn btn-primary w-100 fw-bold py-2" style="border-radius:12px; background:linear-gradient(135deg,#0d6efd,#0a4ab2); border:none;">
                        <i class="bi bi-send-fill me-2"></i>Submit Claim
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Stagger card animations ──
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.item-card-wrapper').forEach((card, i) => {
            const inner = card.querySelector('.item-card');
            if (inner) inner.style.animationDelay = (i * 0.06) + 's';
        });
        initShowMore();
    });

    function initShowMore() {
        document.querySelectorAll('.tab-pane').forEach(tab => {
            const cards = tab.querySelectorAll('.item-card-wrapper');
            const INITIAL = 6;
            if (cards.length <= INITIAL) return;
            cards.forEach((c, i) => { if (i >= INITIAL) c.classList.add('hidden-item'); });
            const row = tab.querySelector('.row');
            const btnWrap = document.createElement('div');
            btnWrap.className = 'text-center mt-4 col-12';
            btnWrap.innerHTML = `<button class="show-more-btn" onclick="toggleShowMore(this)"><i class="bi bi-chevron-down"></i> Show All (${cards.length - INITIAL} more)</button>`;
            if (row && row.parentNode) row.parentNode.insertBefore(btnWrap, row.nextSibling);
        });
    }

    function toggleShowMore(btn) {
        const tab = btn.closest('.tab-pane');
        const hidden = tab.querySelectorAll('.item-card-wrapper.hidden-item');
        if (hidden.length > 0) {
            hidden.forEach((c, i) => {
                c.classList.remove('hidden-item');
                const inner = c.querySelector('.item-card');
                if (inner) {
                    inner.style.animationDelay = (i * 0.05) + 's';
                    inner.style.animation = 'none';
                    requestAnimationFrame(() => { inner.style.animation = ''; });
                }
            });
            btn.innerHTML = '<i class="bi bi-chevron-up"></i> Show Less';
        } else {
            const cards = tab.querySelectorAll('.item-card-wrapper');
            cards.forEach((c, i) => { if (i >= 6) c.classList.add('hidden-item'); });
            btn.innerHTML = `<i class="bi bi-chevron-down"></i> Show All (${cards.length - 6} more)`;
            tab.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ── Search across both tabs ──
    document.getElementById('gallerySearch').addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        let found = false;
        document.querySelectorAll('.item-card-wrapper').forEach(card => {
            const match = card.getAttribute('data-name').includes(term);
            card.classList.toggle('hidden-item', !match);
            if (match) found = true;
        });
        document.getElementById('noResults').classList.toggle('d-none', found);
    });

    // ── Category Filter Pills ──
    document.querySelectorAll('.cat-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const cat = this.dataset.cat;
            let found = false;
            document.querySelectorAll('.item-card-wrapper').forEach(card => {
                if (cat === 'all') {
                    card.classList.remove('hidden-item');
                    found = true;
                } else {
                    const name = card.getAttribute('data-name');
                    const match = name.includes(cat);
                    card.classList.toggle('hidden-item', !match);
                    if (match) found = true;
                }
            });
            document.getElementById('noResults').classList.toggle('d-none', found);
        });
    });

    // ── Claim Slip Modal via data-attributes ──
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-claim-slip');
        if (!btn) return;
        document.getElementById('csItemId').value = btn.dataset.id;
        document.getElementById('csDateReported').textContent = btn.dataset.reported;
        document.getElementById('csPlaceFound').textContent = btn.dataset.location || '—';
        document.getElementById('csDateFound').textContent = btn.dataset.datefound || '—';
        document.getElementById('csItemDesc').textContent = btn.dataset.name;
        document.getElementById('csContent').textContent = btn.dataset.desc || '—';
        document.getElementById('csDateClaim').textContent = new Date().toLocaleDateString('en-US', {year:'numeric', month:'long', day:'2-digit'});
        document.getElementById('csSection').value = '';
        document.getElementById('csProof').value = '';
        document.getElementById('csPhoto').value = '';
        document.getElementById('csSubmitAlert').innerHTML = '';
        document.getElementById('csSubmitBtn').disabled = false;
        document.getElementById('csSubmitBtn').innerHTML = '<i class="bi bi-send-fill me-2"></i>Submit Claim';
        new bootstrap.Modal(document.getElementById('claimSlipModal')).show();
    });

    async function submitClaimSlip(e) {
        e.preventDefault();
        const alertDiv = document.getElementById('csSubmitAlert');
        const btn = document.getElementById('csSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';
        alertDiv.innerHTML = '';

        const fd = new FormData(document.getElementById('claimSlipForm'));
        try {
            const r = await fetch('{{ route("claims.store", ":item") }}'.replace(':item', fd.get('item_id')), {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json().catch(() => ({}));
            if (r.ok || data.success) {
                alertDiv.innerHTML = '<div class="alert alert-success border-0 py-2 rounded-3 mb-0"><i class="bi bi-check-circle-fill me-2"></i>Claim submitted successfully!</div>';
                btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Done!';
                setTimeout(() => location.reload(), 1200);
            } else if (data.duplicate) {
                alertDiv.innerHTML = '<div class="alert alert-warning border-0 py-2 rounded-3 mb-0"><i class="bi bi-exclamation-circle me-2"></i>You already submitted a claim for this item.</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Submit Claim';
            } else {
                alertDiv.innerHTML = '<div class="alert alert-success border-0 py-2 rounded-3 mb-0"><i class="bi bi-check-circle-fill me-2"></i>Claim submitted!</div>';
                btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Done!';
                setTimeout(() => location.reload(), 1200);
            }
        } catch(err) {
            alertDiv.innerHTML = '<div class="alert alert-danger border-0 py-2 rounded-3 mb-0"><i class="bi bi-exclamation-circle me-2"></i>Error submitting. Please try again.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Submit Claim';
        }
    }
</script>
@endpush
