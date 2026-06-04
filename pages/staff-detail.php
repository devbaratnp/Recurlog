<?php require_once '../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Staff Detail - Recurlog</title>
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
<body class="bg-gray-50 font-sans min-h-screen">
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
foreach ($tasks as $t) {
  if ($t['status'] === 'completed') $completed++;
  elseif ($t['status'] === 'missed') $missed++;
}
$pending = $total - $completed - $missed;
$rate = $total > 0 ? round(($completed / $total) * 100) : 0;

function statusPill($status) {
  $map = [
    'pending' => ['bg' => '#FFF3CD', 'text' => '#856404', 'icon' => 'clock', 'label' => 'Pending'],
    'completed' => ['bg' => '#D4EDDA', 'text' => '#155724', 'icon' => 'check-circle', 'label' => 'Completed'],
    'missed' => ['bg' => '#F8D7DA', 'text' => '#721C24', 'icon' => 'alert-circle', 'label' => 'Missed']
  ];
  $cfg = $map[$status] ?? $map['pending'];
  return '<span class="status-pill" style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:' . $cfg['bg'] . ';color:' . $cfg['text'] . '"><i data-lucide="' . $cfg['icon'] . '" class="w-3 h-3"></i> ' . $cfg['label'] . '</span>';
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
          <a href="staff.html" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
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
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
              <div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Total Tasks</p><p class="text-lg font-bold text-navy"><?= $total ?></p></div>
              <div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Completed</p><p class="text-lg font-bold text-brand"><?= $completed ?></p></div>
              <div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Missed</p><p class="text-lg font-bold text-danger"><?= $missed ?></p></div>
              <div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Rate</p><p class="text-lg font-bold text-navy"><?= $rate ?>%</p></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card overflow-hidden fade-in">
        <div class="card-header">
          <h2 class="font-semibold text-navy text-base flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-brand"></i> Assigned Tasks</h2>
        </div>
        <div class="p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Task Title</th>
                <th>Customer</th>
                <th>Scheduled Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tasks)): ?>
              <tr><td colspan="4" class="text-center text-gray-400 py-8">No tasks assigned.</td></tr>
              <?php else: ?>
              <?php foreach ($tasks as $t): ?>
              <tr>
                <td data-label="Task Title" class="font-medium text-navy"><?= htmlspecialchars($t['title']) ?></td>
                <td data-label="Customer" class="text-gray-600"><?= htmlspecialchars($t['customer_name'] ?? 'Unknown') ?></td>
                <td data-label="Date" class="text-gray-600"><?= fmtDate($t['scheduled_date']) ?></td>
                <td data-label="Status"><?= statusPill($t['status']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
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
</body>
</html>
