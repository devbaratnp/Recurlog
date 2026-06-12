<?php
require_once '../includes/config.php';
require_once '../includes/notification_helper.php';
requireAuth();

$staffId = $_SESSION['user_staff_id'] ?? null;
if (!$staffId) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();
$userName = $_SESSION['user_name'] ?? 'Staff';

// Fetch staff profile
$sResult = $db->query("SELECT id, name, phone, avatar FROM fscrm_staff WHERE id = $staffId");
$staff = $sResult->fetch_assoc();
if (!$staff) {
    die('Staff record not found.');
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    requireCsrfToken();
    $taskId = (int)$_POST['task_id'];
    $notes = trim($_POST['notes'] ?? '');
    $signature = trim($_POST['signature_data'] ?? '');
    $receivedName = trim($_POST['received_name'] ?? '');
    $receivedContact = trim($_POST['received_contact'] ?? '');

    $check = $db->prepare("SELECT id, customer_id, title FROM fscrm_tasks WHERE id = ? AND assigned_to = ?");
    $check->bind_param('ii', $taskId, $staffId);
    $check->execute();
    $task = $check->get_result()->fetch_assoc();

    if ($task) {
        $compStmt = $db->prepare("UPDATE fscrm_tasks SET status = 'completed', completed_date = ?, notes = CONCAT(notes, '\n', ?), received_name = ?, received_contact = ?, signature = ? WHERE id = ?");
        $compStmt->bind_param('sssssi', $today, $notes, $receivedName, $receivedContact, $signature, $taskId);
        $compStmt->execute();

        $notifText = $userName . ' completed "' . $task['title'] . '"';
        createNotification($db, $notifText, 'task_completed', $taskId);
        setFlash('Task "' . $task['title'] . '" completed successfully');
    }

    header('Location: staff-dashboard.php');
    exit;
}

