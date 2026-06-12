<?php
$pageTitle = 'Tasks';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notification_helper.php';
requireAuth();
$db = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

function statusPill($status) {
  $labels = ['pending'=>'Pending','completed'=>'Completed','missed'=>'Missed'];
  $s = $status ?: 'pending';
  return '<span class="badge badge-' . $s . '">' . ($labels[$s] ?? 'Pending') . '</span>';
}

function formatRelative($date) {
  if (!$date) return '';
  $d = new DateTime($date);
  $now = new DateTime();
  $now->setTime(0, 0, 0);
  $d->setTime(0, 0, 0);
  $diff = (int) $now->diff($d)->format('%r%a');
  if ($diff === 0) return 'Today';
  if ($diff === -1) return 'Yesterday';
  if ($diff === 1) return 'Tomorrow';
  if ($diff > 1 && $diff <= 7) return 'In ' . $diff . ' days';
  if ($diff < 0 && $diff >= -7) return abs($diff) . ' days ago';
  return $d->format('M j, Y');
}

// Handle POST (complete task)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') { requireCsrfToken();
  $taskId = intval($_POST['task_id'] ?? 0);
  $compDate = $_POST['completed_date'] ?? '';
  $compNotes = trim($_POST['notes'] ?? '');

  if ($taskId && $compDate) {
    $stmt = $db->prepare("UPDATE fscrm_tasks SET status='completed', completed_date=?, notes = CASE WHEN notes IS NULL OR notes = '' THEN ? ELSE CONCAT(notes, '\n', ?) END WHERE id=?");
    $stmt->bind_param('sssi', $compDate, $compNotes, $compNotes, $taskId);
    $stmt->execute();

    // Notification
    $tStmt = $db->prepare("SELECT title, customer_id FROM fscrm_tasks WHERE id = ?");
    $tStmt->bind_param('i', $taskId);
    $tStmt->execute();
    $tRow = $tStmt->get_result()->fetch_assoc();
    if ($tRow) {
      $custStmt = $db->prepare("SELECT name FROM fscrm_customers WHERE id = ?");
      $custStmt->bind_param('i', $tRow['customer_id']);
      $custStmt->execute();
      $cRow = $custStmt->get_result()->fetch_assoc();
      $notifText = 'Task "' . $tRow['title'] . '" completed for ' . ($cRow ? $cRow['name'] : 'customer');
      createNotification($db, $notifText, 'task', $taskId);
      setFlash('Task "' . $tRow['title'] . '" marked as completed');
    }
  }
  header('Location: tasks.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
  exit;
}

// Determine current tab
$validTabs = ['today', 'upcoming', 'missed'];
$currentTab = $_GET['status'] ?? 'today';
if (!in_array($currentTab, $validTabs)) $currentTab = 'today';

$today = date('Y-m-d');

// Build query
$where = '';
$params = [];
$types = '';

if ($currentTab === 'today') {
  $where = 'WHERE t.scheduled_date = ?';
  $params[] = $today;
  $types = 's';
} elseif ($currentTab === 'upcoming') {
  $where = 'WHERE t.scheduled_date > ? AND t.status = ?';
  $params[] = $today;
  $params[] = 'pending';
  $types = 'ss';
} elseif ($currentTab === 'missed') {
  $where = 'WHERE t.status = ?';
  $params[] = 'missed';
  $types = 's';
}

