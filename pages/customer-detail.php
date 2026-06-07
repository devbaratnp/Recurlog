<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();
$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$customerId) {
    header('Location: customers.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Categories lookup
$cats = [];
$catResult = $db->query("SELECT id, name, color FROM fscrm_categories");
while ($row = $catResult->fetch_assoc()) {
    $cats[$row['id']] = $row;
}

// Staff lookup
$staff = [];
$staffResult = $db->query("SELECT id, name FROM fscrm_staff");
while ($row = $staffResult->fetch_assoc()) {
    $staff[$row['id']] = $row;
}

// Services
$svcStmt = $db->prepare("SELECT * FROM fscrm_services WHERE customer_id = ?");
$svcStmt->bind_param('i', $customerId);
$svcStmt->execute();
$services = $svcStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tasks
$taskStmt = $db->prepare("SELECT * FROM fscrm_tasks WHERE customer_id = ? ORDER BY scheduled_date DESC");
$taskStmt->bind_param('i', $customerId);
$taskStmt->execute();
$taskRows = $taskStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks = [];
foreach ($taskRows as $row) {
    $tasks[$row['id']] = $row;
}

function statusPill($status) {
    $map = ['pending'=>['bg'=>'#FEF3C7','text'=>'#92400E','icon'=>'clock','label'=>'Pending'],'completed'=>['bg'=>'#D1FAE5','text'=>'#065F46','icon'=>'check-circle','label'=>'Completed'],'missed'=>['bg'=>'#FEE2E2','text'=>'#991B1B','icon'=>'alert-circle','label'=>'Missed']];
    $cfg = $map[$status] ?? $map['pending'];
    return '<span class="status-pill" style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:'.$cfg['bg'].';color:'.$cfg['text'].'"><i data-lucide="'.$cfg['icon'].'" class="w-3 h-3"></i> '.$cfg['label'].'</span>';
}

function catBadge($cats, $catId, $serviceFor) {
    if ($catId && isset($cats[$catId])) return '<span class="badge badge-info">' . htmlspecialchars($cats[$catId]['name']) . '</span>';
    return '<span class="badge badge-pending">' . htmlspecialchars($serviceFor ?: '—') . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= htmlspecialchars($customer['name']) ?> - Customer Detail</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Poppins', 'sans-serif'] },
          colors: { brand: '#1DB954', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' }
        }
      }
    }
  </script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>" />
  <style>
    body { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<?php $pageTitle = htmlspecialchars($customer['name']); require_once '../includes/header.php'; ?>
<div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <a href="customers.php" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
          </a>
          <h1 class="page-title"><?= htmlspecialchars($customer['name']) ?></h1>
        </div>
      </div>
    </header>

    <div class="p-4 md:p-6 max-w-5xl mx-auto">

      <div class="card p-5 sm:p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
          <h2 class="text-lg font-semibold text-navy">Customer Information</h2>
          <div class="flex items-center gap-2">
            <a href="customer-add.php?id=<?= $customerId ?>" class="btn btn-sm btn-secondary"><i data-lucide="pencil" class="w-4 h-4"></i> Edit</a>
            <a href="customer-report.php?id=<?= $customerId ?>" class="btn btn-sm btn-secondary"><i data-lucide="file-text" class="w-4 h-4"></i> Report</a>
            <span class="inline-flex items-center gap-1 px-3 py-1 bg-brand/10 text-brand text-xs font-medium rounded-full">
              <i data-lucide="check-circle" class="w-3 h-3"></i> Active
            </span>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="block text-gray-400 text-xs uppercase tracking-wider font-medium mb-1">Name</span>
            <span class="text-gray-900 font-medium"><?= htmlspecialchars($customer['name']) ?></span>
          </div>
          <div>
            <span class="block text-gray-400 text-xs uppercase tracking-wider font-medium mb-1">Phone</span>
            <span class="text-gray-900"><?= htmlspecialchars($customer['phone']) ?></span>
          </div>
          <div class="sm:col-span-2">
            <span class="block text-gray-400 text-xs uppercase tracking-wider font-medium mb-1">Address</span>
            <span class="text-gray-900"><?= htmlspecialchars($customer['address']) ?></span>
          </div>
          <div>
            <span class="block text-gray-400 text-xs uppercase tracking-wider font-medium mb-1">Area / Locality</span>
            <span class="text-gray-900 font-medium"><?= htmlspecialchars($customer['area'] ?: '—') ?></span>
          </div>
          <div class="sm:col-span-2">
            <span class="block text-gray-400 text-xs uppercase tracking-wider font-medium mb-1">Services</span>
            <div class="flex flex-wrap gap-1.5 mt-1">
<?php
$svcFor = $customer['services_for'] ? array_map('trim', explode(',', $customer['services_for'])) : [];
if ($svcFor):
    foreach ($svcFor as $s):
?>
              <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><?= htmlspecialchars($s) ?></span>
<?php
    endforeach;
else:
?>
              <span class="text-gray-400">None</span>
<?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-navy">Services</h2>
        <a href="service-add.php" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand text-white font-medium rounded-xl hover:bg-green-600 transition-colors brand-glow text-sm">
          <i data-lucide="plus" class="w-4 h-4"></i> Add Service
        </a>
      </div>

      <div class="card overflow-hidden mb-8">
        <div class="p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th>Service Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Next Due</th>
                <th>Status</th>
                <th>Staff</th>
              </tr>
            </thead>
            <tbody id="customer-services-body">
<?php if (empty($services)): ?>
            </tbody>
          </table>
        </div>
        <div class="flex flex-col items-center justify-center py-12 text-center">
          <i data-lucide="tool" class="w-10 h-10 text-gray-300 mb-2"></i>
          <p class="text-gray-400 text-sm">No services found for this customer</p>
        </div>
<?php else: foreach ($services as $s):
    $isRecurring = !empty($s['is_recurring']);
    $typeLabel = $isRecurring ? 'Recurring' : 'One-Time';
    $nextDue = $s['first_scheduled_date'] ? date('M j, Y', strtotime($s['first_scheduled_date'])) : '—';
    $staffName = isset($staff[$s['assigned_to']]) ? htmlspecialchars($staff[$s['assigned_to']]['name']) : '—';

    // Find latest task for this service
    $latestTask = null;
    foreach ($tasks as $t) {
        if ($t['service_id'] == $s['id']) {
            if (!$latestTask || $t['scheduled_date'] > $latestTask['scheduled_date']) {
                $latestTask = $t;
            }
        }
    }
    $statusHtml = $latestTask ? statusPill($latestTask['status']) : '<span class="text-xs text-gray-400">—</span>';
    $problemHtml = $s['problem'] ? '<div class="text-xs text-gray-400 mt-0.5">' . htmlspecialchars(substr($s['problem'], 0, 60)) . (strlen($s['problem']) > 60 ? '...' : '') . '</div>' : '';
?>
              <tr>
                <td data-label="Service Title" class="font-medium text-gray-900"><?= htmlspecialchars($s['title']) ?><?= $problemHtml ?></td>
                <td data-label="Category" class="text-gray-600"><?= catBadge($cats, $s['category_id'], $s['service_for']) ?></td>
                <td data-label="Type"><span class="badge <?= $isRecurring ? 'badge-info' : 'badge-pending' ?>"><?= $typeLabel ?></span></td>
                <td data-label="Next Due" class="text-gray-600"><?= $nextDue ?></td>
                <td data-label="Status"><?= $statusHtml ?></td>
                <td data-label="Staff" class="text-gray-600">
                  <span><?= $staffName ?></span>
                  <button class="cd-reassign-svc text-purple-500 hover:text-purple-700 ml-1 align-middle" title="Change staff" data-service-id="<?= $s['id'] ?>" data-current-staff="<?= $s['assigned_to'] ?? '' ?>">
                    <i data-lucide="user-switch" class="w-3 h-3"></i>
                  </button>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
      </div>

      <h2 class="text-lg font-semibold text-navy mb-4">Tasks</h2>
      <div class="card overflow-hidden mb-8">
        <div class="p-0">
          <table class="data-table">
            <thead>
              <tr>
                <th class="w-12">Done</th>
                <th>Task</th>
                <th>Date</th>
                <th>Status</th>
                <th>Assigned To</th>
              </tr>
            </thead>
            <tbody id="customer-tasks-body">
<?php if (empty($tasks)): ?>
            </tbody>
          </table>
        </div>
        <div class="flex flex-col items-center justify-center py-12 text-center">
          <i data-lucide="clipboard-x" class="w-10 h-10 text-gray-300 mb-2"></i>
          <p class="text-gray-400 text-sm">No tasks found for this customer</p>
        </div>
<?php else:
    $taskRows = array_values($tasks);
    usort($taskRows, function($a, $b) { return strcmp($b['scheduled_date'], $a['scheduled_date']); });
    foreach ($taskRows as $t):
        $isCompleted = $t['status'] === 'completed';
        $today = date('Y-m-d');
        $dateCls = (!$isCompleted && $t['scheduled_date'] < $today) ? 'text-danger' : 'text-gray-600';
        $dateFormatted = date('M j, Y', strtotime($t['scheduled_date']));
        $staffName = isset($staff[$t['assigned_to']]) ? htmlspecialchars($staff[$t['assigned_to']]['name']) : '—';
        $statusHtml = statusPill($t['status']);

        $checkbox = $isCompleted
            ? '<span class="inline-flex items-center justify-center w-5 h-5 rounded bg-brand text-white"><i data-lucide="check" class="w-3.5 h-3.5"></i></span>'
            : '<button class="cd-task-check w-5 h-5 rounded border-2 border-gray-300 hover:border-brand transition-colors align-middle" title="Mark complete" data-task-id="' . $t['id'] . '"></button>';
?>
              <tr>
                <td data-label="Done"><?= $checkbox ?></td>
                <td data-label="Task" class="font-medium text-gray-900"><?= htmlspecialchars($t['title']) ?></td>
                <td data-label="Date" class="<?= $dateCls ?>"><?= $dateFormatted ?></td>
                <td data-label="Status"><?= $statusHtml ?></td>
                <td data-label="Assigned To" class="text-gray-600">
                  <span><?= $staffName ?></span>
                  <button class="cd-reassign-task text-purple-500 hover:text-purple-700 ml-1 align-middle" title="Change staff" data-task-id="<?= $t['id'] ?>" data-current-staff="<?= $t['assigned_to'] ?? '' ?>">
                    <i data-lucide="user-switch" class="w-3 h-3"></i>
                  </button>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
      </div>

    </div>
  </div>

  <!-- Complete Task Modal -->
  <div id="cd-complete-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-lg font-bold text-gray-900">Complete Task</h3>
        <button onclick="closeCdComplete()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <p id="cd-complete-customer" class="text-sm font-medium text-gray-500 mb-4"></p>
      <input type="hidden" id="cd-complete-task-id" value="">
      <div class="space-y-4">
        <div>
          <label for="cd-complete-date" class="block text-sm font-semibold text-gray-700 mb-1.5">Date</label>
          <input type="date" id="cd-complete-date" class="form-input">
        </div>
        <div>
          <label for="cd-complete-note" class="block text-sm font-semibold text-gray-700 mb-1.5">Note</label>
          <textarea id="cd-complete-note" rows="3" class="form-textarea" placeholder="e.g. changed RO housing, pre filter — rs 1500"></textarea>
        </div>
        <div class="flex gap-3 pt-1">
          <button onclick="closeCdComplete()" class="btn btn-md btn-secondary flex-1">Cancel</button>
          <button id="cd-complete-confirm" class="btn btn-md btn-primary flex-1 brand-glow">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <!-- CSRF token for AJAX requests -->
  <?= csrfHiddenField() ?>
  <script>
    // Fallback: if navigated here via localStorage (old links), redirect with proper URL
    (function() {
      var lsId = localStorage.getItem('fscrm_currentCustomerId');
      var urlId = new URLSearchParams(window.location.search).get('id');
      if (!urlId && lsId) {
        window.location.replace('customer-detail.php?id=' + encodeURIComponent(lsId));
      }
    })();
  </script>
  <script>
<?php
$taskJson = json_encode(array_values($tasks));
$customerJson = json_encode($customer);
$servicesJson = json_encode($services);
?>
  window.__CUSTOMER = <?= $customerJson ?>;
  window.__SERVICES = <?= $servicesJson ?>;
  window.__TASKS = <?= $taskJson ?>;
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      lucide.createIcons();

    (function() {
      var customerId = <?= $customerId ?>;
      var customer = window.__CUSTOMER;
      var servicesList = window.__SERVICES || [];
      var tasksList = window.__TASKS || [];

      function getCategoryColor(service) {
        var map = {
          'RO': 'bg-emerald-100 text-emerald-700',
          'TV': 'bg-blue-100 text-blue-700',
          'Refrigerator': 'bg-cyan-100 text-cyan-700',
          'AC': 'bg-orange-100 text-orange-700',
          'Washing Machine': 'bg-purple-100 text-purple-700',
          'Other': 'bg-gray-100 text-gray-700'
        };
        return map[service] || 'bg-gray-100 text-gray-700';
      }

      // ====== Complete Task Modal ======
      function cdOpenComplete(taskId) {
        var task = tasksList.find(function(t) { return String(t.id) === String(taskId); });
        document.getElementById('cd-complete-task-id').value = taskId;
        document.getElementById('cd-complete-customer').textContent = customer.name + (task ? ' \u2014 ' + task.title : '');
        document.getElementById('cd-complete-date').value = (function() { var d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); })();
        document.getElementById('cd-complete-note').value = '';
        document.getElementById('cd-complete-modal').style.display = 'flex';
      }

      window.closeCdComplete = function() {
        document.getElementById('cd-complete-modal').style.display = 'none';
      };

      document.getElementById('cd-complete-confirm').addEventListener('click', function() {
        var id = document.getElementById('cd-complete-task-id').value;
        var date = document.getElementById('cd-complete-date').value;
        var note = document.getElementById('cd-complete-note').value.trim();
        if (!date) { window.showToast('Please select a date.', 'error'); return; }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'task-complete-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
          if (xhr.status === 200) {
            window.closeCdComplete();
            window.location.reload();
            window.showToast('Task completed.', 'success');
          }
        };
        xhr.send('id=' + encodeURIComponent(id) + '&date=' + encodeURIComponent(date) + '&note=' + encodeURIComponent(note));
      });

      document.getElementById('cd-complete-modal').addEventListener('click', function(e) {
        if (e.target === this) window.closeCdComplete();
      });

      // Wire up task check buttons
      document.querySelectorAll('.cd-task-check').forEach(function(btn) {
        btn.addEventListener('click', function() { cdOpenComplete(this.getAttribute('data-task-id')); });
      });

      try { lucide.createIcons(); } catch(e) {}

      // ========== REASSIGN SERVICE ==========
      document.querySelectorAll('.cd-reassign-svc').forEach(function (btn) {
        btn.addEventListener('click', function () {
          window.reassignStaff({
            entityType: 'service',
            entityId: parseInt(this.dataset.serviceId, 10),
            currentStaffId: this.dataset.currentStaff || null,
            onSuccess: function () { window.location.reload(); }
          });
        });
      });

      // ========== REASSIGN TASK ==========
      document.querySelectorAll('.cd-reassign-task').forEach(function (btn) {
        btn.addEventListener('click', function () {
          window.reassignStaff({
            entityType: 'task',
            entityId: parseInt(this.dataset.taskId, 10),
            currentStaffId: this.dataset.currentStaff || null,
            onSuccess: function () { window.location.reload(); }
          });
        });
      });
    })();
    });
  </script>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>
