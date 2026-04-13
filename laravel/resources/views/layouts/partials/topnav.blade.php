<style>
    .navbar { 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: all 0.35s ease;
    }
    .navbar.scrolled {
        background-color: rgba(0, 35, 70, 0.98) !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    }
    .bisu-logo { height: 44px; width: auto; margin-right: 10px; object-fit: contain; }
    .nav-link { 
        font-weight: 500; 
        transition: all 0.3s ease;
        padding: 8px 16px !important;
        font-size: 0.9rem;
    }
    .nav-link:hover { color: #ffcc00 !important; }
    .nav-link.active { color: #ffcc00 !important; font-weight: 700; }
    
    /* Unread badge pulse */
    .badge-pulse {
        animation: badgePulse 2s ease-in-out infinite;
    }
    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
    }

    .user-avatar {
        width: 37px; height: 37px; 
        background: linear-gradient(135deg, #ffcc00, #e8b800); 
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #003366; margin-right: 10px;
        box-shadow: 0 2px 8px rgba(255,204,0,0.3);
        transition: all 0.3s;
    }
    .dropdown-menu {
        border: none; box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18); border-radius: 14px;
        padding: 8px;
        animation: dropdownFade 0.25s ease;
    }
    @keyframes dropdownFade {
        from { opacity: 0; transform: translateY(-10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
</style>

<nav class="navbar navbar-expand-lg navbar-dark shadow fixed-top" style="background-color: rgba(0, 51, 102, 0.95) !important;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <img src="{{ asset('uploads/BISU-LOGO.png') }}" alt="BISU Logo" class="bisu-logo">
            <svg class="brand-logo-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" style="height: 32px; width: 32px; margin-right: 10px;">
                <circle cx="20" cy="20" r="14" stroke="#ffcc00" stroke-width="3.5"/>
                <line x1="30" y1="30" x2="42" y2="42" stroke="#ffcc00" stroke-width="3.5" stroke-linecap="round"/>
                <polyline points="13,20 18,25 27,15" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="brand-text">
                <div class="brand-name" style="color: #fff; font-weight: 800;">Found<span style="color: #ffcc00;">It!</span></div>
                <div class="brand-sub" style="font-size: 0.65rem; color: rgba(255,255,255,0.55);">BISU Candijay Campus</div>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('items.create') ? 'active' : '' }}" href="{{ route('items.create') }}">
                        <i class="bi bi-check-circle"></i> Report Found
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 {{ request()->routeIs('lost-reports.create') ? 'active' : '' }}" href="{{ route('lost-reports.create') }}">
                        <i class="bi bi-exclamation-circle"></i> Report Lost
                    </a>
                </li>

                @auth
                <li class="nav-item">
                    <a class="nav-link px-3 position-relative {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                        <i class="bi bi-inbox-fill"></i> Inbox
                        @if(isset($unreadCount) && $unreadCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-pulse" style="font-size:.6rem; margin-top: 8px; margin-left: -5px;">
                                {{ $unreadCount }}
                            </span>
                        @endif
                    </a>
                </li>

                <li class="nav-item" style="border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px; margin-left: 10px;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->fname, 0, 1)) }}
                        </div>
                        <div class="dropdown">
                            <button class="nav-link dropdown-toggle ps-0" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; background: none;">
                                <span style="font-size: 0.95rem;">{{ auth()->user()->fname }} {{ auth()->user()->lname }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-circle me-2"></i>My Profile</a></li>
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#myReportsModal">
                                        <i class="bi bi-folder2-open me-2"></i>My Reports
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item logout-btn text-danger" style="border: none; background: none; width: 100%; text-align: left;">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
                @else
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="btn btn-warning fw-bold rounded-pill px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </a>
                </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

@auth
<!-- My Reports Modal -->
<div class="modal fade" id="myReportsModal" tabindex="-1" aria-labelledby="myReportsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold fs-4 mt-2 ms-2" id="myReportsModalLabel">
                    <i class="bi bi-person-badge text-primary me-2"></i>My Submissions
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th class="border-0">ITEM</th>
                                <th class="border-0">PLACE</th>
                                <th class="border-0">STATUS</th>
                                <th class="border-0">DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($myItems) && $myItems->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="bi bi-clipboard-x text-muted opacity-25" style="font-size: 3rem;"></i>
                                        <p class="mt-2 text-muted">No reports found under your account.</p>
                                    </td>
                                </tr>
                            @elseif(isset($myItems))
                                @foreach ($myItems as $item)
                                <tr class="hover-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $item->image_path ? asset('storage/' . $item->image_path) : 'https://placehold.co/400x300?text=No+Image' }}" 
                                                 class="rounded-3 shadow-sm me-3" style="width: 45px; height: 45px; object-fit: cover;">
                                            <span class="fw-semibold text-dark">{{ $item->item_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ $item->location }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $isFoundFound = $item->report_type == 'Found';
                                            $status = $item->status;
                                            
                                            $badgeClass = 'bg-secondary-subtle text-secondary';
                                            $dotClass = 'bg-secondary';

                                            if ($isFoundFound) {
                                                $badgeClass = ($status == 'Published') ? 'bg-success-subtle text-success' : (($status == 'Claiming') ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary');
                                                $dotClass = ($status == 'Published') ? 'bg-success' : (($status == 'Claiming') ? 'bg-warning' : 'bg-primary');
                                            } else {
                                                $badgeClass = ($status == 'Lost') ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
                                                $dotClass = ($status == 'Lost') ? 'bg-danger' : 'bg-success';
                                            }
                                        @endphp
                                        <div class="mb-1">
                                            <span class="badge {{ $isFoundFound ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' }} border-0 rounded-pill" style="font-size: 0.6rem;">{{ $item->report_type }}</span>
                                        </div>
                                        <span class="badge {{ $badgeClass }} border px-2 py-1 rounded-pill d-inline-flex align-items-center">
                                            <span class="status-dot {{ $dotClass }} me-2"></span>{{ $status }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ \Carbon\Carbon::parse($item->created_at)->format('M d') }}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- My Claims -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="bi bi-hand-index-thumb text-primary me-2"></i>My Claims
                        <span class="badge bg-light text-dark border ms-2 small" style="font-size: .7rem;">{{ isset($myClaims) ? $myClaims->count() : 0 }}</span>
                    </h6>
                    
                    @if(isset($myClaims) && $myClaims->isEmpty())
                        <div class="text-center py-4 bg-light rounded-4">
                            <p class="text-muted mb-0 small">You haven't claimed any items yet.</p>
                        </div>
                    @elseif(isset($myClaims))
                        <div class="row g-3">
                            @foreach ($myClaims as $claim)
                                @php
                                    $cStatus = $claim->status ?? 'pending';
                                    $badgeClass = 'bg-warning-subtle text-warning';
                                    $icon = 'bi-hourglass-split';
                                    
                                    if ($cStatus === 'verified') {
                                        $badgeClass = 'bg-success-subtle text-success';
                                        $icon = 'bi-check-circle-fill';
                                    } elseif ($cStatus === 'rejected') {
                                        $badgeClass = 'bg-danger-subtle text-danger';
                                        $icon = 'bi-x-circle-fill';
                                    }
                                @endphp
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4 hover-row h-100">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="{{ optional($claim->item)->image_path ? asset('storage/' . $claim->item->image_path) : 'https://placehold.co/400x300?text=No+Image' }}" 
                                                 class="rounded-3 shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="fw-bold text-dark text-truncate" style="font-size:.9rem;">{{ optional($claim->item)->item_name }}</div>
                                                <div class="d-flex align-items-center justify-content-between mt-1">
                                                    <span class="badge {{ $badgeClass }} border px-2 py-1 rounded-pill d-inline-flex align-items-center" style="font-size:.7rem;">
                                                        <i class="bi {{ $icon }} me-1"></i>{{ ucfirst($cStatus) }}
                                                    </span>
                                                    <small class="text-muted" style="font-size:.7rem;">{{ $claim->created_at->format('M d') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer border-0 bg-light-subtle rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endauth

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 30) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }, { passive: true });
        }
    });
</script>
