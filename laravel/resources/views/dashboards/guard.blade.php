@extends('layouts.portal')

@section('title', 'Guard Dashboard – FoundIt!')

@push('styles')
<style>
    :root { --teal: #0d9488; --teal2: #14b8a6; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
    .section { display: none; }
    .section.active { display: block; animation: fadeUp .3s ease; }

    .stat-card { border: none; border-radius: 16px; transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,.1) !important; }
    .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-lbl { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }

    .content-card { border: none; border-radius: 16px; overflow: hidden; }
    .table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 11px 16px; border: none; }
    .table tbody td { padding: 11px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    .sb { padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
    .sb-lost{background:#fee2e2;color:#dc2626} .sb-found{background:#dcfce7;color:#16a34a}
    .sb-pending{background:#fef9c3;color:#ca8a04} .sb-published{background:#dbeafe;color:#1d4ed8}
    .sb-claiming{background:#ede9fe;color:#7c3aed} .sb-returned{background:#dcfce7;color:#16a34a}
    .sb-resolved{background:#dcfce7;color:#16a34a} .sb-matching{background:#fef3c7;color:#d97706}

    .form-control,.form-select { border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 9px 13px; font-size: .875rem; transition: all .2s; }
    .form-control:focus,.form-select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
    .btn-teal { background: var(--teal); color: #fff; border: none; border-radius: 10px; font-weight: 600; padding: 9px 20px; transition: all .2s; }
    .btn-teal:hover { background: var(--teal2); color: #fff; transform: translateY(-1px); }
    .search-bar { border: none; background: transparent; }
    .search-bar:focus { box-shadow: none; border: none; }

    .admin-row { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
    .admin-av { width: 36px; height: 36px; border-radius: 50%; background: var(--teal); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .82rem; flex-shrink: 0; }
    .tip-card { background: linear-gradient(135deg,#f0fdfa,#ccfbf1); border: none; border-radius: 14px; }
    .tip-card li { font-size: .86rem; color: #0f766e; margin-bottom: 6px; }
    .mini-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #f8fafc; border-radius: 9px; border: 1px solid #e8f0f8; }

    .claim-item-card { border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 16px; background: #fff; }
    .claim-item-header { display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; cursor: pointer; }
    .claim-item-header img { width: 56px; height: 56px; object-fit: cover; border-radius: 10px; flex-shrink: 0; }
    .claim-item-header .item-title { font-weight: 700; font-size: .92rem; color: #1e293b; }
    .claim-item-header .item-meta { font-size: .75rem; color: #64748b; margin-top: 2px; }
    .claimer-row-card { display: flex; align-items: flex-start; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; transition: background .15s; }
    .claimer-row-card:last-child { border-bottom: none; }
    .claimer-row-card:hover { background: #f8fafc; }
    .claimer-num { width: 28px; height: 28px; border-radius: 50%; background: #7c3aed; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
    .claimer-info .cname { font-weight: 700; font-size: .88rem; }
    .claimer-info .cmeta { font-size: .76rem; color: #64748b; margin-top: 2px; }
    .claimer-info .cmsg { font-size: .78rem; font-style: italic; color: #94a3b8; background: #f8fafc; border-left: 3px solid #c4b5fd; padding: 6px 10px; border-radius: 0 8px 8px 0; margin-top: 6px; }
    .claimer-proof img { max-height: 80px; border-radius: 8px; object-fit: cover; cursor: pointer; border: 1.5px solid #e2e8f0; margin-top: 6px; }
    .btn-mark-returned { background: linear-gradient(135deg,#16a34a,#15803d); color: #fff; border: none; border-radius: 10px; padding: 7px 16px; font-size: .8rem; font-weight: 700; cursor: pointer; transition: all .2s; flex-shrink: 0; }
    .btn-mark-returned:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22,163,74,.3); }
    .empty-claims { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .empty-claims i { font-size: 3rem; display: block; margin-bottom: 12px; }

    .guard-nav-btn { font-size: .85rem; font-weight: 600; border-radius: 10px; padding: 8px 16px; }
    .guard-nav-btn.active { background: var(--teal) !important; color: white !important; border-color: var(--teal) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-shield-check me-2" style="color:var(--teal)"></i>Guard Dashboard</h4>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F d, Y') }}</div>
        </div>
        <span class="badge rounded-pill px-3 py-2" style="background:#dcfce7;color:#16a34a;font-size:.78rem">
            <i class="bi bi-circle-fill me-1" style="font-size:.45rem"></i>On Duty
        </span>
    </div>

    <!-- Section Navigation -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary active" onclick="goTo('overview',this)"><i class="bi bi-grid-1x2 me-1"></i>Overview</button>
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary" onclick="goTo('lost-reports',this)"><i class="bi bi-exclamation-circle me-1"></i>Lost Reports</button>
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary" onclick="goTo('found-items',this)"><i class="bi bi-box-seam me-1"></i>Found Items</button>
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary" onclick="goTo('log-item',this)"><i class="bi bi-plus-circle me-1"></i>Log Found Item</button>
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary" onclick="goTo('process-claim',this)">
            <i class="bi bi-person-check me-1"></i>Process Claim
            @if($pendingClaims > 0)
            <span class="badge bg-danger ms-1">{{ $pendingClaims }}</span>
            @endif
        </button>
        <button class="btn btn-sm guard-nav-btn btn-outline-secondary" onclick="goTo('contact-admin',this)"><i class="bi bi-headset me-1"></i>Contact Admin</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div id="overview" class="section active">
        <div class="row g-3 mb-4">
            @php
                $stats = [
                    ['Lost Reports', $totalLost, '#fee2e2', '#dc2626', 'bi-exclamation-circle'],
                    ['Found Items', $totalFound, '#fef9c3', '#ca8a04', 'bi-box-seam'],
                    ['Returned', $totalClaimed, '#dcfce7', '#16a34a', 'bi-check-circle'],
                    ['Pending Claims', $pendingClaims, '#ede9fe', '#7c3aed', 'bi-person-check'],
                ];
            @endphp
            @foreach($stats as [$lbl, $val, $bg, $col, $ico])
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="stat-icon" style="background:{{ $bg }};color:{{ $col }}"><i class="bi {{ $ico }}"></i></div>
                        <div><div class="stat-lbl">{{ $lbl }}</div><div class="stat-num">{{ $val }}</div></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Recent Lost Reports -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2" style="color:var(--teal)"></i>Recent Lost Reports</h5>
            <button class="btn btn-sm btn-teal" onclick="goTo('lost-reports',document.querySelectorAll('.guard-nav-btn')[1])">View All <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
        <div class="card content-card shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Item</th><th>Reporter</th><th>Last Seen</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($lostReports->take(5) as $r)
                        <tr>
                            <td><div class="fw-semibold">{{ $r->item_name }}</div><small class="text-muted">{{ Str::limit($r->description, 45) }}</small></td>
                            <td>{{ $r->owner_name ?? '—' }}</td>
                            <td><small>{{ $r->last_seen_location ?? 'N/A' }}</small></td>
                            <td><small class="text-muted">{{ $r->created_at->format('M d, Y') }}</small></td>
                            <td><span class="sb sb-{{ strtolower($r->status ?? 'lost') }}">{{ $r->status ?? 'Lost' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No lost reports yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Found Items -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-warning"></i>Recent Found Items</h5>
            <button class="btn btn-sm btn-teal" onclick="goTo('found-items',document.querySelectorAll('.guard-nav-btn')[2])">View All <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
        <div class="card content-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Item</th><th>Location Found</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($foundItems->take(5) as $item)
                        <tr>
                            <td><div class="fw-semibold">{{ $item->item_name }}</div></td>
                            <td><small><i class="bi bi-geo-alt text-danger me-1"></i>{{ $item->found_location ?? 'N/A' }}</small></td>
                            <td><small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small></td>
                            <td><span class="sb sb-{{ strtolower($item->status ?? 'pending') }}">{{ $item->status ?? 'Pending' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No found items yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ LOST REPORTS ═══ -->
    <div id="lost-reports" class="section">
        <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-circle me-2 text-danger"></i>All Lost Reports</h5>
        <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
            <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control search-bar" id="lostSearch" placeholder="Search item, reporter, or location…"></div>
        </div></div>
        <div class="card content-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="lostTable">
                    <thead><tr><th>#</th><th>Item</th><th>Reporter</th><th>Contact</th><th>Last Seen</th><th>Date</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lostReports as $r)
                        <tr>
                            <td class="text-muted fw-semibold">#{{ $r->id }}</td>
                            <td><div class="fw-semibold">{{ $r->item_name }}</div><small class="text-muted">{{ Str::limit($r->description, 50) }}</small></td>
                            <td>{{ $r->owner_name ?? '—' }}</td>
                            <td><small><i class="bi bi-telephone text-primary me-1"></i>{{ $r->owner_contact ?? 'N/A' }}</small></td>
                            <td><small>{{ $r->last_seen_location ?? 'N/A' }}</small></td>
                            <td><small class="text-muted">{{ $r->created_at->format('M d, Y') }}</small></td>
                            <td><span class="sb sb-{{ strtolower($r->status ?? 'lost') }}">{{ $r->status ?? 'Lost' }}</span></td>
                            <td><button class="btn btn-sm btn-outline-success" onclick="prefillLog('{{ addslashes($r->item_name) }}', {{ $r->id }})"><i class="bi bi-plus me-1"></i>Log Found</button></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No lost reports yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ FOUND ITEMS ═══ -->
    <div id="found-items" class="section">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-warning"></i>Found Items</h5>
            <button class="btn btn-sm btn-teal" onclick="goTo('log-item',document.querySelectorAll('.guard-nav-btn')[3])"><i class="bi bi-plus me-1"></i>Log New Item</button>
        </div>
        <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
            <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control search-bar" id="foundSearch" placeholder="Search items…"></div>
        </div></div>
        <div class="card content-card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="foundTable">
                    <thead><tr><th>#</th><th>Item</th><th>Location Found</th><th>Date</th><th>Logged By</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($foundItems as $item)
                        <tr>
                            <td class="text-muted fw-semibold">#{{ $item->id }}</td>
                            <td><div class="fw-semibold">{{ $item->item_name }}</div><small class="text-muted">{{ Str::limit($item->description, 50) }}</small></td>
                            <td><small><i class="bi bi-geo-alt text-danger me-1"></i>{{ $item->found_location ?? 'N/A' }}</small></td>
                            <td><small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small></td>
                            <td><small>{{ trim(($item->user->fname ?? '') . ' ' . ($item->user->lname ?? '')) ?: 'Guard' }}</small></td>
                            <td><span class="sb sb-{{ strtolower($item->status ?? 'pending') }}">{{ $item->status ?? 'Pending' }}</span></td>
                            <td>
                                @if(!in_array(strtolower($item->status ?? ''), ['returned','claimed']))
                                <button class="btn btn-sm btn-teal" onclick="markClaimed({{ $item->id }})"><i class="bi bi-check2 me-1"></i>Mark Claimed</button>
                                @else
                                <span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i>Done</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 mb-2 opacity-25"></i>No found items logged yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ LOG FOUND ITEM ═══ -->
    <div id="log-item" class="section">
        <h5 class="fw-bold mb-4"><i class="bi bi-plus-circle me-2" style="color:var(--teal)"></i>Log a Found Item</h5>
        <div id="logAlert" class="mb-3"></div>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card content-card shadow-sm p-4">
                    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="guard_log" value="1">
                        <div class="mb-3"><label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="logItemName" class="form-control" placeholder="e.g. Black wallet, iPhone 13" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="logDescription" class="form-control" rows="3" placeholder="Color, brand, distinguishing features..."></textarea></div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Location Found <span class="text-danger">*</span></label>
                                <input type="text" name="found_location" id="logLocation" class="form-control" placeholder="e.g. Library, Canteen" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Date & Time Found <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_found" id="logDateFound" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label fw-semibold">Turned in by <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="turned_by" id="logTurnedBy" class="form-control" placeholder="Name of student who found it"></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Photo <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="image" class="form-control" accept="image/*"></div>
                        <div class="mb-4"><label class="form-label fw-semibold">Matched Lost Report # <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" name="lost_report_id" id="logLostReportId" class="form-control" placeholder="Enter ID if it matches a lost report">
                            <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>Check Lost Reports tab to find a matching report.</div>
                        </div>
                        <button type="submit" class="btn btn-teal w-100 py-2"><i class="bi bi-plus-circle me-2"></i>Log Found Item</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5 d-flex flex-column gap-3">
                <div class="card tip-card p-4">
                    <h6 class="fw-bold mb-3" style="color:var(--teal)"><i class="bi bi-lightbulb me-2"></i>Tips for Logging</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Be specific with the description</li>
                        <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Note the exact location it was found</li>
                        <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Check Lost Reports for a match first</li>
                        <li class="mb-2"><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Record who turned the item in</li>
                        <li><i class="bi bi-check2-circle me-2" style="color:var(--teal)"></i>Store the item at the Guard House</li>
                    </ul>
                </div>
                <div class="card content-card shadow-sm p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-collection me-2 text-primary"></i>Recent Logs</h6>
                    @forelse($foundItems->take(4) as $fi)
                    <div class="mini-item mb-2">
                        <i class="bi bi-box-seam text-warning"></i>
                        <div class="flex-grow-1">
                            <div style="font-size:.82rem;font-weight:600">{{ $fi->item_name }}</div>
                            <div style="font-size:.7rem;color:#94a3b8">{{ $fi->created_at->format('M d, Y') }}</div>
                        </div>
                        <span class="sb sb-{{ strtolower($fi->status ?? 'pending') }}">{{ $fi->status }}</span>
                    </div>
                    @empty
                    <p class="text-muted small mb-0">No items logged yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ PROCESS CLAIM ═══ -->
    <div id="process-claim" class="section">
        <div class="d-flex align-items-center justify-content-between mb-1">
            <h5 class="fw-bold mb-0"><i class="bi bi-person-check me-2" style="color:var(--teal)"></i>Process Claim</h5>
            <span class="badge rounded-pill px-3 py-2" style="background:#ede9fe;color:#7c3aed;font-size:.78rem">
                {{ $pendingClaims }} item{{ $pendingClaims != 1 ? 's' : '' }} being claimed
            </span>
        </div>
        <p class="text-muted mb-4" style="font-size:.85rem">
            <i class="bi bi-info-circle me-1"></i>
            When a student comes to the guard house to collect an item, verify their identity against the claimers listed below, then mark the correct person as <strong>Returned</strong>.
        </p>

        @if($claimingItems->isEmpty())
        <div class="empty-claims">
            <i class="bi bi-inbox"></i>
            <div class="fw-bold mb-1">No items being claimed right now</div>
            <small>Items will appear here when a student submits a claim and the status is set to "Claiming".</small>
        </div>
        @else
        <div class="card border-0 shadow-sm mb-3"><div class="card-body py-2 px-3">
            <div class="input-group"><span class="input-group-text bg-white border-0 pe-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control search-bar" id="claimSearch" placeholder="Search item name…" oninput="searchClaims(this.value)"></div>
        </div></div>

        <div id="claimItemsList">
            @foreach($claimingItems as $ci)
            @php $itemClaims = $claimsByItem[$ci->id] ?? collect(); @endphp
            <div class="claim-item-card" data-item-name="{{ strtolower($ci->item_name) }}">
                <div class="claim-item-header" onclick="toggleClaimers({{ $ci->id }})">
                    <img src="{{ $ci->image_path ? asset('storage/'.$ci->image_path) : 'https://placehold.co/56x56?text=?' }}" alt="{{ $ci->item_name }}">
                    <div class="flex-grow-1">
                        <div class="item-title">{{ $ci->item_name }}</div>
                        <div class="item-meta">
                            <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $ci->found_location }}
                            &nbsp;·&nbsp;
                            <i class="bi bi-building me-1"></i>{{ $ci->storage_location }}
                        </div>
                        <div class="item-meta mt-1">
                            <i class="bi bi-people me-1" style="color:#7c3aed"></i>
                            <strong style="color:#7c3aed">{{ $itemClaims->count() }} claimer{{ $itemClaims->count() != 1 ? 's' : '' }}</strong>
                        </div>
                    </div>
                    <i class="bi bi-chevron-down text-muted" id="chevron-{{ $ci->id }}"></i>
                </div>

                <div id="claimers-{{ $ci->id }}" style="display:none">
                    @forelse($itemClaims as $idx => $cl)
                    @php $clName = trim(($cl->user->fname ?? '') . ' ' . ($cl->user->lname ?? '')) ?: 'Unknown'; @endphp
                    <div class="claimer-row-card">
                        <div class="claimer-num">{{ $idx + 1 }}</div>
                        <div class="claimer-info flex-grow-1">
                            <div class="cname">{{ $clName }}</div>
                            <div class="cmeta">
                                <i class="bi bi-telephone me-1"></i>{{ $cl->user->contact_number ?? '—' }}
                                &nbsp;·&nbsp;
                                <i class="bi bi-envelope me-1"></i>{{ $cl->user->email ?? '—' }}
                            </div>
                            <div class="cmeta"><i class="bi bi-clock me-1"></i>Claimed {{ $cl->claimed_at ? \Carbon\Carbon::parse($cl->claimed_at)->format('M d, Y g:i A') : '' }}</div>
                            @if(!empty($cl->claim_message))
                            <div class="cmsg">"{{ $cl->claim_message }}"</div>
                            @endif
                            @if(!empty($cl->image_path))
                            <div class="claimer-proof">
                                <div class="cmeta mb-1"><i class="bi bi-image me-1"></i>Photo proof:</div>
                                <img src="{{ asset('storage/'.$cl->image_path) }}" onclick="window.open('{{ asset('storage/'.$cl->image_path) }}','_blank')" alt="Proof photo">
                            </div>
                            @endif
                        </div>
                        <button class="btn-mark-returned"
                                onclick="markReturned({{ $ci->id }}, {{ $cl->user_id }}, '{{ addslashes($clName) }}', '{{ addslashes($ci->item_name) }}', this)">
                            <i class="bi bi-check2-circle me-1"></i>Mark Returned
                        </button>
                    </div>
                    @empty
                    <div class="p-4 text-center text-muted small">No claimers found for this item yet.</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ═══ CONTACT ADMIN ═══ -->
    <div id="contact-admin" class="section">
        <h5 class="fw-bold mb-4"><i class="bi bi-headset me-2" style="color:var(--teal)"></i>Contact Admin</h5>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card content-card shadow-sm p-4 h-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-people me-2" style="color:var(--teal)"></i>Admin Contacts</h6>
                    @forelse($admins as $a)
                    <div class="admin-row mb-2">
                        <div class="admin-av">{{ strtoupper(substr($a->fname,0,1).substr($a->lname,0,1)) }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.875rem">{{ $a->fname }} {{ $a->lname }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ $a->email }}</div>
                        </div>
                        <a href="mailto:{{ $a->email }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope"></i></a>
                    </div>
                    @empty
                    <p class="text-muted small">No admin accounts found.</p>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card content-card shadow-sm p-4 h-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2" style="color:var(--teal)"></i>Send In-App Message</h6>
                    <div id="msgAlert" class="mb-3"></div>
                    <form action="{{ route('messages.store') }}" method="POST">
                        @csrf
                        @if($admins->isNotEmpty())
                        <input type="hidden" name="receiver_id" value="{{ $admins->first()->id }}">
                        @endif
                        <div class="mb-3"><label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" id="msgSubject" class="form-control" placeholder="e.g. Unclaimed item since last week" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold">Message</label>
                            <textarea name="body" id="msgBody" class="form-control" rows="5" placeholder="Describe the situation..." required></textarea></div>
                        <button type="submit" class="btn btn-teal w-100 py-2"><i class="bi bi-send me-2"></i>Send Message to Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.getElementById('logDateFound').value = new Date().toISOString().slice(0,16);

    function goTo(id, btn) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.guard-nav-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        if (btn) btn.classList.add('active');
        window.scrollTo({top:0, behavior:'smooth'});
    }

    function prefillLog(name, id) {
        document.getElementById('logItemName').value = name;
        document.getElementById('logLostReportId').value = id;
        goTo('log-item', document.querySelectorAll('.guard-nav-btn')[3]);
    }

    // Search
    document.getElementById('lostSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#lostTable tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q)?'':'none');
    });
    document.getElementById('foundSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#foundTable tbody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q)?'':'none');
    });

    function toggleClaimers(itemId) {
        const panel = document.getElementById('claimers-' + itemId);
        const chevron = document.getElementById('chevron-' + itemId);
        const open = panel.style.display === 'none';
        panel.style.display = open ? 'block' : 'none';
        chevron.className = open ? 'bi bi-chevron-up text-muted' : 'bi bi-chevron-down text-muted';
    }

    function searchClaims(q) {
        document.querySelectorAll('#claimItemsList .claim-item-card').forEach(card => {
            card.style.display = card.dataset.itemName.includes(q.toLowerCase()) ? '' : 'none';
        });
    }

    async function markReturned(itemId, claimerId, claimerName, itemName, btn) {
        if (!confirm(`✅ Confirm: "${claimerName}" is the rightful owner of "${itemName}"?\n\nThis will mark the item as Returned.`)) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>Saving…';
        try {
            const r = await fetch(`/items/${itemId}/return`, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({claimer_id: claimerId})
            });
            const d = await r.json();
            if (d.success) {
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Returned!';
                btn.style.background = '#16a34a';
                const printBtn = document.createElement('button');
                printBtn.className = 'btn-mark-returned ms-2';
                printBtn.style.background = 'linear-gradient(135deg,#3b82f6,#1d4ed8)';
                printBtn.innerHTML = '<i class="bi bi-printer me-1"></i>Print Slip';
                printBtn.onclick = () => window.open(`/claim-slip/${itemId}`, '_blank');
                btn.parentNode.insertBefore(printBtn, btn.nextSibling);
                setTimeout(() => location.reload(), 3000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Mark Returned';
                alert('Error: ' + (d.message || 'Unknown error'));
            }
        } catch(err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Mark Returned';
            alert('Server error. Please try again.');
        }
    }

    async function markClaimed(id) {
        if (!confirm('Mark this item as claimed by the owner?')) return;
        try {
            const r = await fetch(`/items/${id}/claim-status`, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({status: 'Returned'})
            });
            const d = await r.json();
            if (d.success) location.reload();
            else alert(d.message);
        } catch(err) { alert('Server error'); }
    }
</script>
@endpush
