<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['type_id'] != 4) {
    header("Location: auth.php"); exit;
}
include 'db.php';

// Get all organizers with detailed permissions
$organizers = $pdo->query("
    SELECT id, fname, lname, organizer_role, can_edit, email, contact_number
    FROM users 
    WHERE type_id = 6 
    ORDER BY organizer_role DESC, fname
")->fetchAll();

// Get all tasks
$tasks = $pdo->query("
    SELECT t.*, 
        u1.fname as assigned_to_fname, u1.lname as assigned_to_lname,
        u2.fname as assigned_by_fname, u2.lname as assigned_by_lname
    FROM admin_tasks t
    JOIN users u1 ON t.assigned_to = u1.id
    JOIN users u2 ON t.assigned_by = u2.id
    ORDER BY 
        FIELD(t.status, 'pending', 'in_progress', 'completed', 'cancelled'),
        FIELD(t.priority, 'urgent', 'high', 'normal', 'low'),
        t.created_at DESC
")->fetchAll();

// Stats
$totalTasks     = count($tasks);
$pendingTasks   = count(array_filter($tasks, fn($t) => $t['status'] === 'pending'));
$inProgressTasks= count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
$completedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Task Management – Admin</title>
    <style>
      /* Shared styles are in admin_header.php */
      .stat-num { font-family: 'Inter',sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; }
      .stat-lbl { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
      .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; }

      .task-row { cursor: pointer; transition: background .15s; }
      .task-row:hover { background: #f8fafc; }

      /* ── Card header/body alignment fix ─────────────────────── */
      .admin-card > .card-header { border-bottom: 1px solid #f1f5f9; border-radius: 16px 16px 0 0; }
      .admin-card > .card-body { border-radius: 0 0 16px 16px; }

      /* ── Filter controls ────────────────────────────────────── */
      .task-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
      .task-filters .form-select { min-width: 120px; flex: 1; }
      .task-filters .input-group { min-width: 180px; flex: 2; }

      /* Team Management Styles */
      .team-card { border: none; border-radius: 16px; background: #fff; box-shadow: var(--admin-card-shadow); }
      .team-card > .card-header { border-bottom: 1px solid #f1f5f9; border-radius: 16px 16px 0 0; }
      .organizer-item { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; transition: background .2s; }
      .organizer-item:last-child { border-bottom: none; }
      .organizer-item:hover { background: #f8fafc; }
      .org-av { width: 38px; height: 38px; border-radius: 50%; background: var(--admin-blue); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="container-fluid px-4 mt-4">

  <!-- Page Header -->
  <div class="admin-page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h2 class="fw-bold mb-1">Task Management</h2>
        <p class="mb-0 opacity-75 small">Assign tasks to SSG organizers and track progress</p>
      </div>
      <button class="btn btn-outline-light border-2 fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#createTaskModal" style="border-radius: 12px; background: rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
        <i class="bi bi-plus-circle me-1"></i>Create New Task
      </button>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <?php foreach([
      ['Total Tasks',  $totalTasks,     '#3b82f6','bi-list-task'],
      ['Pending',      $pendingTasks,   '#f59e0b','bi-clock-history'],
      ['In Progress',  $inProgressTasks,'#8b5cf6','bi-arrow-repeat'],
      ['Completed',    $completedTasks, '#22c55e','bi-check-circle'],
    ] as [$lbl,$val,$col,$ico]): ?>
    <div class="col-xl-3 col-md-6">
      <div class="admin-card h-100">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="stat-lbl mb-1"><?= $lbl ?></div>
              <div class="stat-num" style="color:<?= $col ?>"><?= $val ?></div>
            </div>
            <div class="stat-icon" style="background:<?= $col ?>20;color:<?= $col ?>">
              <i class="bi <?= $ico ?>"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div id="taskAlert"></div>

  <div class="row g-4 align-items-start">
    <!-- Tasks Column -->
    <div class="col-lg-8">
      <!-- Tasks Table -->
      <div class="admin-card">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="fw-bold mb-0"><i class="bi bi-list-task me-2" style="color:var(--admin-blue)"></i>All Tasks</h6>
          <div class="task-filters">
            <div class="input-group input-group-sm" style="width:220px">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
              <input type="text" id="taskSearch" class="form-control form-control-sm border-start-0" placeholder="Search tasks..." oninput="filterTasks()">
            </div>
            <select class="form-select form-select-sm" id="filterStatus" onchange="filterTasks()">
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <select class="form-select form-select-sm" id="filterPriority" onchange="filterTasks()">
              <option value="">All Priority</option>
              <option value="urgent">Urgent</option>
              <option value="high">High</option>
              <option value="normal">Normal</option>
              <option value="low">Low</option>
            </select>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 admin-table" id="tasksTable">
              <thead>
                <tr>
                  <th class="ps-4">Task</th>
                  <th>Assigned To</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Due Date</th>
                  <th>Created</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($tasks)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">
                  <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>No tasks yet
                </td></tr>
                <?php else: ?>
                  <?php foreach($tasks as $task): ?>
                  <tr class="task-row" 
                      data-status="<?= $task['status'] ?>" 
                      data-priority="<?= $task['priority'] ?>">
                    <td class="ps-4">
                      <div class="fw-semibold"><?= htmlspecialchars($task['title']) ?></div>
                      <?php if($task['description']): ?>
                      <small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 60)) ?><?= strlen($task['description']) > 60 ? '...' : '' ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:50%;background:var(--admin-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
                          <?= strtoupper(substr($task['assigned_to_fname'], 0, 1)) ?>
                        </div>
                        <div>
                          <div style="font-size:.85rem;font-weight:600"><?= htmlspecialchars($task['assigned_to_fname'].' '.$task['assigned_to_lname']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php 
                        $prioClass = 'bg-status-info';
                        if($task['priority']=='urgent') $prioClass = 'bg-status-danger';
                        if($task['priority']=='high')   $prioClass = 'bg-status-pending';
                        if($task['priority']=='low')    $prioClass = 'bg-status-info opacity-75';
                      ?>
                      <span class="status-badge <?= $prioClass ?>"><?= ucfirst($task['priority']) ?></span>
                    </td>
                    <td>
                      <?php 
                        $statusClass = 'bg-status-info';
                        if($task['status']=='pending')   $statusClass = 'bg-status-pending';
                        if($task['status']=='completed') $statusClass = 'bg-status-success';
                        if($task['status']=='cancelled') $statusClass = 'bg-status-danger opacity-50';
                      ?>
                      <span class="status-badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                    </td>
                    <td>
                      <?php if($task['due_date']): ?>
                      <small><?= date('M d, Y', strtotime($task['due_date'])) ?></small>
                      <?php else: ?>
                      <small class="text-muted">No deadline</small>
                      <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= date('M d, Y', strtotime($task['created_at'])) ?></small></td>
                    <td class="pe-4 text-end">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="viewTask(<?= $task['id'] ?>)" title="View Details">
                          <i class="bi bi-eye"></i>
                        </button>
                        <?php if($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                        <button class="btn btn-outline-danger" onclick="cancelTask(<?= $task['id'] ?>)" title="Cancel Task">
                          <i class="bi bi-x-circle"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /admin-card -->
    </div><!-- /col-lg-8 -->

    <!-- Team Column -->
    <div class="col-lg-4">
      <div class="card team-card shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-people-fill me-2 text-primary"></i>Team Management
          </h6>
          <span class="badge bg-light text-dark border"><?= count($organizers) ?> Members</span>
        </div>
        <div class="card-body p-0">
          <div class="px-3 pb-2">
            <div class="alert alert-info border-0 rounded-3 text-center mb-0" style="font-size: .75rem; padding: 10px;">
              <i class="bi bi-info-circle me-1"></i>
              Manage permissions here. <strong>SSG President</strong> is always exempt.
            </div>
          </div>
          <div style="max-height: 520px; overflow-y: auto;">
            <?php foreach($organizers as $org): 
              $isPres = (strtolower($org['organizer_role']) === 'president');
              $initials = strtoupper(substr($org['fname'],0,1).substr($org['lname'],0,1));
            ?>
            <div class="organizer-item">
              <div class="d-flex align-items-center gap-3">
                <div class="org-av"><?= $initials ?></div>
                <div class="flex-grow-1">
                  <div class="fw-bold" style="font-size: .88rem;">
                    <?= htmlspecialchars($org['fname'].' '.$org['lname']) ?>
                    <?php if($isPres): ?><span class="text-warning small ms-1" title="President"></span><?php endif; ?>
                  </div>
                  <div class="text-muted" style="font-size: .72rem;">
                    <?= $isPres ? 'SSG President' : 'SSG Member' ?>
                  </div>
                </div>
                <div class="form-check form-switch mb-0">
                  <?php if(!$isPres): ?>
                  <input class="form-check-input" type="checkbox" role="switch"
                         <?= $org['can_edit'] ? 'checked' : '' ?>
                         onchange='togglePermission(<?= $org['id'] ?>, this)'
                         id="perm_<?= $org['id'] ?>">
                  <?php else: ?>
                  <span class="badge bg-success bg-opacity-10 text-success border-0 px-2" style="font-size: .65rem;">Exempt</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div><!-- /row -->

</div><!-- /container -->

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Create New Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="createTaskAlert" class="mb-3"></div>
        <form id="createTaskForm">
          <div class="mb-3">
            <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" placeholder="e.g. Publish pending items" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Task details..."></textarea>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Assign To <span class="text-danger">*</span></label>
              <select class="form-select" name="assigned_to" required>
                <option value="">Select organizer...</option>
                <?php foreach($organizers as $org): ?>
                <option value="<?= $org['id'] ?>">
                  <?= htmlspecialchars($org['fname'].' '.$org['lname']) ?>
                  <?= $org['organizer_role'] === 'president' ? ' 👑' : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Task Type</label>
              <select class="form-select" name="task_type">
                <option value="general">General</option>
                <option value="found_item">Found Item</option>
                <option value="lost_report">Lost Report</option>
                <option value="user_approval">User Approval</option>
              </select>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
              <select class="form-select" name="priority" required>
                <option value="normal" selected>Normal</option>
                <option value="low">Low</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Due Date</label>
              <input type="date" class="form-control" name="due_date">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Related ID (optional)</label>
            <input type="number" class="form-control" name="related_id" placeholder="Item or report ID">
            <small class="text-muted">Link this task to a specific item or report</small>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="createTask()">
          <i class="bi bi-plus-circle me-2"></i>Create Task
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
      <div class="modal-header border-0" id="taskDetailHeader" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
        <h5 class="modal-title text-white">
          <i class="bi bi-clipboard-check me-2"></i>Task Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="taskDetailBody">
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script>
function filterTasks() {
  const statusFilter = document.getElementById('filterStatus').value;
  const priorityFilter = document.getElementById('filterPriority').value;
  const searchQuery = (document.getElementById('taskSearch')?.value || '').toLowerCase();
  const rows = document.querySelectorAll('.task-row');

  rows.forEach(row => {
    const status = row.dataset.status;
    const priority = row.dataset.priority;
    const text = row.textContent.toLowerCase();
    const showStatus = !statusFilter || status === statusFilter;
    const showPriority = !priorityFilter || priority === priorityFilter;
    const showSearch = !searchQuery || text.includes(searchQuery);
    row.style.display = (showStatus && showPriority && showSearch) ? '' : 'none';
  });
}

async function createTask() {
  const form = document.getElementById('createTaskForm');
  const div = document.getElementById('createTaskAlert');
  
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  div.innerHTML = `<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Creating task...</div>`;

  const fd = new FormData(form);
  fd.append('action', 'create_task');

  try {
    const res = await fetch('task_actions.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('createTaskModal')).hide();
      setTimeout(() => location.reload(), 1000);
    } else {
      div.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${data.message}</div>`;
    }
  } catch(err) {
    div.innerHTML = `<div class="alert alert-danger">Server error</div>`;
  }
}

async function cancelTask(taskId) {
  showConfirm({
    title: 'Cancel Task?',
    msg: 'Are you sure you want to cancel this task? The assigned organizer will be notified.',
    type: 'danger',
    confirmText: 'Yes, Cancel Task',
    onConfirm: async () => {
      try {
        const fd = new FormData();
        fd.append('action', 'update_task_status');
        fd.append('task_id', taskId);
        fd.append('status', 'cancelled');

        const res = await fetch('task_actions.php', {method:'POST', body:fd});
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast(data.message, 'danger');
        }
      } catch(err) {
        showToast('Server error', 'danger');
      }
    }
  });
}

// Task detail data from PHP
const allTasks = <?= json_encode($tasks) ?>;

function viewTask(taskId) {
    const task = allTasks.find(t => t.id == taskId);
    if (!task) return;

    const priorityColors = { urgent: '#dc2626', high: '#f59e0b', normal: '#3b82f6', low: '#94a3b8' };
    const statusColors = { pending: '#f59e0b', in_progress: '#8b5cf6', completed: '#22c55e', cancelled: '#94a3b8' };
    const headerColors = {
        pending: 'linear-gradient(135deg,#f59e0b,#d97706)',
        in_progress: 'linear-gradient(135deg,#8b5cf6,#6d28d9)',
        completed: 'linear-gradient(135deg,#22c55e,#16a34a)',
        cancelled: 'linear-gradient(135deg,#94a3b8,#64748b)'
    };

    document.getElementById('taskDetailHeader').style.background = headerColors[task.status] || headerColors.pending;

    const dueDate = task.due_date
        ? new Date(task.due_date).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric'})
        : 'No deadline';
    const createdDate = new Date(task.created_at).toLocaleDateString('en-US', {year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit'});

    document.getElementById('taskDetailBody').innerHTML = `
        <div class="mb-3">
            <h5 class="fw-bold mb-1">${task.title}</h5>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge-priority badge-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span>
                <span class="badge-status badge-${task.status}">${task.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</span>
            </div>
        </div>

        ${task.description ? `
        <div class="mb-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px">Description</div>
            <p class="mb-0" style="font-size:.88rem;white-space:pre-line">${task.description}</p>
        </div>` : ''}

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px">
                        <i class="bi bi-person-fill me-1"></i>Assigned To
                    </div>
                    <div class="fw-bold" style="font-size:.88rem">${task.assigned_to_fname} ${task.assigned_to_lname}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px">
                        <i class="bi bi-person me-1"></i>Assigned By
                    </div>
                    <div class="fw-bold" style="font-size:.88rem">${task.assigned_by_fname} ${task.assigned_by_lname}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px">
                        <i class="bi bi-calendar-event me-1"></i>Due Date
                    </div>
                    <div class="fw-semibold" style="font-size:.88rem">${dueDate}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px">
                        <i class="bi bi-clock me-1"></i>Created
                    </div>
                    <div class="fw-semibold" style="font-size:.88rem">${createdDate}</div>
                </div>
            </div>
        </div>

        ${task.related_id ? `
        <div class="p-2 rounded text-center" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:.82rem">
            <i class="bi bi-link-45deg me-1 text-primary"></i>Linked to ID: <strong>#${task.related_id}</strong>
        </div>` : ''}
    `;

    new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
}

function togglePermission(userId, checkbox) {
    const canEdit = checkbox.checked ? 1 : 0;
    checkbox.disabled = true;
    
    fetch('update_organizer_permission.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `user_id=${userId}&can_edit=${canEdit}`
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            showToast('Error updating permission: ' + (data.error || 'Unknown error'), 'danger');
            checkbox.checked = !checkbox.checked;
        } else {
            showToast('Permission updated successfully', 'success');
        }
    })
    .catch(() => {
        showToast('Server error occurred.', 'danger');
        checkbox.checked = !checkbox.checked;
    })
    .finally(() => {
        checkbox.disabled = false;
    });
}
</script>
</div><!-- /admin-main-content -->
</body>
</html>