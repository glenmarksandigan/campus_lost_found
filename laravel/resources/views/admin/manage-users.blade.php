@extends('layouts.portal')

@section('title', 'Manage Users — FoundIt!')

@section('content')
<div class="page-body p-4">
    <div class="page-header mb-4 p-4 text-white rounded-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>User Management</h2>
                <p class="opacity-75 mb-0">Manage all system users and their roles</p>
            </div>
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus me-2"></i>Add User
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search users..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="1" {{ request('role') == '1' ? 'selected' : '' }}>Student</option>
                        <option value="2" {{ request('role') == '2' ? 'selected' : '' }}>Guard</option>
                        <option value="4" {{ request('role') == '4' ? 'selected' : '' }}>Admin</option>
                        <option value="5" {{ request('role') == '5' ? 'selected' : '' }}>Super Admin</option>
                        <option value="6" {{ request('role') == '6' ? 'selected' : '' }}>Organizer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Student ID</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td class="px-4">{{ $user->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:36px; height:36px; font-size:.8rem;">
                                        {{ strtoupper(substr($user->fname, 0, 1)) }}{{ strtoupper(substr($user->lname, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $user->fname }} {{ $user->lname }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><small>{{ $user->email }}</small></td>
                            <td>{{ $user->student_id ?? '—' }}</td>
                            <td>
                                @php
                                    $roleBadge = match((int)$user->type_id) {
                                        5 => 'bg-purple text-white',
                                        4 => 'bg-primary',
                                        6 => 'bg-success',
                                        2 => 'bg-warning text-dark',
                                        default => 'bg-light text-dark border',
                                    };
                                @endphp
                                <span class="badge {{ $roleBadge }} px-2 py-1">{{ $user->roleName() }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $user->status === 'Active' ? 'bg-success' : ($user->status === 'Pending' ? 'bg-warning text-dark' : 'bg-danger') }} px-2 py-1">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser({{ json_encode($user) }})" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.users.resetPassword', $user) }}" method="POST" onsubmit="return confirm('Reset password for {{ $user->fname }}?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" title="Reset Password"><i class="bi bi-key"></i></button>
                                    </form>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete {{ $user->fname }} {{ $user->lname }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people display-5 d-block mb-2 opacity-25"></i>
                                No users found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $users->links() }}</div>
        </div>
    </div>
</div>

{{-- Add User Modal --}}
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6"><input type="text" name="fname" class="form-control" placeholder="First Name" required></div>
                        <div class="col-6"><input type="text" name="lname" class="form-control" placeholder="Last Name" required></div>
                    </div>
                    <input type="email" name="email" class="form-control mt-3" placeholder="Email Address" required>
                    <input type="text" name="student_id" class="form-control mt-3" placeholder="Student ID (optional)">
                    <input type="text" name="contact_number" class="form-control mt-3" placeholder="Contact Number (optional)">
                    <select name="type_id" class="form-select mt-3" required>
                        <option value="">Select Role...</option>
                        <option value="1">Student</option>
                        <option value="2">Guard</option>
                        <option value="4">Admin</option>
                        <option value="6">SSG Organizer</option>
                    </select>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                        <i class="bi bi-check-circle me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit User Modal --}}
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-warning" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6"><input type="text" name="fname" id="edit_fname" class="form-control" placeholder="First Name" required></div>
                        <div class="col-6"><input type="text" name="lname" id="edit_lname" class="form-control" placeholder="Last Name" required></div>
                    </div>
                    <input type="email" name="email" id="edit_email" class="form-control mt-3" placeholder="Email" required>
                    <select name="type_id" id="edit_type_id" class="form-select mt-3" required>
                        <option value="1">Student</option>
                        <option value="2">Guard</option>
                        <option value="4">Admin</option>
                        <option value="5">Super Admin</option>
                        <option value="6">SSG Organizer</option>
                    </select>
                    <select name="status" id="edit_status" class="form-select mt-3" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editUser(user) {
    document.getElementById('editUserForm').action = `/manage/users/${user.id}`;
    document.getElementById('edit_fname').value = user.fname;
    document.getElementById('edit_lname').value = user.lname;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_type_id').value = user.type_id;
    document.getElementById('edit_status').value = user.status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>
@endpush

@push('styles')
<style>.bg-purple { background: #7c3aed !important; }</style>
@endpush
@endsection
