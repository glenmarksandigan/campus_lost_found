@extends('layouts.portal')

@section('title', $lostReport->item_name . ' — Lost Report')

@section('content')
<div class="container py-4" style="max-width: 900px;">
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
            <div class="col-md-5">
                <img src="{{ $lostReport->image_path ? asset('storage/' . $lostReport->image_path) : 'https://placehold.co/500x500?text=No+Image' }}"
                     class="w-100 h-100" style="object-fit: cover; min-height: 300px;">
            </div>
            <div class="col-md-7">
                <div class="card-body p-4">
                    @php
                        $badgeColor = match($lostReport->status) {
                            'Lost' => 'danger',
                            'Matching' => 'warning',
                            'Resolved' => 'success',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $badgeColor }} px-3 py-2 mb-3" style="border-radius: 8px;">{{ $lostReport->status }}</span>

                    <h2 class="fw-bold mb-3">{{ $lostReport->item_name }}</h2>

                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Description</div>
                        <p>{{ $lostReport->description ?? 'No description provided.' }}</p>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Last Seen</div>
                                <div class="fw-bold">{{ $lostReport->last_seen_location ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-calendar3 me-1"></i>Reported</div>
                                <div class="fw-bold">{{ $lostReport->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-person me-1"></i>Owner</div>
                                <div class="fw-bold">{{ $lostReport->owner_name ?? ($lostReport->user?->fname . ' ' . $lostReport->user?->lname) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small fw-bold"><i class="bi bi-telephone me-1"></i>Contact</div>
                                <div class="fw-bold">{{ $lostReport->owner_contact ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Owner (for finders) --}}
    @auth
    @if($lostReport->status === 'Lost' && auth()->id() !== $lostReport->user_id)
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 20px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-telephone-outbound me-2 text-success"></i>Found this item? Contact the owner</h5>
            <form action="{{ route('lost-reports.contact', $lostReport) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Your Name</label>
                        <input type="text" name="finder_name" class="form-control" value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Your Contact</label>
                        <input type="text" name="finder_contact" class="form-control" value="{{ auth()->user()->contact_number }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-bold small">Message</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Tell the owner where/how you found their item..."></textarea>
                </div>
                <button type="submit" class="btn btn-success fw-bold mt-3 rounded-pill px-4">
                    <i class="bi bi-send me-2"></i>Notify Owner
                </button>
            </form>
        </div>
    </div>
    @endif
    @endauth

    {{-- Contacts from finders (visible to owner & admins) --}}
    @auth
    @if((auth()->id() === $lostReport->user_id || in_array((int) auth()->user()->type_id, [4, 5, 6])) && $lostReport->contacts->count() > 0)
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 20px;">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-bell me-2 text-warning"></i>Finder Contacts ({{ $lostReport->contacts->count() }})</h5>
        </div>
        <div class="card-body p-4 pt-0">
            @foreach($lostReport->contacts as $contact)
            <div class="border rounded-3 p-3 mb-3">
                <div class="fw-bold">{{ $contact->finder_name }}</div>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $contact->finder_contact ?? 'N/A' }}</small>
                @if($contact->message)
                <p class="mt-2 mb-0 p-2 bg-light rounded small fst-italic">"{{ $contact->message }}"</p>
                @endif
                <small class="text-muted d-block mt-1">{{ $contact->created_at->diffForHumans() }}</small>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endauth
</div>
@endsection
