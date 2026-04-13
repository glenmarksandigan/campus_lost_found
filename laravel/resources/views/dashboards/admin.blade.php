@extends('layouts.portal')

@section('title', 'Admin Dashboard | FoundIt!')

@push('styles')
<style>
    :root { --blue: #0d6efd; --navy: #0f172a; }

    @keyframes fadeInUp { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
    .fade-in-up { animation: fadeInUp 0.5s ease both; }
    .fade-in-up:nth-child(1) { animation-delay: 0s; }
    .fade-in-up:nth-child(2) { animation-delay: 0.07s; }
    .fade-in-up:nth-child(3) { animation-delay: 0.14s; }
    .fade-in-up:nth-child(4) { animation-delay: 0.21s; }

    .greeting-text { font-size: 1.35rem; font-weight: 800; margin-bottom: 2px; }
    .greeting-sub { font-size: 0.88rem; opacity: 0.8; }

    .page-header {
        background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%);
        border-radius: 20px; padding: 1.75rem 2rem;
        color: white; margin-bottom: 1.75rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .stat-card { border: none; border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; overflow: hidden; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.1) !important; }
    .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
    .chart-wrapper  { position: relative; height: 300px; width: 100%; }
    .donut-wrapper  { position: relative; height: 220px; width: 100%; }
    .section-label { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8; margin-bottom: 12px; }
    .period-btn { font-size: 0.8rem; font-weight: 600; border-radius: 8px; padding: 5px 14px; }
    .period-btn.active { background: var(--blue); color: white; border-color: var(--blue); }
    .dist-bar-wrap { margin-bottom: 12px; }
    .dist-bar-label { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px; }
    .dist-bar { height: 8px; border-radius: 100px; background: #e2e8f0; overflow: hidden; }
    .dist-bar-fill { height: 100%; border-radius: 100px; transition: width 0.8s ease; }
    .quick-action-btn { border-radius: 14px; padding: 18px; text-align: center; transition: all 0.3s; border: 2px solid #e2e8f0; background: white; }
    .quick-action-btn:hover { border-color: var(--blue); background: #eff6ff; transform: translateY(-3px); }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }
    .pulse { animation: pulse 2s infinite; }
    .btn-messages { background: #ffffff; border: 1.5px solid #ffffff; color: #3f5486 !important; border-radius: 10px; padding: 7px 16px; font-size: .875rem; font-weight: 600; transition: background .2s; }
    .btn-messages:hover { background: #e2e8f0; }
    .btn-messages .badge { font-size: .7rem; }

    /* Section Navigation */
    .section { display: none; animation: fadeUp .3s ease; }
    .section.active { display: block; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    .activity-item { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; transition: background .15s; }
    .activity-item:hover { background: #f8fafc; }
    .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
    .activity-text { flex: 1; min-width: 0; }
    .activity-title { font-weight: 600; font-size: .88rem; margin-bottom: 2px; }
    .activity-meta { font-size: .75rem; color: #94a3b8; }
    .activity-card { border: none; border-radius: 16px; overflow: hidden; }
    .report-table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
    .report-table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }

    /* Inbox Modal */
    .inbox-modal-wrap { display: flex; height: 560px; overflow: hidden; }
    .inbox-modal-sidebar { width: 260px; flex-shrink: 0; border-right: 1px solid #f1f5f9; overflow-y: auto; background: #fff; }
    .inbox-modal-sidebar-header { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; font-weight: 800; font-size: .95rem; display: flex; align-items: center; gap: 8px; position: sticky; top: 0; background: #fff; z-index: 1; }
    .inbox-conv-item { padding: 13px 18px; border-bottom: 1px solid #f8fafc; cursor: pointer; text-decoration: none; display: block; color: inherit; transition: background 0.15s; }
    .inbox-conv-item:hover { background: #f8fafc; }
    .inbox-conv-item.active { background: #eff6ff; border-left: 3px solid #0d6efd; }
    .inbox-conv-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0a4ab2); color: white; font-weight: 700; font-size: .9rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .inbox-conv-name { font-weight: 700; font-size: .85rem; }
    .inbox-conv-subject { font-size: .73rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .inbox-conv-time { font-size: .68rem; color: #94a3b8; }
    .inbox-modal-chat { flex: 1; display: flex; flex-direction: column; background: #fff; overflow: hidden; }
    .inbox-modal-chat-header { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .inbox-modal-messages { flex: 1; overflow-y: auto; padding: 18px 20px; display: flex; flex-direction: column; gap: 10px; }
    .inbox-modal-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; gap: 10px; }
    .chat-bubble { max-width: 72%; padding: 10px 14px; border-radius: 18px; font-size: .86rem; line-height: 1.5; }
    .chat-bubble.sent { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .chat-bubble.received { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
    .bubble-name { font-size: .68rem; font-weight: 700; opacity: .7; margin-bottom: 3px; }
    .bubble-time { font-size: .65rem; opacity: .6; margin-top: 4px; text-align: right; }
    .chat-bubble.sent .bubble-time { color: rgba(255,255,255,0.7); }
    .inbox-modal-input { padding: 14px 20px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0; }
    .inbox-modal-input textarea { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 14px; font-size: .88rem; resize: none; font-family: inherit; max-height: 90px; transition: border-color 0.2s; outline: none; }
    .inbox-modal-input textarea:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.08); }
    .inbox-send-btn { width: 42px; height: 42px; border-radius: 50%; background: #0d6efd; border: none; color: white; display: flex; align-items: center; justify-content: center; font-size: .95rem; flex-shrink: 0; cursor: pointer; transition: background 0.2s, transform 0.2s; }
    .inbox-send-btn:hover { background: #0a4ab2; transform: scale(1.06); }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 mt-4">

    <!-- Header -->
    <div class="page-header fade-in-up mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                @php
                    $hour = (int)now()->format('G');
                    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                @endphp
                <div class="greeting-text">{{ $greet }}, {{ auth()->user()->fname }} 👋</div>
                <div id="pageSubTitle" class="greeting-sub mb-0">Here's what's happening with Lost & Found today.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('messages.index') }}" class="btn btn-messages position-relative">
                    <i class="bi bi-envelope-fill me-1"></i> Messages
                </a>
            </div>
        </div>
    </div>

    <!-- Section Nav Pills -->
    <div class="d-flex gap-2 mb-4">
        <button class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-primary" onclick="showTab('dashboard')">
            <i class="bi bi-grid me-1"></i>Dashboard
        </button>
        <button class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-outline-secondary" onclick="showTab('activity-log')">
            <i class="bi bi-activity me-1"></i>Activity Log
        </button>
        <button class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-outline-secondary" onclick="showTab('reports')">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- DASHBOARD VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="dashboard-view" class="section active">

    <!-- ROW 1: Main Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Total Found Items</p>
                            <h2 class="fw-bold mb-0 counter-value" data-target="{{ $totalItems }}">{{ $totalItems }}</h2>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> {{ $thisMonth }} this month</small>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Returned</p>
                            <h2 class="fw-bold mb-0 text-success counter-value" data-target="{{ $returnedCount }}">{{ $returnedCount }}</h2>
                            <small class="text-success"><i class="bi bi-check-circle"></i> {{ $thisMonthReturned }} this month</small>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Lost Reports</p>
                            <h2 class="fw-bold mb-0 text-warning counter-value" data-target="{{ $totalLost }}">{{ $totalLost }}</h2>
                            <small class="text-danger"><i class="bi bi-exclamation-circle"></i> {{ $unresolvedLost }} unresolved</small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-search"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 fade-in-up">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-label mb-1">Success Rate</p>
                            <h2 class="fw-bold mb-0 text-info counter-value" data-target="{{ $successRate }}">{{ $successRate }}%</h2>
                            <small class="text-muted"><i class="bi bi-trophy"></i> Items returned</small>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: User + Lost/Found Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Student Accounts</h6>
                        @if($pendingUsers > 0)
                        <a href="{{ route('admin.users.index') }}" class="badge bg-warning text-dark text-decoration-none pulse">
                            {{ $pendingUsers }} Pending
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-primary">{{ $totalStudents }}</div>
                            <div style="font-size:.72rem;color:#94a3b8">Total</div>
                        </div>
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-success">{{ $approvedUsers }}</div>
                            <div style="font-size:.72rem;color:#94a3b8">Approved</div>
                        </div>
                        <div class="col-4 text-center p-2 rounded bg-light">
                            <div class="fw-bold fs-5 text-warning">{{ $pendingUsers }}</div>
                            <div style="font-size:.72rem;color:#94a3b8">Pending</div>
                        </div>
                    </div>
                    @php $approvalRate = $totalStudents > 0 ? round($approvedUsers/$totalStudents*100) : 0; @endphp
                    <div class="dist-bar-wrap">
                        <div class="dist-bar-label"><span>Approval Rate</span><span class="fw-bold">{{ $approvalRate }}%</span></div>
                        <div class="dist-bar"><div class="dist-bar-fill bg-success" style="width:{{ $approvalRate }}%"></div></div>
                    </div>
                    <small class="text-muted"><i class="bi bi-person-plus me-1"></i>{{ $newUsersMonth }} new students this month</small>
                    <div class="mt-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-people me-1"></i>Manage Accounts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-search me-2 text-warning"></i>Lost Reports Distribution</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="donut-wrapper mb-3"><canvas id="lostChart"></canvas></div>
                    <div class="row g-2 text-center">
                        <div class="col-4"><div class="p-2 rounded" style="background:#fef3c7"><div class="fw-bold text-warning">{{ $unresolvedLost }}</div><div style="font-size:.7rem;color:#92400e">Lost</div></div></div>
                        <div class="col-4"><div class="p-2 rounded" style="background:#eff6ff"><div class="fw-bold text-primary">{{ $matchingLost }}</div><div style="font-size:.7rem;color:#1e40af">Matching</div></div></div>
                        <div class="col-4"><div class="p-2 rounded" style="background:#f0fdf4"><div class="fw-bold text-success">{{ $resolvedLost }}</div><div style="font-size:.7rem;color:#166534">Resolved</div></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Found Items Distribution</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="donut-wrapper mb-3"><canvas id="foundChart"></canvas></div>
                    <div class="row g-2 text-center">
                        <div class="col-6"><div class="p-2 rounded bg-light"><div class="fw-bold text-warning">{{ $pendingCount }}</div><div style="font-size:.7rem;color:#94a3b8">Pending</div></div></div>
                        <div class="col-6"><div class="p-2 rounded bg-light"><div class="fw-bold text-info">{{ $publishedCount }}</div><div style="font-size:.7rem;color:#94a3b8">Published</div></div></div>
                        <div class="col-6"><div class="p-2 rounded bg-light"><div class="fw-bold text-primary">{{ $claimingCount }}</div><div style="font-size:.7rem;color:#94a3b8">Claiming</div></div></div>
                        <div class="col-6"><div class="p-2 rounded bg-light"><div class="fw-bold text-success">{{ $returnedCount }}</div><div style="font-size:.7rem;color:#94a3b8">Returned</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: Main Chart + Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0">Reporting Trends</h6>
                        <div class="btn-group">
                            <button class="btn btn-outline-secondary period-btn" onclick="setPeriod('monthly',this)">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="{{ route('items.manage') }}" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-box-seam text-primary fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Found Items</div>
                            <small class="text-muted">{{ $totalItems - $returnedCount }} active</small>
                        </div>
                    </a>
                    <a href="{{ route('lost-reports.manage') }}" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-search text-warning fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Lost Reports</div>
                            <small class="text-muted">{{ $unresolvedLost }} need attention</small>
                        </div>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
                        <div class="quick-action-btn">
                            <i class="bi bi-people text-success fs-3 d-block mb-1"></i>
                            <div class="fw-bold">Manage Users</div>
                            <small class="text-muted">{{ $pendingUsers }} pending approval</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: Activity + System Notice -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card activity-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Recent System Activity</h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 fw-bold" onclick="showTab('activity-log')">View All</button>
                </div>
                <div class="card-body p-0" style="max-height:480px; overflow-y:auto">
                    @forelse($recentActivity->take(10) as $act)
                    @php
                        $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'SuperAdmin', 6=>'Organizer'];
                        $role = $roleMap[$act->user->type_id ?? 1] ?? 'User';
                        $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                        $bgColor = $actionColors[$act->action_type] ?? '#64748b';
                        $iconMap = ['create'=>'plus-circle','update'=>'pencil','delete'=>'trash','approve'=>'check-circle','reject'=>'x-circle','publish'=>'send'];
                        $icon = $iconMap[$act->action_type] ?? 'dot';
                    @endphp
                    <div class="activity-item">
                        <div class="activity-icon" style="background:{{ $bgColor }}20;color:{{ $bgColor }}">
                            <i class="bi bi-{{ $icon }}"></i>
                        </div>
                        <div class="activity-text">
                            <div class="activity-title">
                                <strong>{{ $act->user->fname ?? '' }} {{ $act->user->lname ?? '' }}</strong>
                                <span class="badge border bg-light text-dark fw-normal ms-1" style="font-size:.65rem">{{ $role }}</span>
                            </div>
                            <div class="activity-meta">
                                {{ ucfirst($act->action_type) }} · {{ $act->created_at->format('M d, g:i A') }}
                                @if($act->details)
                                · <span class="text-dark opacity-75">{{ $act->details }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5"><i class="bi bi-inbox d-block fs-2 opacity-25 mb-2"></i>No activity logged yet</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-info"></i>System Notice</h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <i class="bi bi-robot display-4 text-primary opacity-25 mb-3 d-block"></i>
                        <h5>Automated Tracking</h5>
                        <p class="text-muted small">Manual tasks have been replaced with the Automatic Activity Log. Every status change, approval, and system update is now recorded automatically with full actor details.</p>
                        <hr class="opacity-10">
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm" onclick="showTab('activity-log')">View Detailed Logs</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- End Dashboard View -->

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- ACTIVITY LOG VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="activity-log-view" class="section">
        <div class="card activity-card shadow-sm border-0 mb-5">
            <div class="card-header bg-white border-0 py-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Complete System Logs</h5>
                <div class="input-group input-group-sm w-auto">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" id="activitySearch" placeholder="Filter logs..." onkeyup="searchLogs()">
                </div>
            </div>
            <div class="card-body p-0" style="max-height:700px; overflow-y:auto;">
                @foreach($recentActivity as $act)
                @php
                    $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'SuperAdmin', 6=>'Organizer'];
                    $role = $roleMap[$act->user->type_id ?? 1] ?? 'User';
                    $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                    $bgColor = $actionColors[$act->action_type] ?? '#64748b';
                    $iconMap = ['create'=>'plus-circle','update'=>'pencil','delete'=>'trash','approve'=>'check-circle','reject'=>'x-circle','publish'=>'send'];
                    $icon = $iconMap[$act->action_type] ?? 'dot';
                @endphp
                <div class="activity-item log-row">
                    <div class="activity-icon" style="background:{{ $bgColor }}20;color:{{ $bgColor }}">
                        <i class="bi bi-{{ $icon }}"></i>
                    </div>
                    <div class="activity-text">
                        <div class="activity-title">
                            <strong>{{ $act->user->fname ?? '' }} {{ $act->user->lname ?? '' }}</strong>
                            <span class="badge border bg-light text-dark fw-normal ms-1" style="font-size:.65rem">{{ $role }}</span>
                            <span class="ms-1 text-muted fw-normal">performed</span> <strong>{{ $act->action_type }}</strong>
                        </div>
                        <div class="activity-meta">
                            <i class="bi bi-clock me-1"></i>{{ $act->created_at->format('M d, Y - g:i A') }}
                            <span class="mx-2 text-muted">|</span>
                            <span>Target: <span class="text-dark fw-bold">{{ ucfirst($act->target_type) }} #{{ $act->target_id }}</span></span>
                            @if($act->details)
                            <br><span class="text-muted mt-1 d-inline-block">Details: <span class="opacity-75">{{ $act->details }}</span></span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- REPORTS VIEW -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div id="reports-view" class="section">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-filter me-2"></i>Filter Reports</h6>
            <form action="{{ route('dashboard') }}" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="section" value="reports">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">REPORT TYPE</label>
                    <select name="report_type" class="form-select border-0 bg-light rounded-3">
                        <option value="lost" {{ request('report_type','lost')=='lost'?'selected':'' }}>Lost Item Reports</option>
                        <option value="found" {{ request('report_type')=='found'?'selected':'' }}>Found Item Database</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">TIME PERIOD</label>
                    <select name="time" class="form-select border-0 bg-light rounded-3">
                        <option value="">All Time</option>
                        <option value="month" {{ request('time')=='month'?'selected':'' }}>This Month</option>
                        <option value="semester" {{ request('time')=='semester'?'selected':'' }}>This Semester</option>
                        <option value="year" {{ request('time')=='year'?'selected':'' }}>This Year</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">STATUS</label>
                    <select name="status" class="form-select border-0 bg-light rounded-3">
                        <option value="">All</option>
                        <option value="Lost" {{ request('status')=='Lost'?'selected':'' }}>Lost</option>
                        <option value="Matching" {{ request('status')=='Matching'?'selected':'' }}>Matching</option>
                        <option value="Resolved" {{ request('status')=='Resolved'?'selected':'' }}>Resolved</option>
                        <option value="Published" {{ request('status')=='Published'?'selected':'' }}>Published</option>
                        <option value="Claiming" {{ request('status')=='Claiming'?'selected':'' }}>Claiming</option>
                        <option value="Returned" {{ request('status')=='Returned'?'selected':'' }}>Returned</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="bg-light p-2 rounded-3">
                        <label class="form-label small fw-bold text-muted ms-2 mb-1">CUSTOM RANGE</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-transparent" value="{{ request('start_date') }}">
                            <span class="text-muted">–</span>
                            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-transparent" value="{{ request('end_date') }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-3"><i class="bi bi-search me-2"></i>Apply</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Filtered Report Result</h6>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-2"></i>Print Report</button>
            </div>
            <div class="table-responsive">
                <table class="table report-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th><th>Item Name</th><th>Status</th><th>Posted Date</th><th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Use the filters above to generate a report.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart data
    const monthlyFoundData = @json($monthlyFoundData);
    const monthlyLostData  = @json($monthlyLostData);

    // Lost Reports Donut
    new Chart(document.getElementById('lostChart'), {
        type: 'doughnut',
        data: {
            labels: ['Lost', 'Matching', 'Resolved'],
            datasets: [{ data: [{{ $unresolvedLost }}, {{ $matchingLost }}, {{ $resolvedLost }}], backgroundColor: ['#f59e0b','#3b82f6','#22c55e'], borderWidth: 0, cutout: '70%' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // Found Items Donut
    new Chart(document.getElementById('foundChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Published', 'Claiming', 'Returned'],
            datasets: [{ data: [{{ $pendingCount }}, {{ $publishedCount }}, {{ $claimingCount }}, {{ $returnedCount }}], backgroundColor: ['#f59e0b','#06b6d4','#3b82f6','#22c55e'], borderWidth: 0, cutout: '70%' }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // Trend Chart
    const trendChart = new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [
                { label: 'Found Items', data: monthlyFoundData, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.08)', tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#0d6efd', pointBorderColor: '#fff', pointBorderWidth: 2 },
                { label: 'Lost Reports', data: monthlyLostData, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.4, fill: true, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#f59e0b', pointBorderColor: '#fff', pointBorderWidth: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 12, weight: '600' }, padding: 16 } }, tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', padding: 12, cornerRadius: 10 } },
            scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { precision: 0 } }, x: { grid: { display: false } } }
        }
    });

    // Section toggle
    function showTab(sectionId) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(sectionId + '-view');
        if (target) {
            target.classList.add('active');
            window.scrollTo(0, 0);
            const titles = { 'dashboard': "Here's what's happening with Lost & Found today.", 'activity-log': 'System Activity Log', 'reports': 'System Reports & Analytics' };
            const subTitle = document.getElementById('pageSubTitle');
            if (subTitle) subTitle.textContent = titles[sectionId] || 'Admin Dashboard';
        }
    }

    // Open sections based on URL param
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        if (section === 'reports') showTab('reports');
        else if (section === 'activity') showTab('activity-log');
    });

    // Log search
    window.searchLogs = function() {
        const q = document.getElementById('activitySearch').value.toLowerCase();
        document.querySelectorAll('.log-row').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(q) ? 'flex' : 'none';
        });
    };

    // Counter animation
    document.querySelectorAll('.counter-value').forEach(el => {
        const target = parseInt(el.dataset.target) || 0;
        if (target === 0) return;
        let current = 0;
        const step = Math.ceil(target / 40);
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            const suffix = el.textContent.includes('%') ? '%' : '';
            el.textContent = current + suffix;
            if (current >= target) clearInterval(timer);
        }, 25);
    });
</script>
@endpush
