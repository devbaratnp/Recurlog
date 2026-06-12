<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$db = getDB();

$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$editId) { header('Location: onetime-task.php'); exit; }

$stmt = $db->prepare("SELECT t.*, c.name AS customer_name, s.name AS staff_name, sv.service_for, sv.problem AS service_problem FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id WHERE t.id = ?");
$stmt->bind_param('i', $editId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
if (!$task) { header('Location: onetime-task.php'); exit; }

$staffList = $db->query("SELECT id, name FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCsrfToken();
  $title = trim($_POST['title'] ?? '');
  $problem = trim($_POST['problem'] ?? '');
  $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
  $scheduledDate = $_POST['scheduled_date'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $notes = trim($_POST['notes'] ?? '');
  $error = null;

  if (!$title) $error = 'Title is required.';

  if (!$error) {
    $db->begin_transaction();
    try {
      $stmt = $db->prepare("UPDATE fscrm_tasks SET title=?, assigned_to=?, scheduled_date=?, status=?, notes=? WHERE id=?");
      $stmt->bind_param('sisssi', $title, $assignedTo, $scheduledDate, $status, $notes, $editId);
      $stmt->execute();
      // Also update problem on the linked service if it exists
      if ($task['service_id'] && $problem) {
        $pStmt = $db->prepare("UPDATE fscrm_services SET problem=? WHERE id=?");
        $pStmt->bind_param('si', $problem, $task['service_id']);
        $pStmt->execute();
      }
      $stmt->execute();
      $db->commit();
      setFlash('Task "' . $title . '" updated successfully');
      header('Location: task-detail.php?id=' . $editId);
      exit;
    } catch (Exception $e) {
      $db->rollback();
      $error = 'Failed to update task. Please try again.';
    }
  }
}

$pageTitle = 'Edit Task';
?><?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-content">
  <header class="page-header">
    <div class="page-header-inner">
      <div class="flex items-center gap-2">
        <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <a href="task-detail.php?id=<?= $editId ?>" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
          <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="page-title">Edit Task</h1>
      </div>
    </div>
  </header>

  <div class="p-4 md:p-6 max-w-3xl mx-auto">

    <p class="text-gray-500 text-sm mb-4">Update task details</p>

<?php if (!empty($error)): ?>
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

    <form method="POST" action=""><?= csrfHiddenField() ?>
    <div class="card p-5 sm:p-8 space-y-6">

      <div>
        <label for="task-customer" class="form-label">Customer</label>
        <input type="text" id="task-customer" readonly value="<?= htmlspecialchars($task['customer_name'] ?: '—') ?>" class="form-input bg-gray-50 cursor-not-allowed">
      </div>

      <div>
        <label for="task-title" class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" id="task-title" name="title" value="<?= htmlspecialchars($task['title']) ?>" class="form-input" maxlength="255">
      </div>

      <div>
        <label for="task-problem" class="form-label">Problem Description</label>
        <textarea id="task-problem" name="problem" rows="3" class="form-textarea" placeholder="Describe the issue..." maxlength="1000"><?= htmlspecialchars($task['service_problem'] ?? '') ?></textarea>
      </div>

      <div>
        <label for="task-status" class="form-label">Status</label>
        <select id="task-status" name="status" class="form-select">
          <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="missed" <?= $task['status'] === 'missed' ? 'selected' : '' ?>>Missed</option>
        </select>
      </div>

      <div>
        <label for="task-date" class="form-label">Scheduled Date</label>
        <input type="date" id="task-date" name="scheduled_date" value="<?= htmlspecialchars($task['scheduled_date']) ?>" class="form-input max-w-xs">
      </div>

      <div>
        <label for="task-staff" class="form-label">Assign To</label>
        <select id="task-staff" name="assigned_to" class="form-select">
          <option value="">Unassigned</option>
          <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $task['assigned_to'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="task-notes" class="form-label">Notes</label>
        <textarea id="task-notes" name="notes" rows="3" class="form-textarea" maxlength="1000"><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
      </div>

    </div>

    <div class="mt-6 flex gap-3">
      <a href="task-detail.php?id=<?= $editId ?>" class="btn btn-md btn-secondary flex-1 md:flex-none">Cancel</a>
      <button type="submit" class="btn btn-md btn-primary flex-1 md:flex-none brand-glow">
        <i data-lucide="save" class="w-4 h-4"></i> Update Task
      </button>
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200">
      <button type="button" id="edit-delete-btn" class="w-full md:w-auto px-6 py-3 border border-danger text-danger text-sm font-semibold rounded-lg hover:bg-red-50 transition-colors flex items-center justify-center gap-2" data-task-id="<?= $editId ?>" data-task-title="<?= htmlspecialchars($task['title']) ?>" data-task-customer="<?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?>" data-task-status="<?= $task['status'] ?>" data-task-date="<?= $task['scheduled_date'] ?>">
        <i data-lucide="trash-2" class="w-4 h-4"></i> Delete Task
      </button>
    </div>
    </form>

  </div>
</div>
<script>
  // ========== DELETE MODAL ==========
  document.addEventListener('DOMContentLoaded', function () {
    var deleteBtn = document.getElementById('edit-delete-btn');
    if (!deleteBtn) return;
    deleteBtn.addEventListener('click', function () {
      var id = parseInt(this.dataset.taskId, 10);
      if (!id) return;
      document.getElementById('del-edit-title').textContent = this.dataset.taskTitle;
      document.getElementById('del-edit-customer').textContent = this.dataset.taskCustomer;
      document.getElementById('del-edit-status').textContent = this.dataset.taskStatus;
      document.getElementById('del-edit-date').textContent = this.dataset.taskDate;
      document.getElementById('edit-delete-modal').style.display = 'flex';
    });

    document.getElementById('edit-del-confirm-btn').addEventListener('click', async function () {
      var id = parseInt(deleteBtn.dataset.taskId, 10);
      if (!id) return;
      var btn = this;
      btn.disabled = true; btn.textContent = 'Deleting...';
      try {
        var res = await fetch('../api/tasks.php?id=' + id, { method: 'DELETE' });
        var data = await res.json();
        if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete'; return; }
        showToast('Task deleted successfully', 'success');
        window.location.href = 'onetime-task.php';
      } catch (e) { showToast('Network error', 'error'); btn.disabled = false; btn.textContent = 'Delete'; }
    });
  });

  function closeEditDeleteModal() {
    document.getElementById('edit-delete-modal').style.display = 'none';
  }
</script>

<!-- DELETE CONFIRM MODAL -->
<div id="edit-delete-modal" class="modal-overlay" style="display:none">
  <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
      <button type="button" onclick="closeEditDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
    <div class="text-sm text-gray-600 mb-1 space-y-1">
      <p><span class="font-medium">Task:</span> <span id="del-edit-title"></span></p>
      <p><span class="font-medium">Customer:</span> <span id="del-edit-customer"></span></p>
      <p><span class="font-medium">Status:</span> <span id="del-edit-status"></span></p>
      <p><span class="font-medium">Date:</span> <span id="del-edit-date"></span></p>
    </div>
    <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
    <div class="flex gap-3">
      <button type="button" onclick="closeEditDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
      <button type="button" id="edit-del-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
    </div>
  </div>
</div>

</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
