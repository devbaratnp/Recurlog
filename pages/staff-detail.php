<?php require_once '../includes/config.php'; ?>
<?php $pageTitle = 'Staff Detail'; require_once '../includes/header.php'; ?>
<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
  $id = isset($_SESSION['fscrm_currentStaffId']) ? (int)$_SESSION['fscrm_currentStaffId'] : 0;
}

$stmt = $db->prepare("SELECT * FROM fscrm_staff WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
if (!$staff) {
  header('Location: staff.php');
  exit;
}

$stmt = $db->prepare("SELECT t.*, c.name as customer_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id WHERE t.assigned_to = ? ORDER BY t.scheduled_date DESC");
$stmt->bind_param('i', $id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($tasks);
$completed = 0;
$missed = 0;
$today = date('Y-m-d');
$completedToday = 0;
foreach ($tasks as $t) {
  if ($t['status'] === 'completed') { $completed++; if ($t['completed_date'] === $today) $completedToday++; }
  elseif ($t['status'] === 'missed') $missed++;
}
$pending = $total - $completed - $missed;
$rate = $total > 0 ? round(($completed / $total) * 100) : 0;

$groupedMissed = []; $groupedPending = []; $groupedCompleted = [];
foreach ($tasks as $t) {
  if ($t['status'] === 'missed') $groupedMissed[] = $t;
  elseif ($t['status'] === 'pending') $groupedPending[] = $t;
  elseif ($t['status'] === 'completed') $groupedCompleted[] = $t;
}

function badgePill($status) {
  $map = ['pending' => 'badge-pending', 'completed' => 'badge-completed', 'missed' => 'badge-danger'];
  $icons = ['pending' => 'clock', 'completed' => 'check-circle', 'missed' => 'alert-circle'];
  $class = $map[$status] ?? 'badge-pending';
  $icon = $icons[$status] ?? 'clock';
  return '<span class="badge ' . $class . '"><i data-lucide="' . $icon . '" class="w-3 h-3"></i> ' . ucfirst($status) . '</span>';
}

function fmtDate($date) {
  if (!$date) return '';
  return date('M j, Y', strtotime($date));
}
?>
  <div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <a href="staff.php" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
          <h1 class="page-title"><?= htmlspecialchars($staff['name']) ?></h1>
        </div>
      </div>
    </header>

    <div class="p-4 sm:p-6 space-y-6">
      <div class="card p-6 fade-in">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
          <img src="<?= htmlspecialchars($staff['avatar']) ?>" alt="<?= htmlspecialchars($staff['name']) ?>" class="w-20 h-20 rounded-full object-cover border-2 border-gray-100">
          <div class="text-center sm:text-left flex-1">
            <h2 class="text-lg font-bold text-navy"><?= htmlspecialchars($staff['name']) ?></h2>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($staff['phone'] ?? '') ?></p>
            <div class="stat-grid mt-4">
              <div class="stat-box" style="flex:1.5"><p class="stat-box-label">All</p><p class="stat-box-value"><?= $total ?></p></div>
              <div class="stat-box"><p class="stat-box-label">Comp</p><p class="stat-box-value" style="color:var(--primary)"><?= $completed ?></p></div>
              <div class="stat-box" style="flex:0.7;background:#3B82F6;border-color:#3B82F6"><p class="stat-box-label" style="color:rgba(255,255,255,0.7)">Today</p><p class="stat-box-value" style="color:#fff"><?= $completedToday ?></p></div>
              <div class="stat-box"><p class="stat-box-label">Up</p><p class="stat-box-value" style="color:var(--amber)"><?= $pending ?></p></div>
              <div class="stat-box"><p class="stat-box-label">Mis</p><p class="stat-box-value" style="color:var(--danger)"><?= $missed ?></p></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card overflow-hidden fade-in">
        <div class="card-header">
          <h2 class="font-semibold text-navy text-base flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-brand"></i> Assigned Tasks</h2>
        </div>
        <div class="p-3">
          <?php if (empty($tasks)): ?>
          <div class="empty-state">
            <i data-lucide="clipboard-list"></i>
            <p>No tasks assigned</p>
            <p class="empty-sub">Assign tasks from the task list to track performance</p>
          </div>
          <?php else: ?>
            <?php if (!empty($groupedMissed)): ?>
            <div class="flex items-center gap-1.5 px-2 py-2 text-xs font-semibold text-red-600">
              <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Missed (<?= count($groupedMissed) ?>)
            </div>
            <?php foreach ($groupedMissed as $t): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-3 mb-2 hover:shadow-md transition-all">
              <div class="flex items-start gap-2">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <a href="task-detail.php?id=<?= $t['id'] ?>" class="text-sm font-semibold text-navy hover:underline truncate"><?= htmlspecialchars($t['title']) ?></a>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5 truncate"><?= htmlspecialchars($t['customer_name'] ?? 'Customer #' . $t['customer_id']) ?></p>
                </div>
                <?= badgePill($t['status']) ?>
              </div>
              <div class="flex items-center gap-1 mt-1.5 text-[11px] text-gray-400">
                <i data-lucide="calendar" class="w-3 h-3"></i> <?= htmlspecialchars($t['scheduled_date']) ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($groupedPending)): ?>
            <div class="flex items-center gap-1.5 px-2 py-2 text-xs font-semibold text-amber-600" style="color:var(--amber)">
              <i data-lucide="clock" class="w-3.5 h-3.5"></i> Upcoming (<?= count($groupedPending) ?>)
            </div>
            <?php foreach ($groupedPending as $t): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-3 mb-2 hover:shadow-md transition-all">
              <div class="flex items-start gap-2">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <a href="task-detail.php?id=<?= $t['id'] ?>" class="text-sm font-semibold text-navy hover:underline truncate"><?= htmlspecialchars($t['title']) ?></a>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5 truncate"><?= htmlspecialchars($t['customer_name'] ?? 'Customer #' . $t['customer_id']) ?></p>
                </div>
                <?= badgePill($t['status']) ?>
              </div>
              <div class="flex items-center gap-1 mt-1.5 text-[11px] text-gray-400">
                <i data-lucide="calendar" class="w-3 h-3"></i> <?= htmlspecialchars($t['scheduled_date']) ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($groupedCompleted)): ?>
            <div class="flex items-center gap-1.5 px-2 py-2 text-xs font-semibold text-green-600">
              <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Completed (<?= count($groupedCompleted) ?>)
            </div>
            <?php foreach ($groupedCompleted as $t): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-3 mb-2 hover:shadow-md transition-all">
              <div class="flex items-start gap-2">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <a href="task-detail.php?id=<?= $t['id'] ?>" class="text-sm font-semibold text-navy hover:underline truncate"><?= htmlspecialchars($t['title']) ?></a>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5 truncate"><?= htmlspecialchars($t['customer_name'] ?? 'Customer #' . $t['customer_id']) ?></p>
                </div>
                <?= badgePill($t['status']) ?>
              </div>
              <div class="flex items-center gap-1 mt-1.5 text-[11px] text-gray-400">
                <i data-lucide="calendar" class="w-3 h-3"></i> <?= htmlspecialchars($t['scheduled_date']) ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>
  <?php require_once '../includes/footer.php'; ?>
