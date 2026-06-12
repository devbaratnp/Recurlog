<?php
$pageTitle = 'Add Service';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notification_helper.php';
requireAuth();
$db = getDB();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireCsrfToken();
  $customerId = intval($_POST['customer_id'] ?? 0);
  $categoryId = intval($_POST['category_id'] ?? 0);
  $serviceFor = trim($_POST['service_for'] ?? '');
  $problem = trim($_POST['problem'] ?? '');
  $isRecurring = !empty($_POST['is_recurring']) ? 1 : 0;
  $firstDate = $_POST['first_scheduled_date'] ?? '';
  $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
  $notes = trim($_POST['notes'] ?? '');
  $error = null;

  if (!$customerId || $customerId === 0) {
    $error = 'Please select a customer.';
  } elseif (!$serviceFor) {
    $error = 'Please select a service type.';
  } elseif (!$categoryId) {
    $error = 'Please select a category.';
  } elseif (!$firstDate) {
    $error = 'Please select a first scheduled date.';
  }

  if (!$error) {
    $db->begin_transaction();
    try {
      $catStmt = $db->prepare("SELECT name FROM fscrm_categories WHERE id = ?");
      $catStmt->bind_param('i', $categoryId);
      $catStmt->execute();
      $catRow = $catStmt->get_result()->fetch_assoc();
      $title = ($catRow ? $catRow['name'] : 'Service') . ' - ' . $serviceFor;

      $recValue = $isRecurring ? intval($_POST['rec_value'] ?? 1) : null;
      $recUnit = $isRecurring ? ($_POST['rec_unit'] ?? 'days') : null;
      $repeatFrom = $isRecurring ? ($_POST['repeat_from'] ?? 'last-done') : null;

      $stmt = $db->prepare("INSERT INTO fscrm_services (customer_id, category_id, service_for, title, problem, is_recurring, first_scheduled_date, assigned_to, notes, rec_value, rec_unit, repeat_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param('iisssisisiss', $customerId, $categoryId, $serviceFor, $title, $problem, $isRecurring, $firstDate, $assignedTo, $notes, $recValue, $recUnit, $repeatFrom);
      $stmt->execute();
      $serviceId = $db->insert_id;

      $taskStmt = $db->prepare("INSERT INTO fscrm_tasks (service_id, customer_id, title, status, scheduled_date, assigned_to, notes, category_id) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)");
      $taskStmt->bind_param('iissssi', $serviceId, $customerId, $title, $firstDate, $assignedTo, $notes, $categoryId);
      $taskStmt->execute();

      $custStmt = $db->prepare("SELECT name FROM fscrm_customers WHERE id = ?");
      $custStmt->bind_param('i', $customerId);
      $custStmt->execute();
      $custRow = $custStmt->get_result()->fetch_assoc();
      $notifText = 'New service "' . $title . '" added for ' . ($custRow ? $custRow['name'] : 'customer');
      createNotification($db, $notifText, 'service', $serviceId);

      $db->commit();
      setFlash('Service "' . $title . '" added successfully for ' . ($custRow ? $custRow['name'] : 'customer'));
      header('Location: customer-detail.php');
      exit;
    } catch (Exception $e) {
      $db->rollback();
      error_log('Service add failed: ' . $e->getMessage());
      $error = 'Failed to save service. Please try again.';
    }
  }
}

