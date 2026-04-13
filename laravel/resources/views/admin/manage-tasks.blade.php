@extends('layouts.portal')

@section('title', 'Task Management — FoundIt!')

@section('content')
<div class="page-body p-4">
    <div class="page-header mb-4 p-4 text-white rounded-4" style="background: linear-gradient(135deg, #0f4c81 0%, #0ea5e9 100%);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="bi bi-clipboard2-check me-2"></i>Task Management</h2>
                <p class="opacity-75 mb-0">Assign and track tasks for SSG organizers</p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <button class="btn btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="bi bi-plus-circle me-2"></i>Assign Task
            </button>
            @endif
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
                            <th class="px-4">Task</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $task)
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold">{{ $task->title }}</div>
                                @if($task->description)
                                <small class="text-muted">{{ Str::limit($task->description, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $task->assignedTo?->fname }} {{ $task->assignedTo?->lname }}</td>
                            <td>
                                @php
                                    $priorityBadge = match($task->priority) {
                                        'urgent' => 'bg-danger',
                                        'high' => 'bg-warning text-dark',
                                        'normal' => 'bg-primary',
                                        default => 'bg-light text-dark border',
                                    };
                                @endphp
                                <span class="badge {{ $priorityBadge }} px-2 py-1">{{ ucfirst($task->priority) }}</span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select" data-task-id="{{ $task->id }}" style="width: 130px;">
                                    @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <small class="text-muted">{{ $task->due_date?->format('M d, Y') ?? '—' }}</small>
                            </td>
                            <td class="text-end px-4">
                                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?')" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard2 display-5 d-block mb-2 opacity-25"></i>
                                No tasks yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Task Modal --}}
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold"><i class="bi bi-clipboard2-plus me-2"></i>Assign New Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <input type="text" name="title" class="form-control mb-3" placeholder="Task Title" required>
                    <textarea name="description" class="form-control mb-3" rows="3" placeholder="Description (optional)"></textarea>
                    <select name="assigned_to" class="form-select mb-3" required>
                        <option value="">Assign to...</option>
                        @foreach($organizers as $org)
                        <option value="{{ $org->id }}">{{ $org->fname }} {{ $org->lname }}</option>
                        @endforeach
                    </select>
                    <div class="row g-3">
                        <div class="col-6">
                            <select name="priority" class="form-select" required>
                                <option value="normal" selected>Normal Priority</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="date" name="due_date" class="form-control" placeholder="Due Date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                        <i class="bi bi-send me-2"></i>Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', function() {
            fetch(`/tasks/${this.dataset.taskId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ status: this.value })
            }).then(() => {
                this.classList.add('border-success');
                setTimeout(() => this.classList.remove('border-success'), 1500);
            });
        });
    });
</script>
@endpush
@endsection
