@extends('layouts.portal')

@section('title', $item->item_name . ' — FoundIt!')

@section('content')
<div class="container py-4" style="max-width: 1000px;">
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
        <div class="row g-0">
            {{-- Image --}}
            <div class="col-md-5">
                <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : 'https://placehold.co/500x500?text=No+Image' }}"
                     class="w-100 h-100" style="object-fit: cover; min-height: 350px;">
            </div>

            {{-- Details --}}
            <div class="col-md-7">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        @php
                            $badgeColor = match($item->status) {
                                'Pending' => 'warning',
                                'Published' => 'primary',
                                'Claiming' => 'info',
                                'Returned' => 'success',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeColor }} me-2 px-3 py-2" style="border-radius: 8px;">{{ $item->status }}</span>
                        @if($item->category)
                        <span class="badge bg-light text-dark border px-3 py-2" style="border-radius: 8px;">
                            <i class="bi bi-tag me-1"></i>{{ $item->category }}
                        </span>
                        @endif
                    </div>

                    <h2 class="fw-bold mb-3">{{ $item->item_name }}</h2>

                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Description</div>
                        <p>{{ $item->description }}</p>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Found At</div>
                                <div class="fw-bold">{{ $item->found_location }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-calendar3 me-1"></i>Date Found</div>
                                <div class="fw-bold">{{ $item->found_date ? \Carbon\Carbon::parse($item->found_date)->format('M d, Y') : 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-archive me-1"></i>Storage</div>
                                <div class="fw-bold">{{ $item->storage_location ?? 'SSG Office' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-person me-1"></i>Reported By</div>
                                <div class="fw-bold">{{ $item->user?->fname }} {{ $item->user?->lname }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Claim Form (for students, on Published items) --}}
                    @auth
                    @if($item->status === 'Published' && auth()->user()->isStudent())
                    <div class="mt-4 p-4 border rounded-4" style="background: linear-gradient(135deg, #eff6ff, #f0f9ff);">
                        <h5 class="fw-bold mb-3"><i class="bi bi-hand-index-thumb me-2 text-primary"></i>Claim This Item</h5>
                        <form action="{{ route('claims.store', $item) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Why do you believe this is yours?</label>
                                <textarea name="claim_message" class="form-control" rows="3" placeholder="Describe the item in detail..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Proof of Ownership (optional)</label>
                                <input type="file" name="image_path" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                                <i class="bi bi-send me-2"></i>Submit Claim
                            </button>
                        </form>
                    </div>
                    @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- Claimers Section (for admins/organizers) --}}
    @auth
    @if(in_array((int) auth()->user()->type_id, [4, 5, 6, 2]) && $item->claims->count() > 0)
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 20px;">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-people-fill me-2 text-warning"></i>
                Claimers ({{ $item->claims->count() }})
            </h5>
        </div>
        <div class="card-body p-4 pt-0">
            @foreach($item->claims as $claim)
            <div class="border rounded-3 p-3 mb-3 {{ $claim->status === 'verified' ? 'border-success' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="fw-bold">{{ $claim->user->fname }} {{ $claim->user->lname }}</span>
                        @php
                            $claimBadge = match($claim->status) {
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                                'returned' => 'info',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $claimBadge }} ms-2">{{ ucfirst($claim->status) }}</span>
                    </div>
                    <small class="text-muted">{{ $claim->created_at->diffForHumans() }}</small>
                </div>
                @if($claim->claim_message)
                <p class="mb-2 p-2 bg-light rounded small fst-italic">
                    <i class="bi bi-chat-quote me-1"></i>"{{ $claim->claim_message }}"
                </p>
                @endif
                @if($claim->image_path)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $claim->image_path) }}" class="rounded shadow-sm" style="max-height: 120px; cursor: pointer;" onclick="window.open(this.src)">
                </div>
                @endif

                @if($claim->status === 'pending')
                <div class="d-flex gap-2 mt-2">
                    <form action="{{ route('claims.verify', $claim) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-success fw-bold rounded-pill px-3">
                            <i class="bi bi-patch-check me-1"></i>Verify
                        </button>
                    </form>
                    <form action="{{ route('claims.reject', $claim) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    </form>
                </div>
                @endif

                @if($claim->status === 'verified' && $item->status !== 'Returned')
                <form action="{{ route('claims.return', $claim) }}" method="POST" class="mt-2">
                    @csrf
                    <button class="btn btn-sm btn-primary fw-bold rounded-pill px-3">
                        <i class="bi bi-check-all me-1"></i>Confirm Return
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endauth
</div>
@endsection