// Load data
$customers = $db->query("SELECT id, name FROM fscrm_customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$categories = $db->query("SELECT id, name FROM fscrm_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$staffList = $db->query("SELECT id, name, avatar FROM fscrm_staff ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$serviceTypeRows = $db->query("SELECT name FROM fscrm_service_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <a href="customer-detail.php" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
          </a>
          <h1 class="page-title">Add Service</h1>
        </div>
      </div>
    </header>

    <div class="p-4 md:p-6 lg:p-8 max-w-3xl mx-auto">

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" id="service-form"><?= csrfHiddenField() ?>
        <div class="p-5 md:p-7 space-y-6">
          <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <input type="hidden" name="service_for" id="input-service-for" value="">
          <input type="hidden" name="is_recurring" id="input-is-recurring" value="1">

          <!-- Customer Dropdown -->
          <div>
            <label for="service-customer" class="form-label">Customer</label>
            <select id="service-customer" name="customer_id" class="form-select">
              <option value="">Select Customer</option>
              <option value="__add_new__">+ Add New Customer</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Service For -->
          <div>
            <label class="form-label">Service For</label>
            <div id="service-for-chips" class="flex flex-wrap gap-2">
              <?php foreach ($serviceTypeRows as $st): ?>
                <button type="button" data-value="<?= htmlspecialchars($st['name']) ?>" class="chip-btn px-4 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:border-brand hover:text-brand transition-colors"><?= htmlspecialchars($st['name']) ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Problem / Issue Description -->
          <div>
            <label for="service-problem" class="form-label">Problem / Issue Description</label>
             <textarea id="service-problem" name="problem" rows="3" class="form-textarea" placeholder="Describe the customer's problem or issue in detail..." maxlength="1000"><?= htmlspecialchars($_POST['problem'] ?? '') ?></textarea>
          </div>

          <!-- Category -->
          <div>
            <label for="service-category" class="form-label">Category</label>
            <select id="service-category" name="category_id" class="form-select">
              <option value="">Select Category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Service Type Toggle -->
          <div>
            <label class="form-label">Service Type</label>
            <div class="flex rounded-lg border border-gray-300 overflow-hidden w-fit">
              <button type="button" id="service-type-onetime" data-value="onetime" class="px-5 py-2 text-sm font-medium transition-colors">One Time</button>
              <button type="button" id="service-type-recurring" data-value="recurring" class="px-5 py-2 text-sm font-medium transition-colors bg-brand text-white">Recurring</button>
            </div>
          </div>

          <!-- Recurrence Section -->
          <div id="recurrence-section" class="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex items-center gap-2">
              <i data-lucide="repeat" class="w-4 h-4 text-brand"></i>
              <span class="text-sm font-semibold text-gray-700">Recurrence Settings</span>
            </div>

            <div class="flex items-center gap-3">
              <label for="rec-value" class="text-sm text-gray-600 whitespace-nowrap">Repeat Every</label>
              <input type="number" id="rec-value" name="rec_value" value="1" min="1" class="form-input w-20 text-center">
              <select id="rec-unit" name="rec_unit" class="form-select w-auto">
                <option value="days">Days</option>
                <option value="weeks">Weeks</option>
                <option value="months">Months</option>
                <option value="years">Years</option>
              </select>
            </div>

            <div>
              <label class="form-label">Repeat From</label>
              <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                  <input type="radio" name="repeat_from" value="last-done" checked class="accent-brand">
                  Last Done Date
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                  <input type="radio" name="repeat_from" value="fixed-schedule" class="accent-brand">
                  Fixed Schedule
                </label>
              </div>
            </div>

            <div id="rec-preview" class="text-sm text-gray-500 italic bg-white px-3 py-2.5 rounded-lg border border-gray-200">
              This service will repeat every 1 day(s) from the Last Done Date
            </div>
          </div>

          <!-- First Scheduled Date -->
          <div>
            <label for="service-first-date" class="form-label">First Scheduled Date</label>
            <input type="date" id="service-first-date" name="first_scheduled_date" class="form-input max-w-xs">
          </div>

          <!-- Assign To -->
          <div>
            <label for="service-staff" class="form-label">Assign To</label>
            <select id="service-staff" name="assigned_to" class="form-select max-w-xs">
              <option value="">Select Staff</option>
              <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Notes -->
          <div>
            <label for="service-notes" class="form-label">Notes</label>
             <textarea id="service-notes" name="notes" rows="4" class="form-textarea" placeholder="Any additional notes..." maxlength="1000"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
          </div>

          <!-- Save Button -->
          <div class="pt-2">
            <button type="submit" class="btn btn-md btn-primary brand-glow">
              <i data-lucide="save" class="w-4 h-4"></i>Save Service
            </button>
          </div>
        </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // ========== CHIP SELECTION ==========
    var selectedServiceFor = null;

    document.addEventListener('DOMContentLoaded', function () {
      var chips = document.getElementById('service-for-chips');
      if (chips) {
        chips.querySelectorAll('.chip-btn').forEach(function (btn) {
          btn.addEventListener('click', function () {
            chips.querySelectorAll('.chip-btn').forEach(function (b) {
              b.className = 'chip-btn px-4 py-2 rounded-full text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:border-brand hover:text-brand transition-colors';
            });
            btn.className = 'chip-btn px-4 py-2 rounded-full text-sm font-medium border border-brand bg-brand text-white transition-colors';
            selectedServiceFor = btn.dataset.value;
            document.getElementById('input-service-for').value = selectedServiceFor;
          });
        });
      }

      // Default date
      var today = new Date();
      var y = today.getFullYear();
      var m = String(today.getMonth() + 1).padStart(2, '0');
      var d = String(today.getDate()).padStart(2, '0');
      document.getElementById('service-first-date').value = y + '-' + m + '-' + d;

      // Service type toggle
      var onetimeBtn = document.getElementById('service-type-onetime');
      var recurringBtn = document.getElementById('service-type-recurring');
      var section = document.getElementById('recurrence-section');
      var isRecurringInput = document.getElementById('input-is-recurring');

      function setActive(active) {
        [onetimeBtn, recurringBtn].forEach(function (btn) {
          btn.className = 'px-5 py-2 text-sm font-medium transition-colors';
        });
        if (active === 'recurring') {
          recurringBtn.classList.add('bg-brand', 'text-white');
          onetimeBtn.classList.add('bg-gray-100', 'text-gray-600');
          section.style.display = 'block';
          isRecurringInput.value = '1';
        } else {
          onetimeBtn.classList.add('bg-brand', 'text-white');
          recurringBtn.classList.add('bg-gray-100', 'text-gray-600');
          section.style.display = 'none';
          isRecurringInput.value = '0';
        }
        updateRecurrencePreview();
      }

      onetimeBtn.addEventListener('click', function () { setActive('onetime'); });
      recurringBtn.addEventListener('click', function () { setActive('recurring'); });
      setActive('recurring');

      // Recurrence preview
      document.getElementById('rec-value').addEventListener('input', updateRecurrencePreview);
      document.getElementById('rec-unit').addEventListener('change', updateRecurrencePreview);
      document.querySelectorAll('input[name="repeat_from"]').forEach(function (el) {
        el.addEventListener('change', updateRecurrencePreview);
      });

      // Add new customer redirect
      document.getElementById('service-customer').addEventListener('change', function () {
        if (this.value === '__add_new__') {
          window.location.href = 'customer-add.php';
        }
      });

      // Sidebar highlight
      document.querySelectorAll('.sidebar-nav a').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && (href.indexOf('customer') > -1)) {
          link.classList.add('bg-brand/10', 'text-brand');
          link.classList.remove('text-white/70', 'hover:text-white');
        }
      });
    });

    function updateRecurrencePreview() {
      var val = document.getElementById('rec-value').value || '1';
      var unit = document.getElementById('rec-unit').value;
      var repeatEl = document.querySelector('input[name="repeat_from"]:checked');
      var from = repeatEl ? (repeatEl.value === 'fixed-schedule' ? 'Fixed Schedule' : 'Last Done Date') : 'Last Done Date';
      var preview = document.getElementById('rec-preview');
      preview.textContent = 'This service will repeat every ' + val + ' ' + unit + ' from the ' + from;
    }
  </script>

  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
