@extends('layouts.portal')

@section('title', 'Manage Lost Reports — FoundIt!')

@section('content')
<div class="page-body p-4">
    <div class="page-header mb-4 p-4 text-white rounded-4" style="background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-search me-2"></i>Lost Reports Management</h2>
                <p class="opacity-75 mb-0">Track and manage all lost item reports</p>
            </div>
        </div>
        <div class="d-flex gap-3 mt-4">
            @foreach($statusCounts as $label => $count)
            <div class="flex-fill p-3 rounded-3 text-center" style="background: rgba(255,255,255,0.15);">
                <h3 class="fw-bold mb-0">{{ $count }}</h3>
                <small class="opacity-90">{{ $statusLabels[$label] }}</small>
            </div>
            @endforeach
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search reports...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3 pt-3">
                @foreach($statuses as $i => $status)
                <li class="nav-item">
                    <button class="nav-link {{ $i === 0 ? 'active' : '' }} fw-bold" data-bs-toggle="tab" data-bs-target="#tab-{{ $status }}">
                        {{ $statusLabels[$status] }}
                        <span class="ms-2 badge rounded-pill bg-light text-dark border">{{ $statusCounts[$status] }}</span>
                    </button>
                </li>
                @endforeach
            </ul>

            <div class="tab-content p-4">
                @foreach($statuses as $i => $status)
                <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="tab-{{ $status }}">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:80px">Photo</th>
                                    <th>Item Details</th>
                                    <th>Owner</th>
                                    <th>Contact</th>
                                    <th>Date</th>
                                    <th class="text-end" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data[$status] as $report)
                                <tr data-item-id="{{ $report->id }}">
                                    <td>
                                        <img src="{{ $report->image_path ? asset('storage/' . $report->image_path) : 'https://placehold.co/80x80?text=N/A' }}"
                                             class="rounded-3 border shadow-sm" style="width:65px; height:65px; object-fit:cover;">
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $report->item_name }}</div>
                                        <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> {{ $report->last_seen_location ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $report->owner_name ?? ($report->user?->fname . ' ' . $report->user?->lname) }}</td>
                                    <td><small>{{ $report->owner_contact ?? 'N/A' }}</small></td>
                                    <td><small class="text-muted">{{ $report->created_at->format('M d, Y') }}</small></td>
                                    <td class="text-end">
                                        <a href="{{ route('lost-reports.show', $report) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        <select class="form-select form-select-sm d-inline-block status-select" style="width: auto;" data-report-id="{{ $report->id }}">
                                            <option selected disabled>Status...</option>
                                            @foreach($statuses as $s)
                                            @if($s !== $status)
                                            <option value="{{ $s }}">{{ $statusLabels[$s] }}</option>
                                            @endif
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
                                        No reports in {{ $statusLabels[$status] }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr[data-item-id]').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const id = this.dataset.reportId;
            fetch(`/lost-reports/${id}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ status: this.value })
            }).then(() => location.reload());
        });
    });
</script>
@endpush
@endsection
