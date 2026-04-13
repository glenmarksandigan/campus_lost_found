@php
    $type_id = auth()->user()->type_id;
    $role = match($type_id) {
        2 => 'guard',
        4 => 'admin',
        5 => 'superadmin',
        6 => 'organizer',
        default => 'user'
    };
    
    $brand_name = match($type_id) {
        2 => 'Guard Portal',
        4 => 'Admin Panel',
        5 => 'Super Admin',
        6 => 'SSG Organizer',
        default => 'FoundIt!'
    };

    $accent_color = match($role) {
        'guard' => '#0d9488',
        'organizer' => '#0d9488',
        'superadmin' => '#7c3aed',
        default => '#3b82f6'
    };
@endphp

<aside class="admin-sidebar" id="adminSidebar" style="background: #0f172a; width: 260px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1040; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.06);">
    <!-- Brand -->
    <div class="sidebar-brand" style="padding: 20px 18px; border-bottom: 1px solid rgba(255,255,255,0.06); min-height: 72px; display: flex; align-items: center; gap: 12px;">
        <i class="bi {{ $role === 'guard' ? 'bi-shield-check' : ($role === 'superadmin' ? 'bi-eye-fill' : 'bi-people-fill') }}" style="font-size:1.3rem; color:{{ $accent_color }};"></i>
        <div class="sidebar-brand-text">
            <div class="brand-name" style="color: #fff; font-weight: 800; font-size: 0.95rem;">{{ $brand_name }}</div>
            <div class="brand-sub" style="color: {{ $accent_color }}; font-size: 0.62rem; font-weight: 700; text-transform: uppercase;">BISU CANDIJAY</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav" style="flex: 1; overflow-y: auto; padding: 8px 10px;">
        <div class="sidebar-section-label" style="padding: 20px 22px 8px; font-size: 0.62rem; color: rgba(255,255,255,0.25); text-transform: uppercase; font-weight: 700;">Main</div>
        
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') && !request('section') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
            <i class="bi bi-grid-1x2-fill"></i>
            <span class="link-text">Dashboard</span>
        </a>

        @if(in_array($type_id, [2, 4, 5, 6]))
            <div class="sidebar-section-label" style="padding: 20px 22px 8px; font-size: 0.62rem; color: rgba(255,255,255,0.25); text-transform: uppercase; font-weight: 700;">Management</div>
            
            <a href="{{ route('items.manage') }}" class="sidebar-link {{ request()->routeIs('items.manage') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-box-seam"></i>
                <span class="link-text">Found Items</span>
            </a>

            <a href="{{ route('lost-reports.manage') }}" class="sidebar-link {{ request()->routeIs('lost-reports.manage') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-search"></i>
                <span class="link-text">Lost Reports</span>
            </a>
        @endif

        @if(in_array($type_id, [4, 5]))
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-people"></i>
                <span class="link-text">Manage Users</span>
            </a>

            <a href="{{ route('dashboard', ['section' => 'reports']) }}" class="sidebar-link {{ request('section') == 'reports' ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="link-text">System Reports</span>
            </a>

            <a href="{{ route('messages.index') }}" class="sidebar-link {{ request()->routeIs('messages.*') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-chat-square-text"></i>
                <span class="link-text">User Conversations</span>
            </a>

            <div class="sidebar-section-label" style="padding: 20px 22px 8px; font-size: 0.62rem; color: rgba(255,255,255,0.25); text-transform: uppercase; font-weight: 700;">Log</div>

            <a href="{{ route('dashboard', ['section' => 'activity']) }}" class="sidebar-link {{ request('section') == 'activity' ? 'active' : '' }}" style="display: flex; align-items: center; gap: 14px; padding: 11px 14px; color: rgba(255,255,255,0.65); text-decoration: none; border-radius: 10px; font-size: 0.86rem;">
                <i class="bi bi-activity"></i>
                <span class="link-text">Activity Log</span>
            </a>
        @endif
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer" style="padding: 14px 10px; border-top: 1px solid rgba(255,255,255,0.06);">
        <div class="sidebar-user-info" style="display: flex; align-items: center; gap: 10px; padding: 8px 14px; border-radius: 10px; background: rgba(255,255,255,0.04); margin-bottom: 8px;">
            <div class="sidebar-user-avatar" style="width: 34px; height: 34px; border-radius: 50%; background: {{ $accent_color }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700;">
                {{ strtoupper(substr(auth()->user()->fname, 0, 1)) }}
            </div>
            <div>
                <div class="sidebar-user-name" style="color: #fff; font-weight: 600; font-size: 0.82rem;">{{ auth()->user()->fname }}</div>
                <div class="sidebar-user-role" style="color: rgba(255,255,255,0.45); font-size: 0.68rem;">{{ ucfirst($role) }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 9px 14px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.15); color: #f87171; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">
                <i class="bi bi-box-arrow-left"></i>
                <span>Log Out</span>
            </button>
        </form>
    </div>
</aside>

<style>
    .sidebar-link.active {
        background: rgba(255,255,255,0.08);
        color: #fff !important;
        font-weight: 600;
    }
    .sidebar-link:hover {
        background: rgba(255,255,255,0.04);
        color: #fff !important;
    }
</style>
