@extends('layouts.portal')

@section('title', 'Super Admin Dashboard – FoundIt!')

@push('styles')
<style>
    :root { --purple: #7c3aed; }

    .section { display: none; animation: fadeUp .3s ease; }
    .section.active { display: block; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

    .stat-card { border: none; border-radius: 16px; transition: transform .2s, box-shadow .2s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important; }
    .stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }

    .activity-card { border: none; border-radius: 16px; }
    .activity-item { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; transition: background .15s; }
    .activity-item:hover { background: #f8fafc; }
    .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
    .activity-text { flex: 1; min-width: 0; }
    .activity-title { font-weight: 600; font-size: .88rem; margin-bottom: 2px; }
    .activity-meta { font-size: .75rem; color: #94a3b8; }

    .organizer-card { padding: 16px; border: 2px solid #e2e8f0; border-radius: 14px; transition: all .2s; }
    .organizer-card:hover { border-color: #7c3aed; background: #faf5ff; }
    .organizer-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #5b21b6); color: #fff; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .chart-container { position: relative; height: 280px; }

    .table thead th { background: #f8fafc; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 12px 16px; border: none; }
    .table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .86rem; border-color: #f1f5f9; }
    .table tbody tr:hover { background: #f8fafc; }

    .sa-nav-btn { font-size: .85rem; font-weight: 600; border-radius: 10px; padding: 8px 16px; }
    .sa-nav-btn.active { background: var(--purple) !important; color: white !important; border-color: var(--purple) !important; }
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
            <h4 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2" style="color:var(--purple)"></i>Super Admin Dashboard</h4>
            <div class="text-muted small">{{ $greet }}, {{ auth()->user()->fname }} 👋</div>
        </div>
        <span class="badge px-3 py-2 rounded-pill" style="background:#ede9fe;color:#7c3aed;font-size:.8rem">
            <i class="bi bi-eye me-1"></i>Observer Mode
        </span>
    </div>

    <!-- Section Nav -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <button class="btn btn-sm sa-nav-btn btn-outline-secondary active" onclick="goTo('dashboard',this)"><i class="bi bi-grid-1x2 me-1"></i>Dashboard</button>
        <button class="btn btn-sm sa-nav-btn btn-outline-secondary" onclick="goTo('activity-log',this)"><i class="bi bi-activity me-1"></i>Activity Log</button>
        <button class="btn btn-sm sa-nav-btn btn-outline-secondary" onclick="goTo('organizer-team',this)"><i class="bi bi-people me-1"></i>Organizer Team</button>
        <button class="btn btn-sm sa-nav-btn btn-outline-secondary" onclick="goTo('reports',this)"><i class="bi bi-file-earmark-bar-graph me-1"></i>Reports</button>
    </div>

    <!-- ═══ DASHBOARD ═══ -->
    <div id="dashboard" class="section active">
        <div class="alert border-0 rounded-3 shadow-sm mb-4" style="background:linear-gradient(135deg,#faf5ff,#ede9fe);color:#5b21b6">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Observer Mode:</strong> You have read-only access to monitor system activity. Contact the Admin for any changes.
        </div>

        <div class="row g-3 mb-4">
            @php
                $stats = [
                    ['Total Items', $totalItems, '#3b82f6', 'bi-box-seam'],
                    ['Returned', $returnedItems, '#22c55e', 'bi-check-circle'],
                    ['Lost Reports', $totalLost, '#f59e0b', 'bi-search'],
                    ['Active Tasks', $activeTasks, '#8b5cf6', 'bi-list-task'],
                    ['Total Users', $totalUsers, '#06b6d4', 'bi-people'],
                    ['Pending Items', $pendingItems, '#ef4444', 'bi-hourglass'],
                ];
            @endphp
            @foreach($stats as [$lbl, $val, $col, $ico])
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="stat-icon" style="background:{{ $col }}20;color:{{ $col }}"><i class="bi {{ $ico }}"></i></div>
                        </div>
                        <div class="stat-lbl">{{ $lbl }}</div>
                        <div class="stat-num" style="color:{{ $col }}">{{ $val }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card activity-card shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>6-Month Trends</h6>
                    </div>
                    <div class="card-body"><div class="chart-container"><canvas id="trendChart"></canvas></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card activity-card shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>Quick Activity</h6>
                    </div>
                    <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                        @forelse($recentActivity->take(6) as $act)
                        @php
                            $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'Super Admin', 6=>'Organizer'];
                            $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                            $bgColor = $actionColors[$act->action_type] ?? '#64748b';
                            $iconMap = ['create'=>'plus-circle','update'=>'pencil','delete'=>'trash','approve'=>'check-circle','reject'=>'x-circle','publish'=>'send'];
                            $icon = $iconMap[$act->action_type] ?? 'dot';
                        @endphp
                        <div class="activity-item">
                            <div class="activity-icon" style="background:{{ $bgColor }}20;color:{{ $bgColor }}"><i class="bi bi-{{ $icon }}"></i></div>
                            <div class="activity-text">
                                <div class="activity-title">{{ $act->user->fname ?? '' }} {{ $act->user->lname ?? '' }}</div>
                                <div class="activity-meta">{{ ucfirst($act->action_type) }} · {{ $act->created_at->format('M d, g:i A') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-3 mb-2 opacity-25"></i>No activity yet</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ ACTIVITY LOG ═══ -->
    <div id="activity-log" class="section">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h5 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>System Activity Log</h5>
            <div class="input-group input-group-sm" style="width:250px">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control form-control-sm border-start-0" id="activitySearch" placeholder="Search activity..." oninput="searchActivity()">
            </div>
        </div>
        <div class="card activity-card shadow-sm">
            <div class="card-body p-0" style="max-height:600px; overflow-y:auto">
                @forelse($recentActivity as $act)
                @php
                    $roleMap = [1=>'Student', 2=>'Guard', 3=>'Staff', 4=>'Admin', 5=>'Super Admin', 6=>'Organizer'];
                    $role = $roleMap[$act->user->type_id ?? 1] ?? 'User';
                    $actionColors = ['create'=>'#3b82f6','update'=>'#f59e0b','delete'=>'#ef4444','approve'=>'#22c55e','reject'=>'#ef4444','publish'=>'#06b6d4'];
                    $bgColor = $actionColors[$act->action_type] ?? '#64748b';
                    $iconMap = ['create'=>'plus-circle','update'=>'pencil','delete'=>'trash','approve'=>'check-circle','reject'=>'x-circle','publish'=>'send'];
                    $icon = $iconMap[$act->action_type] ?? 'dot';
                @endphp
                <div class="activity-item activity-row">
                    <div class="activity-icon" style="background:{{ $bgColor }}20;color:{{ $bgColor }}"><i class="bi bi-{{ $icon }}"></i></div>
                    <div class="activity-text">
                        <div class="activity-title">
                            <strong>{{ $act->user->fname ?? '' }} {{ $act->user->lname ?? '' }}</strong>
                            <span class="badge bg-light text-dark border ms-1" style="font-size:.65rem">{{ $role }}</span>
                            {{ ucfirst($act->action_type) }} {{ $act->target_type }} #{{ $act->target_id }}
                        </div>
                        <div class="activity-meta">
                            <i class="bi bi-clock me-1"></i>{{ $act->created_at->format('M d, Y g:i A') }}
                            @if($act->details)<span class="mx-1">•</span>{{ Str::limit($act->details, 60) }}@endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-5"><i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No activity yet</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ═══ ORGANIZER TEAM ═══ -->
    <div id="organizer-team" class="section">
        <h5 class="fw-bold mb-4"><i class="bi bi-people me-2" style="color:var(--purple)"></i>Organizer Team Performance</h5>
        @if($organizerStats->isEmpty())
        <div class="text-center text-muted py-5"><i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No organizers found</div>
        @else
        <div class="row g-3">
            @foreach($organizerStats as $org)
            <div class="col-lg-4 col-md-6">
                <div class="organizer-card">
                    <div class="d-flex align-items-center gap-3">
                        <div class="organizer-avatar">{{ strtoupper(substr($org->fname,0,1)) }}</div>
                        <div style="flex:1">
                            <div class="fw-bold">{{ $org->fname }} {{ $org->lname }}</div>
                            <div class="small text-muted">{{ $org->organizer_role == 'president' ? '👑 SSG President' : 'SSG Member' }}</div>
                            <div class="d-flex gap-2 mt-2">
                                <span class="badge bg-success" style="font-size:.72rem">✓ {{ $org->completed_tasks }} done</span>
                                <span class="badge bg-warning text-dark" style="font-size:.72rem">⏳ {{ $org->active_tasks }} active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ═══ REPORTS ═══ -->
    <div id="reports" class="section">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-bar-graph me-2" style="color:var(--purple)"></i>Detailed Reports</h5>
                <small class="text-muted">Filter and analyze lost items and found items data</small>
            </div>
        </div>

        <div class="card activity-card shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="{{ route('dashboard') }}" method="GET">
                    <input type="hidden" name="section" value="reports">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-2">Type</label>
                            <select name="report_type" class="form-select form-select-sm">
                                <option value="lost" {{ request('report_type','lost')=='lost'?'selected':'' }}>📋 Lost Reports</option>
                                <option value="found" {{ request('report_type')=='found'?'selected':'' }}>📦 Found Items</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-2">Period</label>
                            <select name="time" class="form-select form-select-sm">
                                <option value="">All Time</option>
                                <option value="month" {{ request('time')=='month'?'selected':'' }}>This Month</option>
                                <option value="semester" {{ request('time')=='semester'?'selected':'' }}>This Semester</option>
                                <option value="year" {{ request('time')=='year'?'selected':'' }}>This Year</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-2">From</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-2">To</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-2">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="Lost" {{ request('status')=='Lost'?'selected':'' }}>Lost</option>
                                <option value="Matching" {{ request('status')=='Matching'?'selected':'' }}>Matching</option>
                                <option value="Resolved" {{ request('status')=='Resolved'?'selected':'' }}>Resolved</option>
                                <option value="Pending" {{ request('status')=='Pending'?'selected':'' }}>Pending</option>
                                <option value="Published" {{ request('status')=='Published'?'selected':'' }}>Published</option>
                                <option value="Returned" {{ request('status')=='Returned'?'selected':'' }}>Returned</option>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Apply</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card activity-card shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>Report Data</h6>
                <div class="input-group input-group-sm" style="width:200px">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control form-control-sm border-start-0" id="reportSearch" placeholder="Search..." oninput="searchReport()">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-funnel display-5 d-block mb-2 opacity-25"></i>
                    <p>Use the filters above to generate a report.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function goTo(id, btn) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.sa-nav-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        if (btn) btn.classList.add('active');
        window.scrollTo({top:0, behavior:'smooth'});
    }

    function searchActivity() {
        const q = (document.getElementById('activitySearch')?.value || '').toLowerCase();
        document.querySelectorAll('.activity-row').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    function searchReport() {
        const q = (document.getElementById('reportSearch')?.value || '').toLowerCase();
        document.querySelectorAll('.report-row').forEach(r => {
            r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    }

    // Chart
    const monthlyData = @json($monthlyData);
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.label),
            datasets: [
                { label: 'Found Items', data: monthlyData.map(d => d.items), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.4, fill: true, borderWidth: 2.5 },
                { label: 'Lost Reports', data: monthlyData.map(d => d.lost), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.4, fill: true, borderWidth: 2.5 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
    });

    // Auto-navigate
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        if (section) {
            const btn = document.querySelector(`[onclick*="${section}"]`);
            if (btn) goTo(section, btn);
        }
    });
</script>
@endpush