// Handle drill-down filter params
$filterLabel = null;
if (isset($_GET['filter_status'])) {
  $fs = $_GET['filter_status'];
  if ($fs === 'today') {
    $where = 'WHERE t.scheduled_date = ?';
    $params = [$today]; $types = 's';
    $filterLabel = 'Today\'s Tasks';
  } elseif ($fs === 'missed') {
    $where = 'WHERE t.status = ?';
    $params = ['missed']; $types = 's';
    $filterLabel = 'Missed Tasks';
  } elseif ($fs === 'completed') {
    $where = 'WHERE t.status = ?';
    $params = ['completed']; $types = 's';
    $filterLabel = 'Completed Tasks';
  }
}
if (isset($_GET['filter_type'])) {
  $ft = $_GET['filter_type'];
  if ($ft === 'one-time' || $ft === 'recurring') {
    $isRec = ($ft === 'recurring') ? 1 : 0;
    $where = ($where ? $where . ' AND ' : 'WHERE ') . 's.is_recurring = ?';
    $params[] = $isRec;
    $types .= 'i';
    $filterLabel = ucfirst($ft) . ' Tasks';
  }
}
if (isset($_GET['filter_area']) && $_GET['filter_area'] !== '') {
  $area = $_GET['filter_area'];
  $where = ($where ? $where . ' AND ' : 'WHERE ') . 'c.area = ?';
  $params[] = $area;
  $types .= 's';
  $filterLabel = 'Area: ' . htmlspecialchars($area);
}
if (isset($_GET['filter_staff']) && $_GET['filter_staff'] !== '') {
  $staffId = intval($_GET['filter_staff']);
  $where = ($where ? $where . ' AND ' : 'WHERE ') . 't.assigned_to = ?';
  $params[] = $staffId;
  $types .= 'i';
  $filterLabel = 'Staff Tasks';
}

// Get staff members for display
$staffMap = [];
$staffResult = $db->query("SELECT id, name, avatar FROM fscrm_staff ORDER BY name");
while ($s = $staffResult->fetch_assoc()) {
  $staffMap[$s['id']] = $s;
}

// Count total
$countSql = "SELECT COUNT(*) as cnt FROM fscrm_tasks t
             LEFT JOIN fscrm_customers c ON t.customer_id = c.id
             LEFT JOIN fscrm_services s ON t.service_id = s.id
             $where";
if (!empty($params)) {
  $countStmt = $db->prepare($countSql);
  $countStmt->bind_param($types, ...$params);
  $countStmt->execute();
  $totalRecords = (int)$countStmt->get_result()->fetch_assoc()['cnt'];
} else {
  $totalRecords = (int)$db->query($countSql)->fetch_assoc()['cnt'];
}
$totalPages = max(1, (int)ceil($totalRecords / $perPage));

