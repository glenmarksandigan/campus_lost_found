@extends('layouts.portal')

@section('title', 'Organizer Dashboard – FoundIt!')

@push('styles')
<style>
    :root { --teal: #0d9488; }

    .section { display: none; animation: fadeUp .3s ease; }
    .section.active { display: block; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    .stat-card { border: none; border-radius: 16px; transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
    .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .stat-num { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .stat-lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }

    .task-card { border: none; border-radius: 14px; padding: 16px; margin-bottom: 12px; background: #fff; border-left: 4px solid #e2e8f0; transition: all .2s; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .task-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .task-card.urgent { border-left-color: #ef4444; } .task-card.high { border-left-color: #f59e0b; }
    .task-card.normal { border-left-color: #3b82f6; } .task-card.low  { border-left-color: #94a3b8; }
    .task-title { font-weight: 700; font-size: .92rem; margin-bottom: 6px; }
    .task-meta { font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    .badge-priority { padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
    .badge-urgent { background: #fee2e2; color: #dc2626; } .badge-high { background: #fef3c7; color: #ca8a04; }
    .badge-normal { background: #dbeafe; color: #1d4ed8; } .badge-low  { background: #f1f5f9; color: #64748b; }

    .badge-status { padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
    .badge-pending { background: #fef3c7; color: #ca8a04; } .badge-in_progress { background: #dbeafe; color: #1d4ed8; }
    .badge-completed { background: #dcfce7; color: #16a34a; } .badge-published { background: #dbeafe; color: #1d4ed8; }
    .badge-claiming { background: #e0e7ff; color: #4f46e5; } .badge-returned { background: #dcfce7; color: #16a34a; }
    .badge-lost { background: #fee2e2; color: #dc2626; } .badge-resolved { background: #dcfce7; color: #16a34a; }
    .badge-matching { background: #fef3c7; color: #d97706; }

    .content-card { border: none; border-radius: 16px; overflow: hidden; }
    .table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    .claimer-count-badge { display: inline-flex; align-items: center; gap: 5px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 20px; padding: 4px 10px; font-size: 0.78rem; font-weight: 700; color: #92400e; cursor: pointer; transition: all 0.2s; }
    .claimer-count-badge:hover { background: #fef3c7; border-color: #f59e0b; }
    .claimer-count-badge.none { background: #f8fafc; border-color: #e2e8f0; color: #94a3b8; cursor: default; }

    .claimer-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; background: #fff; }
    .claimer-card .claimer-num { width: 28px; height: 28px; border-radius: 50%; background: #0d9488; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; }

    .org-nav-btn { font-size: .85rem; font-weight: 600; border-radius: 10px; padding: 8px 16px; }
    .org-nav-btn.active { background: var(--teal) !important; color: white !important; border-color: var(--teal) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 mt-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            @php
                $hour = (int)now()->format('G');
                $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            @endphp
            <h4 class="fw-bold mb-0"><i class="bi bi-clipboard2-data me-2" style="color:var(--teal)"></i>Organizer Dashboard</h4>
            <div class="text-muted small">{{ $greet }}, {{ auth()->user()->fname }} 👋</div>
        </div>
        <span class="badge px-3 py-2 rounded-pill" style="background:#ccfbf1;color:#0d9488;font-size:.8rem">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F d, Y') }}
        </span>
    </div>

    <!-- Section Nav -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="btn btn-sm org-nav-btn btn-outline-secondary active" onclick="goTo('overview',this)"><i class="bi bi-grid-1x2 me-1"></i>Overview</button>
        <button class="btn btn-sm org-nav-btn btn-outline-secondary" onclick="goTo('my-tasks',this)"><i class="bi bi-list-task me-1"></i>My Tasks</button>
        <button class="btn btn-sm org-nav-btn btn-outline-secondary" onclick="goTo('found-items',this)"><i class="bi bi-box-seam me-1"></i>Found Items</button>
        <button class="btn btn-sm org-nav-btn btn-outline-secondary" onclick="goTo('lost-reports',this)"><i class="bi bi-search me-1"></i>Lost Reports</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div id="overview" class="section active">
        <div class="row g-3 mb-4">
            @php
                $stats = [
                    ['Pending Items', $pendingItems, '#fef9c7', '#ca8a04', 'bi-hourglass-split'],
                    ['Published', $publishedItems, '#dcfce7', '#16a34a', 'bi-megaphone'],
                    ['Claiming', $claimingItems, '#dbeafe', '#1d4ed8', 'bi-hand-thumbs-up'],
                    ['Lost Reports', $unresolvedLost, '#fee2e2', '#dc2626', 'bi-exclamation-circle'],
                ];
            @endphp
            @foreach($stats as [$lbl, $val, $bg, $col, $ico])
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div class="stat-icon" style="background:{{ $bg }};color:{{ $col }}"><i class="bi {{ $ico }}"></i></div>
                        <div><div class="stat-lbl">{{ $lbl }}</div><div class="stat-num" style="color:{{ $col }}">{{ $val }}</div></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <h5 class="fw-bold mb-3"><i class="bi bi-list-task me-2" style="color:var(--teal)"></i>Active Tasks</h5>
        @if($pendingTasks->isEmpty() && $inProgressTasks->isEmpty())
        <div class="alert alert-success border-0 rounded-3 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>All caught up!</strong> You have no pending tasks right now.
        </div>
        @else
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3" style="border-left:4px solid #f59e0b !important">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="bi bi-clock-history me-2 text-warning"></i>Pending</h6>
                        <div class="display-6 fw-bold text-warning">{{ $pendingTasks->count() }}</div>
                        <small class="text-muted">Tasks waiting to be started</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-3" style="border-left:4px solid #3b82f6 !important">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="bi bi-arrow-repeat me-2 text-primary"></i>In Progress</h6>
                        <div class="display-6 fw-bold text-primary">{{ $inProgressTasks->count() }}</div>
                        <small class="text-muted">Tasks currently working on</small>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- ═══ MY TASKS ═══ -->
    <div id="my-tasks" class="section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-list-task me-2" style="color:var(--teal)"></i>My Tasks</h5>
            <span class="badge bg-light text-dark border">{{ $completedTasks->count() }} / {{ $tasks->count() }} completed</span>
        </div>
        <div id="taskAlert" class="mb-3"></div>

        @if($pendingTasks->isNotEmpty())
        <h6 class="text-muted mb-3"><i class="bi bi-clock me-1"></i>Pending ({{ $pendingTasks->count() }})</h6>
        @foreach($pendingTasks as $task)
        <div class="task-card {{ $task->priority }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="task-title">{{ $task->title }}</div>
                <button class="btn btn-sm btn-primary" onclick="updateTask({{ $task->id }}, 'in_progress')"><i class="bi bi-play-fill"></i> Start</button>
            </div>
            @if($task->description)<div class="text-muted small mb-2">{{ $task->description }}</div>@endif
            <div class="task-meta">
                <span class="badge-priority badge-{{ $task->priority }}">{{ $task->priority }}</span>
                <span><i class="bi bi-person text-muted"></i> Assigned by {{ $task->fname }} {{ $task->lname }}</span>
                @if($task->due_date)<span><i class="bi bi-calendar text-muted"></i> Due: {{ \Carbon\Carbon::parse($task->due_date)->format('M d') }}</span>@endif
            </div>
        </div>
        @endforeach
        @endif

        @if($inProgressTasks->isNotEmpty())
        <h6 class="text-muted mb-3 mt-4"><i class="bi bi-arrow-repeat me-1"></i>In Progress ({{ $inProgressTasks->count() }})</h6>
        @foreach($inProgressTasks as $task)
        <div class="task-card {{ $task->priority }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="task-title">{{ $task->title }}</div>
                <button class="btn btn-sm btn-success" onclick="updateTask({{ $task->id }}, 'completed')"><i class="bi bi-check-lg"></i> Complete</button>
            </div>
            @if($task->description)<div class="text-muted small mb-2">{{ $task->description }}</div>@endif
            <div class="task-meta">
                <span class="badge-status badge-in_progress">In Progress</span>
                <span class="badge-priority badge-{{ $task->priority }}">{{ $task->priority }}</span>
            </div>
        </div>
        @endforeach
        @endif

        @if($completedTasks->isNotEmpty())
        <h6 class="text-muted mb-3 mt-4"><i class="bi bi-check-circle me-1"></i>Completed ({{ $completedTasks->count() }})</h6>
        @foreach($completedTasks->take(5) as $task)
        <div class="task-card low opacity-75">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="task-title">{{ $task->title }}</div>
                <span class="badge-status badge-completed">Completed</span>
            </div>
            <div class="task-meta">
                <span><i class="bi bi-check-circle text-success"></i> Done {{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('M d, g:i A') : '' }}</span>
            </div>
        </div>
        @endforeach
        @endif

        @if($tasks->isEmpty())
        <div class="text-center text-muted py-5"><i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No tasks assigned yet</div>
        @endif
    </div>

    <!-- ═══ FOUND ITEMS ═══ -->
    <div id="found-items" class="section">
        <h5 class="fw-bold mb-3"><i class="bi bi-box-seam me-2 text-warning"></i>Found Items Management</h5>

        @if(!$hasEditAccess)
        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4">
            <i class="bi bi-shield-lock-fill me-2"></i>
            <strong>Editing Disabled:</strong> Your editing permissions have been restricted by an Administrator.
        </div>
        @endif

        <div id="itemAlert" class="mb-3"></div>

        <div class="card content-card shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold">All Found Items ({{ $allItems->count() }})</span>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="width:200px">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm border-start-0" id="itemSearchInput" placeholder="Search items..." oninput="searchItems()">
                    </div>
                    <select class="form-select form-select-sm" style="width:auto" onchange="filterItems(this.value)">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Published">Published</option>
                        <option value="Claiming">Claiming</option>
                        <option value="Returned">Returned</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="itemsTable">
                    <thead><tr><th>Item</th><th>Location</th><th>Claimer</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($allItems as $item)
                        @php $itemClaims = $allClaims[$item->id] ?? []; $claimCount = count($itemClaims); @endphp
                        <tr data-status="{{ $item->status }}">
                            <td><div class="fw-semibold">{{ $item->item_name }}</div><small class="text-muted">{{ Str::limit($item->description, 50) }}</small></td>
                            <td><small>{{ $item->found_location ?? 'N/A' }}</small></td>
                            <td>
                                @if($claimCount > 0)
                                <span class="claimer-count-badge" onclick="viewClaimers(@json($itemClaims), '{{ addslashes($item->item_name) }}', {{ $item->id }}, '{{ $item->image_path }}')">
                                    <i class="bi bi-people-fill"></i>{{ $claimCount }} Claimer{{ $claimCount > 1 ? 's' : '' }}
                                    <i class="bi bi-chevron-right" style="font-size:.65rem"></i>
                                </span>
                                @else
                                <span class="claimer-count-badge none"><i class="bi bi-clock"></i> 0 Claims</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small></td>
                            <td><span class="badge-status badge-{{ strtolower($item->status) }}">{{ $item->status }}</span></td>
                            <td>
                                <select class="form-select form-select-sm" onchange="updateItemStatus({{ $item->id }}, this.value, this)" style="min-width:120px" {{ !$hasEditAccess ? 'disabled' : '' }} data-original="{{ $item->status }}">
                                    <option value="{{ $item->status }}" selected>{{ $item->status }}</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Published">Published</option>
                                    <option value="Claiming">Claiming</option>
                                    <option value="Returned">Returned</option>
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No items found yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ LOST REPORTS ═══ -->
    <div id="lost-reports" class="section">
        <h5 class="fw-bold mb-3"><i class="bi bi-search me-2 text-danger"></i>Lost Reports Management</h5>
        <div id="lostAlert" class="mb-3"></div>
        <div class="card content-card shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold">All Lost Reports ({{ $allLostReports->count() }})</span>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="width:200px">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm border-start-0" id="lostSearchInput" placeholder="Search reports..." oninput="searchLost()">
                    </div>
                    <select class="form-select form-select-sm" style="width:auto" onchange="filterLost(this.value)">
                        <option value="">All Status</option>
                        <option value="Lost">Lost</option>
                        <option value="Matching">Matching</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="lostTable">
                    <thead><tr><th>Item</th><th>Last Seen</th><th>Owner</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($allLostReports as $report)
                        <tr data-status="{{ $report->status ?? 'Lost' }}">
                            <td><div class="fw-semibold">{{ $report->item_name }}</div><small class="text-muted">{{ Str::limit($report->description, 50) }}</small></td>
                            <td><small>{{ $report->last_seen_location ?? 'N/A' }}</small></td>
                            <td><small>{{ $report->owner_name ?? 'N/A' }}</small></td>
                            <td><small class="text-muted">{{ $report->created_at->format('M d, Y') }}</small></td>
                            <td><span class="badge-status badge-{{ strtolower($report->status ?? 'lost') }}">{{ $report->status ?? 'Lost' }}</span></td>
                            <td>
                                <select class="form-select form-select-sm" onchange="updateLostStatus({{ $report->id }}, this.value, this)" style="min-width:120px" {{ !$hasEditAccess ? 'disabled' : '' }} data-original="{{ $report->status ?? 'Lost' }}">
                                    <option value="{{ $report->status ?? 'Lost' }}" selected>{{ $report->status ?? 'Lost' }}</option>
                                    <option value="Lost">Lost</option>
                                    <option value="Matching">Matching</option>
                                    <option value="Resolved">Resolved</option>
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No lost reports yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Claimers Modal -->
<div class="modal fade" id="claimersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="modal-header border-0 bg-warning text-dark px-4 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i><span id="claimersModalTitle">Claimers</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>Review all claimers below. Verify their proof of ownership before returning items.</p>
                <div id="claimersListContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function goTo(id, btn) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.org-nav-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        if (btn) btn.classList.add('active');
        window.scrollTo({top:0, behavior:'smooth'});
    }

    function filterItems(status) {
        document.querySelectorAll('#itemsTable tbody tr[data-status]').forEach(row => {
            row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
        });
    }
    function filterLost(status) {
        document.querySelectorAll('#lostTable tbody tr[data-status]').forEach(row => {
            row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
        });
    }
    function searchItems() {
        const q = (document.getElementById('itemSearchInput')?.value || '').toLowerCase();
        document.querySelectorAll('#itemsTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }
    function searchLost() {
        const q = (document.getElementById('lostSearchInput')?.value || '').toLowerCase();
        document.querySelectorAll('#lostTable tbody tr').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    async function updateTask(taskId, status) {
        const div = document.getElementById('taskAlert');
        div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Updating...</div>`;
        try {
            const r = await fetch(`/admin/tasks/${taskId}/status`, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({status})
            });
            const d = await r.json();
            div.innerHTML = d.success
                ? `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${d.message || 'Task updated!'}</div>`
                : `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${d.message || 'Error'}</div>`;
            if (d.success) setTimeout(() => location.reload(), 800);
        } catch(err) { div.innerHTML = `<div class="alert alert-danger">Server error</div>`; }
    }

    async function updateItemStatus(itemId, status, sel) {
        if (status === sel.dataset.original) return;
        try {
            const r = await fetch(`/items/${itemId}/status`, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({status})
            });
            const d = await r.json();
            if (d.success) {
                const row = sel.closest('tr'), badge = row.querySelector('.badge-status');
                badge.className = 'badge-status badge-' + status.toLowerCase();
                badge.textContent = status; row.dataset.status = status; sel.dataset.original = status;
            } else { alert('Error: ' + d.message); sel.value = sel.dataset.original; }
        } catch(err) { alert('Server error'); sel.value = sel.dataset.original; }
    }

    async function updateLostStatus(reportId, status, sel) {
        if (status === sel.dataset.original) return;
        try {
            const r = await fetch(`/lost-reports/${reportId}/status`, {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({status})
            });
            const d = await r.json();
            if (d.success) {
                const row = sel.closest('tr'), badge = row.querySelector('.badge-status');
                badge.className = 'badge-status badge-' + status.toLowerCase();
                badge.textContent = status; row.dataset.status = status; sel.dataset.original = status;
            } else { alert('Error: ' + d.message); sel.value = sel.dataset.original; }
        } catch(err) { alert('Server error'); sel.value = sel.dataset.original; }
    }

    function viewClaimers(claims, itemName, itemId, itemImg) {
        document.getElementById('claimersModalTitle').textContent = `Claimers for: ${itemName}`;
        let html = `<div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-4 border">
            <img src="${itemImg ? '/storage/' + itemImg : 'https://placehold.co/70x70?text=?'}" class="rounded-3 shadow-sm" style="width:70px;height:70px;object-fit:cover">
            <div><div class="fw-bold text-primary mb-1">FOUND ITEM REFERENCE</div><div class="small text-muted">${itemName} (ID: #${itemId})</div></div>
        </div>`;

        if (!claims || claims.length === 0) {
            html = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox display-5 d-block mb-2"></i>No claims found.</div>';
        } else {
            claims.forEach((c, i) => {
                const fullName = `${c.fname || c.user?.fname || ''} ${c.lname || c.user?.lname || ''}`.trim() || 'Unknown';
                const date = c.claimed_at ? new Date(c.claimed_at).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'}) : '';
                const status = c.status || 'pending';
                html += `<div class="claimer-card mb-3 p-3 border rounded-3 bg-white shadow-sm">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="claimer-num">${i+1}</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${fullName} <span class="badge bg-light text-dark border ms-1" style="font-size:.65rem">${status}</span></div>
                            <small class="text-muted">${date}</small>
                        </div>
                    </div>
                    <div class="row g-2 small mb-2">
                        <div class="col-sm-6"><i class="bi bi-envelope me-1 text-muted"></i>${c.email || c.user?.email || '—'}</div>
                        <div class="col-sm-6"><i class="bi bi-telephone me-1 text-muted"></i>${c.contact_number || c.user?.contact_number || '—'}</div>
                    </div>
                    ${c.claim_message ? `<div style="background:#f8fafc;border-left:3px solid #fcd34d;border-radius:0 8px 8px 0;padding:8px 12px;font-size:.82rem;font-style:italic;color:#475569;">"${c.claim_message}"</div>` : ''}
                    ${c.image_path ? `<div class="mt-2"><img src="/storage/${c.image_path}" class="rounded" style="max-height:80px;cursor:pointer" onclick="window.open('/storage/${c.image_path}','_blank')"></div>` : ''}
                </div>`;
            });
        }
        document.getElementById('claimersListContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('claimersModal')).show();
    }
</script>
@endpush
