@extends('layouts.portal')

@section('title', 'Lost Reports — FoundIt!')

@section('content')
<div class="container py-4" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-search me-2 text-danger"></i>Lost Item Reports</h2>
            <p class="text-muted mb-0">Browse lost items and help reunite them with their owners</p>
        </div>
        <a href="{{ route('lost-reports.create') }}" class="btn btn-danger fw-bold rounded-pill px-4">
            <i class="bi bi-megaphone me-2"></i>Report Lost Item
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    @if($reports->count())
    <div class="row g-4">
        @foreach($reports as $report)
        <div class="col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; overflow: hidden; transition: transform 0.2s;">
                <img src="{{ $report->image_path ? asset('storage/' . $report->image_path) : 'https://placehold.co/400x250?text=No+Image' }}"
                     class="card-img-top" style="height: 200px; object-fit: cover;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        @php
                            $badgeColor = match($report->status) {
                                'Lost' => 'danger',
                                'Matching' => 'warning',
                                'Resolved' => 'success',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeColor }} px-2 py-1" style="border-radius: 6px;">{{ $report->status }}</span>
                        <small class="text-muted">{{ $report->created_at->diffForHumans() }}</small>
                    </div>
                    <h6 class="fw-bold mb-1">{{ $report->item_name }}</h6>
                    <p class="text-muted small mb-2">{{ Str::limit($report->description, 80) }}</p>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $report->last_seen_location ?? 'Unknown' }}
                    </div>
                    <a href="{{ route('lost-reports.show', $report) }}" class="btn btn-sm btn-outline-danger w-100 fw-bold rounded-pill">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-5">
        <i class="bi bi-search display-3 text-muted opacity-25 d-block mb-3"></i>
        <h5 class="text-muted">No lost reports yet</h5>
        <p class="text-muted">Be the first to report a lost item</p>
    </div>
    @endif
</div>

@push('styles')
<style>
    .col-md-4 .card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.1) !important; }
</style>
@endpush
@endsection
