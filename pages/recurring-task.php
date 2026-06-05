<?php
$pageTitle = 'Recurring Task';
require_once __DIR__ . '/../includes/config.php';
requireAuth();
$db = getDB();

function statusPill($status) {
  $configs = [
    'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'icon' => 'clock', 'label' => 'Pending'],
    'completed' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'icon' => 'check-circle', 'label' => 'Completed'],
    'missed' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'icon' => 'alert-circle', 'label' => 'Missed']
  ];
  $cfg = $configs[$status] ?? $configs['pending'];
  return '<span class="status-pill" style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:' . $cfg['bg'] . ';color:' . $cfg['text'] . '"><i data-lucide="' . $cfg['icon'] . '" class="w-3 h-3"></i> ' . $cfg['label'] . '</span>';
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCsrfToken();
  $customerId = intval($_POST['customer_id'] ?? 0);
  $serviceFor = trim($_POST['service_for'] ?? '');
  $problem = trim($_POST['problem'] ?? '');
  $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
  $firstDate = $_POST['first_scheduled_date'] ?? '';
  $recValue = intval($_POST['rec_value'] ?? 1);
  $recUnit = $_POST['rec_unit'] ?? 'days';
  $repeatFrom = $_POST['repeat_from'] ?? 'last-done';
  $notes = trim($_POST['notes'] ?? '');
  $completedBy = trim($_POST['completed_by'] ?? '');
  $completedDate = $_POST['completed_date'] ?? '';
  $recName = trim($_POST['rec_name'] ?? '');
  $recContact = trim($_POST['rec_contact'] ?? '');
  $signature = trim($_POST['signature_data'] ?? '');
  $error = null;

  if (!$customerId) {
    $error = 'Please select a customer.';
  } elseif (!$serviceFor) {
    $error = 'Please select a service.';
  } elseif (!$problem) {
    $error = 'Please enter the problem description.';
  }

  if (!$error) {
    $db->begin_transaction();
    try {
      $custStmt = $db->prepare("SELECT name FROM fscrm_customers WHERE id = ?");
      $custStmt->bind_param('i', $customerId);
      $custStmt->execute();
      $custRow = $custStmt->get_result()->fetch_assoc();
      $title = $serviceFor . ($custRow ? ' - ' . $custRow['name'] : '');

      $stmt = $db->prepare("INSERT INTO fscrm_services (customer_id, category_id, service_for, title, problem, is_recurring, first_scheduled_date, assigned_to, notes, rec_value, rec_unit, repeat_from) VALUES (?, NULL, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param('issssiisss', $customerId, $serviceFor, $title, $problem, $firstDate, $assignedTo, $notes, $recValue, $recUnit, $repeatFrom);
      $stmt->execute();
      $serviceId = $db->insert_id;

      $taskTitle = $title;
      $taskStmt = $db->prepare("INSERT INTO fscrm_tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, notes) VALUES (?, ?, ?, 'pending', ?, ?, ?)");
      $taskStmt->bind_param('iissis', $serviceId, $customerId, $taskTitle, $firstDate, $assignedTo, $notes);
      $taskStmt->execute();
      $taskId = $db->insert_id;

      if ($completedBy || $completedDate || $recName || $signature) {
        $compDate = $completedDate ?: $firstDate;
        $compStmt = $db->prepare("UPDATE fscrm_tasks SET status='completed', completed_date=?, completed_by=?, received_name=?, received_contact=?, signature=? WHERE id=?");
        $compStmt->bind_param('sssssi', $compDate, $completedBy, $recName, $recContact, $signature, $taskId);
        $compStmt->execute();
      }

      $notifText = 'New recurring task "' . $title . '" added for ' . ($custRow ? $custRow['name'] : 'customer');
      $notifStmt = $db->prepare("INSERT INTO fscrm_notifications (text, type, related_id) VALUES (?, 'service', ?)");
      $notifStmt->bind_param('si', $notifText, $serviceId);
      $notifStmt->execute();

      $db->commit();
      header('Location: customer-detail.php');
      exit;
    } catch (Exception $e) {
      $db->rollback();
      error_log('Recurring task add failed: ' . $e->getMessage());
      $error = 'Failed to save task. Please try again.';
    }
  }
}

