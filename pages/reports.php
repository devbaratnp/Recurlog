<?php
require_once '../includes/config.php';
$db = getDB();

function statusPill($status) {
    $map = array(
        'completed' => 'badge-completed',
        'in_progress' => 'badge-info',
        'pending' => 'badge-pending',
        'scheduled' => 'badge-pending',
        'missed' => 'badge-danger',
        'overdue' => 'badge-danger',
        'cancelled' => 'badge-secondary',
    );
    $class = isset($map[$status]) ? $map[$status] : 'badge-pending';
    return '<span class="badge ' . $class . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

$result = $db->query("SELECT t.*, c.name AS customer_name, s.name AS staff_name, cat.name AS category_name, cat.color AS category_color, sv.is_recurring FROM fscrm_tasks t LEFT JOIN fscrm_customers c ON t.customer_id = c.id LEFT JOIN fscrm_staff s ON t.assigned_to = s.id LEFT JOIN fscrm_categories cat ON t.category_id = cat.id LEFT JOIN fscrm_services sv ON t.service_id = sv.id ORDER BY t.scheduled_date DESC");
$tasks = array();
while ($r = $result->fetch_assoc()) {
    $tasks[] = array(
        'id' => (int)$r['id'],
        'title' => $r['title'],
        'customerId' => $r['customer_id'] ? (int)$r['customer_id'] : null,
        'customerName' => $r['customer_name'],
        'assignedTo' => $r['assigned_to'] ? (int)$r['assigned_to'] : null,
        'staffName' => $r['staff_name'],
        'scheduledDate' => $r['scheduled_date'],
        'completedDate' => $r['completed_date'],
        'status' => $r['status'],
        'notes' => $r['notes'],
        'serviceId' => $r['service_id'] ? (int)$r['service_id'] : null,
        'categoryId' => $r['category_id'] ? (int)$r['category_id'] : null,
        'categoryName' => $r['category_name'],
        'categoryColor' => $r['category_color'],
        'isRecurring' => $r['is_recurring'],
        'completedBy' => $r['completed_by'],
    );
}

$staffResult = $db->query("SELECT id, name FROM fscrm_staff ORDER BY name");
$staff = array();
while ($r = $staffResult->fetch_assoc()) {
    $staff[] = array('id' => (int)$r['id'], 'name' => $r['name']);
}

$catResult = $db->query("SELECT id, name, color FROM fscrm_categories ORDER BY name");
$categories = array();
while ($r = $catResult->fetch_assoc()) {
    $categories[] = array('id' => (int)$r['id'], 'name' => $r['name'], 'color' => $r['color']);
}

$custResult = $db->query("SELECT id, name FROM fscrm_customers ORDER BY name");
$customers = array();
while ($r = $custResult->fetch_assoc()) {
    $customers[] = array('id' => (int)$r['id'], 'name' => $r['name']);
}

$tasksJson = json_encode($tasks);
$staffJson = json_encode($staff);
$categoriesJson = json_encode($categories);
$customersJson = json_encode($customers);

$pageTitle = 'Reports';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Reports - Recurlog</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .filter-tab { cursor: pointer; }
  </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen">
<?php require_once '../includes/header.php'; ?>
  <div class="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <h1 class="page-title">Reports</h1>
        </div>
      </div>
    </header>

    <div class="p-4 sm:p-6 space-y-6">
      <section class="card p-5 fade-in" data-section="recurring">
        <h2 class="text-lg font-bold text-navy mb-4">Recurring Tasks</h2>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div class="flex flex-wrap gap-2" data-filters="recurring">
            <button data-filter="all" class="filter-tab btn btn-sm" style="background:var(--color-primary);color:#fff">All</button>
            <button data-filter="missed" class="filter-tab btn btn-sm btn-secondary">Missed</button>
            <button data-filter="today" class="filter-tab btn btn-sm btn-secondary">Today</button>
            <button data-filter="thisWeek" class="filter-tab btn btn-sm btn-secondary">This Week</button>
            <button data-filter="thisMonth" class="filter-tab btn btn-sm btn-secondary">This Month</button>
          </div>
          <div class="report-custom-range">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" id="report-from-recurring" class="form-input" />
            <label class="text-sm text-gray-500">To</label>
            <input type="date" id="report-to-recurring" class="form-input" />
          </div>
        </div>
        <div id="recurring-stats" class="grid grid-cols-3 gap-3 mb-4"></div>
        <div class="mb-4" style="max-height:250px"><canvas id="chart-recurring"></canvas></div>
        <div class="p-0">
          <table class="data-table">
            <thead><tr><th>Task Title</th><th>Customer</th><th>Staff</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="report-recurring-body"></tbody>
          </table>
        </div>
      </section>

      <section class="card p-5 fade-in" data-section="onetime">
        <h2 class="text-lg font-bold text-navy mb-4">One-Time Tasks</h2>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div class="flex flex-wrap gap-2" data-filters="onetime">
            <button data-filter="all" class="filter-tab btn btn-sm" style="background:var(--color-primary);color:#fff">All</button>
            <button data-filter="missed" class="filter-tab btn btn-sm btn-secondary">Missed</button>
            <button data-filter="today" class="filter-tab btn btn-sm btn-secondary">Today</button>
            <button data-filter="thisWeek" class="filter-tab btn btn-sm btn-secondary">This Week</button>
            <button data-filter="thisMonth" class="filter-tab btn btn-sm btn-secondary">This Month</button>
          </div>
          <div class="report-custom-range">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" id="report-from-onetime" class="form-input" />
            <label class="text-sm text-gray-500">To</label>
            <input type="date" id="report-to-onetime" class="form-input" />
          </div>
        </div>
        <div id="onetime-stats" class="grid grid-cols-3 gap-3 mb-4"></div>
        <div class="mb-4" style="max-height:250px"><canvas id="chart-onetime"></canvas></div>
        <div class="p-0">
          <table class="data-table">
            <thead><tr><th>Task Title</th><th>Customer</th><th>Staff</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="report-onetime-body"></tbody>
          </table>
        </div>
      </section>

      <section class="card p-5 fade-in" data-section="staff">
        <h2 class="text-lg font-bold text-navy mb-4">Staff-Wise Report</h2>
        <select id="report-staff-select" class="form-select mb-4 w-full sm:w-64"></select>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div class="flex flex-wrap gap-2" data-filters="staff">
            <button data-filter="all" class="filter-tab btn btn-sm" style="background:var(--color-primary);color:#fff">All</button>
            <button data-filter="missed" class="filter-tab btn btn-sm btn-secondary">Missed</button>
            <button data-filter="today" class="filter-tab btn btn-sm btn-secondary">Today</button>
            <button data-filter="thisWeek" class="filter-tab btn btn-sm btn-secondary">This Week</button>
            <button data-filter="thisMonth" class="filter-tab btn btn-sm btn-secondary">This Month</button>
          </div>
          <div class="report-custom-range">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" id="report-from-staff" class="form-input" />
            <label class="text-sm text-gray-500">To</label>
            <input type="date" id="report-to-staff" class="form-input" />
          </div>
        </div>
        <div id="staff-stats" class="grid grid-cols-3 gap-3 mb-4"></div>
        <div class="mb-4" style="max-height:250px"><canvas id="chart-staff"></canvas></div>
        <div class="p-0">
          <table class="data-table">
            <thead><tr><th>Task Title</th><th>Customer</th><th>Staff</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="report-staff-body"></tbody>
          </table>
        </div>
      </section>

      <section class="card p-5 fade-in" data-section="category">
        <h2 class="text-lg font-bold text-navy mb-4">Category-Wise Report</h2>
        <select id="report-category-select" class="form-select mb-4 w-full sm:w-64"></select>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div class="flex flex-wrap gap-2" data-filters="category">
            <button data-filter="all" class="filter-tab btn btn-sm" style="background:var(--color-primary);color:#fff">All</button>
            <button data-filter="missed" class="filter-tab btn btn-sm btn-secondary">Missed</button>
            <button data-filter="today" class="filter-tab btn btn-sm btn-secondary">Today</button>
            <button data-filter="thisWeek" class="filter-tab btn btn-sm btn-secondary">This Week</button>
            <button data-filter="thisMonth" class="filter-tab btn btn-sm btn-secondary">This Month</button>
          </div>
          <div class="report-custom-range">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" id="report-from-category" class="form-input" />
            <label class="text-sm text-gray-500">To</label>
            <input type="date" id="report-to-category" class="form-input" />
          </div>
        </div>
        <div id="category-stats" class="grid grid-cols-3 gap-3 mb-4"></div>
        <div class="mb-4" style="max-height:250px"><canvas id="chart-category"></canvas></div>
        <div class="p-0">
          <table class="data-table">
            <thead><tr><th>Task Title</th><th>Customer</th><th>Staff</th><th>Date</th><th>Status</th></tr></thead>
            <tbody id="report-category-body"></tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <!-- Task Detail Modal -->
  <div id="task-detail-modal" class="modal-overlay" style="display:none">
    <div class="modal-content max-w-lg" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-gray-900">Task Details</h3>
        <button onclick="closeModal('task-detail-modal')" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div id="task-detail-body" class="space-y-4">
        <div>
          <p class="text-sm text-gray-500">Title</p>
          <p id="dt-title" class="font-semibold text-navy text-lg"></p>
        </div>
        <hr class="border-gray-100">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Customer</p>
            <p id="dt-customer" class="font-semibold text-gray-700 mt-1"></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Assigned To</p>
            <p id="dt-staff" class="font-semibold text-gray-700 mt-1"></p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Scheduled Date</p>
            <p id="dt-date" class="font-semibold text-gray-700 mt-1"></p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Status</p>
            <p id="dt-status" class="mt-1"></p>
          </div>
        </div>
        <div id="dt-notes-row" class="hidden">
          <hr class="border-gray-100">
          <p class="text-sm text-gray-500">Notes</p>
          <p id="dt-notes" class="text-sm text-gray-700 mt-1 italic"></p>
        </div>
      </div>
    </div>
  </div>

  <script>
    var charts = { recurring: null, onetime: null, staff: null, category: null };

    // ── Embedded data from PHP ──
    var __tasks = <?= $tasksJson ?>;
    var __staff = <?= $staffJson ?>;
    var __categories = <?= $categoriesJson ?>;
    var __customers = <?= $customersJson ?>;

    var __customerMap = {};
    __customers.forEach(function(c) { __customerMap[c.id] = c.name; });
    var __staffMap = {};
    __staff.forEach(function(s) { __staffMap[s.id] = s.name; });
    var __taskMap = {};
    __tasks.forEach(function(t) { __taskMap[t.id] = t; });

    function fmtDate(dateStr) {
      if (!dateStr) return 'N/A';
      var d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function statusPill(status) {
      var cls = 'badge-pending';
      if (status === 'completed') cls = 'badge-completed';
      else if (status === 'in_progress') cls = 'badge-info';
      else if (status === 'missed' || status === 'overdue') cls = 'badge-danger';
      else if (status === 'cancelled') cls = 'badge-secondary';
      var label = status ? status.replace(/_/g, ' ') : 'Unknown';
      label = label.charAt(0).toUpperCase() + label.slice(1);
      return '<span class="badge ' + cls + '">' + label + '</span>';
    }

    window.formatDate = fmtDate;
    window.renderStatusPill = statusPill;

    function getCustomerName(id) {
      return __customerMap[id] || 'Unknown';
    }
    function getStaffName(id) {
      return __staffMap[id] || 'Unknown';
    }

    window.getTask = function(id) { return __taskMap[id] || null; };
    window.getCustomer = function(id) { return __customerMap[id] ? { id: id, name: __customerMap[id] } : null; };
    window.getStaffMember = function(id) { return __staffMap[id] ? { id: id, name: __staffMap[id] } : null; };

    function computeReport(taskList, filter, from, to) {
      var filtered = taskList.filter(function(t) {
        if (filter === 'missed' && t.status !== 'missed') return false;
        if (t.scheduledDate) {
          if (from && t.scheduledDate < from) return false;
          if (to && t.scheduledDate > to) return false;
        }
        return true;
      });
      var total = filtered.length;
      var completed = filtered.filter(function(t) { return t.status === 'completed'; }).length;
      var missed = filtered.filter(function(t) { return t.status === 'missed'; }).length;
      var completionRate = total > 0 ? Math.round(completed / total * 100) : 0;
      return { tasks: filtered, total: total, completionRate: completionRate, missed: missed };
    }

    window.getRecurringTasksReport = function(filter, from, to) {
      return computeReport(__tasks.filter(function(t) { return t.isRecurring == 1 || t.isRecurring === '1'; }), filter, from, to);
    };
    window.getOneTimeTasksReport = function(filter, from, to) {
      return computeReport(__tasks.filter(function(t) { return t.isRecurring != 1 && t.isRecurring !== '1'; }), filter, from, to);
    };
    window.getStaffWiseReport = function(staffId, filter, from, to) {
      return computeReport(__tasks.filter(function(t) { return t.assignedTo == staffId; }), filter, from, to);
    };
    window.getCategoryWiseReport = function(catId, filter, from, to) {
      return computeReport(__tasks.filter(function(t) { return t.categoryId == catId; }), filter, from, to);
    };
    window.getStaff = function() { return __staff; };
    window.getCategories = function() { return __categories; };

    // ── Existing rendering code (unchanged) ──

    function getWeekStart() {
      var d = new Date();
      var day = d.getDay();
      var diff = d.getDate() - day + (day === 0 ? -6 : 1);
      d.setDate(diff);
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function getWeekEnd() {
      var d = new Date();
      var day = d.getDay();
      var diff = d.getDate() - day + (day === 0 ? 0 : 7);
      d.setDate(diff);
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function applyPresetFilter(sectionKey, preset) {
      var fromId = 'report-from-' + sectionKey;
      var toId = 'report-to-' + sectionKey;
      var today = new Date();
      var y = today.getFullYear();
      var m = String(today.getMonth() + 1).padStart(2, '0');
      var d = String(today.getDate()).padStart(2, '0');
      var dateStr = y + '-' + m + '-' + d;
      var from, to;
      switch (preset) {
        case 'today':
          from = dateStr; to = dateStr; break;
        case 'thisWeek':
          from = getWeekStart(); to = getWeekEnd(); break;
        case 'thisMonth':
          from = y + '-' + m + '-01'; to = dateStr; break;
        default:
          return;
      }
      document.getElementById(fromId).value = from;
      document.getElementById(toId).value = to;
    }

    function rowColor(status) {
      if (status === 'completed') return 'bg-green-50/50 hover:bg-green-100/50';
      if (status === 'missed' || status === 'overdue') return 'bg-red-50/50 hover:bg-red-100/50';
      if (status === 'pending' || status === 'scheduled') return 'bg-amber-50/30 hover:bg-amber-100/30';
      if (status === 'cancelled') return 'bg-gray-50/50 hover:bg-gray-100/50';
      if (status === 'in_progress') return 'bg-blue-50/30 hover:bg-blue-100/30';
      return 'hover:bg-gray-50/50';
    }

    function renderTableBody(tbodyId, tasks) {
      var tbody = document.getElementById(tbodyId);
      tbody.innerHTML = tasks.map(function(t) {
        return '<tr class="border-b border-gray-50 transition-colors cursor-pointer ' + rowColor(t.status) + '" data-task-id="' + t.id + '">' +
          '<td class="px-4 py-3 font-medium text-navy">' + t.title + '</td>' +
          '<td class="px-4 py-3 text-gray-600">' + getCustomerName(t.customerId) + '</td>' +
          '<td class="px-4 py-3 text-gray-600">' + getStaffName(t.assignedTo) + '</td>' +
          '<td class="px-4 py-3 text-gray-600">' + fmtDate(t.scheduledDate) + '</td>' +
          '<td class="px-4 py-3">' + statusPill(t.status) + '</td>' +
        '</tr>';
      }).join('');

      tbody.querySelectorAll('tr[data-task-id]').forEach(function(row) {
        row.addEventListener('click', function() {
          var id = this.getAttribute('data-task-id');
          showTaskDetail(id);
        });
      });
    }

    function renderStats(statsId, data) {
      document.getElementById(statsId).innerHTML =
        '<div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Total</p><p class="text-lg font-bold text-navy">' + data.total + '</p></div>' +
        '<div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Completed%</p><p class="text-lg font-bold text-brand">' + data.completionRate + '%</p></div>' +
        '<div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-xs text-gray-500">Missed</p><p class="text-lg font-bold text-danger">' + data.missed + '</p></div>';
    }

    function buildChart(canvasId, chartKey, reportData) {
      var ctx = document.getElementById(canvasId).getContext('2d');
      if (charts[chartKey]) { charts[chartKey].destroy(); }

      var weeks = {};
      reportData.tasks.forEach(function(t) {
        if (!t.scheduledDate) return;
        var d = new Date(t.scheduledDate + 'T00:00:00');
        var ws = new Date(d);
        ws.setDate(d.getDate() - d.getDay());
        var key = ws.getFullYear() + '-' + String(ws.getMonth()+1).padStart(2,'0') + '-' + String(ws.getDate()).padStart(2,'0');
        if (!weeks[key]) weeks[key] = { completed: 0 };
        if (t.status === 'completed') weeks[key].completed++;
      });

      var sortedWeeks = Object.keys(weeks).sort().slice(-4);
      var labels = sortedWeeks.map(function(w) { return fmtDate(w); });
      var completedData = sortedWeeks.map(function(w) { return weeks[w].completed; });

      charts[chartKey] = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Completed',
            data: completedData,
            backgroundColor: '#1DB954',
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
          }
        }
      });
    }

    function renderSection(sectionKey, reportFn, filter, statsId, chartId, chartKey, tbodyId) {
      var data = reportFn(filter);
      renderStats(statsId, data);
      renderTableBody(tbodyId, data.tasks);
      buildChart(chartId, chartKey, data);
    }

    function renderStaffWise() {
      var staffId = document.getElementById('report-staff-select').value;
      if (!staffId) return;
      var filter = document.querySelector('[data-filters="staff"] .bg-brand')?.getAttribute('data-filter') || 'all';
      var from = document.getElementById('report-from-staff').value;
      var to = document.getElementById('report-to-staff').value;
      var data = window.getStaffWiseReport(staffId, filter, from, to);
      renderStats('staff-stats', data);
      renderTableBody('report-staff-body', data.tasks);
      buildChart('chart-staff', 'staff', data);
    }

    function renderCategoryWise() {
      var catId = document.getElementById('report-category-select').value;
      if (!catId) return;
      var filter = document.querySelector('[data-filters="category"] .bg-brand')?.getAttribute('data-filter') || 'all';
      var from = document.getElementById('report-from-category').value;
      var to = document.getElementById('report-to-category').value;
      var data = window.getCategoryWiseReport(catId, filter, from, to);
      renderStats('category-stats', data);
      renderTableBody('report-category-body', data.tasks);
      buildChart('chart-category', 'category', data);
    }

    function showTaskDetail(taskId) {
      var task = window.getTask(taskId);
      if (!task) {
        window.showToast('Task not found.', 'error');
        return;
      }
      var customer = window.getCustomer(task.customerId);
      var staff = window.getStaffMember(task.assignedTo);

      document.getElementById('dt-title').textContent = task.title || 'Untitled Task';
      document.getElementById('dt-customer').textContent = customer ? customer.name : 'Unknown';
      document.getElementById('dt-staff').textContent = staff ? staff.name : 'Unassigned';
      document.getElementById('dt-date').textContent = task.scheduledDate ? fmtDate(task.scheduledDate) : 'Not scheduled';
      document.getElementById('dt-status').innerHTML = statusPill(task.status);

      var notesRow = document.getElementById('dt-notes-row');
      var notesEl = document.getElementById('dt-notes');
      if (task.notes) {
        notesEl.textContent = task.notes;
        notesRow.classList.remove('hidden');
      } else {
        notesRow.classList.add('hidden');
      }

      try { lucide.createIcons(); } catch(e) {}
      openModal('task-detail-modal');
    }

    function openModal(id) {
      document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    document.addEventListener('click', function (e) {
      if (e.target === document.getElementById('task-detail-modal')) {
        closeModal('task-detail-modal');
      }
    });

    function initReports() {
      var staffSelect = document.getElementById('report-staff-select');
      staffSelect.innerHTML = '<option value="">Select Staff</option>' + __staff.map(function(s) { return '<option value="' + s.id + '">' + s.name + '</option>'; }).join('');

      var catSelect = document.getElementById('report-category-select');
      catSelect.innerHTML = '<option value="">Select Category</option>' + __categories.map(function(c) { return '<option value="' + c.id + '">' + c.name + '</option>'; }).join('');

      var now = new Date();
      var dateStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
      var monthStart = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-01';
      ['recurring', 'onetime', 'staff', 'category'].forEach(function(key) {
        document.getElementById('report-from-' + key).value = monthStart;
        document.getElementById('report-to-' + key).value = dateStr;
      });

      renderSection('recurring', function(filter) {
        return window.getRecurringTasksReport(filter, document.getElementById('report-from-recurring').value, document.getElementById('report-to-recurring').value);
      }, 'all', 'recurring-stats', 'chart-recurring', 'recurring', 'report-recurring-body');
      renderSection('onetime', function(filter) {
        return window.getOneTimeTasksReport(filter, document.getElementById('report-from-onetime').value, document.getElementById('report-to-onetime').value);
      }, 'all', 'onetime-stats', 'chart-onetime', 'onetime', 'report-onetime-body');

      document.querySelectorAll('.filter-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var filter = this.getAttribute('data-filter');
          var parent = this.closest('[data-filters]');
          if (parent) {
            parent.querySelectorAll('.filter-tab').forEach(function(t) { t.className = 'filter-tab px-3 py-1.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200'; });
            this.className = 'filter-tab px-3 py-1.5 text-xs font-medium rounded-full bg-brand text-white';
            var section = parent.getAttribute('data-filters');

            if (filter === 'today' || filter === 'thisWeek' || filter === 'thisMonth') {
              applyPresetFilter(section, filter);
              filter = 'all';
            }

            if (section === 'recurring') renderSection('recurring', function(f) { return window.getRecurringTasksReport(f, document.getElementById('report-from-recurring').value, document.getElementById('report-to-recurring').value); }, filter, 'recurring-stats', 'chart-recurring', 'recurring', 'report-recurring-body');
            else if (section === 'onetime') renderSection('onetime', function(f) { return window.getOneTimeTasksReport(f, document.getElementById('report-from-onetime').value, document.getElementById('report-to-onetime').value); }, filter, 'onetime-stats', 'chart-onetime', 'onetime', 'report-onetime-body');
            else if (section === 'staff') { if (document.getElementById('report-staff-select').value) renderStaffWise(); }
            else if (section === 'category') { if (document.getElementById('report-category-select').value) renderCategoryWise(); }
          }
        });
      });

      document.getElementById('report-staff-select').addEventListener('change', function() {
        var filter = document.querySelector('[data-filters="staff"] .bg-brand')?.getAttribute('data-filter') || 'all';
        if (this.value) renderStaffWise();
      });
      document.getElementById('report-category-select').addEventListener('change', function() {
        var filter = document.querySelector('[data-filters="category"] .bg-brand')?.getAttribute('data-filter') || 'all';
        if (this.value) renderCategoryWise();
      });

      ['report-from-recurring','report-to-recurring'].forEach(function(id){ document.getElementById(id).addEventListener('change', function(){ renderSection('recurring', function(filter){ return window.getRecurringTasksReport(filter, document.getElementById('report-from-recurring').value, document.getElementById('report-to-recurring').value); }, document.querySelector('[data-filters="recurring"] .bg-brand')?.getAttribute('data-filter') || 'all', 'recurring-stats', 'chart-recurring', 'recurring', 'report-recurring-body'); }); });
      ['report-from-onetime','report-to-onetime'].forEach(function(id){ document.getElementById(id).addEventListener('change', function(){ renderSection('onetime', function(filter){ return window.getOneTimeTasksReport(filter, document.getElementById('report-from-onetime').value, document.getElementById('report-to-onetime').value); }, document.querySelector('[data-filters="onetime"] .bg-brand')?.getAttribute('data-filter') || 'all', 'onetime-stats', 'chart-onetime', 'onetime', 'report-onetime-body'); }); });
      ['report-from-staff','report-to-staff'].forEach(function(id){ document.getElementById(id).addEventListener('change', function(){ if (document.getElementById('report-staff-select').value) renderStaffWise(); }); });
      ['report-from-category','report-to-category'].forEach(function(id){ document.getElementById(id).addEventListener('change', function(){ if (document.getElementById('report-category-select').value) renderCategoryWise(); }); });

      if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    document.addEventListener('DOMContentLoaded', function () {
      initReports();
    });
  </script>
  <?php require_once '../includes/footer.php'; ?>
</body>
</html>
