@extends('layouts.portal')

@section('title', 'Manage Found Items — FoundIt!')

@section('content')
<div class="page-body p-4">
    {{-- Header --}}
    <div class="page-header mb-4 p-4 text-white rounded-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-box-seam me-2"></i>Found Items Management</h2>
                <p class="opacity-75 mb-0">Track, manage, and process all found items</p>
            </div>
        </div>
        <div class="d-flex gap-3 mt-4">
            @foreach($statusCounts as $label => $count)
            <div class="flex-fill p-3 rounded-3 text-center" style="background: rgba(255,255,255,0.1);">
                <h3 class="fw-bold mb-0">{{ $count }}</h3>
                <small class="opacity-90">{{ $statusLabels[$label] }}</small>
            </div>
            @endforeach
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search by item name, place, or finder...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted"><i class="bi bi-folder me-2"></i>Total: <strong>{{ $totalCount }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabbed Table --}}
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3 pt-3" id="adminTab">
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
                                    <th style="width:150px">Claimers</th>
                                    <th style="width:170px">Storage</th>
                                    <th style="width:120px">Date</th>
                                    <th class="text-end" style="width:200px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data[$status] as $item)
                                <tr data-item-id="{{ $item->id }}">
                                    <td>
                                        <a href="{{ route('items.show', $item) }}">
                                            <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : 'https://placehold.co/80x80?text=N/A' }}"
                                                 class="rounded-3 border shadow-sm" style="width:70px; height:70px; object-fit:cover; cursor:pointer;">
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $item->item_name }}</div>
                                        <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> {{ $item->found_location }}</small>
                                        @if($item->category)
                                        <br><span class="badge bg-light text-dark border mt-1" style="font-size:.7rem;"><i class="bi bi-tag me-1"></i>{{ $item->category }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->claims->count() > 0)
                                        <a href="{{ route('items.show', $item) }}" class="text-decoration-none">
                                            <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill fw-bold" style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; font-size:.78rem;">
                                                <i class="bi bi-people-fill"></i> {{ $item->claims->count() }} Claimer{{ $item->claims->count() > 1 ? 's' : '' }}
                                            </span>
                                        </a>
                                        @else
                                        <span class="text-muted small"><i class="bi bi-clock"></i> No claims</span>
                                        @endif
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm storage-select" data-item-id="{{ $item->id }}"
                                                {{ !auth()->user()->hasEditAccess() ? 'disabled' : '' }}>
                                            @foreach(['SSG Office', 'Guard House', "Finder's Possession"] as $loc)
                                            <option value="{{ $loc }}" {{ ($item->storage_location ?? 'SSG Office') === $loc ? 'selected' : '' }}>{{ $loc }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group mb-1">
                                            <a href="{{ route('items.show', $item) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this item permanently?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Delete" {{ !auth()->user()->hasEditAccess() ? 'disabled' : '' }}>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <select class="form-select form-select-sm border-primary status-select" data-item-id="{{ $item->id }}"
                                                {{ !auth()->user()->hasEditAccess() ? 'disabled' : '' }}>
                                            <option selected disabled>Change Status...</option>
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
                                        No items in {{ $statusLabels[$status] }}
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
    // Search
    document.getElementById('searchInput').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr[data-item-id]').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Status change
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            fetch(`/items/${itemId}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ status: this.value })
            }).then(() => location.reload());
        });
    });

    // Storage change
    document.querySelectorAll('.storage-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            fetch(`/items/${itemId}/storage`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ storage_location: this.value })
            }).then(() => {
                this.classList.add('border-success');
                setTimeout(() => this.classList.remove('border-success'), 1500);
            });
        });
    });
</script>
@endpush
@endsection