// Load data
$customers = $db->query("SELECT id, name FROM fscrm_customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$serviceTypeRows = $db->query("SELECT name FROM fscrm_service_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$staffList = $db->query("SELECT id, name FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch recurring tasks
$rtResult = $db->query("
  SELECT t.*, c.name AS customer_name, s.name AS staff_name
  FROM fscrm_tasks t
  LEFT JOIN fscrm_customers c ON t.customer_id = c.id
  LEFT JOIN fscrm_staff s ON t.assigned_to = s.id
  LEFT JOIN fscrm_services sv ON t.service_id = sv.id
  WHERE sv.is_recurring = 1
  ORDER BY t.scheduled_date DESC
  LIMIT 100
");
$recurringTasks = $rtResult ? $rtResult->fetch_all(MYSQLI_ASSOC) : [];

function statusPillShort($status) {
  $map = ['pending'=>'#FEF3C7:#92400E','completed'=>'#D1FAE5:#065F46','missed'=>'#FEE2E2:#991B1B'];
  $cfg = explode(':', $map[$status] ?? $map['pending']);
  return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:'.$cfg[0].';color:'.$cfg[1].'">'.ucfirst($status).'</span>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Recurring Tasks - Recurlog</title>
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
  <style>
    .signature-pad {
      width: 100%; height: 160px; border: 1px dashed #cbd5e1; border-radius: 10px;
      background: #fff; touch-action: none; cursor: crosshair; display: block;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-content">
      <header class="page-header">
        <div class="page-header-inner">
          <div class="flex items-center gap-2">
            <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
              <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            <h1 class="page-title">Recurring Tasks</h1>
          </div>
          <button id="show-add-form" class="btn btn-sm btn-primary flex items-center gap-1.5">
            <i data-lucide="plus" class="w-4 h-4"></i> New
          </button>
        </div>
      </header>

    <div class="p-4 md:p-6 lg:p-8 max-w-5xl mx-auto">

      <!-- Task List -->
      <div id="task-list-section">
        <?php if (empty($recurringTasks)): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center mb-4">
            <i data-lucide="repeat" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
            <p class="text-gray-500 mb-3">No recurring tasks yet.</p>
            <button onclick="document.getElementById('show-add-form').click()" class="btn btn-sm btn-primary">Create First Task</button>
          </div>
        <?php else: ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-4">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Task</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Customer</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Staff</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Date</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Status</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wide">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recurringTasks as $t): ?>
                  <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                      <a href="task-detail.php?id=<?= $t['id'] ?>" class="font-medium text-navy hover:text-brand transition-colors"><?= htmlspecialchars($t['title']) ?></a>
                    </td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($t['customer_name'] ?: '—') ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($t['staff_name'] ?: '—') ?></td>
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= htmlspecialchars($t['scheduled_date']) ?></td>
                    <td class="px-4 py-3 text-center"><?= statusPillShort($t['status']) ?></td>
                    <td class="px-4 py-3 text-right">
                      <a href="task-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost p-1.5" title="Edit">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Add Form (hidden by default) -->
      <div id="add-form-section" class="hidden">
      <div class="flex items-center gap-2 mb-4">
        <button id="hide-add-form" class="text-sm text-gray-500 hover:text-navy flex items-center gap-1">
          <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to list
        </button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" id="rt-form"><?= csrfHiddenField() ?>
        <div class="p-5 md:p-7 space-y-6">

          <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <input type="hidden" name="signature_data" id="rt-signature-data" value="">

          <!-- Customer -->
          <div>
            <label for="rt-customer" class="block text-sm font-semibold text-gray-700 mb-1.5">Customer <span class="text-danger">*</span></label>
            <div class="flex gap-2 items-center">
              <select id="rt-customer" name="customer_id" class="form-select flex-1">
                <option value="">Select Customer</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <a href="customer-add.php" class="px-3 py-2.5 bg-brand text-white rounded-lg text-sm font-semibold flex items-center gap-1 whitespace-nowrap" title="Add new customer">
                <i data-lucide="plus" class="w-4 h-4"></i>
              </a>
            </div>
          </div>

          <!-- Service For -->
          <div>
            <label for="rt-service" class="block text-sm font-semibold text-gray-700 mb-1.5">Service For <span class="text-danger">*</span></label>
            <div class="flex gap-2 items-center">
              <select id="rt-service" name="service_for" class="form-select flex-1">
                <option value="">Select Service</option>
                <?php foreach ($serviceTypeRows as $st): ?>
                  <option value="<?= htmlspecialchars($st['name']) ?>"><?= htmlspecialchars($st['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="rt-service-add-btn" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-600 flex items-center gap-1 whitespace-nowrap" title="Add service type">
                <i data-lucide="plus" class="w-4 h-4"></i>
              </button>
            </div>
            <div id="rt-service-add-row" class="mt-2 hidden">
              <div class="flex gap-2">
                <input type="text" id="rt-new-service" class="form-input flex-1" placeholder="New service type (e.g. Water Heater)">
                <button type="button" id="rt-service-save" class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-semibold">Save</button>
                <button type="button" id="rt-service-cancel" class="px-4 py-2 border border-gray-200 rounded-lg text-sm">Cancel</button>
              </div>
            </div>
          </div>

          <!-- Problem Description -->
          <div>
            <label for="rt-problem" class="block text-sm font-semibold text-gray-700 mb-1.5">Problem Description <span class="text-danger">*</span></label>
             <textarea id="rt-problem" name="problem" rows="3" class="form-textarea" placeholder="Describe the problem or work to be done..." maxlength="1000"><?= htmlspecialchars($_POST['problem'] ?? '') ?></textarea>
          </div>

          <!-- Assign To -->
          <div>
            <label for="rt-staff" class="block text-sm font-semibold text-gray-700 mb-1.5">Order Assign To</label>
            <select id="rt-staff" name="assigned_to" class="form-select">
              <option value="">Select Staff</option>
              <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Schedule -->
          <div>
            <label for="rt-date" class="block text-sm font-semibold text-gray-700 mb-1.5">Schedule Task</label>
            <input type="date" id="rt-date" name="first_scheduled_date" class="form-input max-w-xs">
          </div>

          <!-- Recurrence Setting -->
          <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4">
            <div class="flex items-center gap-2">
              <i data-lucide="repeat" class="w-4 h-4 text-brand"></i>
              <span class="text-sm font-bold text-navy">Recurrence Setting</span>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
              <label for="rt-rec-value" class="text-sm text-gray-600 whitespace-nowrap">Repeat Every</label>
              <input type="number" id="rt-rec-value" name="rec_value" value="1" min="1" class="form-input w-20 text-center">
              <select id="rt-rec-unit" name="rec_unit" class="form-select w-auto">
                <option value="days">Days</option>
                <option value="weeks">Weeks</option>
                <option value="months">Months</option>
                <option value="years">Years</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Repeat From</label>
              <div class="flex gap-4 flex-wrap">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                  <input type="radio" name="repeat_from" value="last-done" checked class="accent-brand"> Last Done Date
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                  <input type="radio" name="repeat_from" value="fixed-schedule" class="accent-brand"> Fixed Schedule
                </label>
              </div>
            </div>

            <div id="rt-rec-preview" class="text-sm text-gray-500 italic bg-white px-3 py-2.5 rounded-lg border border-gray-200">
              This service will repeat every 1 day(s) from the Last Done Date
            </div>

            <div>
              <label for="rt-note" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
               <textarea id="rt-note" name="notes" rows="3" class="form-textarea" placeholder="Any additional notes..." maxlength="1000"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
          </div>

          <!-- In Report (optional completion) -->
          <div class="pt-2 border-t border-gray-100">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-bold text-navy">In Report</h3>
              <span class="text-xs text-gray-400">Fill only if the first task is already completed</span>
            </div>

            <div class="space-y-4">
              <div>
                <label for="rt-completed-by" class="block text-sm font-medium text-gray-600 mb-1">Task Completed By</label>
                 <input type="text" id="rt-completed-by" name="completed_by" class="form-input" placeholder="Staff / person name" maxlength="100">
              </div>
              <div>
                <label for="rt-completed-date" class="block text-sm font-medium text-gray-600 mb-1">Task Completed Date</label>
                <input type="date" id="rt-completed-date" name="completed_date" class="form-input max-w-xs">
              </div>

              <div>
                <p class="text-sm font-bold text-navy mb-2">Received By</p>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label for="rt-rec-name" class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                     <input type="text" id="rt-rec-name" name="rec_name" class="form-input" placeholder="Receiver name" maxlength="100">
                  </div>
                  <div>
                    <label for="rt-rec-contact" class="block text-xs font-medium text-gray-500 mb-1">Contact</label>
                     <input type="text" id="rt-rec-contact" name="rec_contact" class="form-input" placeholder="Phone" maxlength="20">
                  </div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-medium text-gray-500">Signature</label>
                  <button type="button" id="sig-clear" class="text-xs text-brand hover:underline">Clear</button>
                </div>
                <canvas id="sig-pad" class="signature-pad" width="600" height="160"></canvas>
              </div>
            </div>
          </div>

          <!-- Save -->
          <div class="pt-2 flex gap-3">
            <a href="dashboard.php" class="btn btn-md btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-md btn-primary brand-glow">
              <i data-lucide="save" class="w-4 h-4"></i> Save Task
            </button>
          </div>

        </div>
        </form>
      </div>
    </div>
    </div>
  </div>

  <script>
    // ===== Toggle list/add-form =====
    document.addEventListener('DOMContentLoaded', function () {
      var listSection = document.getElementById('task-list-section');
      var formSection = document.getElementById('add-form-section');
      var showBtn = document.getElementById('show-add-form');
      var hideBtn = document.getElementById('hide-add-form');
      var formInited = false;
      if (showBtn) showBtn.addEventListener('click', function () {
        listSection.classList.add('hidden'); formSection.classList.remove('hidden');
        if (!formInited) {
          formInited = true;
          var now = new Date();
          var d = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
          var dateInput = document.getElementById('rt-date');
          if (dateInput) dateInput.value = d;
          initSignaturePad();
          // Trigger recurrence preview
          var updateFn = document.getElementById('rt-rec-preview');
          if (updateFn) updateFn.textContent = 'Every 1 days from Last Done Date';
        }
      });
      if (hideBtn) hideBtn.addEventListener('click', function () {
        formSection.classList.add('hidden'); listSection.classList.remove('hidden');
      });
      // Update recurrence preview on the fly (for when init happens)
    });

    // ===== Signature pad =====
    var sigCanvas = null, sigCtx = null, sigDrawing = false, sigHasInk = false;

    function initSignaturePad() {
      sigCanvas = document.getElementById('sig-pad');
      if (!sigCanvas) return;
      sigCtx = sigCanvas.getContext('2d');
      sigCtx.lineWidth = 2; sigCtx.lineCap = 'round'; sigCtx.strokeStyle = '#0B1E3D';

      function pos(e) {
        var rect = sigCanvas.getBoundingClientRect();
        var src = (e.touches && e.touches[0]) ? e.touches[0] : e;
        return {
          x: (src.clientX - rect.left) * (sigCanvas.width / rect.width),
          y: (src.clientY - rect.top) * (sigCanvas.height / rect.height)
        };
      }
      function start(e) { e.preventDefault(); sigDrawing = true; var p = pos(e); sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); }
      function move(e) { if (!sigDrawing) return; e.preventDefault(); var p = pos(e); sigCtx.lineTo(p.x, p.y); sigCtx.stroke(); sigHasInk = true; }
      function end() { sigDrawing = false; }

      sigCanvas.addEventListener('mousedown', start);
      sigCanvas.addEventListener('mousemove', move);
      window.addEventListener('mouseup', end);
      sigCanvas.addEventListener('touchstart', start, { passive: false });
      sigCanvas.addEventListener('touchmove', move, { passive: false });
      sigCanvas.addEventListener('touchend', end);

      document.getElementById('sig-clear').addEventListener('click', function () {
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        sigHasInk = false;
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      // Save signature data before form submit
      document.getElementById('rt-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (sigHasInk && sigCanvas) {
          var raw = sigCanvas.toDataURL('image/png');
          document.getElementById('rt-signature-data').value = await window.compressSignature(raw);
        }
        this.submit();
      });

      // Recurrence preview
      function updatePreview() {
        var val = document.getElementById('rt-rec-value').value || '1';
        var unit = document.getElementById('rt-rec-unit').value;
        var repeatEl = document.querySelector('input[name="repeat_from"]:checked');
        var from = (repeatEl && repeatEl.value === 'fixed-schedule') ? 'Fixed Schedule' : 'Last Done Date';
        document.getElementById('rt-rec-preview').textContent =
          'This service will repeat every ' + val + ' ' + unit + ' from the ' + from;
      }

      document.getElementById('rt-rec-value').addEventListener('input', updatePreview);
      document.getElementById('rt-rec-unit').addEventListener('change', updatePreview);
      document.querySelectorAll('input[name="repeat_from"]').forEach(function (el) {
        el.addEventListener('change', updatePreview);
      });
      updatePreview();

      // ===== Add service type inline =====
      var row = document.getElementById('rt-service-add-row');
      document.getElementById('rt-service-add-btn').addEventListener('click', function () {
        row.classList.remove('hidden');
        document.getElementById('rt-new-service').focus();
      });
      document.getElementById('rt-service-cancel').addEventListener('click', function () {
        row.classList.add('hidden');
        document.getElementById('rt-new-service').value = '';
      });
      document.getElementById('rt-service-save').addEventListener('click', function () {
        var name = document.getElementById('rt-new-service').value.trim();
        if (!name) { window.showToast('Enter a service type name.', 'error'); return; }

        fetch('../api/service_types.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name: name })
        }).then(function (r) { return r.json(); }).then(function (res) {
          if (res.success) {
            window.location.reload();
          } else {
            window.showToast(res.error || 'Failed to save.', 'error');
          }
        }).catch(function () {
          window.showToast('Network error.', 'error');
        });
      });
    });
  </script>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>