// Build and execute task query with pagination
$sql = "SELECT t.*, c.name AS customer_name, s.problem AS service_problem
        FROM fscrm_tasks t
        LEFT JOIN fscrm_customers c ON t.customer_id = c.id
        LEFT JOIN fscrm_services s ON t.service_id = s.id
        $where
        ORDER BY t.scheduled_date DESC
        LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
  $stmt = $db->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
  $tasks = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Tasks</h1>
        </div>
        <a href="service-add.php" class="btn btn-sm btn-primary">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Service
        </a>
      </div>
    </header>

    <div class="p-4 md:p-6 lg:p-8 max-w-4xl mx-auto">

      <!-- Drill-Down Filter Banner -->
      <?php if ($filterLabel): ?>
      <div id="filter-banner" class="filter-banner" style="display:flex">
        <div class="filter-banner-left">
          <a href="dashboard.php" class="flex items-center gap-1.5 text-brand font-medium hover:underline">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
          </a>
          <span class="text-gray-300">|</span>
          <span class="font-medium">Showing:</span>
          <span class="font-semibold"><?= htmlspecialchars($filterLabel) ?></span>
        </div>
        <a href="tasks.php" class="filter-banner-clear">
          <i data-lucide="x" class="w-3 h-3"></i> Clear Filter
        </a>
      </div>
      <?php endif; ?>

      <!-- Tabs (hidden when filter is active) -->
      <div id="task-tabs" class="flex rounded-lg bg-gray-100 p-1 mb-4 w-fit"<?= $filterLabel ? ' style="display:none"' : '' ?>>
        <a href="?status=today" class="tab-btn px-5 py-2 text-sm font-medium rounded-md transition-colors <?= $currentTab === 'today' ? 'bg-brand text-white' : 'text-gray-600 hover:text-gray-900' ?>">Today</a>
        <a href="?status=upcoming" class="tab-btn px-5 py-2 text-sm font-medium rounded-md transition-colors <?= $currentTab === 'upcoming' ? 'bg-brand text-white' : 'text-gray-600 hover:text-gray-900' ?>">Upcoming</a>
        <a href="?status=missed" class="tab-btn px-5 py-2 text-sm font-medium rounded-md transition-colors <?= $currentTab === 'missed' ? 'bg-brand text-white' : 'text-gray-600 hover:text-gray-900' ?>">Missed</a>
      </div>

      <!-- Search -->
      <div class="relative mb-4">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input type="text" id="task-search" placeholder="Search tasks..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand/40 focus:border-brand text-sm bg-white">
      </div>

      <!-- Tasks Container -->
      <div id="tasks-container" class="space-y-3">
        <?php if (empty($tasks)): ?>
          <div class="empty-state">
            <i data-lucide="<?= $iconMap[$currentTab] ?? 'calendar' ?>"></i>
            <p>No <?= htmlspecialchars($currentTab) ?> tasks</p>
            <p class="empty-sub"><?= htmlspecialchars($msgs[$currentTab] ?? '') ?></p>
            <?php if ($currentTab !== 'missed'): ?>
              <a href="service-add.php" class="btn btn-sm btn-primary mt-3"><i data-lucide="plus" class="w-4 h-4"></i> Add Service</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php foreach ($tasks as $task):
            $staff = $staffMap[$task['assigned_to']] ?? null;
            $problemText = $task['service_problem'] ?? '';
            $isPending = $task['status'] === 'pending';
            $problemHtml = $problemText ? '<div class="text-xs text-gray-400 mt-1"><span class="font-medium text-gray-500">Problem:</span> ' . htmlspecialchars(substr($problemText, 0, 80)) . (strlen($problemText) > 80 ? '...' : '') . '</div>' : '';
          ?>
          <div class="task-card bg-white rounded-xl border border-gray-200 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 text-sm md:text-base truncate"><?= htmlspecialchars($task['title']) ?></h3>
                <?= $problemHtml ?>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-2 text-xs text-gray-500">
                  <span class="flex items-center gap-1.5"><i data-lucide="user" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?></span>
                  <span class="flex items-center gap-1.5">
                    <?php if ($staff): ?>
                      <img src="<?= htmlspecialchars($staff['avatar']) ?>" class="w-4 h-4 rounded-full" alt="">
                    <?php else: ?>
                      <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                    <?php endif; ?>
                    <?= $staff ? htmlspecialchars($staff['name']) : 'Unassigned' ?>
                  </span>
                  <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> <?= formatRelative($task['scheduled_date']) ?></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <?= statusPill($task['status']) ?>
              </div>
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2">
              <?php if ($isPending): ?>
                <button class="complete-btn px-4 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark Complete</button>
              <?php else: ?>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg flex items-center gap-1.5 opacity-50 cursor-not-allowed" disabled><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> <?= $task['status'] === 'completed' ? 'Completed' : 'Missed' ?></button>
              <?php endif; ?>
              <button class="reassign-task-btn px-3 py-1.5 bg-purple-50 text-purple-600 text-xs font-semibold rounded-lg hover:bg-purple-100 transition-colors flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>" data-current-staff="<?= $task['assigned_to'] ?? '' ?>"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Reassign</button>
              <button class="delete-task-btn px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>" data-task-title="<?= htmlspecialchars($task['title']) ?>" data-task-customer="<?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?>" data-task-status="<?= $task['status'] ?>" data-task-date="<?= $task['scheduled_date'] ?>"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete</button>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRM MODAL -->
  <div id="delete-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
        <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div id="delete-modal-body" class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Task:</span> <span id="del-task-title"></span></p>
        <p><span class="font-medium">Customer:</span> <span id="del-task-customer"></span></p>
        <p><span class="font-medium">Status:</span> <span id="del-task-status"></span></p>
        <p><span class="font-medium">Date:</span> <span id="del-task-date"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>

  <!-- MARK COMPLETE MODAL -->
  <form method="POST" id="complete-form" action="">
    <input type="hidden" name="action" value="complete">
    <input type="hidden" name="task_id" id="modal-task-id" value=""><?= csrfHiddenField() ?>
    <div id="complete-modal" class="modal-overlay" style="display:none">
      <div class="modal-content" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-gray-900">Mark Task Complete</h3>
          <button type="button" onclick="closeCompleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label for="modal-complete-date" class="form-label">Completion Date</label>
            <input type="date" id="modal-complete-date" name="completed_date" class="form-input">
          </div>
          <div>
            <label for="modal-complete-notes" class="form-label">Completion Notes</label>
             <textarea id="modal-complete-notes" name="notes" rows="3" class="form-textarea" placeholder="Notes about the completion..." maxlength="1000"></textarea>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" onclick="closeCompleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
            <button type="submit" class="btn btn-md btn-primary">Confirm</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    // ========== SEARCH (client-side filter) ==========
    document.addEventListener('DOMContentLoaded', function () {
      var searchInput = document.getElementById('task-search');
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          var q = this.value.toLowerCase().trim();
          document.querySelectorAll('.task-card').forEach(function (card) {
            var text = card.textContent.toLowerCase();
            card.style.display = q === '' || text.indexOf(q) >= 0 ? '' : 'none';
          });
        });
      }
    });

    // ========== COMPLETE MODAL ==========
    var pendingCompleteTaskId = null;

    document.addEventListener('DOMContentLoaded', function () {
      // Prefill modal date
      var now = new Date();
      var y = now.getFullYear();
      var m = String(now.getMonth() + 1).padStart(2, '0');
      var d = String(now.getDate()).padStart(2, '0');
      var dateInput = document.getElementById('modal-complete-date');
      if (dateInput) dateInput.value = y + '-' + m + '-' + d;

      // Attach complete button handlers
      document.querySelectorAll('.complete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          pendingCompleteTaskId = parseInt(this.dataset.taskId, 10);
          document.getElementById('modal-task-id').value = pendingCompleteTaskId;
          openCompleteModal();
        });
      });
    });

    function openCompleteModal() {
      document.getElementById('complete-modal').style.display = 'flex';
    }

    function closeCompleteModal() {
      document.getElementById('complete-modal').style.display = 'none';
      pendingCompleteTaskId = null;
    }

    // Close modal on overlay click
    document.addEventListener('click', function (e) {
      var modal = document.getElementById('complete-modal');
      if (e.target === modal) {
        closeCompleteModal();
      }
    });

    // ========== DELETE TASK ==========
    var deleteTaskId = null;

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.delete-task-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteTaskId = parseInt(this.dataset.taskId, 10);
          document.getElementById('del-task-title').textContent = this.dataset.taskTitle;
          document.getElementById('del-task-customer').textContent = this.dataset.taskCustomer;
          document.getElementById('del-task-status').textContent = this.dataset.taskStatus;
          document.getElementById('del-task-date').textContent = this.dataset.taskDate;
          document.getElementById('delete-modal').style.display = 'flex';
        });
      });

      document.getElementById('delete-confirm-btn').addEventListener('click', async function () {
        if (!deleteTaskId) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Deleting...';
        try {
          var res = await fetch('../api/tasks.php?id=' + deleteTaskId, { method: 'DELETE' });
          var data = await res.json();
          if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); return; }
          var card = document.querySelector('.delete-task-btn[data-task-id="' + deleteTaskId + '"]').closest('.task-card');
          if (card) card.remove();
          showToast('Task deleted successfully', 'success');
          closeDeleteModal();
        } catch (e) {
          showToast('Network error', 'error');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Delete';
          try { lucide.createIcons(); } catch (e) {}
        }
      });
    });

    function closeDeleteModal() {
      document.getElementById('delete-modal').style.display = 'none';
      deleteTaskId = null;
    }

    // ========== REASSIGN TASK ==========
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.reassign-task-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          window.reassignStaff({
            entityType: 'task',
            entityId: parseInt(this.dataset.taskId, 10),
            currentStaffId: this.dataset.currentStaff || null
          });
        });
      });
    });
  </script>

  <?php if ($totalPages > 1): ?>
  <?php
    $queryParams = $_GET;
    unset($queryParams['page']);
    $queryString = http_build_query($queryParams);
    $pageLink = $queryString ? "?$queryString&page=" : "?page=";
  ?>
  <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-100">
    <p class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalRecords ?> records)</p>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
        <a href="<?= $pageLink . ($page - 1) ?>" class="btn btn-sm btn-secondary">&laquo; Previous</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a href="<?= $pageLink . ($page + 1) ?>" class="btn btn-sm btn-secondary">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
