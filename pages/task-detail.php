<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$db = getDB();

$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$taskId) { header('Location: onetime-task.php'); exit; }

$isStaff = !empty($_SESSION['user_staff_id']);
$staffId = $_SESSION['user_staff_id'] ?? null;

$stmt = $db->prepare("
  SELECT t.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
         s.name AS staff_name, s.phone AS staff_phone, sv.service_for, sv.problem AS service_problem,
         sv.is_recurring, sv.first_scheduled_date, cat.name AS category_name
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

$pageTitle = 'Task Detail - ' . htmlspecialchars($task['title']);

function statusPill($status) {
  $map = [
    'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'Pending'],
    'completed' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'Completed'],
    'missed' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'Missed'],
  ];
  $cfg = $map[$status] ?? $map['pending'];
  return '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium" style="background:' . $cfg['bg'] . ';color:' . $cfg['text'] . '">' . $cfg['label'] . '</span>';
}

$avatar = 'https://ui-avatars.com/api/?name=' . urlencode($task['customer_name'] ?: 'U') . '&background=1DB954&color=fff&size=40';
?>
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
          colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-50 font-sans min-h-screen <?= $isStaff ? 'pb-20' : '' ?>">
<?php if ($isStaff): ?>
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
      </div>
    </div>

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
    <?php endif; ?>

  </div>
</div>

<?php if ($isStaff): ?>
<!-- Staff Bottom Nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex md:hidden z-40">
  <a href="staff-dashboard.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-brand">
    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Dashboard</span>
  </a>
  <a href="onetime-task.php" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="calendar-check" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Tasks</span>
  </a>
  <button onclick="history.back()" class="flex flex-col items-center justify-center gap-0.5 px-2 py-2 flex-1 text-gray-500">
    <i data-lucide="arrow-left" class="w-5 h-5"></i>
    <span class="text-[10px] font-medium">Back</span>
  </button>
</nav>
<?php endif; ?>

<script>lucide.createIcons();</script>
</body>
</html>
