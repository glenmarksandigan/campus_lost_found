@extends('layouts.portal')

@section('title', 'Success Log — FoundIt!')

@section('content')
<div class="page-body p-4">
    <div class="page-header mb-4 p-4 text-white rounded-4" style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-trophy me-2"></i>Success Log</h2>
                <p class="opacity-75 mb-0">Returned items and their claimers — {{ $returnedItems->count() }} total</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">Item</th>
                            <th>Found Location</th>
                            <th>Returned To</th>
                            <th>Contact</th>
                            <th>Date Returned</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returnedItems as $item)
                        @php
                            $claim = $item->claims->first();
                            $claimer = $claim?->user;
                        @endphp
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : 'https://placehold.co/50x50?text=N/A' }}"
                                         class="rounded-3" style="width:50px; height:50px; object-fit:cover;">
                                    <div>
                                        <div class="fw-bold">{{ $item->item_name }}</div>
                                        <small class="text-muted">{{ $item->category }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item->found_location }}</td>
                            <td>
                                @if($claimer)
                                <div class="fw-bold">{{ $claimer->fname }} {{ $claimer->lname }}</div>
                                <small class="text-muted">{{ $claimer->student_id ?? $claimer->email }}</small>
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $claimer?->contact_number ?? '—' }}</td>
                            <td><small class="text-muted">{{ $claim?->updated_at?->format('M d, Y') ?? '—' }}</small></td>
                            <td class="text-end px-4">
                                <a href="{{ route('claim-slip', $item) }}" class="btn btn-sm btn-outline-success" target="_blank">
                                    <i class="bi bi-printer me-1"></i>Claim Slip
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-trophy display-5 d-block mb-2 opacity-25"></i>
                                No returned items yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
