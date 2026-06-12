<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$db = getDB();

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$taskId) { header('Location: onetime-task.php'); exit; }

$isStaff = !empty($_SESSION['user_staff_id']);
$staffId = $_SESSION['user_staff_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Staff';

$stmt = $db->prepare("
  SELECT t.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
         s.name AS staff_name, s.phone AS staff_phone, sv.service_for, sv.problem AS service_problem,
         sv.is_recurring, sv.rec_value, sv.rec_unit, sv.first_scheduled_date, cat.name AS category_name
  FROM fscrm_tasks t
  LEFT JOIN fscrm_customers c ON t.customer_id = c.id
  LEFT JOIN fscrm_staff s ON t.assigned_to = s.id
  LEFT JOIN fscrm_services sv ON t.service_id = sv.id
  LEFT JOIN fscrm_categories cat ON sv.category_id = cat.id
  WHERE t.id = ?
");
$stmt->bind_param('i', $taskId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) { header('Location: onetime-task.php'); exit; }

// Staff can only see their own tasks
if ($isStaff && $task['assigned_to'] != $staffId) { header('Location: staff-dashboard.php'); exit; }

// Handle complete task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
  requireCsrfToken();
  $notes = trim($_POST['notes'] ?? '');
  $signature = trim($_POST['signature_data'] ?? '');
  $receivedName = trim($_POST['received_name'] ?? '');
  $receivedContact = trim($_POST['received_contact'] ?? '');
  $today = date('Y-m-d');

  if ($isStaff) {
    $check = $db->prepare("SELECT id, title FROM fscrm_tasks WHERE id = ? AND assigned_to = ?");
    $check->bind_param('ii', $taskId, $staffId);
  } else {
    $check = $db->prepare("SELECT id, title FROM fscrm_tasks WHERE id = ?");
    $check->bind_param('i', $taskId);
  }
  $check->execute();
  $t = $check->get_result()->fetch_assoc();

  if ($t) {
    $compStmt = $db->prepare("UPDATE fscrm_tasks SET status='completed', completed_date=?, notes=CONCAT(COALESCE(notes,''), '\n', ?), received_name=?, received_contact=?, signature=? WHERE id=?");
    $compStmt->bind_param('sssssi', $today, $notes, $receivedName, $receivedContact, $signature, $taskId);
    $compStmt->execute();
    setFlash('Task "' . $t['title'] . '" completed successfully');
  }
  header('Location: task-detail.php?id=' . $taskId);
  exit;
}

$pageTitle = 'Task Detail - ' . htmlspecialchars($task['title']);

function statusPill($status) {
  $labels = ['pending'=>'Pending','completed'=>'Completed','missed'=>'Missed'];
  $s = $status ?: 'pending';
  return '<span class="badge badge-' . $s . '">' . ($labels[$s] ?? 'Pending') . '</span>';
}

function weeksToText($weeks) {
  if ($weeks % 4 === 0 && $weeks >= 4) return ($weeks/4) . ' months';
  return $weeks . ' weeks';
}

$avatar = 'https://ui-avatars.com/api/?name=' . urlencode($task['customer_name'] ?: 'U') . '&background=22C55E&color=fff&size=40';
?>
<?php if ($isStaff): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#22C55E', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body class="bg-[#F2F2F7] font-sans min-h-screen pb-20">
  <!-- Staff Header -->
  <header class="bg-navy text-white sticky top-0 z-30 shadow-lg">
    <div class="flex items-center justify-between px-4 py-3 max-w-4xl mx-auto">
      <div class="flex items-center gap-3">
        <a href="staff-dashboard.php" class="text-white/70 hover:text-white">
          <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-sm font-bold">Task Detail</h1>
      </div>
      <a href="logout.php" class="flex items-center gap-1.5 text-sm text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg px-3 py-2 transition-colors">
        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
      </a>
    </div>
  </header>
<?php else: ?>
  <?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php endif; ?>

<div class="page-content">
  <div class="max-w-3xl mx-auto p-4 md:p-6 lg:p-8">

    <!-- Breadcrumb -->
    <nav class="text-xs text-gray-400 mb-4 flex items-center gap-1.5">
      <a href="<?= $isStaff ? 'staff-dashboard.php' : 'onetime-task.php' ?>" class="hover:text-brand transition-colors"><?= $isStaff ? 'Dashboard' : 'Tasks' ?></a>
      <span>/</span>
      <span class="text-gray-600 font-medium"><?= htmlspecialchars($task['title']) ?></span>
    </nav>

    <!-- Header Card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center shrink-0">
            <i data-lucide="clipboard-list" class="w-5 h-5 text-brand"></i>
          </div>
          <div>
            <h1 class="text-lg font-bold text-navy"><?= htmlspecialchars($task['title']) ?></h1>
            <p class="text-xs text-gray-400 mt-0.5">Task #<?= $task['id'] ?></p>
          </div>
        </div>
        <?= statusPill($task['status']) ?>
        <?php if (!$isStaff): ?>
        <div class="flex items-center gap-1 shrink-0 ml-2">
          <a href="task-edit.php?id=<?= $task['id'] ?>" class="p-2 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100" title="Edit">
            <i data-lucide="pencil" class="w-4 h-4"></i>
          </a>
          <button class="delete-detail-btn p-2 text-red-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50" title="Delete" data-task-id="<?= $task['id'] ?>" data-task-title="<?= htmlspecialchars($task['title']) ?>" data-task-customer="<?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?>" data-task-status="<?= $task['status'] ?>" data-task-date="<?= $task['scheduled_date'] ?>">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recurring Banner -->
    <?php if (!empty($task['is_recurring'])): ?>
    <div class="bg-brand/10 rounded-xl border-l-4 border-brand p-3 mb-4 flex items-center gap-3">
      <i data-lucide="repeat" class="w-4 h-4 text-brand shrink-0"></i>
      <div>
        <p class="text-sm font-bold text-brand">Recurring Task</p>
        <p class="text-xs text-gray-600 mt-0.5">
          <?php
          if ($task['rec_value'] && $task['rec_unit']) {
            echo 'Every ' . $task['rec_value'] . ' ' . $task['rec_unit'];
          }
          ?>
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

      <!-- Customer -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Customer</h2>
        <div class="flex items-center gap-3">
          <img src="<?= $avatar ?>" alt="" class="w-10 h-10 rounded-full">
          <div>
            <p class="text-sm font-medium text-navy"><?= htmlspecialchars($task['customer_name'] ?: 'Unknown') ?></p>
            <?php if ($task['customer_phone']): ?>
              <a href="tel:<?= htmlspecialchars($task['customer_phone']) ?>" class="text-xs text-brand hover:underline"><?= htmlspecialchars($task['customer_phone']) ?></a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($task['customer_address']): ?>
          <p class="text-xs text-gray-500 mt-2 flex items-center gap-1.5">
            <i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i> <?= htmlspecialchars($task['customer_address']) ?>
          </p>
        <?php endif; ?>
      </div>

      <!-- Schedule -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Schedule</h2>
        <div class="space-y-2">
          <div class="flex items-center gap-2.5 text-sm">
            <i data-lucide="calendar" class="w-4 h-4 text-gray-400 shrink-0"></i>
            <span class="text-gray-600">Scheduled:</span>
            <span class="font-medium text-navy ml-auto"><?= htmlspecialchars($task['scheduled_date']) ?></span>
          </div>
          <?php if ($task['completed_date']): ?>
          <div class="flex items-center gap-2.5 text-sm">
            <i data-lucide="check-circle" class="w-4 h-4 text-brand shrink-0"></i>
            <span class="text-gray-600">Completed:</span>
            <span class="font-medium text-navy ml-auto"><?= htmlspecialchars($task['completed_date']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($task['first_scheduled_date']): ?>
          <div class="flex items-center gap-2.5 text-sm">
            <i data-lucide="repeat" class="w-4 h-4 text-gray-400 shrink-0"></i>
            <span class="text-gray-600">First Scheduled:</span>
            <span class="font-medium text-navy ml-auto"><?= htmlspecialchars($task['first_scheduled_date']) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Assignment -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Assignment</h2>
        <div class="flex items-center gap-2.5 text-sm">
          <i data-lucide="user" class="w-4 h-4 text-gray-400 shrink-0"></i>
          <span class="text-gray-600">Assigned To:</span>
          <span class="font-medium text-navy ml-auto"><?= htmlspecialchars($task['staff_name'] ?: 'Unassigned') ?></span>
        </div>
        <?php if ($task['staff_phone']): ?>
        <div class="flex items-center gap-2.5 text-sm mt-2">
          <i data-lucide="phone" class="w-4 h-4 text-gray-400 shrink-0"></i>
          <a href="tel:<?= htmlspecialchars($task['staff_phone']) ?>" class="text-brand hover:underline ml-auto text-sm"><?= htmlspecialchars($task['staff_phone']) ?></a>
        </div>
        <?php endif; ?>
        <?php if (!$isStaff): ?>
        <div class="mt-3 pt-3 border-t border-gray-100">
          <button class="reassign-detail-btn text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>" data-current-staff="<?= $task['assigned_to'] ?? '' ?>">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Change Assignee
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Service -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Service</h2>
        <div class="space-y-2">
          <div class="flex items-center gap-2.5 text-sm">
            <i data-lucide="wrench" class="w-4 h-4 text-gray-400 shrink-0"></i>
            <span class="font-medium text-navy"><?= htmlspecialchars($task['service_for'] ?: '—') ?></span>
          </div>
          <?php if ($task['category_name']): ?>
          <div class="flex items-center gap-2.5 text-sm">
            <i data-lucide="tag" class="w-4 h-4 text-gray-400 shrink-0"></i>
            <span class="text-gray-500"><?= htmlspecialchars($task['category_name']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($task['is_recurring']): ?>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand/10 text-brand text-xs font-medium rounded-full">
            <i data-lucide="repeat" class="w-3 h-3"></i> Recurring
          </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Problem / Notes -->
    <?php if ($task['service_problem'] || $task['notes']): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
      <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Details</h2>
      <?php if ($task['service_problem']): ?>
      <div class="mb-3">
        <p class="text-xs text-gray-500 mb-1">Problem</p>
        <p class="text-sm text-navy"><?= nl2br(htmlspecialchars($task['service_problem'])) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($task['notes']): ?>
      <div>
        <p class="text-xs text-gray-500 mb-1">Notes</p>
        <p class="text-sm text-navy"><?= nl2br(htmlspecialchars($task['notes'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Completion Details -->
    <?php if ($task['status'] === 'completed' && ($task['received_name'] || $task['received_contact'] || $task['completed_by'] || $task['signature'])): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
      <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
        <i data-lucide="clipboard-check" class="w-4 h-4 text-brand"></i> Completion Details
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php if ($task['completed_by']): ?>
        <div>
          <p class="text-xs text-gray-500">Completed By</p>
          <p class="text-sm font-medium text-navy"><?= htmlspecialchars($task['completed_by']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($task['received_name']): ?>
        <div>
          <p class="text-xs text-gray-500">Received By</p>
          <p class="text-sm font-medium text-navy"><?= htmlspecialchars($task['received_name']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($task['received_contact']): ?>
        <div>
          <p class="text-xs text-gray-500">Contact</p>
          <p class="text-sm font-medium text-navy"><?= htmlspecialchars($task['received_contact']) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($task['signature']): ?>
        <div class="sm:col-span-2">
          <p class="text-xs text-gray-500 mb-1">Signature</p>
          <img src="<?= htmlspecialchars($task['signature']) ?>" alt="Signature" class="h-16 border border-gray-200 rounded-lg bg-gray-50 p-1">
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <?php if (!$isStaff): ?>
    <div class="flex gap-3">
      <a href="onetime-task.php" class="btn btn-secondary flex items-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Tasks
      </a>
    </div>
    <?php else: ?>
    <!-- Staff: Mark Complete Bottom Bar -->
    <?php if ($task['status'] !== 'completed'): ?>
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-3 z-30 md:bottom-0" style="bottom: 64px;" id="staff-complete-bar">
      <button type="button" id="staff-complete-btn" class="w-full bg-brand text-white font-semibold text-sm rounded-xl py-3 flex items-center justify-center gap-2 hover:bg-brand/90 transition-colors brand-glow">
        <i data-lucide="check-square" class="w-4 h-4"></i> Mark Complete
      </button>
    </div>
    <style>
      @media (min-width: 768px) {
        #staff-complete-bar { bottom: 0 !important; }
      }
    </style>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Staff Complete Modal -->
    <div id="staff-complete-modal" class="modal-overlay" style="display:none">
      <div class="modal-content" onclick="event.stopPropagation()">
        <form method="POST" id="staff-complete-form">
          <input type="hidden" name="complete_task" value="1">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <?= csrfHiddenField() ?>
          <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900">Mark Task Complete</h3>
            <button type="button" onclick="document.getElementById('staff-complete-modal').style.display='none'" class="text-gray-400 hover:text-gray-600">
              <i data-lucide="x" class="w-5 h-5"></i>
            </button>
          </div>
          <p class="text-sm text-gray-500 mb-5"><?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?> — <?= htmlspecialchars($task['title']) ?></p>
          <div class="space-y-4">
            <div>
              <label class="form-label">Completion Notes</label>
              <textarea name="notes" rows="3" class="form-textarea" maxlength="1000" placeholder="Describe what was done..."></textarea>
            </div>
            <div>
              <label class="form-label">Customer Signature</label>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <canvas id="sig-canvas" width="400" height="150" class="w-full touch-none" style="background:#fff;min-height:120px"></canvas>
              </div>
              <div class="flex gap-2 mt-2">
                <button type="button" onclick="clearDetailSignature()" class="btn btn-sm btn-secondary text-xs">Clear</button>
              </div>
              <input type="hidden" name="signature_data" id="detail-signature-data" value="">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Received By</label>
                <input type="text" name="received_name" class="form-input" maxlength="100" placeholder="Name">
              </div>
              <div>
                <label class="form-label">Contact</label>
                <input type="text" name="received_contact" class="form-input" maxlength="20" placeholder="Phone">
              </div>
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button type="button" onclick="document.getElementById('staff-complete-modal').style.display='none'" class="btn btn-md btn-secondary flex-1">Cancel</button>
            <button type="submit" class="btn btn-md btn-primary flex-1 brand-glow">Confirm</button>
          </div>
        </form>
      </div>
    </div>

    <!-- CSRF token for AJAX requests -->
    <?= csrfHiddenField() ?>

  </div>
</div>

<?php if ($isStaff): ?>
<!-- Staff Bottom Nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex md:hidden z-40">
  <a href="staff-dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Dashboard</span>
  </a>
  <a href="tasks.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="calendar" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Tasks</span>
  </a>
  <a href="orders.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="briefcase" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Orders</span>
  </a>
  <a href="daybook.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="book-open" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Daybook</span>
  </a>
  <button onclick="document.getElementById('staff-more-modal-detail').classList.toggle('hidden')" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="menu" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">More</span>
  </button>
</nav>

<!-- Staff More Modal -->
<div id="staff-more-modal-detail" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:320px">
    <h3 class="font-semibold text-navy text-lg mb-4">More</h3>
    <div class="space-y-1">
      <a href="customers.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="users" class="w-5 h-5 text-gray-400"></i> Customers</a>
      <a href="customer-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="user-plus" class="w-5 h-5 text-gray-400"></i> Add Customer</a>
      <a href="onetime-task.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="calendar-check" class="w-5 h-5 text-gray-400"></i> One-Time Tasks</a>
      <a href="recurring-task.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="repeat" class="w-5 h-5 text-gray-400"></i> Recurring Tasks</a>
      <a href="order-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="plus" class="w-5 h-5 text-gray-400"></i> Add Order</a>
      <a href="notifications.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="bell" class="w-5 h-5 text-gray-400"></i> Notifications</a>
      <hr class="my-2 border-gray-100">
      <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-danger hover:bg-red-50 transition-colors w-full min-h-[44px]"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a>
    </div>
  </div>
</div>
<script src="../assets/js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var reassignBtn = document.querySelector('.reassign-detail-btn');
    if (reassignBtn) {
      reassignBtn.addEventListener('click', function () {
        window.reassignStaff({
          entityType: 'task',
          entityId: parseInt(this.dataset.taskId, 10),
          currentStaffId: this.dataset.currentStaff || null,
          onSuccess: function () { window.location.reload(); }
        });
      });
    }

    // ===== Staff Complete Modal =====
    var staffCompleteBtn = document.getElementById('staff-complete-btn');
    var staffCompleteModal = document.getElementById('staff-complete-modal');
    if (staffCompleteBtn && staffCompleteModal) {
      staffCompleteBtn.addEventListener('click', function () {
        staffCompleteModal.style.display = 'flex';
        setTimeout(initDetailSignature, 100);
      });
    }

    // ===== Delete button on detail page =====
    document.querySelectorAll('.delete-detail-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = parseInt(this.dataset.taskId, 10);
        if (!id) return;
        if (confirm('Delete "' + this.dataset.taskTitle + '"? This cannot be undone.')) {
          fetch('../api/tasks.php?id=' + id, { method: 'DELETE' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data.success) { window.location.href = 'onetime-task.php'; showToast('Task deleted', 'success'); }
              else { showToast(data.error || 'Delete failed', 'error'); }
            })
            .catch(function () { showToast('Network error', 'error'); });
        }
      });
    });
  });

  // ===== Staff Signature Pad =====
  var detailSigCanvas = null, detailSigCtx = null, detailSigDrawing = false, detailSigHasInk = false;

  function initDetailSignature() {
    detailSigCanvas = document.getElementById('sig-canvas');
    if (!detailSigCanvas) return;
    detailSigCtx = detailSigCanvas.getContext('2d');
    detailSigCtx.lineWidth = 2; detailSigCtx.lineCap = 'round'; detailSigCtx.strokeStyle = '#0B1E3D';

    function pos(e) {
      var rect = detailSigCanvas.getBoundingClientRect();
      var src = (e.touches && e.touches[0]) ? e.touches[0] : e;
      return {
        x: (src.clientX - rect.left) * (detailSigCanvas.width / rect.width),
        y: (src.clientY - rect.top) * (detailSigCanvas.height / rect.height)
      };
    }
    function start(e) { e.preventDefault(); detailSigDrawing = true; var p = pos(e); detailSigCtx.beginPath(); detailSigCtx.moveTo(p.x, p.y); }
    function move(e) { if (!detailSigDrawing) return; e.preventDefault(); var p = pos(e); detailSigCtx.lineTo(p.x, p.y); detailSigCtx.stroke(); detailSigHasInk = true; }
    function end() { detailSigDrawing = false; }

    detailSigCanvas.addEventListener('mousedown', start);
    detailSigCanvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    detailSigCanvas.addEventListener('touchstart', start, { passive: false });
    detailSigCanvas.addEventListener('touchmove', move, { passive: false });
    detailSigCanvas.addEventListener('touchend', end);
  }

  function clearDetailSignature() {
    if (detailSigCtx) { detailSigCtx.clearRect(0, 0, detailSigCanvas.width, detailSigCanvas.height); detailSigHasInk = false; }
  }

  // Save signature on form submit
  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('staff-complete-form');
    if (form) {
      form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (detailSigHasInk && detailSigCanvas) {
          var raw = detailSigCanvas.toDataURL('image/png');
          document.getElementById('detail-signature-data').value = await window.compressSignature(raw);
        }
        this.submit();
      });
    }
  });
</script>
<script>lucide.createIcons();</script>
</body>
</html>
<?php else: ?>
</main>
<?php require_once '../includes/footer.php'; ?>
<?php endif; ?>