// Fetch tasks
$todayTasks = $db->query("SELECT t.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id WHERE t.assigned_to = $staffId AND t.scheduled_date = '$today' AND t.status = 'pending' ORDER BY t.scheduled_date")->fetch_all(MYSQLI_ASSOC);
$upcomingTasks = $db->query("SELECT t.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id WHERE t.assigned_to = $staffId AND t.scheduled_date > '$today' AND t.status = 'pending' ORDER BY t.scheduled_date LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$recentTasks = $db->query("SELECT t.*, c.name as customer_name FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id WHERE t.assigned_to = $staffId AND t.status IN ('completed','missed') ORDER BY t.scheduled_date DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$assignedOrders = $db->query("SELECT o.*, c.name as customer_name FROM fscrm_orders o LEFT JOIN fscrm_customers c ON o.customer_id = c.id WHERE o.assigned_to = $staffId AND o.status IN ('pending','assigned') ORDER BY o.scheduled_date LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Stats
$stats = $db->query("SELECT
    SUM(CASE WHEN status = 'completed' AND completed_date = '$today' THEN 1 ELSE 0 END) as completed_today,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
FROM fscrm_tasks WHERE assigned_to = $staffId")->fetch_assoc();

$pageTitle = 'Staff Dashboard';
$avatar = $staff['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($staff['name']) . '&background=22C55E&color=fff&size=200';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Staff Dashboard - Recurlog</title>
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
  <!-- Header -->
  <header class="bg-navy text-white sticky top-0 z-30 shadow-lg">
    <div class="flex items-center justify-between px-4 py-3 max-w-4xl mx-auto">
      <div class="flex items-center gap-3">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="" class="w-9 h-9 rounded-full border-2 border-brand/50">
        <div>
          <h1 class="text-sm font-bold leading-tight"><?= htmlspecialchars($staff['name']) ?></h1>
          <p class="text-xs text-white/50">Field Staff</p>
        </div>
      </div>
      <a href="logout.php" class="flex items-center gap-1.5 text-sm text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg px-3 py-2 transition-colors">
        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
      </a>
    </div>
  </header>

  <main class="max-w-4xl mx-auto p-4 space-y-5">

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-brand"><?= (int)$stats['completed_today'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Completed Today</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-amber"><?= (int)$stats['pending'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Pending</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-danger"><?= (int)$stats['missed'] ?></p>
        <p class="text-xs text-gray-500 mt-1">Missed</p>
      </div>
    </div>

    <!-- Progress Bar -->
    <?php $totalCount = (int)$stats['completed_today'] + (int)$stats['pending'] + (int)$stats['missed']; ?>
    <?php if ($totalCount > 0): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <div class="flex items-center justify-between mb-1.5">
        <span class="text-xs font-medium text-gray-500">Progress</span>
        <span class="text-xs font-semibold text-navy"><?= (int)$stats['completed_today'] ?>/<?= $totalCount ?></span>
      </div>
      <div class="w-full bg-gray-100 rounded-full h-2.5">
        <div class="bg-brand h-2.5 rounded-full transition-all" style="width:<?= $totalCount > 0 ? round(((int)$stats['completed_today'] / $totalCount) * 100) : 0 ?>%"></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="flex gap-2 flex-wrap">
      <a href="orders.php" class="quick-action-btn flex-1 min-w-0"><i data-lucide="clipboard-list"></i> Orders</a>
      <a href="customers.php" class="quick-action-btn flex-1 min-w-0"><i data-lucide="users"></i> Customers</a>
      <a href="daybook.php" class="quick-action-btn flex-1 min-w-0"><i data-lucide="calendar"></i> Daybook</a>
    </div>

    <!-- Create Task -->
    <div>
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Create Task</p>
      <div class="flex gap-2">
        <a href="onetime-task.php" class="flex flex-1 items-center justify-center gap-1.5 py-3.5 rounded-xl font-semibold text-sm text-white" style="background:#F59E0B">
          <i data-lucide="clock" class="w-4.5 h-4.5" style="width:18px;height:18px"></i> One Time
        </a>
        <a href="recurring-task.php" class="flex flex-1 items-center justify-center gap-1.5 py-3.5 rounded-xl font-semibold text-sm text-white" style="background:#22C55E">
          <i data-lucide="refresh-cw" class="w-4.5 h-4.5" style="width:18px;height:18px"></i> Recurring
        </a>
      </div>
    </div>

    <!-- Today's Tasks -->
    <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-navy text-sm flex items-center gap-2">
          <i data-lucide="calendar-check" class="w-4 h-4 text-brand"></i> Today's Tasks
        </h2>
        <span class="text-xs bg-brand/10 text-brand font-medium px-2 py-0.5 rounded-full"><?= count($todayTasks) ?></span>
      </div>
      <?php if ($todayTasks): ?>
        <?php foreach ($todayTasks as $t): ?>
        <div class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-navy"><a href="task-detail.php?id=<?= $t['id'] ?>" class="text-inherit hover:underline"><?= htmlspecialchars($t['title']) ?></a></p>
              <p class="text-xs text-gray-500 mt-0.5">
                <?= htmlspecialchars($t['customer_name'] ?: 'Customer #' . $t['customer_id']) ?>
                <?php if ($t['customer_phone']): ?> &middot; <?= htmlspecialchars($t['customer_phone']) ?><?php endif; ?>
              </p>
              <?php if ($t['customer_address']): ?>
                <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($t['customer_address']) ?></p>
              <?php endif; ?>
            </div>
            <button onclick="openComplete(<?= $t['id'] ?>)" class="btn btn-sm btn-primary whitespace-nowrap shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5"></i> Complete
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="px-4 py-8 text-center">
          <i data-lucide="check-circle" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
          <p class="text-sm text-gray-400">No tasks scheduled for today</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Upcoming Tasks -->
    <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-navy text-sm flex items-center gap-2">
          <i data-lucide="calendar" class="w-4 h-4 text-amber"></i> Upcoming
        </h2>
        <span class="text-xs bg-amber/10 text-amber font-medium px-2 py-0.5 rounded-full"><?= count($upcomingTasks) ?></span>
      </div>
      <?php if ($upcomingTasks): ?>
        <?php foreach ($upcomingTasks as $t): ?>
        <div class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-navy"><a href="task-detail.php?id=<?= $t['id'] ?>" class="text-inherit hover:underline"><?= htmlspecialchars($t['title']) ?></a></p>
              <p class="text-xs text-gray-500 mt-0.5">
                <?= htmlspecialchars($t['customer_name'] ?: 'Customer #' . $t['customer_id']) ?>
                &middot; <span class="text-amber font-medium"><?= date('M j', strtotime($t['scheduled_date'])) ?></span>
              </p>
            </div>
            <span class="text-xs text-gray-400 shrink-0"><?= date('D', strtotime($t['scheduled_date'])) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="px-4 py-8 text-center">
          <i data-lucide="calendar" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
          <p class="text-sm text-gray-400">No upcoming tasks</p>
        </div>
      <?php endif; ?>
    </section>

    <!-- Assigned Orders -->
    <?php if ($assignedOrders): ?>
    <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-navy text-sm flex items-center gap-2">
          <i data-lucide="clipboard-list" class="w-4 h-4 text-purple-500"></i> Assigned Orders
        </h2>
        <span class="text-xs bg-purple-100 text-purple-600 font-medium px-2 py-0.5 rounded-full"><?= count($assignedOrders) ?></span>
      </div>
      <?php foreach ($assignedOrders as $o): ?>
      <div class="px-4 py-3 border-b border-gray-50 last:border-0">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-navy"><?= htmlspecialchars($o['problem']) ?></p>
            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($o['customer_name'] ?: 'Customer #' . $o['customer_id']) ?></p>
          </div>
          <span class="shrink-0"><span class="badge badge-order-<?= $o['status'] === 'assigned' ? 'assigned' : ($o['status'] === 'completed' ? 'completed' : 'pending') ?>"><?= ucfirst($o['status']) ?></span></span>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- Recent Activity -->
    <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100">
        <h2 class="font-semibold text-navy text-sm flex items-center gap-2">
          <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i> Recent Activity
        </h2>
      </div>
      <?php if ($recentTasks): ?>
        <?php foreach (array_slice($recentTasks, 0, 10) as $t): ?>
        <div class="px-4 py-2.5 border-b border-gray-50 last:border-0 flex items-center gap-3">
          <div class="w-1.5 h-1.5 rounded-full <?= $t['status'] === 'completed' ? 'bg-brand' : 'bg-danger' ?> shrink-0"></div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-600 truncate"><a href="task-detail.php?id=<?= $t['id'] ?>" class="text-inherit hover:underline"><?= htmlspecialchars($t['title']) ?></a></p>
            <p class="text-[11px] text-gray-400"><?= htmlspecialchars($t['customer_name'] ?: '') ?> &middot; <?= date('M j', strtotime($t['scheduled_date'])) ?></p>
          </div>
          <span class="text-[11px] font-medium <?= $t['status'] === 'completed' ? 'text-brand' : 'text-danger' ?>"><?= ucfirst($t['status']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="px-4 py-8 text-center">
          <p class="text-sm text-gray-400">No recent activity</p>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <!-- Complete Modal -->
  <div id="complete-modal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="closeModal(event)">
    <div class="bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl max-h-[90vh] overflow-y-auto shadow-2xl" onclick="event.stopPropagation()">
      <form method="POST" action="" onsubmit="return prepareSignature()">
        <?= csrfHiddenField() ?>
        <input type="hidden" name="complete_task" value="1">
        <input type="hidden" name="task_id" id="modal-task-id" value="">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
          <h3 class="font-semibold text-navy">Mark Complete</h3>
          <button type="button" onclick="closeModal(event)" class="text-gray-400 hover:text-gray-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="form-label text-sm">Completion Notes</label>
            <textarea name="notes" rows="3" class="form-input w-full" maxlength="1000" placeholder="Describe what was done..."></textarea>
          </div>

          <!-- Signature -->
          <div>
            <label class="form-label text-sm">Customer Signature</label>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <canvas id="sig-canvas" width="400" height="150" class="w-full touch-none" style="background:#fff;min-height:120px"></canvas>
            </div>
            <div class="flex gap-2 mt-2">
              <button type="button" onclick="clearSignature()" class="btn btn-sm btn-secondary text-xs">Clear</button>
            </div>
            <input type="hidden" name="signature_data" id="signature_data" value="">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label text-sm">Received By</label>
              <input type="text" name="received_name" class="form-input" maxlength="100" placeholder="Name">
            </div>
            <div>
              <label class="form-label text-sm">Contact</label>
              <input type="text" name="received_contact" class="form-input" maxlength="20" placeholder="Phone">
            </div>
          </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
          <button type="button" onclick="closeModal(event)" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button type="submit" class="btn btn-md btn-primary flex-1">
            <i data-lucide="check" class="w-4 h-4"></i> Confirm Complete
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    var sigCanvas, sigCtx, isDrawing = false;

    function initSigPad() {
      sigCanvas = document.getElementById('sig-canvas');
      if (!sigCanvas) return;
      sigCtx = sigCanvas.getContext('2d');
      sigCtx.strokeStyle = '#1f2937';
      sigCtx.lineWidth = 2.5;
      sigCtx.lineCap = 'round';
      sigCtx.lineJoin = 'round';

      sigCanvas.addEventListener('mousedown', startDraw);
      sigCanvas.addEventListener('mousemove', draw);
      sigCanvas.addEventListener('mouseup', stopDraw);
      sigCanvas.addEventListener('mouseleave', stopDraw);
      sigCanvas.addEventListener('touchstart', function(e) { e.preventDefault(); startDraw(e); });
      sigCanvas.addEventListener('touchmove', function(e) { e.preventDefault(); draw(e); });
      sigCanvas.addEventListener('touchend', stopDraw);
    }

    function getPos(e) {
      var rect = sigCanvas.getBoundingClientRect();
      var clientX = e.touches ? e.touches[0].clientX : e.clientX;
      var clientY = e.touches ? e.touches[0].clientY : e.clientY;
      return { x: (clientX - rect.left) * (sigCanvas.width / rect.width), y: (clientY - rect.top) * (sigCanvas.height / rect.height) };
    }

    function startDraw(e) {
      if (!sigCtx) return;
      isDrawing = true;
      var pos = getPos(e);
      sigCtx.beginPath();
      sigCtx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
      if (!isDrawing || !sigCtx) return;
      var pos = getPos(e);
      sigCtx.lineTo(pos.x, pos.y);
      sigCtx.stroke();
    }

    function stopDraw() {
      isDrawing = false;
    }

    function clearSignature() {
      if (!sigCtx) return;
      sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
      document.getElementById('signature_data').value = '';
    }

    async function prepareSignature() {
      if (!sigCanvas) return true;
      var dataUrl = sigCanvas.toDataURL('image/png');
      var empty = sigCanvas.toDataURL('image/png') === sigCanvas.toDataURL('image/png');
      var isBlank = true;
      var imageData = sigCtx.getImageData(0, 0, sigCanvas.width, sigCanvas.height);
      for (var i = 3; i < imageData.data.length; i += 4) {
        if (imageData.data[i] !== 0) { isBlank = false; break; }
      }
      if (!isBlank) {
        if (typeof window.compressSignature === 'function') {
          document.getElementById('signature_data').value = await window.compressSignature(dataUrl);
        } else {
          document.getElementById('signature_data').value = dataUrl;
        }
      }
      return true;
    }

    function openComplete(taskId) {
      document.getElementById('modal-task-id').value = taskId;
      document.getElementById('complete-modal').classList.remove('hidden');
      lucide.createIcons();
      setTimeout(function() { initSigPad(); }, 100);
    }

    function closeModal(e) {
      if (!e || e.target === e.currentTarget || e.target.closest('button')?.type === 'button') {
        document.getElementById('complete-modal').classList.add('hidden');
        document.getElementById('sig-canvas')?.remove();
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>

<!-- Staff Bottom Nav (mobile) -->
<nav class="bottom-nav md:hidden" style="background:white;position:fixed;bottom:0;left:0;right:0;z-index:40;display:flex;justify-content:space-around;align-items:center;padding:4px 0 8px;border-top:1px solid #E2E8F0">
  <a href="staff-dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 active"><i data-lucide="layout-dashboard" class="w-5 h-5" style="color:#22C55E"></i><span class="text-[10px] font-medium truncate w-full text-center" style="color:#22C55E">Dashboard</span></a>
  <a href="tasks.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="calendar" class="w-5 h-5" style="color:#94A3B8"></i><span class="text-[10px] font-medium truncate w-full text-center" style="color:#64748B">Tasks</span></a>
  <a href="orders.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="briefcase" class="w-5 h-5" style="color:#94A3B8"></i><span class="text-[10px] font-medium truncate w-full text-center" style="color:#64748B">Orders</span></a>
  <a href="daybook.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="book-open" class="w-5 h-5" style="color:#94A3B8"></i><span class="text-[10px] font-medium truncate w-full text-center" style="color:#64748B">Daybook</span></a>
  <button onclick="document.getElementById('staff-more-modal').classList.toggle('hidden')" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500"><i data-lucide="menu" class="w-5 h-5" style="color:#94A3B8"></i><span class="text-[10px] font-medium truncate w-full text-center" style="color:#64748B">More</span></button>
</nav>

<!-- Staff More Modal -->
<div id="staff-more-modal" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
  <div class="modal-content" onclick="event.stopPropagation()" style="max-width:320px">
    <h3 class="font-semibold text-navy text-lg mb-4">More</h3>
    <div class="space-y-1">
      <a href="customers.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="users" class="w-5 h-5 text-gray-400"></i> Customers</a>
      <a href="customer-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="user-plus" class="w-5 h-5 text-gray-400"></i> Add Customer</a>
      <a href="onetime-task.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="calendar-check" class="w-5 h-5 text-gray-400"></i> One-Time Tasks</a>
      <a href="order-add.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="plus" class="w-5 h-5 text-gray-400"></i> Add Order</a>
      <a href="notifications.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px]"><i data-lucide="bell" class="w-5 h-5 text-gray-400"></i> Notifications</a>
      <hr class="my-2 border-gray-100">
      <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-danger hover:bg-red-50 transition-colors w-full min-h-[44px]"><i data-lucide="log-out" class="w-5 h-5"></i> Logout</a>
    </div>
  </div>
</div>
</body>
</html>
