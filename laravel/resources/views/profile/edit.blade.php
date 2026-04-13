@extends('layouts.portal')

@section('title', 'My Profile – FoundIt!')

@push('styles')
<style>
    .page-wrap { max-width: 860px; margin: 20px auto; padding: 0 16px 60px; }

    .profile-hero {
        background: linear-gradient(135deg, #0b1d3a, #1e3a6e);
        border-radius: 18px; padding: 28px 30px; display: flex; align-items: center; gap: 22px;
        margin-bottom: 24px; color: #fff; position: relative; overflow: hidden;
    }
    .profile-hero::before {
        content: ''; position: absolute; top: -30px; right: -30px;
        width: 200px; height: 200px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255,204,0,0.1), transparent 70%);
    }
    .avatar {
        width: 76px; height: 76px; border-radius: 50%;
        background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
        font-size: 2rem; flex-shrink: 0;
        border: 3px solid transparent;
        background-image: linear-gradient(rgba(255,255,255,.18), rgba(255,255,255,.18)), linear-gradient(135deg, #ffcc00, #0d6efd, #ffcc00);
        background-origin: border-box;
        background-clip: padding-box, border-box;
        animation: avatarRing 4s linear infinite;
    }
    @keyframes avatarRing { 0%{filter:hue-rotate(0deg)} 100%{filter:hue-rotate(360deg)} }
    .profile-hero h4 { font-family:'Syne',sans-serif; font-weight:800; font-size:1.25rem; margin:0 0 3px; position:relative; }
    .profile-hero p { color:rgba(255,255,255,.7); font-size:.85rem; margin:0; position:relative; }
    .type-badge { display:inline-block; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:20px; padding:3px 12px; font-size:.75rem; font-weight:600; margin-top:6px; }

    .stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
    .stat-card {
        background:#fff; border-radius:14px; padding:18px; text-align:center;
        box-shadow:0 2px 12px rgba(0,0,0,.06); border:1px solid #f1f5f9;
        transition:all .3s; opacity:0; transform:translateY(16px);
        animation:statReveal .5s ease forwards;
    }
    .stat-card:nth-child(1){animation-delay:0s} .stat-card:nth-child(2){animation-delay:.1s} .stat-card:nth-child(3){animation-delay:.2s}
    @keyframes statReveal { to{opacity:1;transform:translateY(0)} }
    .stat-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
    .stat-num { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; color:#0b1d3a; line-height:1; }
    .stat-label { font-size:.78rem; color:#94a3b8; font-weight:500; margin-top:4px; }
    .stat-icon { font-size:1.3rem; margin-bottom:8px; }

    .section-label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; margin:16px 0 10px; display:flex; align-items:center; gap:8px; }
    .section-label::after { content:''; flex:1; height:1px; background:#e5e7eb; }

    .locked-field { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 13px; font-size:.875rem; color:#64748b; display:flex; align-items:center; gap:8px; }
    .locked-field i { color:#94a3b8; }

    .btn-save { background:#0d6efd; color:#fff; border:none; border-radius:10px; padding:11px 28px; font-weight:700; font-size:.9rem; transition:all .2s; cursor:pointer; }
    .btn-save:hover { background:#0b5ed7; transform:translateY(-1px); }
    .btn-save-teal { background:#0d9488; }
    .btn-save-teal:hover { background:#0f766e; }

    .input-group .btn-eye { border:1.5px solid #e2e8f0; border-left:none; border-radius:0 10px 10px 0; background:#f8fafc; color:#64748b; padding:0 13px; cursor:pointer; }

    @media (max-width:600px) {
        .profile-hero { flex-direction:column; text-align:center; }
        .stat-row { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    <!-- Hero -->
    <div class="profile-hero">
        <div class="avatar"><i class="bi bi-person-fill"></i></div>
        <div style="position:relative">
            <h4>{{ $user->fname }} {{ $user->mname ? $user->mname.' ' : '' }}{{ $user->lname }}</h4>
            <p>{{ $user->email }}</p>
            <span class="type-badge">
                <i class="bi bi-{{ (int)$user->type_id === 1 ? 'mortarboard' : 'person-badge' }} me-1"></i>
                {{ (int)$user->type_id === 1 ? 'Student' : 'Staff / Employee' }}
            </span>
        </div>
    </div>

    <!-- Activity Stats -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="stat-icon" style="color:#0d6efd"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-num" data-count="{{ $countFound }}">0</div>
            <div class="stat-label">Items Reported</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#10b981"><i class="bi bi-hand-index-thumb-fill"></i></div>
            <div class="stat-num" data-count="{{ $countClaimed }}">0</div>
            <div class="stat-label">Claims Made</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#f59e0b"><i class="bi bi-chat-dots-fill"></i></div>
            <div class="stat-num" data-count="{{ $countMsgs }}">0</div>
            <div class="stat-label">Messages Sent</div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="card border-0 shadow-sm" style="border-radius:16px; opacity:0; transform:translateY(16px); animation:statReveal .5s .3s ease forwards;">
        <div class="card-header bg-transparent border-bottom" style="padding:16px 22px 12px; font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:#0b1d3a; display:flex; align-items:center; gap:8px;">
            <i class="bi bi-pencil-square"></i> Edit My Profile
        </div>
        <div class="card-body" style="padding:22px">
            @if(session('success'))
            <div class="alert alert-success border-0 rounded-3" style="font-size:.85rem"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
            @endif

            <!-- Locked Fields -->
            <div class="section-label">Cannot be changed — contact admin</div>
            <div class="row g-3 mb-2">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" style="font-size:.85rem"><i class="bi bi-envelope me-1"></i>School Email</label>
                    <div class="locked-field"><i class="bi bi-lock-fill"></i>{{ $user->email }}</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" style="font-size:.85rem"><i class="bi bi-card-text me-1"></i>{{ (int)$user->type_id === 1 ? 'Student ID' : 'Employee ID' }}</label>
                    <div class="locked-field"><i class="bi bi-lock-fill"></i>{{ $user->student_id ?? 'N/A' }}</div>
                </div>
            </div>

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf @method('PATCH')

                <div class="section-label">Name</div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold" style="font-size:.85rem">First Name <span class="text-danger">*</span></label>
                        <input name="fname" type="text" class="form-control" value="{{ old('fname', $user->fname) }}" required style="border-radius:10px">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Middle Name</label>
                        <input name="mname" type="text" class="form-control" value="{{ old('mname', $user->mname) }}" style="border-radius:10px">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Last Name <span class="text-danger">*</span></label>
                        <input name="lname" type="text" class="form-control" value="{{ old('lname', $user->lname) }}" required style="border-radius:10px">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label fw-semibold" style="font-size:.85rem">Contact Number</label>
                        <input name="contact_number" type="tel" class="form-control" value="{{ old('contact_number', $user->contact_number) }}" placeholder="09xx-xxx-xxxx" style="border-radius:10px">
                    </div>
                </div>

                <button type="submit" class="btn-save mt-2"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card border-0 shadow-sm" style="border-radius:16px; opacity:0; transform:translateY(16px); animation:statReveal .5s .4s ease forwards;">
        <div class="card-header bg-transparent border-bottom" style="padding:16px 22px 12px; font-family:'Syne',sans-serif; font-weight:700; font-size:.95rem; color:#0b1d3a; display:flex; align-items:center; gap:8px;">
            <i class="bi bi-shield-lock"></i> Change Password
        </div>
        <div class="card-body" style="padding:22px">
            <form action="{{ route('profile.update') }}" method="POST" style="max-width:400px">
                @csrf @method('PATCH')
                <input type="hidden" name="_password_change" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Current Password <span class="text-danger">*</span></label>
                    <input name="current_password" type="password" class="form-control" placeholder="••••••••" required style="border-radius:10px">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">New Password <span class="text-danger">*</span></label>
                    <input name="password" type="password" class="form-control" placeholder="At least 6 characters" required style="border-radius:10px">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Confirm New Password <span class="text-danger">*</span></label>
                    <input name="password_confirmation" type="password" class="form-control" placeholder="Repeat new password" required style="border-radius:10px">
                </div>
                <button type="submit" class="btn-save btn-save-teal"><i class="bi bi-key me-2"></i>Update Password</button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-num[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        let current = 0;
        const step = Math.ceil(target / 40);
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current;
            if (current >= target) clearInterval(timer);
        }, 25);
    });
});
</script>
@endpush
